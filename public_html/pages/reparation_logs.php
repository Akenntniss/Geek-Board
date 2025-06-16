<?php
// Inclure la configuration de la base de données
require_once('config/database.php');

$shop_pdo = getShopDBConnection();
require_once('includes/functions.php');
require_once('includes/task_logger.php');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    set_message("Vous devez être connecté pour accéder à cette page.", "danger");
    redirect('accueil');
    exit;
}

// Vérifier que l'utilisateur est un administrateur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    set_message("Vous n'avez pas les autorisations nécessaires pour accéder à cette page.", "danger");
    redirect('accueil');
    exit;
}

// Activer le débogage
$DEBUG = true; // Mettre à true pour voir les logs de débogage

// Paramètres de pagination
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

// Filtres
$employe_id = isset($_GET['employe_id']) ? intval($_GET['employe_id']) : 0;
$reparation_id = isset($_GET['reparation_id']) ? intval($_GET['reparation_id']) : 0;
$action_type = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$heure_debut = isset($_GET['heure_debut']) ? $_GET['heure_debut'] : '';
$heure_fin = isset($_GET['heure_fin']) ? $_GET['heure_fin'] : '';
$search_term = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_action';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
$view_mode = isset($_GET['view_mode']) ? $_GET['view_mode'] : 'timeline';
$group_by = isset($_GET['group_by']) ? $_GET['group_by'] : 'none';
$log_type = isset($_GET['log_type']) ? $_GET['log_type'] : 'all';

// Valeurs par défaut pour les filtres rapides
if (empty($date_debut) && empty($date_fin)) {
    $quick_filter = isset($_GET['quick_filter']) ? $_GET['quick_filter'] : '';
    switch ($quick_filter) {
        case 'today':
            $date_debut = $date_fin = date('Y-m-d');
            break;
        case 'yesterday':
            $date_debut = $date_fin = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'week':
            $date_debut = date('Y-m-d', strtotime('monday this week'));
            $date_fin = date('Y-m-d');
            break;
        case 'month':
            $date_debut = date('Y-m-01');
            $date_fin = date('Y-m-d');
            break;
    }
}

// Construction de la requête SQL avec filtres
if ($log_type === 'tasks' || $log_type === 'all') {
    // Requête pour les logs de tâches
    $task_sql = "
        SELECT 
            tl.id,
            tl.task_id as entity_id,
            tl.user_id as employe_id,
            tl.action_type,
            tl.old_status as statut_avant,
            tl.new_status as statut_apres,
            tl.action_timestamp as date_action,
            tl.user_name as employe_nom,
            tl.task_title,
            tl.details,
            'task' as log_source,
            '' as type_appareil,
            '' as modele,
            '' as client_nom,
            '' as reparation_description,
            u.role as employe_role
        FROM Log_tasks tl
        LEFT JOIN users u ON tl.user_id = u.id
        WHERE 1=1
    ";
}

if ($log_type === 'repairs' || $log_type === 'all') {
    // Requête pour les logs de réparations
    $repair_sql = "
        SELECT 
            rl.id,
            rl.reparation_id as entity_id,
            rl.employe_id,
            rl.action_type,
            rl.statut_avant,
            rl.statut_apres,
            rl.date_action,
            u.full_name as employe_nom,
            '' as task_title,
            rl.details,
            'repair' as log_source,
            r.type_appareil,
            r.modele,
            CONCAT(c.nom, ' ', c.prenom) as client_nom,
            r.description_probleme as reparation_description,
            u.role as employe_role
        FROM reparation_logs rl
        JOIN reparations r ON rl.reparation_id = r.id
        JOIN users u ON rl.employe_id = u.id
        JOIN clients c ON r.client_id = c.id
        WHERE 1=1
    ";
}

// Union des deux requêtes selon le filtre choisi
if ($log_type === 'all') {
    $sql = "(" . $repair_sql . ") UNION (" . $task_sql . ")";
} elseif ($log_type === 'tasks') {
    $sql = $task_sql;
} else {
    $sql = $repair_sql;
}

$params = [];

// Construire les conditions de filtre pour chaque type de log
$filter_conditions = [];

if ($employe_id > 0) {
    $filter_conditions[] = "employe_id = ?";
    $params[] = $employe_id;
}

if (!empty($action_type)) {
    $filter_conditions[] = "action_type = ?";
    $params[] = $action_type;
}

if (!empty($date_debut)) {
    if (!empty($heure_debut)) {
        $filter_conditions[] = "date_action >= ?";
        $params[] = $date_debut . ' ' . $heure_debut . ':00';
    } else {
        $filter_conditions[] = "DATE(date_action) >= ?";
        $params[] = $date_debut;
    }
}

if (!empty($date_fin)) {
    if (!empty($heure_fin)) {
        $filter_conditions[] = "date_action <= ?";
        $params[] = $date_fin . ' ' . $heure_fin . ':59';
    } else {
        $filter_conditions[] = "DATE(date_action) <= ?";
        $params[] = $date_fin;
    }
}

// Filtre spécifique pour les réparations
if ($reparation_id > 0 && ($log_type === 'repairs' || $log_type === 'all')) {
    if ($log_type === 'repairs') {
        $filter_conditions[] = "reparation_id = ?";
        $params[] = $reparation_id;
    } else {
        // Pour 'all', on filtre directement dans chaque sous-requête
        $repair_sql .= " AND rl.reparation_id = ?";
    }
}

// Recherche textuelle adaptée selon le type de log
if (!empty($search_term)) {
    $search_param = "%" . $search_term . "%";
    
    if ($log_type === 'tasks') {
        $filter_conditions[] = "(task_title LIKE ? OR details LIKE ? OR employe_nom LIKE ?)";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    } elseif ($log_type === 'repairs') {
        $filter_conditions[] = "(reparation_description LIKE ? OR type_appareil LIKE ? OR modele LIKE ? OR client_nom LIKE ? OR employe_nom LIKE ? OR details LIKE ? OR statut_avant LIKE ? OR statut_apres LIKE ?)";
        for ($i = 0; $i < 8; $i++) {
            $params[] = $search_param;
        }
    } else {
        // Pour 'all', on applique la recherche dans chaque sous-requête
        $repair_search = "(r.description_probleme LIKE ? OR r.type_appareil LIKE ? OR r.modele LIKE ? OR c.nom LIKE ? OR c.prenom LIKE ? OR u.full_name LIKE ? OR rl.details LIKE ? OR rl.statut_avant LIKE ? OR rl.statut_apres LIKE ?)";
        $task_search = "(tl.task_title LIKE ? OR tl.details LIKE ? OR tl.user_name LIKE ?)";
        
        $repair_sql .= " AND " . $repair_search;
        $task_sql .= " AND " . $task_search;
        
        // Ajouter les paramètres pour la recherche
        for ($i = 0; $i < 9; $i++) {
            $params[] = $search_param;
        }
        for ($i = 0; $i < 3; $i++) {
            $params[] = $search_param;
        }
    }
}

// Appliquer les conditions aux requêtes
if (!empty($filter_conditions) && $log_type !== 'all') {
    $sql .= " AND " . implode(" AND ", $filter_conditions);
} elseif (!empty($filter_conditions) && $log_type === 'all') {
    $conditions_str = " AND " . implode(" AND ", $filter_conditions);
    $repair_sql .= $conditions_str;
    $task_sql .= $conditions_str;
    
    // Reconstruire la requête union avec les conditions
    $sql = "(" . $repair_sql . ") UNION (" . $task_sql . ")";
}

// Compter le total pour la pagination
$count_sql = "SELECT COUNT(*) as total FROM (" . $sql . ") as count_query";
try {
    $count_stmt = $shop_pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_logs = $count_stmt->fetchColumn();
} catch (PDOException $e) {
    $total_logs = 0;
}

// Tri
$valid_sort_columns = ['date_action', 'employe_nom', 'action_type', 'entity_id'];
if (!in_array($sort_by, $valid_sort_columns)) {
    $sort_by = 'date_action';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Pour la requête UNION, on doit envelopper dans une sous-requête pour trier
if ($log_type === 'all') {
    $sql = "SELECT * FROM (" . $sql . ") as combined_logs ORDER BY {$sort_by} {$sort_order}";
} else {
    $sql .= " ORDER BY {$sort_by} {$sort_order}";
}

// Pagination pour la timeline
if ($view_mode === 'timeline') {
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
}

// Debug SQL query
if ($DEBUG) {
    error_log("Requête SQL logs: " . $sql);
    error_log("Paramètres: " . print_r($params, true));
}

// Obtenir les résultats selon le type de log sélectionné
try {
    if ($log_type === 'all') {
        // Exécuter la requête UNION pour tous les logs
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Exécuter la requête pour un type spécifique
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    if ($DEBUG) {
        error_log("Nombre de logs trouvés ($log_type): " . count($logs));
    }
} catch (PDOException $e) {
    $logs = [];
    set_message("Erreur lors de la récupération des logs: " . $e->getMessage(), "danger");
    if ($DEBUG) {
        error_log("Erreur SQL: " . $e->getMessage());
    }
}

// Récupérer la liste des employés pour le filtre
try {
    $stmt = $shop_pdo->query("SELECT id, full_name as nom FROM users WHERE role = 'technicien' OR role = 'admin' ORDER BY full_name");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $employes = [];
}

// Récupérer les types d'actions uniques selon le type de log
try {
    if ($log_type === 'tasks') {
        // Actions des tâches uniquement
        $stmt = $shop_pdo->query("SELECT DISTINCT action_type FROM Log_tasks ORDER BY action_type");
        $action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($log_type === 'repairs') {
        // Actions des réparations uniquement
        $stmt = $shop_pdo->query("SELECT DISTINCT action_type FROM reparation_logs ORDER BY action_type");
        $action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // Tous les types d'actions combinés
        $stmt = $shop_pdo->query("
            SELECT DISTINCT action_type FROM reparation_logs 
            UNION 
            SELECT DISTINCT action_type FROM Log_tasks 
            ORDER BY action_type
        ");
        $action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    $action_types = [];
}

// Regrouper les logs par réparation et par employé
$grouped_logs = [];
$employees = [];

// Collecter les données des logs pour la timeline et par employé
foreach ($logs as $log) {
    // Groupement selon le paramètre choisi
    if ($group_by === 'repair') {
        $group_key = $log['log_source'] === 'task' ? 'task_' . $log['entity_id'] : 'repair_' . $log['entity_id'];
        $grouped_logs[$group_key][] = $log;
    } elseif ($group_by === 'employee') {
        $grouped_logs[$log['employe_id']][] = $log;
    } elseif ($group_by === 'date') {
        $date_key = date('Y-m-d', strtotime($log['date_action']));
        $grouped_logs[$date_key][] = $log;
    } else {
        $grouped_logs[] = $log;
    }
    
    // Grouper également par employé pour l'onglet "Activités par employé"
    $employee_id = $log['employe_id'];
    $employee_name = $log['employe_nom'];
    
    // Enregistrer chaque employé unique
    if (!isset($employees[$employee_id])) {
        $employees[$employee_id] = [
            'id' => $employee_id,
            'name' => $employee_name,
            'role' => $log['employe_role'] ?? 'Utilisateur',
            'repairs' => [],
            'tasks' => []
        ];
    }
    
    // Grouper selon le type de log
    if ($log['log_source'] === 'task') {
        // Logs de tâches
        $task_id = $log['entity_id'];
        if (!isset($employees[$employee_id]['tasks'][$task_id])) {
            $employees[$employee_id]['tasks'][$task_id] = [
                'id' => $task_id,
                'title' => $log['task_title'] ?? 'Tâche #' . $task_id,
                'description' => $log['task_description'] ?? '',
                'logs' => []
            ];
        }
        $employees[$employee_id]['tasks'][$task_id]['logs'][] = $log;
    } else {
        // Logs de réparations
        $repair_id = $log['entity_id'];
        if (!isset($employees[$employee_id]['repairs'][$repair_id])) {
            $employees[$employee_id]['repairs'][$repair_id] = [
                'id' => $repair_id,
                'type_appareil' => $log['type_appareil'] ?? '',
                'modele' => $log['modele'] ?? '',
                'client_nom' => $log['client_nom'] ?? '',
                'client_id' => $log['client_id'] ?? '',
                'description' => $log['reparation_description'] ?? '',
                'logs' => []
            ];
        }
        $employees[$employee_id]['repairs'][$repair_id]['logs'][] = $log;
    }
}

// Fonction pour calculer la durée entre deux dates
function calculate_duration($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    
    $duration = '';
    
    if ($interval->d > 0) {
        $duration .= $interval->d . 'j ';
    }
    
    if ($interval->h > 0) {
        $duration .= $interval->h . 'h ';
    }
    
    if ($interval->i > 0) {
        $duration .= $interval->i . 'min';
    } else if ($duration === '') {
        $duration = '< 1min';
    }
    
    return $duration;
}

// Fonction pour obtenir tous les démarrages et fins d'une réparation
function get_all_repair_sequences($logs) {
    $sequences = [];
    $start_logs = [];
    $end_action_types = ['terminer', 'changement_statut', 'ajout_note', 'modification', 'autre'];
    
    // Extraire tous les logs de démarrage
    foreach ($logs as $log) {
        if ($log['action_type'] === 'demarrage') {
            $start_logs[] = $log;
        }
    }
    
    // Trier les logs de démarrage par date (du plus ancien au plus récent)
    usort($start_logs, function($a, $b) {
        return strtotime($a['date_action']) - strtotime($b['date_action']);
    });
    
    // Pour chaque log de démarrage, trouver le log de fin correspondant
    foreach ($start_logs as $start) {
        $start_time = strtotime($start['date_action']);
        $best_end = null;
        $min_time_diff = PHP_INT_MAX;
        
        // Chercher le log de fin le plus proche après ce démarrage
        foreach ($logs as $log) {
            if (in_array($log['action_type'], $end_action_types)) {
                $log_time = strtotime($log['date_action']);
                
                // Le log de fin doit être après le démarrage
                if ($log_time > $start_time) {
                    $time_diff = $log_time - $start_time;
                    
                    // Si c'est le log de fin le plus proche trouvé jusqu'à présent
                    if ($time_diff < $min_time_diff) {
                        $min_time_diff = $time_diff;
                        $best_end = $log;
                    }
                }
            }
        }
        
        // Ajouter cette séquence de démarrage-fin
        $sequences[] = [
            'start' => $start,
            'end' => $best_end
        ];
    }
    
    return $sequences;
}

// Fonction pour obtenir tous les démarrages et fins d'une tâche
function get_all_task_sequences($logs) {
    $sequences = [];
    $start_logs = [];
    $end_action_types = ['terminer', 'pause', 'modifier', 'supprimer'];
    
    // Extraire tous les logs de démarrage
    foreach ($logs as $log) {
        if ($log['action_type'] === 'demarrer') {
            $start_logs[] = $log;
        }
    }
    
    // Trier les logs de démarrage par date (du plus ancien au plus récent)
    usort($start_logs, function($a, $b) {
        return strtotime($a['date_action']) - strtotime($b['date_action']);
    });
    
    // Pour chaque log de démarrage, trouver le log de fin correspondant
    foreach ($start_logs as $start) {
        $start_time = strtotime($start['date_action']);
        $best_end = null;
        $min_time_diff = PHP_INT_MAX;
        
        // Chercher le log de fin le plus proche après ce démarrage
        foreach ($logs as $log) {
            if (in_array($log['action_type'], $end_action_types)) {
                $log_time = strtotime($log['date_action']);
                
                // Le log de fin doit être après le démarrage
                if ($log_time > $start_time) {
                    $time_diff = $log_time - $start_time;
                    
                    // Si c'est le log de fin le plus proche trouvé jusqu'à présent
                    if ($time_diff < $min_time_diff) {
                        $min_time_diff = $time_diff;
                        $best_end = $log;
                    }
                }
            }
        }
        
        // Ajouter cette séquence de démarrage-fin
        $sequences[] = [
            'start' => $start,
            'end' => $best_end
        ];
    }
    
    return $sequences;
}

// Fonction pour obtenir les données de démarrage et terminaison d'une réparation
function get_repair_start_end($logs, $attribution = null) {
    $start = null;
    $end = null;
    
    // Types d'actions considérés comme fin de réparation
    $end_action_types = ['terminer', 'changement_statut', 'ajout_note', 'modification', 'autre'];
    
    // D'abord chercher dans les logs
    foreach ($logs as $log) {
        if ($log['action_type'] === 'demarrage') {
            if (!$start || strtotime($log['date_action']) < strtotime($start['date_action'])) {
                $start = $log;
            }
        } else if (in_array($log['action_type'], $end_action_types)) {
            // Pour les actions de fin, on prend la plus récente
            if (!$end || strtotime($log['date_action']) > strtotime($end['date_action'])) {
                $end = $log;
            }
        }
    }
    
    return ['start' => $start, 'end' => $end];
}

// Fonction pour formater la date
function format_datetime($datetime) {
    $date = new DateTime($datetime);
    return $date->format('d/m/Y H:i:s');
}

// Fonction pour obtenir une couleur en fonction du type d'action
function get_action_color($action_type, $log_source = 'repair') {
    if ($log_source === 'task') {
        switch ($action_type) {
            case 'demarrer':
                return 'primary';
            case 'terminer':
                return 'success';
            case 'pause':
                return 'warning';
            case 'reprendre':
                return 'info';
            case 'modifier':
                return 'secondary';
            case 'creer':
                return 'success';
            case 'supprimer':
                return 'danger';
            default:
                return 'dark';
        }
    } else {
        switch ($action_type) {
            case 'demarrage':
                return 'primary';
            case 'terminer':
                return 'success';
            case 'changement_statut':
                return 'warning';
            case 'ajout_note':
                return 'info';
            case 'modification':
                return 'secondary';
            default:
                return 'dark';
        }
    }
}

// Fonction pour obtenir une icône en fonction du type d'action
function get_action_icon($action_type, $log_source = 'repair') {
    if ($log_source === 'task') {
        switch ($action_type) {
            case 'demarrer':
                return 'play-circle';
            case 'terminer':
                return 'check-circle';
            case 'pause':
                return 'pause-circle';
            case 'reprendre':
                return 'play-circle';
            case 'modifier':
                return 'edit';
            case 'creer':
                return 'plus-circle';
            case 'supprimer':
                return 'trash';
            default:
                return 'tasks';
        }
    } else {
        switch ($action_type) {
            case 'demarrage':
                return 'play-circle';
            case 'terminer':
                return 'stop-circle';
            case 'changement_statut':
                return 'exchange-alt';
            case 'ajout_note':
                return 'sticky-note';
            case 'modification':
                return 'edit';
            default:
                return 'cog';
        }
    }
}

// Fonction pour obtenir un libellé en fonction du type d'action
function get_action_label($action_type, $log_source = 'repair') {
    if ($log_source === 'task') {
        switch ($action_type) {
            case 'demarrer':
                return 'Tâche démarrée';
            case 'terminer':
                return 'Tâche terminée';
            case 'pause':
                return 'Tâche en pause';
            case 'reprendre':
                return 'Tâche reprise';
            case 'modifier':
                return 'Tâche modifiée';
            case 'creer':
                return 'Tâche créée';
            case 'supprimer':
                return 'Tâche supprimée';
            default:
                return 'Action tâche';
        }
    } else {
        switch ($action_type) {
            case 'demarrage':
                return 'Démarrage';
            case 'terminer':
                return 'Terminé';
            case 'changement_statut':
                return 'Changement de statut';
            case 'ajout_note':
                return 'Ajout de note';
            case 'modification':
                return 'Modification';
            default:
                return 'Autre';
        }
    }
}

// Fonction pour obtenir la couleur de fond selon l'employé
function get_employe_background_color($employe_nom) {
    switch (strtolower($employe_nom)) {
        case 'admin':
            return 'bg-danger bg-opacity-10 text-danger';
        case 'rayan':
            return 'bg-primary bg-opacity-10 text-primary';
        case 'benjamin':
            return 'bg-success bg-opacity-10 text-success';
        default:
            return 'bg-secondary bg-opacity-10 text-secondary';
    }
}

// Fonction pour obtenir la couleur principale selon l'employé
function get_employe_color($employe_nom) {
    switch (strtolower($employe_nom)) {
        case 'admin':
            return 'danger';
        case 'rayan':
            return 'primary';
        case 'benjamin':
            return 'success';
        default:
            return 'secondary';
    }
}

// Fonction pour calculer le temps inactif entre deux réparations
function calculate_inactive_time($end_date, $start_date) {
    if (!$end_date || !$start_date) {
        return null;
    }
    
    $end = new DateTime($end_date);
    $start = new DateTime($start_date);
    
    if ($start <= $end) {
        // Les réparations se chevauchent ou se suivent immédiatement
        return '0min';
    }
    
    $interval = $end->diff($start);
    $total_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    
    if ($total_minutes <= 0) {
        return '0min';
    }
    
    $hours = floor($total_minutes / 60);
    $minutes = $total_minutes % 60;
    
    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'min';
    } else {
        return $minutes . 'min';
    }
}

// Fonction pour calculer le temps total de réparation par employé
function calculate_total_work_time($repairs) {
    $total_seconds = 0;
    
    foreach ($repairs as $repair) {
        $repair_data = get_repair_start_end($repair['logs']);
        $start = $repair_data['start'];
        $end = $repair_data['end'];
        
        if ($start && $end) {
            $start_time = new DateTime($start['date_action']);
            $end_time = new DateTime($end['date_action']);
            $diff = $end_time->getTimestamp() - $start_time->getTimestamp();
            $total_seconds += $diff;
        }
    }
    
    // Formater le temps total
    $hours = floor($total_seconds / 3600);
    $minutes = floor(($total_seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'min';
    } else {
        return $minutes . 'min';
    }
}

// Fonction pour calculer le temps total de travail à partir des interventions
function calculate_total_work_time_from_interventions($repairs) {
    $total_seconds = 0;
    
    foreach ($repairs as $repair) {
        $sequences = get_all_repair_sequences($repair['logs']);
        foreach ($sequences as $sequence) {
            if ($sequence['start']) {
                $start_time = strtotime($sequence['start']['date_action']);
                if ($sequence['end']) {
                    $end_time = strtotime($sequence['end']['date_action']);
                    $total_seconds += ($end_time - $start_time);
                }
            }
        }
    }
    
    // Formater le temps total
    $hours = floor($total_seconds / 3600);
    $minutes = floor(($total_seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'min';
    } else {
        return $minutes . 'min';
    }
}
?>

<!-- Styles personnalisés -->
<style>
    /* Styles de la timeline */
    :root {
        /* Variables pour le mode sombre */
        --dark-bg: #1a1d21;
        --dark-card-bg: #242830;
        --dark-text: #e2e8f0;
        --dark-text-secondary: #a0aec0;
        --dark-border: #374151;
        --dark-hover: #2d3748;
        --dark-timeline-line: #4a5568;
        --dark-box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    }

    body.dark-mode {
        background-color: var(--dark-bg);
        color: var(--dark-text);
    }

    .dark-mode-toggle {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4361ee, #3a0ca3);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        border: none;
        transition: all 0.3s ease;
    }

    .dark-mode-toggle:hover {
        transform: scale(1.1);
    }

    /* Timeline styles */
    .timeline {
        position: relative;
        padding: 1rem 0;
    }
    
    .timeline:before {
        content: '';
        position: absolute;
        top: 0;
        left: 18px;
        height: 100%;
        width: 4px;
        background: #e9ecef;
        border-radius: 2px;
    }

    .dark-mode .timeline:before {
        background: var(--dark-timeline-line);
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
        margin-left: 40px;
    }
    
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    
    .timeline-icon {
        position: absolute;
        left: -40px;
        top: 0;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        z-index: 1;
    }
    
    .timeline-content {
        padding: 1.5rem;
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .dark-mode .timeline-content {
        background-color: var(--dark-card-bg);
        box-shadow: var(--dark-box-shadow);
        border: 1px solid var(--dark-border);
    }
    
    .timeline-date {
        display: block;
        margin-bottom: 0.75rem;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .dark-mode .timeline-date {
        color: var(--dark-text-secondary);
    }
    
    .timeline-title {
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .dark-mode .timeline-title {
        color: var(--dark-text);
    }
    
    .timeline-details {
        font-size: 0.95rem;
    }

    .dark-mode .timeline-details {
        color: var(--dark-text-secondary);
    }
    
    /* Styles des cartes */
    .card {
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .dark-mode .card {
        background-color: var(--dark-card-bg);
        border-color: var(--dark-border);
    }
    
    .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.125);
    }

    .dark-mode .card-header {
        background-color: rgba(0,0,0,0.2);
        border-bottom: 1px solid var(--dark-border);
        color: var(--dark-text);
    }
    
    .filter-card {
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .dark-mode .filter-card {
        box-shadow: var(--dark-box-shadow);
    }
    
    .log-card {
        transition: all 0.3s ease;
    }
    
    .log-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .dark-mode .log-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    .log-badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 500;
        line-height: 1;
        color: #fff;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.375rem;
    }
    
    /* Styles pour la timeline de réparation */
    .repair-timeline {
        width: 100%;
        position: relative;
        padding: 8px 0;
    }
    
    .repair-timeline-track {
        display: flex;
        align-items: center;
        position: relative;
        width: 100%;
        height: 24px;
    }
    
    .repair-timeline-track:before {
        content: '';
        position: absolute;
        top: 50%;
        left: 24px;
        right: 24px;
        height: 3px;
        background: linear-gradient(90deg, #28a745, #dc3545);
        border-radius: 3px;
        z-index: 1;
    }
    
    .repair-timeline-start, 
    .repair-timeline-end {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 0.75rem;
    }
    
    .repair-timeline-start i, 
    .repair-timeline-end i {
        background-color: white;
        border-radius: 50%;
        padding: 2px;
        font-size: 1rem;
    }

    .dark-mode .repair-timeline-start i, 
    .dark-mode .repair-timeline-end i {
        background-color: var(--dark-card-bg);
    }
    
    .repair-timeline-duration {
        position: absolute;
        top: -18px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 2px 8px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        z-index: 2;
    }

    .dark-mode .repair-timeline-duration {
        background-color: var(--dark-card-bg);
        border: 1px solid var(--dark-border);
        color: var(--dark-text);
    }
    
    /* Styles pour les tableaux des employés */
    .employee-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 20px;
        height: 100%;
    }
    
    .employee-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }

    .dark-mode .employee-card:hover {
        box-shadow: 0 10px 20px rgba(0,0,0,0.3) !important;
    }
    
    .employee-card .card-header {
        padding: 1rem;
        border-bottom: 2px solid rgba(0,0,0,0.1);
    }

    .dark-mode .employee-card .card-header {
        border-bottom: 2px solid var(--dark-border);
    }
    
    .employee-card .table {
        margin-bottom: 0;
    }

    .dark-mode .table {
        color: var(--dark-text);
    }
    
    .employee-card .table th {
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .dark-mode .employee-card .table th {
        color: var(--dark-text-secondary);
    }
    
    .employee-card .table td {
        vertical-align: middle;
        padding: 0.75rem;
    }
    
    .employee-stats {
        background-color: rgba(0,0,0,0.03);
        border-top: 1px solid rgba(0,0,0,0.125);
        padding: 0.75rem 1rem;
    }

    .dark-mode .employee-stats {
        background-color: rgba(0,0,0,0.2);
        border-top: 1px solid var(--dark-border);
    }
    
    .stats-badge {
        display: inline-flex;
        align-items: center;
        background-color: rgba(255,255,255,0.8);
        border-radius: 1rem;
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
        margin-right: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .dark-mode .stats-badge {
        background-color: rgba(255,255,255,0.1);
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        color: var(--dark-text);
    }
    
    .stats-badge i {
        margin-right: 0.35rem;
    }
    
    /* Style pour les temps inactifs */
    .inactive-time-row {
        position: relative;
        background-color: #f0f5ff !important;
        height: 40px;
    }

    .dark-mode .inactive-time-row {
        background-color: rgba(67, 97, 238, 0.1) !important;
    }
    
    .inactive-time-row::after {
        content: '';
        position: absolute;
        left: 5%;
        right: 5%;
        top: 50%;
        border-top: 2px dashed #adb5bd;
        z-index: 1;
    }

    .dark-mode .inactive-time-row::after {
        border-top: 2px dashed var(--dark-text-secondary);
    }
    
    .inactive-time-badge {
        position: relative;
        z-index: 2;
        background-color: #ffffff;
        border: 1px solid #007bff;
        padding: 4px 12px;
        border-radius: 1rem;
        font-size: 0.85rem;
        font-weight: 500;
        color: #007bff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }

    .dark-mode .inactive-time-badge {
        background-color: var(--dark-card-bg);
        border: 1px solid #4361ee;
        color: #4361ee;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
    
    @media (max-width: 767.98px) {
        .timeline-item {
            margin-left: 30px;
        }
        
        .timeline:before {
            left: 15px;
        }
        
        .timeline-icon {
            left: -30px;
            width: 30px;
            height: 30px;
            font-size: 0.9rem;
        }
        
        .employee-card {
            margin-bottom: 1.5rem;
        }
    }
    
    /* Styles pour le menu d'onglets */
    .nav-tabs .nav-link {
        border-radius: 0.5rem 0.5rem 0 0;
        padding: 0.75rem 1.25rem;
        font-weight: 500;
    }

    .dark-mode .nav-tabs {
        border-bottom: 1px solid var(--dark-border);
    }
    
    .dark-mode .nav-tabs .nav-link {
        color: var(--dark-text-secondary);
    }
    
    .nav-tabs .nav-link.active {
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }

    .dark-mode .nav-tabs .nav-link.active {
        background-color: var(--dark-card-bg);
        border-color: var(--dark-border) var(--dark-border) var(--dark-card-bg);
        color: var(--dark-text);
    }

    .dark-mode .nav-tabs .nav-link:hover:not(.active) {
        background-color: var(--dark-hover);
        border-color: var(--dark-border) var(--dark-border) var(--dark-border);
    }
    
    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fade-in-up {
        animation: fadeInUp 0.5s ease forwards;
    }

    /* Mode sombre pour les formulaires */
    .dark-mode .form-control,
    .dark-mode .form-select {
        background-color: var(--dark-card-bg);
        border-color: var(--dark-border);
        color: var(--dark-text);
    }

    .dark-mode .form-control:focus,
    .dark-mode .form-select:focus {
        border-color: #4361ee;
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.25);
        background-color: var(--dark-card-bg);
    }

    .dark-mode .form-label {
        color: var(--dark-text);
    }

    /* Mode sombre pour les listes */
    .dark-mode .list-group-item {
        background-color: var(--dark-card-bg);
        border-color: var(--dark-border);
        color: var(--dark-text);
    }

    /* Mode sombre pour les badges */
    .dark-mode .badge.bg-secondary {
        background-color: #4a5568 !important;
    }

    /* Mode sombre pour les tableaux */
    .dark-mode .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(255,255,255,0.05);
    }

    .dark-mode .table-hover tbody tr:hover {
        background-color: rgba(67, 97, 238, 0.1);
    }

    /* Styles pour les groupes de logs */
    .group-card {
        border-left: 4px solid #007bff;
        transition: all 0.3s ease;
    }
    
    .group-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .dark-mode .group-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }
    
    .group-card .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .dark-mode .group-card .card-header {
        background: linear-gradient(135deg, var(--dark-card-bg) 0%, rgba(0,0,0,0.3) 100%);
        border-bottom: 1px solid var(--dark-border);
    }
    
    /* Timeline compacte pour les groupes */
    .timeline-item-sm {
        margin-bottom: 1rem;
        margin-left: 30px;
    }
    
    .timeline-item-sm .timeline-icon {
        left: -30px;
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }
    
    .timeline-item-sm .timeline-content {
        padding: 1rem;
        font-size: 0.9rem;
    }
    
    /* Badges plus petits */
    .badge-sm {
        font-size: 0.65em;
        padding: 0.25em 0.5em;
    }
    
    /* Styles pour les filtres améliorés */
    .filter-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .dark-mode .filter-card {
        background: linear-gradient(135deg, var(--dark-card-bg) 0%, rgba(0,0,0,0.2) 100%);
        box-shadow: var(--dark-box-shadow);
    }
    
    .filter-card .card-header {
        background: transparent;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .dark-mode .filter-card .card-header {
        border-bottom: 1px solid var(--dark-border);
    }
    
    /* Boutons radio améliorés */
    .btn-check:checked + .btn-outline-primary {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
        box-shadow: 0 2px 8px rgba(0,123,255,0.3);
    }

    .dark-mode .btn-check:checked + .btn-outline-primary {
        background-color: #4361ee;
        border-color: #4361ee;
        box-shadow: 0 2px 8px rgba(67,97,238,0.3);
    }
    
    /* Animation pour les formulaires */
    .form-control:focus, .form-select:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,123,255,0.15);
    }

    .dark-mode .form-control:focus, 
    .dark-mode .form-select:focus {
        box-shadow: 0 4px 12px rgba(67,97,238,0.25);
    }
    
    /* Pagination moderne */
    .pagination {
        gap: 0.25rem;
    }
    
    .page-link {
        border-radius: 0.5rem;
        border: none;
        padding: 0.5rem 0.75rem;
        margin: 0 0.125rem;
        background-color: #f8f9fa;
        color: #6c757d;
        transition: all 0.3s ease;
    }
    
    .page-link:hover {
        background-color: #007bff;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,123,255,0.3);
    }
    
    .page-item.active .page-link {
        background-color: #007bff;
        color: white;
        box-shadow: 0 4px 12px rgba(0,123,255,0.4);
    }

    .dark-mode .page-link {
        background-color: var(--dark-card-bg);
        color: var(--dark-text-secondary);
        border-color: var(--dark-border);
    }

    .dark-mode .page-link:hover,
    .dark-mode .page-item.active .page-link {
        background-color: #4361ee;
        color: white;
    }
    
    /* Responsive amélioré */
    @media (max-width: 768px) {
        .filter-card .row .col-md-3,
        .filter-card .row .col-md-4,
        .filter-card .row .col-md-6 {
            margin-bottom: 1rem;
        }
        
        .btn-group {
            flex-wrap: wrap;
        }
        
        .btn-group .btn {
            margin-bottom: 0.25rem;
        }
        
        .timeline-item-sm {
            margin-left: 20px;
        }
        
        .timeline-item-sm .timeline-icon {
            left: -20px;
            width: 24px;
            height: 24px;
            font-size: 0.7rem;
        }
    }

    /* Effet de transition lors du changement de mode */
    body, .card, .timeline-content, .form-control, .form-select,
    .table, .nav-tabs, .timeline:before, .badge, .btn, .timeline-icon,
    .card-header, .employee-stats, .inactive-time-row, .inactive-time-badge,
    .repair-timeline-start i, .repair-timeline-end i, .repair-timeline-duration,
    .group-card, .filter-card, .page-link {
        transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- En-tête de page -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-history me-2"></i>
                    Logs des réparations
                </h1>
                <a href="index.php?page=reparations" class="btn btn-outline-primary">
                    <i class="fas fa-tools me-1"></i>
                    Retour aux réparations
                </a>
            </div>

            <!-- Afficher les messages -->
            <?php echo display_message(); ?>

            <!-- Barre de filtres améliorée -->
            <div class="card filter-card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-filter me-2"></i>
                            Filtres et Options d'affichage
                        </h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="resetFilters">
                                <i class="fas fa-undo me-1"></i>
                                Réinitialiser
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                                <i class="fas fa-cog me-1"></i>
                                Avancé
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="index.php" id="filterForm">
                        <input type="hidden" name="page" value="reparation_logs">
                        
                        <!-- Filtres par type de log -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-layer-group me-1"></i>
                                    Type de logs
                                </label>
                                <div class="btn-group me-3" role="group">
                                    <input type="radio" class="btn-check" name="log_type" id="log_all" value="all" <?php echo ($log_type === 'all') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-success" for="log_all">
                                        <i class="fas fa-list me-1"></i>Tout
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="log_type" id="log_repairs" value="repairs" <?php echo ($log_type === 'repairs') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="log_repairs">
                                        <i class="fas fa-tools me-1"></i>Réparations
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="log_type" id="log_tasks" value="tasks" <?php echo ($log_type === 'tasks') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-warning" for="log_tasks">
                                        <i class="fas fa-tasks me-1"></i>Tâches
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filtres rapides par période -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-bolt me-1"></i>
                                    Filtres rapides
                                </label>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="quick_filter" id="filter_all" value="" <?php echo empty($_GET['quick_filter']) ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="filter_all">Tout</label>
                                    
                                    <input type="radio" class="btn-check" name="quick_filter" id="filter_today" value="today" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] === 'today') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="filter_today">Aujourd'hui</label>
                                    
                                    <input type="radio" class="btn-check" name="quick_filter" id="filter_yesterday" value="yesterday" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] === 'yesterday') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="filter_yesterday">Hier</label>
                                    
                                    <input type="radio" class="btn-check" name="quick_filter" id="filter_week" value="week" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] === 'week') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="filter_week">Cette semaine</label>
                                    
                                    <input type="radio" class="btn-check" name="quick_filter" id="filter_month" value="month" <?php echo (isset($_GET['quick_filter']) && $_GET['quick_filter'] === 'month') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="filter_month">Ce mois</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filtres principaux -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="employe_id" class="form-label">
                                    <i class="fas fa-user me-1"></i>
                                    Employé
                                </label>
                                <select name="employe_id" id="employe_id" class="form-select">
                                    <option value="">Tous les employés</option>
                                    <?php foreach ($employes as $employe): ?>
                                        <option value="<?php echo $employe['id']; ?>" <?php echo ($employe_id == $employe['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($employe['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="action_type" class="form-label">
                                    <i class="fas fa-cogs me-1"></i>
                                    Type d'action
                                </label>
                                <select name="action_type" id="action_type" class="form-select">
                                    <option value="">Tous les types</option>
                                    <?php foreach ($action_types as $type): ?>
                                        <option value="<?php echo $type; ?>" <?php echo ($action_type === $type) ? 'selected' : ''; ?>>
                                            <?php echo get_action_label($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="reparation_id" class="form-label">
                                    <i class="fas fa-tools me-1"></i>
                                    Réparation #
                                </label>
                                <input type="number" name="reparation_id" id="reparation_id" class="form-control" 
                                       value="<?php echo $reparation_id > 0 ? $reparation_id : ''; ?>" 
                                       placeholder="ID...">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="search_term" class="form-label">
                                    <i class="fas fa-search me-1"></i>
                                    Recherche
                                </label>
                                <input type="text" name="search_term" id="search_term" class="form-control" 
                                       value="<?php echo htmlspecialchars($search_term); ?>" 
                                       placeholder="Client, appareil, détails...">
                            </div>
                        </div>
                        
                        <!-- Options d'affichage -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="view_mode" class="form-label">
                                    <i class="fas fa-eye me-1"></i>
                                    Mode d'affichage
                                </label>
                                <select name="view_mode" id="view_mode" class="form-select">
                                    <option value="timeline" <?php echo ($view_mode === 'timeline') ? 'selected' : ''; ?>>Timeline</option>
                                    <option value="employees" <?php echo ($view_mode === 'employees') ? 'selected' : ''; ?>>Par employé</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="group_by" class="form-label">
                                    <i class="fas fa-layer-group me-1"></i>
                                    Grouper par
                                </label>
                                <select name="group_by" id="group_by" class="form-select">
                                    <option value="none" <?php echo ($group_by === 'none') ? 'selected' : ''; ?>>Aucun</option>
                                    <option value="date" <?php echo ($group_by === 'date') ? 'selected' : ''; ?>>Date</option>
                                    <option value="repair" <?php echo ($group_by === 'repair') ? 'selected' : ''; ?>>Réparation</option>
                                    <option value="employee" <?php echo ($group_by === 'employee') ? 'selected' : ''; ?>>Employé</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="sort_by" class="form-label">
                                    <i class="fas fa-sort me-1"></i>
                                    Trier par
                                </label>
                                <select name="sort_by" id="sort_by" class="form-select">
                                    <option value="date_action" <?php echo ($sort_by === 'date_action') ? 'selected' : ''; ?>>Date</option>
                                    <option value="employe_nom" <?php echo ($sort_by === 'employe_nom') ? 'selected' : ''; ?>>Employé</option>
                                    <option value="action_type" <?php echo ($sort_by === 'action_type') ? 'selected' : ''; ?>>Type d'action</option>
                                    <option value="reparation_id" <?php echo ($sort_by === 'reparation_id') ? 'selected' : ''; ?>>Réparation</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="sort_order" class="form-label">
                                    <i class="fas fa-sort-amount-down me-1"></i>
                                    Ordre
                                </label>
                                <select name="sort_order" id="sort_order" class="form-select">
                                    <option value="DESC" <?php echo ($sort_order === 'DESC') ? 'selected' : ''; ?>>Décroissant</option>
                                    <option value="ASC" <?php echo ($sort_order === 'ASC') ? 'selected' : ''; ?>>Croissant</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Filtres avancés (collapsible) -->
                        <div class="collapse" id="advancedFilters">
                            <hr>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Période personnalisée
                                    </label>
                                    <div class="row">
                                        <div class="col-6">
                                            <label for="date_debut" class="form-label small">Du</label>
                                            <input type="date" name="date_debut" id="date_debut" class="form-control form-control-sm" 
                                                   value="<?php echo $date_debut; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label for="date_fin" class="form-label small">Au</label>
                                            <input type="date" name="date_fin" id="date_fin" class="form-control form-control-sm" 
                                                   value="<?php echo $date_fin; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-clock me-1"></i>
                                        Heures (optionnel)
                                    </label>
                                    <div class="row">
                                        <div class="col-6">
                                            <label for="heure_debut" class="form-label small">De</label>
                                            <input type="time" name="heure_debut" id="heure_debut" class="form-control form-control-sm" 
                                                   value="<?php echo $heure_debut; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label for="heure_fin" class="form-label small">À</label>
                                            <input type="time" name="heure_fin" id="heure_fin" class="form-control form-control-sm" 
                                                   value="<?php echo $heure_fin; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>
                                    Appliquer les filtres
                                </button>
                                <a href="index.php?page=reparation_logs" class="btn btn-outline-secondary">
                                    <i class="fas fa-eraser me-1"></i>
                                    Effacer
                                </a>
                            </div>
                            
                            <!-- Options de pagination -->
                            <?php if ($view_mode === 'timeline'): ?>
                            <div class="d-flex align-items-center gap-2">
                                <label for="limit" class="form-label mb-0 small">Éléments par page:</label>
                                <select name="limit" id="limit" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                    <option value="10" <?php echo ($limit == 10) ? 'selected' : ''; ?>>10</option>
                                    <option value="20" <?php echo ($limit == 20) ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?php echo ($limit == 50) ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo ($limit == 100) ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Navigation par onglets simplifiée -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <ul class="nav nav-tabs" id="logsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($view_mode === 'timeline') ? 'active' : ''; ?>" 
                                id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline-tab-pane" 
                                type="button" role="tab" aria-controls="timeline-tab-pane" 
                                aria-selected="<?php echo ($view_mode === 'timeline') ? 'true' : 'false'; ?>">
                            <i class="fas fa-stream me-2"></i>
                            Timeline des logs
                            <?php if ($view_mode === 'timeline'): ?>
                                <span class="badge bg-primary ms-2"><?php echo number_format($total_logs); ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($view_mode === 'employees') ? 'active' : ''; ?>" 
                                id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees-tab-pane" 
                                type="button" role="tab" aria-controls="employees-tab-pane" 
                                aria-selected="<?php echo ($view_mode === 'employees') ? 'true' : 'false'; ?>">
                            <i class="fas fa-users me-2"></i>
                            Réparations par employé
                            <span class="badge bg-info ms-2"><?php echo count($employees); ?></span>
                        </button>
                    </li>
                </ul>
                
                <!-- Info résultats et pagination pour timeline -->
                <?php if ($view_mode === 'timeline' && $total_logs > 0): ?>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-muted small">
                        Affichage <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $total_logs); ?> 
                        sur <?php echo number_format($total_logs); ?> résultats
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content mt-3" id="logsTabContent">
                <!-- Onglet Timeline -->
                <div class="tab-pane fade <?php echo ($view_mode === 'timeline') ? 'show active' : ''; ?>" id="timeline-tab-pane" role="tabpanel" aria-labelledby="timeline-tab" tabindex="0">
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($logs)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucun log trouvé pour les critères sélectionnés.
                                </div>
                            <?php else: ?>
                                <!-- Affichage groupé ou normal -->
                                <?php if ($group_by === 'none'): ?>
                                    <!-- Affichage timeline normale -->
                                    <div class="timeline">
                                        <?php foreach ($grouped_logs as $log): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-icon bg-<?php echo get_action_color($log['action_type'], $log['log_source']); ?>">
                                                    <i class="fas fa-<?php echo get_action_icon($log['action_type'], $log['log_source']); ?>"></i>
                                                </div>
                                                <div class="timeline-content log-card">
                                                    <span class="timeline-date">
                                                        <i class="far fa-clock me-1"></i>
                                                        <?php echo format_datetime($log['date_action']); ?>
                                                    </span>
                                                    <h4 class="timeline-title">
                                                        <span class="log-badge bg-<?php echo get_action_color($log['action_type'], $log['log_source']); ?>">
                                                            <?php echo get_action_label($log['action_type'], $log['log_source']); ?>
                                                        </span>
                                                        
                                                        <?php if ($log['log_source'] === 'task'): ?>
                                                            <span class="text-decoration-none ms-2">
                                                                <i class="fas fa-tasks me-1"></i>
                                                                <?php echo htmlspecialchars($log['task_title']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <a href="index.php?page=details_reparation&id=<?php echo $log['entity_id']; ?>" class="text-decoration-none ms-2">
                                                                <i class="fas fa-tools me-1"></i>
                                                                Réparation #<?php echo $log['entity_id']; ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </h4>
                                                    <div class="timeline-details">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p class="mb-1">
                                                                    <strong><i class="fas fa-user me-1"></i> Employé:</strong>
                                                                    <span class="text-<?php echo get_employe_color($log['employe_nom']); ?>">
                                                                        <?php echo htmlspecialchars($log['employe_nom']); ?>
                                                                    </span>
                                                                </p>
                                                                
                                                                <?php if ($log['log_source'] === 'task'): ?>
                                                                    <p class="mb-1">
                                                                        <strong><i class="fas fa-tasks me-1"></i> Type:</strong>
                                                                        <span class="badge bg-warning">Tâche</span>
                                                                    </p>
                                                                    <?php if ($log['task_title']): ?>
                                                                        <p class="mb-1">
                                                                            <strong><i class="fas fa-tag me-1"></i> Titre:</strong>
                                                                            <?php echo htmlspecialchars($log['task_title']); ?>
                                                                        </p>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <p class="mb-1">
                                                                        <strong><i class="fas fa-user-tie me-1"></i> Client:</strong>
                                                                        <?php echo htmlspecialchars($log['client_nom']); ?>
                                                                    </p>
                                                                    <p class="mb-1">
                                                                        <strong><i class="fas fa-mobile-alt me-1"></i> Appareil:</strong>
                                                                        <?php echo htmlspecialchars($log['type_appareil'] . ' ' . $log['modele']); ?>
                                                                    </p>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <?php if ($log['statut_avant'] && $log['statut_apres']): ?>
                                                                    <p class="mb-1">
                                                                        <strong><i class="fas fa-exchange-alt me-1"></i> Changement de statut:</strong>
                                                                        <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($log['statut_avant']); ?></span>
                                                                        <i class="fas fa-arrow-right mx-1"></i>
                                                                        <span class="badge bg-success"><?php echo htmlspecialchars($log['statut_apres']); ?></span>
                                                                    </p>
                                                                <?php endif; ?>
                                                                <?php if ($log['details']): ?>
                                                                    <p>
                                                                        <strong><i class="fas fa-info-circle me-1"></i> Détails:</strong>
                                                                        <?php echo htmlspecialchars($log['details']); ?>
                                                                    </p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Pagination -->
                                    <?php if ($total_logs > $limit): ?>
                                        <?php
                                        $total_pages = ceil($total_logs / $limit);
                                        $current_params = $_GET;
                                        unset($current_params['p']);
                                        $base_url = 'index.php?' . http_build_query($current_params);
                                        ?>
                                        <nav aria-label="Navigation des logs" class="mt-4">
                                            <ul class="pagination justify-content-center">
                                                <!-- Première page -->
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="<?php echo $base_url; ?>&p=1">
                                                            <i class="fas fa-angle-double-left"></i>
                                                        </a>
                                                    </li>
                                                    <li class="page-item">
                                                        <a class="page-link" href="<?php echo $base_url; ?>&p=<?php echo ($page - 1); ?>">
                                                            <i class="fas fa-angle-left"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <!-- Pages autour de la page actuelle -->
                                                <?php
                                                $start_page = max(1, $page - 2);
                                                $end_page = min($total_pages, $page + 2);
                                                
                                                for ($i = $start_page; $i <= $end_page; $i++):
                                                ?>
                                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                        <a class="page-link" href="<?php echo $base_url; ?>&p=<?php echo $i; ?>">
                                                            <?php echo $i; ?>
                                                        </a>
                                                    </li>
                                                <?php endfor; ?>
                                                
                                                <!-- Dernière page -->
                                                <?php if ($page < $total_pages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="<?php echo $base_url; ?>&p=<?php echo ($page + 1); ?>">
                                                            <i class="fas fa-angle-right"></i>
                                                        </a>
                                                    </li>
                                                    <li class="page-item">
                                                        <a class="page-link" href="<?php echo $base_url; ?>&p=<?php echo $total_pages; ?>">
                                                            <i class="fas fa-angle-double-right"></i>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <!-- Affichage groupé -->
                                    <?php foreach ($grouped_logs as $group_key => $group_logs): ?>
                                        <div class="card mb-4 group-card">
                                            <div class="card-header">
                                                <h5 class="mb-0">
                                                    <?php
                                                    switch ($group_by) {
                                                        case 'date':
                                                            echo '<i class="fas fa-calendar me-2"></i>' . date('d/m/Y', strtotime($group_key));
                                                            break;
                                                        case 'repair':
                                                            echo '<i class="fas fa-tools me-2"></i>Réparation #' . $group_key;
                                                            if (isset($group_logs[0])) {
                                                                echo ' - ' . htmlspecialchars($group_logs[0]['type_appareil'] . ' ' . $group_logs[0]['modele']);
                                                            }
                                                            break;
                                                        case 'employee':
                                                            if (isset($group_logs[0])) {
                                                                echo '<i class="fas fa-user me-2"></i>' . htmlspecialchars($group_logs[0]['employe_nom']);
                                                            }
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-primary ms-2"><?php echo count($group_logs); ?> logs</span>
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="timeline">
                                                    <?php foreach ($group_logs as $log): ?>
                                                        <div class="timeline-item timeline-item-sm">
                                                            <div class="timeline-icon bg-<?php echo get_action_color($log['action_type'], $log['log_source']); ?>">
                                                                <i class="fas fa-<?php echo get_action_icon($log['action_type'], $log['log_source']); ?>"></i>
                                                            </div>
                                                            <div class="timeline-content log-card">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <span class="timeline-date">
                                                                        <i class="far fa-clock me-1"></i>
                                                                        <?php echo format_datetime($log['date_action']); ?>
                                                                    </span>
                                                                    <span class="log-badge bg-<?php echo get_action_color($log['action_type'], $log['log_source']); ?>">
                                                                        <?php echo get_action_label($log['action_type'], $log['log_source']); ?>
                                                                    </span>
                                                                </div>
                                                                
                                                                <div class="row">
                                                                    <?php if ($group_by !== 'employee'): ?>
                                                                    <div class="col-md-4">
                                                                        <small class="text-muted">Employé:</small><br>
                                                                        <span class="text-<?php echo get_employe_color($log['employe_nom']); ?>">
                                                                            <?php echo htmlspecialchars($log['employe_nom']); ?>
                                                                        </span>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if ($group_by !== 'repair'): ?>
                                                                    <div class="col-md-4">
                                                                        <?php if ($log['log_source'] === 'task'): ?>
                                                                            <small class="text-muted">Tâche:</small><br>
                                                                            <span class="text-decoration-none">
                                                                                <i class="fas fa-tasks me-1"></i>
                                                                                <?php echo htmlspecialchars($log['task_title'] ?: 'Tâche #' . $log['entity_id']); ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <small class="text-muted">Réparation:</small><br>
                                                                            <a href="index.php?page=details_reparation&id=<?php echo $log['entity_id']; ?>" class="text-decoration-none">
                                                                                <i class="fas fa-tools me-1"></i>
                                                                                #<?php echo $log['entity_id']; ?>
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <div class="col-md-4">
                                                                        <?php if ($log['statut_avant'] && $log['statut_apres']): ?>
                                                                            <small class="text-muted">Statut:</small><br>
                                                                            <span class="badge bg-secondary badge-sm me-1"><?php echo htmlspecialchars($log['statut_avant']); ?></span>
                                                                            <i class="fas fa-arrow-right mx-1 small"></i>
                                                                            <span class="badge bg-success badge-sm"><?php echo htmlspecialchars($log['statut_apres']); ?></span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                
                                                                <?php if ($log['details']): ?>
                                                                    <div class="mt-2">
                                                                        <small class="text-muted">Détails:</small><br>
                                                                        <small><?php echo htmlspecialchars($log['details']); ?></small>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                                <!-- Onglet Réparations par employé -->
                <div class="tab-pane fade <?php echo ($view_mode === 'employees') ? 'show active' : ''; ?>" id="employees-tab-pane" role="tabpanel" aria-labelledby="employees-tab" tabindex="0">
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($employees)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucun employé trouvé avec des logs pour les critères sélectionnés.
                                </div>
                            <?php else: ?>
                                <!-- Simple vue par employé - résumé -->
                                <div class="row">
                                    <?php foreach ($employees as $emp_id => $employee): ?>
                                        <div class="col-lg-6 mb-3">
                                            <div class="card employee-summary-card">
                                                <div class="card-header <?php echo get_employe_background_color($employee['name']); ?>">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-user me-2"></i>
                                                        <?php echo htmlspecialchars($employee['name']); ?>
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <small class="text-muted">Réparations</small>
                                                            <div class="h5 mb-0"><?php echo count($employee['repairs']); ?></div>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted">Tâches</small>
                                                            <div class="h5 mb-0"><?php echo count($employee['tasks']); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Onglet Réparations par employé -->
                <div class="tab-pane fade" id="employees-tab-pane" role="tabpanel" aria-labelledby="employees-tab" tabindex="0">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user-clock me-2"></i>
                                Activités par employé
                                <?php if ($log_type === 'tasks'): ?>
                                    (Tâches uniquement)
                                <?php elseif ($log_type === 'repairs'): ?>
                                    (Réparations uniquement)
                                <?php else: ?>
                                    (Réparations et Tâches)
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($employees)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucune donnée disponible pour les critères sélectionnés.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($employees as $emp_id => $employee): ?>
                                        <div class="col-lg-12 mb-4">
                                            <div class="card employee-card shadow-sm employee-card-clickable" 
                                                 data-employee-id="<?php echo $emp_id; ?>" 
                                                 data-employee-name="<?php echo htmlspecialchars($employee['name'], ENT_QUOTES); ?>">
                                                <div class="card-header <?php echo get_employe_background_color($employee['name']); ?> position-relative">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h5 class="mb-0 fw-bold">
                                                            <i class="fas fa-user me-2"></i>
                                                            <?php echo htmlspecialchars($employee['name']); ?>
                                                            <span class="timeline-indicator">
                                                                <i class="fas fa-chart-line ms-2"></i>
                                                                <small class="ms-1">Voir timeline</small>
                                                            </span>
                                                        </h5>
                                                        <?php
                                                        $total_interventions = 0;
                                                        $completed_interventions = 0;
                                                        
                                                        foreach ($employee['repairs'] as $repair) {
                                                            $sequences = get_all_repair_sequences($repair['logs']);
                                                            foreach ($sequences as $sequence) {
                                                                if ($sequence['start']) {
                                                                    $total_interventions++;
                                                                    if ($sequence['end']) {
                                                                        $completed_interventions++;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                        <div>
                                                            <span class="badge bg-<?php echo get_employe_color($employee['name']); ?> rounded-pill">
                                                                <?php echo $total_interventions; ?> intervention(s)
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-body p-0">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-hover mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th width="5%">#</th>
                                                                    <th width="10%">Type</th>
                                                                    <th width="20%">Réparation/Tâche</th>
                                                                    <th width="20%">Description</th>
                                                                    <th width="15%">Démarrage</th>
                                                                    <th width="15%">Fin</th>
                                                                    <th width="15%">Durée</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                // Trier les réparations par date de démarrage (plus récente en premier)
                                                                uasort($employee['repairs'], function($a, $b) {
                                                                    $a_data = get_repair_start_end($a['logs']);
                                                                    $b_data = get_repair_start_end($b['logs']);
                                                                    
                                                                    $a_start = $a_data['start'] ? $a_data['start']['date_action'] : null;
                                                                    $b_start = $b_data['start'] ? $b_data['start']['date_action'] : null;
                                                                    
                                                                    if (!$a_start && !$b_start) return 0;
                                                                    if (!$a_start) return 1;
                                                                    if (!$b_start) return -1;
                                                                    
                                                                    return strtotime($b_start) - strtotime($a_start);
                                                                });
                                                                
                                                                $repair_count = 0;
                                                                $previous_repair_end = null;
                                                                $sorted_repairs = [];
                                                                
                                                                // Combiner réparations et tâches selon le type de log
                                                                $temp_interventions = [];
                                                                
                                                                // Ajouter les réparations si on les affiche
                                                                if ($log_type !== 'tasks') {
                                                                    foreach ($employee['repairs'] as $repair) {
                                                                        $sequences = get_all_repair_sequences($repair['logs']);
                                                                        
                                                                        // Pour chaque séquence (démarrage-fin), créer une intervention
                                                                        foreach ($sequences as $sequence) {
                                                                            $temp_interventions[] = [
                                                                                'type' => 'repair',
                                                                                'repair' => $repair,
                                                                                'start' => $sequence['start'],
                                                                                'end' => $sequence['end']
                                                                            ];
                                                                        }
                                                                    }
                                                                }
                                                                
                                                                // Ajouter les tâches si on les affiche
                                                                if ($log_type !== 'repairs') {
                                                                    foreach ($employee['tasks'] as $task) {
                                                                        $task_sequences = get_all_task_sequences($task['logs']);
                                                                        
                                                                        // Pour chaque séquence (démarrage-fin), créer une intervention
                                                                        foreach ($task_sequences as $sequence) {
                                                                            $temp_interventions[] = [
                                                                                'type' => 'task',
                                                                                'task' => $task,
                                                                                'start' => $sequence['start'],
                                                                                'end' => $sequence['end']
                                                                            ];
                                                                        }
                                                                    }
                                                                }
                                                                
                                                                // Trier toutes les interventions par date de démarrage (du plus ancien au plus récent)
                                                                usort($temp_interventions, function($a, $b) {
                                                                    $a_time = $a['start'] ? strtotime($a['start']['date_action']) : 0;
                                                                    $b_time = $b['start'] ? strtotime($b['start']['date_action']) : 0;
                                                                    
                                                                    if ($a_time == 0 && $b_time == 0) return 0;
                                                                    if ($a_time == 0) return 1;
                                                                    if ($b_time == 0) return -1;
                                                                    
                                                                    return $a_time - $b_time;
                                                                });
                                                                
                                                                // Ajouter un indicateur pour alterner les couleurs
                                                                $row_class = '';
                                                                
                                                                foreach ($temp_interventions as $index => $intervention): 
                                                                    $is_repair = ($intervention['type'] === 'repair');
                                                                    $item = $is_repair ? $intervention['repair'] : $intervention['task'];
                                                                    $start = $intervention['start'];
                                                                    $end = $intervention['end'];
                                                                    $repair_count++;
                                                                    
                                                                    // Alterner les couleurs de fond des lignes
                                                                    $row_class = ($row_class === 'table-light') ? '' : 'table-light';
                                                                    
                                                                    // Si nous avons une réparation précédente terminée
                                                                    if ($previous_repair_end && $start) {
                                                                        $inactive_time = calculate_inactive_time(
                                                                            $previous_repair_end['date_action'],
                                                                            $start['date_action']
                                                                        );
                                                                        
                                                                        // Afficher seulement si le temps inactif est significatif
                                                                        if ($inactive_time && $inactive_time !== '0min') {
                                                                            // Calculer le temps en minutes pour déterminer l'importance visuelle
                                                                            $prev_end_time = strtotime($previous_repair_end['date_action']);
                                                                            $curr_start_time = strtotime($start['date_action']);
                                                                            $minutes_diff = ($curr_start_time - $prev_end_time) / 60;
                                                                            
                                                                            // Déterminer la classe CSS selon la durée
                                                                            $pause_class = '';
                                                                            $pause_icon = 'fa-pause-circle';
                                                                            $badge_color = 'primary';
                                                                            
                                                                            if ($minutes_diff > 60) { // Plus d'une heure
                                                                                $pause_class = 'bg-danger bg-opacity-25';
                                                                                $pause_icon = 'fa-bed';
                                                                                $badge_color = 'danger';
                                                                            } elseif ($minutes_diff > 30) { // Plus de 30 minutes
                                                                                $pause_class = 'bg-warning bg-opacity-25';
                                                                                $pause_icon = 'fa-coffee';
                                                                                $badge_color = 'warning';
                                                                            } elseif ($minutes_diff > 15) { // Plus de 15 minutes
                                                                                $pause_class = 'bg-info bg-opacity-25';
                                                                                $pause_icon = 'fa-mug-hot';
                                                                                $badge_color = 'info';
                                                                            }
                                                                ?>
                                                                <tr class="inactive-time-row <?php echo $pause_class; ?>">
                                                                    <td colspan="6" class="text-center py-3">
                                                                        <span class="inactive-time-badge text-<?php echo $badge_color; ?> border-<?php echo $badge_color; ?>">
                                                                            <i class="fas <?php echo $pause_icon; ?> me-1"></i>
                                                                            Temps de pause: <?php echo $inactive_time; ?>
                                                                        </span>
                                                                        <div class="mt-1 small text-secondary">
                                                                            <?php 
                                                                            $prev_end = new DateTime($previous_repair_end['date_action']);
                                                                            $next_start = new DateTime($start['date_action']);
                                                                            echo "De " . $prev_end->format('d/m/Y H:i') . " à " . $next_start->format('d/m/Y H:i');
                                                                            ?>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <?php
                                                                        }
                                                                    }
                                                                ?>
                                                                <tr class="<?php echo $row_class; ?>">
                                                                    <td>
                                                                        <?php if ($is_repair): ?>
                                                                            <a href="index.php?page=details_reparation&id=<?php echo $item['id']; ?>" 
                                                                               class="btn btn-sm btn-outline-<?php echo get_employe_color($employee['name']); ?>"
                                                                               onclick="event.stopPropagation();">
                                                                                #<?php echo $item['id']; ?>
                                                                            </a>
                                                                        <?php else: ?>
                                                                            <a href="index.php?page=taches&task_id=<?php echo $item['id']; ?>&open_modal=1" 
                                                                               class="btn btn-sm btn-outline-<?php echo get_employe_color($employee['name']); ?>"
                                                                               onclick="event.stopPropagation();">
                                                                                T#<?php echo $item['id']; ?>
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($is_repair): ?>
                                                                            <span class="badge bg-primary">
                                                                                <i class="fas fa-tools me-1"></i>
                                                                                Réparation
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-success">
                                                                                <i class="fas fa-tasks me-1"></i>
                                                                                Tâche
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($is_repair): ?>
                                                                            <div>
                                                                                <a href="#" class="text-decoration-none client-info-link" data-bs-toggle="modal" data-bs-target="#clientModal" data-client-id="<?php echo $item['client_id']; ?>">
                                                                                    <i class="fas fa-user-tie me-1 text-muted"></i>
                                                                                    <?php echo htmlspecialchars($item['client_nom']); ?>
                                                                                </a>
                                                                            </div>
                                                                            <div class="text-muted small">
                                                                                <i class="fas fa-<?php echo $item['type_appareil'] === 'Smartphone' ? 'mobile-alt' : 'laptop'; ?> me-1"></i>
                                                                                <?php echo htmlspecialchars($item['type_appareil'] . ' ' . $item['modele']); ?>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="fw-bold">
                                                                                <?php echo htmlspecialchars($item['title']); ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <div class="text-wrap small">
                                                                            <?php echo !empty($item['description']) ? htmlspecialchars($item['description']) : '<span class="text-muted">Aucune description</span>'; ?>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (is_array($start)): ?>
                                                                            <div>
                                                                                <i class="fas fa-play-circle text-success me-1"></i>
                                                                                <strong><?php echo (new DateTime($start['date_action']))->format('d/m/Y H:i'); ?></strong>
                                                                            </div>
                                                                            <?php 
                                                                            // Afficher le statut initial s'il existe
                                                                            if (is_array($start) && array_key_exists('statut_avant', $start) && !empty($start['statut_avant'])): 
                                                                            ?>
                                                                            <div class="mt-1">
                                                                                <span class="badge bg-secondary">
                                                                                    <?php echo htmlspecialchars($start['statut_avant']); ?>
                                                                                </span>
                                                                            </div>
                                                                            <?php endif; ?>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary">Non démarré</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (is_array($end)): ?>
                                                                            <div>
                                                                                <?php
                                                                                // Afficher une icône et une couleur différente selon le type d'action de fin
                                                                                $end_icon = 'stop-circle';
                                                                                $end_color = 'danger';
                                                                                
                                                                                if (array_key_exists('action_type', $end)) {
                                                                                    switch ($end['action_type']) {
                                                                                        case 'terminer':
                                                                                            $end_icon = 'stop-circle';
                                                                                            $end_color = 'danger';
                                                                                            break;
                                                                                        case 'changement_statut':
                                                                                            $end_icon = 'exchange-alt';
                                                                                            $end_color = 'warning';
                                                                                            break;
                                                                                        case 'ajout_note':
                                                                                            $end_icon = 'sticky-note';
                                                                                            $end_color = 'info';
                                                                                            break;
                                                                                        case 'modification':
                                                                                            $end_icon = 'edit';
                                                                                            $end_color = 'secondary';
                                                                                            break;
                                                                                        case 'autre':
                                                                                            $end_icon = 'cog';
                                                                                            $end_color = 'dark';
                                                                                            break;
                                                                                    }
                                                                                }
                                                                                ?>
                                                                                <div class="d-flex align-items-center">
                                                                                    <i class="fas fa-<?php echo $end_icon; ?> text-<?php echo $end_color; ?> me-1"></i>
                                                                                    <strong><?php echo (new DateTime($end['date_action']))->format('d/m/Y H:i'); ?></strong>
                                                                                </div>
                                                                                <?php
                                                                                // Afficher le statut final s'il existe
                                                                                if (array_key_exists('statut_apres', $end) && !empty($end['statut_apres'])): 
                                                                                ?>
                                                                                <div class="mt-1">
                                                                                    <span class="badge bg-success">
                                                                                        <?php echo htmlspecialchars($end['statut_apres']); ?>
                                                                                    </span>
                                                                                </div>
                                                                                <?php 
                                                                                // Sinon, afficher le statut avant s'il existe (pour certains types d'actions)
                                                                                elseif (array_key_exists('statut_avant', $end) && !empty($end['statut_avant'])): 
                                                                                ?>
                                                                                <div class="mt-1">
                                                                                    <span class="badge bg-<?php echo array_key_exists('action_type', $end) ? get_action_color($end['action_type']) : 'secondary'; ?>">
                                                                                        <?php echo htmlspecialchars($end['statut_avant']); ?>
                                                                                    </span>
                                                                                </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <?php if (is_array($start)): ?>
                                                                                <span class="badge bg-primary">En cours</span>
                                                                            <?php else: ?>
                                                                                <span class="badge bg-secondary">Non terminé</span>
                                                                            <?php endif; ?>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (is_array($start) && is_array($end)): ?>
                                                                            <span class="badge bg-light text-dark">
                                                                                <?php echo calculate_duration($start['date_action'], $end['date_action']); ?>
                                                                            </span>
                                                                        <?php elseif (is_array($start)): ?>
                                                                            <span class="badge bg-primary">En cours</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <?php
                                                                    // Mettre à jour la fin de réparation précédente si cette réparation est terminée
                                                                    if ($end) {
                                                                        $previous_repair_end = $end;
                                                                    }
                                                                endforeach; 
                                                                
                                                                // Si aucune réparation trouvée
                                                                if ($repair_count === 0):
                                                                ?>
                                                                <tr>
                                                                    <td colspan="6" class="text-center py-4">
                                                                        <div class="alert alert-info mb-0">
                                                                            <i class="fas fa-info-circle me-2"></i>
                                                                            Aucune réparation trouvée pour cet employé
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="card-footer employee-stats">
                                                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                                                        <div>
                                                            <span class="stats-badge">
                                                                <i class="fas fa-check-circle text-success"></i>
                                                                <?php echo $completed_interventions; ?> terminée(s)
                                                            </span>
                                                            <span class="stats-badge">
                                                                <i class="fas fa-hourglass-half text-warning"></i>
                                                                <?php echo $total_interventions - $completed_interventions; ?> en cours
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class="stats-badge">
                                                                <i class="fas fa-clock text-info"></i>
                                                                Temps total: <?php echo calculate_total_work_time_from_interventions($employee['repairs']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Client -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="clientModalLabel"><i class="fas fa-user-tie me-2"></i>Informations Client</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="spinner-border text-primary" role="status" id="clientModalLoader">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
                <div id="clientModalContent" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-header bg-primary bg-opacity-10">
                                    <h5 class="mb-0 text-primary"><i class="fas fa-info-circle me-2"></i>Détails du client</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong><i class="fas fa-user me-2"></i>Nom:</strong> <span id="clientNom"></span></p>
                                    <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> <span id="clientEmail"></span></p>
                                    <p><strong><i class="fas fa-phone me-2"></i>Téléphone:</strong> <span id="clientTelephone"></span></p>
                                    <p><strong><i class="fas fa-map-marker-alt me-2"></i>Adresse:</strong> <span id="clientAdresse"></span></p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center gap-3 mb-3">
                                <a href="#" class="btn btn-success" id="btnCallClient">
                                    <i class="fas fa-phone-alt me-2"></i>Appeler
                                </a>
                                <a href="#" class="btn btn-info text-white" id="btnSmsClient"
                                   onclick="openSmsModal(
                                       currentClientId, 
                                       document.getElementById('clientNom').textContent.split(' ')[0] || '', 
                                       document.getElementById('clientNom').textContent.split(' ')[1] || '', 
                                       document.getElementById('clientTelephone').textContent
                                   ); return false;">
                                    <i class="fas fa-sms me-2"></i>Envoyer SMS
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary bg-opacity-10">
                                    <h5 class="mb-0 text-primary"><i class="fas fa-history me-2"></i>Historique du client</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush" id="clientHistorique" style="max-height: 300px; overflow-y: auto;">
                                        <!-- L'historique sera chargé ici -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-outline-primary" id="btnVoirFiche">
                    <i class="fas fa-external-link-alt me-2"></i>Voir fiche complète
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page reparation_logs chargée');
    
    // Fonction pour afficher un indicateur de chargement
    function showLoadingIndicator() {
        // Créer ou afficher un indicateur de chargement
        let loadingIndicator = document.getElementById('loadingIndicator');
        if (!loadingIndicator) {
            loadingIndicator = document.createElement('div');
            loadingIndicator.id = 'loadingIndicator';
            loadingIndicator.innerHTML = `
                <div class="d-flex justify-content-center align-items-center" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
                    <div class="bg-white p-4 rounded shadow">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border text-primary me-3" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <span>Application des filtres...</span>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingIndicator);
        } else {
            loadingIndicator.style.display = 'block';
        }
    }
    
    // Vérifier que le formulaire existe
    const filterForm = document.getElementById('filterForm');
    console.log('Formulaire filterForm trouvé:', !!filterForm);
    

    
    // Animation pour les éléments de la timeline
    const timelineItems = document.querySelectorAll('.timeline-item');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });
    
    timelineItems.forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(item);
    });
    
    // Activer les tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Animation lors du changement d'onglet
    const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabEls.forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', event => {
            const target = document.querySelector(event.target.getAttribute('data-bs-target'));
            target.querySelectorAll('.card').forEach(card => {
                card.classList.add('fade-in-up');
                setTimeout(() => {
                    card.classList.remove('fade-in-up');
                }, 500);
            });
        });
    });
    
    // Gestion du modal client
    const clientLinks = document.querySelectorAll('.client-info-link');
    const clientModal = document.getElementById('clientModal');
    const clientModalContent = document.getElementById('clientModalContent');
    const clientModalLoader = document.getElementById('clientModalLoader');
    
    // Variable pour stocker l'ID du client actuel
    let currentClientId = '';
    
    // Fonction pour charger les données du client
    function loadClientData(clientId) {
        clientModalContent.style.display = 'none';
        clientModalLoader.style.display = 'block';
        
        console.log('Chargement des données pour le client ID:', clientId);
        
        // Appel AJAX pour récupérer les informations du client
        fetch('ajax/get_client_info.php?client_id=' + clientId, {
            method: 'GET',
            credentials: 'same-origin', // Inclure les cookies de session
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                console.log('Réponse reçue:', response.status, response.statusText);
                return response.json();
            })
            .then(data => {
                console.log('Données reçues:', data);
                
                if (data.success) {
                    // Remplir les informations du client
                    document.getElementById('clientNom').textContent = data.client.nom + ' ' + data.client.prenom;
                    document.getElementById('clientEmail').textContent = data.client.email;
                    document.getElementById('clientTelephone').textContent = data.client.telephone;
                    document.getElementById('clientAdresse').textContent = data.client.adresse;
                    
                    // Configurer les boutons d'action
                    document.getElementById('btnCallClient').href = 'tel:' + data.client.telephone;
                    document.getElementById('btnSmsClient').href = 'sms:' + data.client.telephone;
                    document.getElementById('btnVoirFiche').href = 'index.php?page=details_client&id=' + clientId;
                    
                    // Remplir l'historique du client
                    const historiqueContainer = document.getElementById('clientHistorique');
                    historiqueContainer.innerHTML = '';
                    
                    if (data.historique && data.historique.length > 0) {
                        data.historique.forEach(item => {
                            const historiqueItem = document.createElement('a');
                            historiqueItem.className = 'list-group-item list-group-item-action';
                            historiqueItem.href = 'index.php?page=details_reparation&id=' + item.id;
                            
                            const badgeStatus = document.createElement('span');
                            badgeStatus.className = 'badge bg-' + item.statusColor + ' float-end';
                            badgeStatus.textContent = item.statut;
                            
                            const itemContent = document.createElement('div');
                            itemContent.className = 'd-flex w-100 justify-content-between';
                            
                            const heading = document.createElement('h6');
                            heading.className = 'mb-1';
                            heading.textContent = item.type_appareil + ' ' + item.modele;
                            
                            const date = document.createElement('small');
                            date.className = 'text-muted';
                            date.textContent = item.date_creation;
                            
                            itemContent.appendChild(heading);
                            itemContent.appendChild(date);
                            
                            const details = document.createElement('p');
                            details.className = 'mb-1 small';
                            details.textContent = item.probleme.substring(0, 100) + (item.probleme.length > 100 ? '...' : '');
                            
                            historiqueItem.appendChild(itemContent);
                            historiqueItem.appendChild(details);
                            historiqueItem.appendChild(badgeStatus);
                            
                            historiqueContainer.appendChild(historiqueItem);
                        });
                    } else {
                        historiqueContainer.innerHTML = '<div class="list-group-item text-center py-3">Aucun historique disponible</div>';
                    }
                    
                    // Afficher le contenu du modal
                    clientModalLoader.style.display = 'none';
                    clientModalContent.style.display = 'block';
                } else {
                    // Afficher un message d'erreur
                    clientModalContent.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des données client</div>';
                    clientModalLoader.style.display = 'none';
                    clientModalContent.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                clientModalContent.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des données client</div>';
                clientModalLoader.style.display = 'none';
                clientModalContent.style.display = 'block';
            });
    }
    
    // Événement lors de l'ouverture du modal
    clientModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const clientId = button.getAttribute('data-client-id');
        console.log('Ouverture du modal pour le client ID:', clientId);
        loadClientData(clientId);
    });
    
    // Événement lors du clic sur les liens client
    clientLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const clientId = this.getAttribute('data-client-id');
            console.log('Clic sur le client avec ID:', clientId);
            // Vérifier que l'ID a bien été récupéré
            if (!clientId) {
                console.error('Erreur: ID client manquant dans l\'attribut data-client-id');
                console.log('Element HTML:', this.outerHTML);
            }
            // Le modal sera ouvert par l'attribut data-bs-toggle
        });
    });

    // Fonction pour définir automatiquement les dates selon la période sélectionnée
    function setPeriode(periode) {
        const today = new Date();
        let dateDebut = '';
        let dateFin = '';
        
        // Formater les dates pour l'entrée 'date' HTML
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        switch (periode) {
            case 'aujourd\'hui':
                dateDebut = formatDate(today);
                dateFin = dateDebut;
                break;
            case 'hier':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                dateDebut = formatDate(yesterday);
                dateFin = dateDebut;
                break;
            case 'semaine':
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1)); // Lundi de la semaine
                dateDebut = formatDate(startOfWeek);
                dateFin = formatDate(today);
                break;
            case 'mois':
                const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                dateDebut = formatDate(startOfMonth);
                dateFin = formatDate(today);
                break;
            case 'personnalise':
                // Ne rien faire, l'utilisateur entrera les dates
                break;
        }
        
        document.getElementById('date_debut').value = dateDebut;
        document.getElementById('date_fin').value = dateFin;
    }

    // Recherche dynamique
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            // Recherche dans les réparations de la timeline
            if (document.getElementById('timeline-tab-pane').classList.contains('active')) {
                const timelineItems = document.querySelectorAll('.timeline-item');
                timelineItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            } 
            // Recherche dans les réparations par employé
            else if (document.getElementById('employees-tab-pane').classList.contains('active')) {
                const repairRows = document.querySelectorAll('.employee-card tbody tr:not(.inactive-time-row)');
                repairRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
        
        // Réinitialiser la recherche lors du changement d'onglet
        const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabEls.forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', event => {
                searchInput.value = '';
                const timelineItems = document.querySelectorAll('.timeline-item');
                timelineItems.forEach(item => {
                    item.style.display = '';
                });
                const repairRows = document.querySelectorAll('.employee-card tbody tr');
                repairRows.forEach(row => {
                    row.style.display = '';
                });
            });
        });
    }

    // Gestion des filtres de type de log (Tout / Réparations / Tâches)
    const logTypeButtons = document.querySelectorAll('input[name="log_type"]');
    console.log('Filtres de type de log trouvés:', logTypeButtons.length);
    
    logTypeButtons.forEach(button => {
        // Utiliser 'click' au lieu de 'change' pour une meilleure compatibilité
        button.addEventListener('click', function() {
            console.log('Clic sur filtre de type de log:', this.value);
            // Ajouter un indicateur de chargement
            const form = document.getElementById('filterForm');
            if (form) {
                // Afficher un indicateur de chargement
                showLoadingIndicator();
                
                // Soumettre automatiquement le formulaire
                setTimeout(() => {
                    console.log('Soumission du formulaire pour type de log:', this.value);
                    form.submit();
                }, 200);
            } else {
                console.error('Formulaire filterForm non trouvé !');
            }
        });
    });
    
    // Gestion des filtres rapides
    const quickFilterButtons = document.querySelectorAll('input[name="quick_filter"]');
    console.log('Filtres rapides trouvés:', quickFilterButtons.length);
    
    quickFilterButtons.forEach(button => {
        // Utiliser 'click' au lieu de 'change' pour une meilleure compatibilité
        button.addEventListener('click', function() {
            console.log('Clic sur filtre rapide:', this.value);
            // Vider les champs de dates personnalisées
            const dateDebut = document.getElementById('date_debut');
            const dateFin = document.getElementById('date_fin');
            if (dateDebut) dateDebut.value = '';
            if (dateFin) dateFin.value = '';
            
            // Ajouter un indicateur de chargement
            const form = document.getElementById('filterForm');
            if (form) {
                // Afficher un indicateur de chargement
                showLoadingIndicator();
                
                // Soumettre automatiquement le formulaire
                setTimeout(() => {
                    console.log('Soumission du formulaire pour filtre rapide:', this.value);
                    form.submit();
                }, 100);
            } else {
                console.error('Formulaire filterForm non trouvé !');
            }
        });
    });

    // Fonction de réinitialisation des filtres
    document.getElementById('resetFilters').addEventListener('click', function() {
        window.location.href = 'index.php?page=reparation_logs';
    });

    // Auto-soumission pour les champs select (non radio)
    const autoSubmitFields = ['view_mode', 'group_by', 'sort_by', 'sort_order'];
    autoSubmitFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field && field.type !== 'radio') {
            field.addEventListener('change', function() {
                console.log('Auto-soumission pour le champ:', fieldName, 'valeur:', this.value);
                showLoadingIndicator();
                setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 100);
            });
        }
    });

    // Recherche en temps réel améliorée
    let searchTimeout;
    const searchInput = document.getElementById('search_term');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Recherche côté client pour l'affichage actuel
                const searchTerm = this.value.toLowerCase();
                
                if (document.getElementById('timeline-tab-pane').classList.contains('active')) {
                    // Recherche dans la timeline
                    const timelineItems = document.querySelectorAll('.timeline-item');
                    let visibleCount = 0;
                    
                    timelineItems.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        const isVisible = text.includes(searchTerm);
                        item.style.display = isVisible ? '' : 'none';
                        if (isVisible) visibleCount++;
                    });
                    
                    // Mettre à jour le compteur si présent
                    updateResultsCount(visibleCount);
                } else if (document.getElementById('employees-tab-pane').classList.contains('active')) {
                    // Recherche dans les réparations par employé
                    const repairRows = document.querySelectorAll('.employee-card tbody tr:not(.inactive-time-row)');
                    repairRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                    
                    // Masquer les cartes d'employés qui n'ont aucune réparation visible
                    const employeeCards = document.querySelectorAll('.employee-card');
                    employeeCards.forEach(card => {
                        const visibleRows = card.querySelectorAll('tbody tr:not(.inactive-time-row):not([style*="display: none"])');
                        card.style.display = visibleRows.length > 0 ? '' : 'none';
                    });
                }
            }, 300); // Délai de 300ms
        });
        
        // Soumission du formulaire sur Enter pour recherche côté serveur
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('filterForm').submit();
            }
        });
    }

    // Fonction pour mettre à jour le compteur de résultats
    function updateResultsCount(count) {
        const badge = document.querySelector('#timeline-tab .badge');
        if (badge) {
            badge.textContent = count.toLocaleString();
        }
    }

    // Synchronisation des onglets avec le mode d'affichage
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-bs-target');
            const viewMode = targetTab === '#timeline-tab-pane' ? 'timeline' : 'employees';
            
            // Mettre à jour le champ caché
            const viewModeField = document.getElementById('view_mode');
            if (viewModeField && viewModeField.value !== viewMode) {
                viewModeField.value = viewMode;
                // Soumettre automatiquement pour actualiser les données
                document.getElementById('filterForm').submit();
            }
        });
    });

    // Animation d'apparition pour les nouveaux éléments
    function animateNewElements() {
        const newElements = document.querySelectorAll('.timeline-item, .group-card, .employee-card');
        newElements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 50); // Décalage de 50ms entre chaque élément
        });
    }

    // Appliquer l'animation au chargement
    animateNewElements();

    // Gestion des filtres avancés
    const advancedFiltersToggle = document.querySelector('[data-bs-target="#advancedFilters"]');
    if (advancedFiltersToggle) {
        advancedFiltersToggle.addEventListener('click', function() {
            const icon = this.querySelector('i');
            setTimeout(() => {
                if (document.getElementById('advancedFilters').classList.contains('show')) {
                    icon.className = 'fas fa-cog me-1';
                } else {
                    icon.className = 'fas fa-cog me-1';
                }
            }, 350);
        });
    }

    // Fonction pour appliquer rapidement un filtre de date et soumettre le formulaire
    function applyQuickDateFilter(periode) {
        // Cette fonction est conservée pour la compatibilité mais n'est plus utilisée
        // Les filtres rapides sont maintenant gérés par les boutons radio
        console.warn('applyQuickDateFilter est dépréciée, utilisez les boutons radio');
    }

    // Créer le bouton de toggle
    const toggleButton = document.createElement('button');
    toggleButton.className = 'dark-mode-toggle';
    toggleButton.innerHTML = '<i class="fas fa-moon"></i>';
    document.body.appendChild(toggleButton);
    
    // Vérifier si le mode sombre est déjà activé
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
        toggleButton.innerHTML = '<i class="fas fa-sun"></i>';
    }
    
    // Ajouter l'événement au bouton
    toggleButton.addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
        
        // Mettre à jour l'icône
        if (document.body.classList.contains('dark-mode')) {
            toggleButton.innerHTML = '<i class="fas fa-sun"></i>';
            localStorage.setItem('darkMode', 'enabled');
        } else {
            toggleButton.innerHTML = '<i class="fas fa-moon"></i>';
            localStorage.setItem('darkMode', 'disabled');
        }
    });
});

// Test simple pour vérifier que JavaScript fonctionne
console.log('JavaScript chargé dans reparation_logs.php');

// Ajouter un gestionnaire d'événements alternatif au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM chargé, ajout des gestionnaires d\'événements');
    
    // Ajouter des gestionnaires d'événements pour toutes les cartes d'employés cliquables
    const employeeCards = document.querySelectorAll('.employee-card-clickable');
    console.log('Cartes d\'employés trouvées:', employeeCards.length);
    
    employeeCards.forEach((card, index) => {
        console.log(`Carte ${index}:`, card);
        
        // Ajouter un gestionnaire d'événements de clic
        card.addEventListener('click', function(e) {
            // Vérifier si le clic provient d'un lien interne (avec stopPropagation)
            if (e.target.closest('a[onclick*="stopPropagation"]')) {
                console.log('Clic sur un lien interne, ignorer');
                return;
            }
            
            console.log('Clic détecté sur la carte employé');
            
            const employeeId = this.getAttribute('data-employee-id');
            const employeeName = this.getAttribute('data-employee-name');
            
            console.log('Données extraites:', { employeeId, employeeName });
            
            if (employeeId && employeeName) {
                openEmployeeTimeline(employeeId, employeeName);
            } else {
                alert('Erreur: Données employé manquantes');
            }
        });
        
        // Ajouter un gestionnaire pour le survol pour le débogage
        card.addEventListener('mouseenter', function() {
            console.log(`Survol de la carte ${index}`);
        });
    });
});

// Fonction pour ouvrir la timeline d'un employé
function openEmployeeTimeline(employeeId, employeeName) {
    console.log('openEmployeeTimeline appelée avec:', employeeId, employeeName);
    
    // Test simple d'abord
    if (!employeeId) {
        alert('Erreur: ID employé manquant');
        return;
    }
    
    // Vérifier que les éléments existent
    const modalBody = document.getElementById('employeeTimelineBody');
    const modalLabel = document.getElementById('employeeTimelineModalLabel');
    const modalElement = document.getElementById('employeeTimelineModal');
    
    if (!modalBody || !modalLabel || !modalElement) {
        console.error('Éléments du modal non trouvés:', {
            modalBody: !!modalBody,
            modalLabel: !!modalLabel,
            modalElement: !!modalElement
        });
        alert('Erreur: Éléments du modal non trouvés');
        return;
    }
    
    // Afficher un indicateur de chargement
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-3">Chargement de la timeline de ${employeeName}...</p>
        </div>
    `;
    
    // Mettre à jour le titre du modal
    modalLabel.textContent = `Timeline de ${employeeName}`;
    
    // Ouvrir le modal
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Charger les données via AJAX
    fetch(`ajax_handlers/get_employee_timeline.php?employee_id=${employeeId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayEmployeeTimeline(data);
            } else {
                throw new Error(data.message || 'Erreur lors du chargement des données');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erreur lors du chargement de la timeline: ${error.message}
                </div>
            `;
        });
}

// Fonction pour afficher la timeline dans le modal
function displayEmployeeTimeline(data) {
    const modalBody = document.getElementById('employeeTimelineBody');
    const { employee, timeline, stats } = data;
    
    let html = `
        <!-- Statistiques globales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h5>${stats.total_work_time}</h5>
                        <small>Temps de travail total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-pause fa-2x mb-2"></i>
                        <h5>${stats.total_inactive_time}</h5>
                        <small>Temps d'inactivité</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h5>${stats.completed_tasks}</h5>
                        <small>Tâches terminées</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-percentage fa-2x mb-2"></i>
                        <h5>${stats.efficiency_rate}%</h5>
                        <small>Taux d'efficacité</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Timeline -->
        <div class="timeline-container">
            <h5 class="mb-3">
                <i class="fas fa-history me-2"></i>
                Timeline détaillée (${timeline.length} activités)
            </h5>
    `;
    
    if (timeline.length === 0) {
        html += `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Aucune activité trouvée pour cet employé.
            </div>
        `;
    } else {
        timeline.forEach((item, index) => {
            const isTask = item.type === 'task';
            const iconClass = isTask ? 'fa-tasks' : 'fa-tools';
            const badgeClass = isTask ? 'bg-success' : 'bg-primary';
            const typeLabel = isTask ? 'Tâche' : 'Réparation';
            
            // Afficher le temps d'inactivité avant cette activité (sauf pour la première)
            if (index > 0 && item.inactive_time) {
                const inactiveClass = item.inactive_duration_minutes > 60 ? 'text-danger' : 
                                    (item.inactive_duration_minutes > 30 ? 'text-warning' : 'text-info');
                
                html += `
                    <div class="timeline-item inactive-period mb-3">
                        <div class="timeline-marker bg-secondary">
                            <i class="fas fa-pause"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="card border-secondary">
                                <div class="card-body text-center py-2">
                                    <span class="${inactiveClass}">
                                        <i class="fas fa-clock me-1"></i>
                                        Pause de ${item.inactive_time}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            html += `
                <div class="timeline-item mb-3">
                    <div class="timeline-marker ${badgeClass}">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge ${badgeClass} me-2">${typeLabel} #${item.entity_id}</span>
                                    <strong>${item.title}</strong>
                                </div>
                                <div class="text-end">
                                    ${item.is_completed ? 
                                        `<span class="badge bg-success"><i class="fas fa-check me-1"></i>Terminé</span>` :
                                        `<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>En cours</span>`
                                    }
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">${item.description || 'Aucune description'}</p>
                                <div class="row">
                                    <div class="col-md-4">
                                        <small class="text-muted">Début:</small><br>
                                        <strong>${item.start_time}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Fin:</small><br>
                                        <strong>${item.end_time || 'En cours...'}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Durée:</small><br>
                                        <strong class="text-primary">${item.work_duration || 'En cours...'}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    html += '</div>';
    modalBody.innerHTML = html;
}
</script>

<!-- Modal Timeline Employé -->
<div class="modal fade" id="employeeTimelineModal" tabindex="-1" aria-labelledby="employeeTimelineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeTimelineModalLabel">
                    <i class="fas fa-user-clock me-2"></i>
                    Timeline Employé
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="employeeTimelineBody">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour la carte employé cliquable */
.employee-card-clickable {
    cursor: pointer !important;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    position: relative;
}

.employee-card-clickable::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(0, 123, 255, 0.1), rgba(0, 123, 255, 0.05));
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
    z-index: 1;
}

.employee-card-clickable:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.2) !important;
    border-color: rgba(0, 123, 255, 0.3);
}

.employee-card-clickable:hover::before {
    opacity: 1;
}

.employee-card-clickable:hover .timeline-indicator {
    opacity: 1;
    transform: translateX(5px);
}

.employee-card-clickable:hover .card-header {
    background: linear-gradient(135deg, var(--header-bg-color, #007bff), rgba(0, 123, 255, 0.8)) !important;
}

.timeline-indicator {
    opacity: 0.7;
    transition: all 0.3s ease;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

.timeline-indicator i {
    animation: pulse 2s infinite;
}

.timeline-indicator small {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@keyframes pulse {
    0% { opacity: 0.7; }
    50% { opacity: 1; }
    100% { opacity: 0.7; }
}

/* Indicateur visuel pour montrer que c'est cliquable */
.employee-card-clickable .card-header::after {
    content: "👆 Cliquer pour voir la timeline";
    position: absolute;
    top: 10px;
    right: 15px;
    background: rgba(255, 255, 255, 0.9);
    color: #333;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 2;
}

.employee-card-clickable:hover .card-header::after {
    opacity: 1;
    transform: translateY(0);
}

/* Styles pour la timeline dans le modal */
.timeline-container {
    position: relative;
    max-height: 60vh;
    overflow-y: auto;
    padding-right: 15px;
}

.timeline-item {
    position: relative;
    padding-left: 50px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 40px;
    bottom: -20px;
    width: 2px;
    background: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 10px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    z-index: 1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-content {
    margin-left: 10px;
}

.timeline-item.inactive-period .timeline-marker {
    width: 30px;
    height: 30px;
    font-size: 12px;
    top: 15px;
}

.timeline-item.inactive-period .timeline-content .card {
    background-color: #f8f9fa;
    border-style: dashed;
}

/* Animation pour les éléments de timeline */
.timeline-item {
    opacity: 0;
    transform: translateX(-20px);
    animation: slideInTimeline 0.5s ease forwards;
}

.timeline-item:nth-child(1) { animation-delay: 0.1s; }
.timeline-item:nth-child(2) { animation-delay: 0.2s; }
.timeline-item:nth-child(3) { animation-delay: 0.3s; }
.timeline-item:nth-child(4) { animation-delay: 0.4s; }
.timeline-item:nth-child(5) { animation-delay: 0.5s; }
.timeline-item:nth-child(n+6) { animation-delay: 0.6s; }

@keyframes slideInTimeline {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Responsive pour mobile */
@media (max-width: 768px) {
    .timeline-item {
        padding-left: 40px;
    }
    
    .timeline-marker {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }
    
    .timeline-item:not(:last-child)::before {
        left: 15px;
    }
}

/* Mode sombre pour la timeline */
.dark-mode .timeline-item:not(:last-child)::before {
    background: #495057;
}

.dark-mode .timeline-item.inactive-period .timeline-content .card {
    background-color: #343a40;
    border-color: #495057;
}
</style> 