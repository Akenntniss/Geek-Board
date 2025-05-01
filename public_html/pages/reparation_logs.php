<?php
// Inclure la configuration de la base de données
require_once('config/database.php');
require_once('includes/functions.php');

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

// Filtres
$employe_id = isset($_GET['employe_id']) ? intval($_GET['employe_id']) : 0;
$reparation_id = isset($_GET['reparation_id']) ? intval($_GET['reparation_id']) : 0;
$action_type = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$heure_debut = isset($_GET['heure_debut']) ? $_GET['heure_debut'] : '';
$heure_fin = isset($_GET['heure_fin']) ? $_GET['heure_fin'] : '';
$search_term = isset($_GET['search_term']) ? trim($_GET['search_term']) : '';

// Construction de la requête SQL avec filtres
$sql = "
    SELECT rl.*, 
           r.type_appareil, r.modele, r.client_id, r.description_probleme as reparation_description,
           u.full_name as employe_nom,
           u.role as employe_role,
           CONCAT(c.nom, ' ', c.prenom) as client_nom
    FROM reparation_logs rl
    JOIN reparations r ON rl.reparation_id = r.id
    JOIN users u ON rl.employe_id = u.id
    JOIN clients c ON r.client_id = c.id
    WHERE 1=1
";
$params = [];

// Appliquer les filtres
if ($employe_id > 0) {
    $sql .= " AND rl.employe_id = ?";
    $params[] = $employe_id;
}
if ($reparation_id > 0) {
    $sql .= " AND rl.reparation_id = ?";
    $params[] = $reparation_id;
}
if (!empty($action_type)) {
    $sql .= " AND rl.action_type = ?";
    $params[] = $action_type;
}
if (!empty($date_debut)) {
    if (!empty($heure_debut)) {
        $sql .= " AND rl.date_action >= ?";
        $params[] = $date_debut . ' ' . $heure_debut . ':00';
    } else {
        $sql .= " AND DATE(rl.date_action) >= ?";
        $params[] = $date_debut;
    }
}
if (!empty($date_fin)) {
    if (!empty($heure_fin)) {
        $sql .= " AND rl.date_action <= ?";
        $params[] = $date_fin . ' ' . $heure_fin . ':59';
    } else {
        $sql .= " AND DATE(rl.date_action) <= ?";
        $params[] = $date_fin;
    }
}
if (!empty($search_term)) {
    $sql .= " AND (
        r.description_probleme LIKE ? 
        OR r.type_appareil LIKE ? 
        OR r.modele LIKE ? 
        OR c.nom LIKE ? 
        OR c.prenom LIKE ? 
        OR u.full_name LIKE ?
        OR rl.details LIKE ?
        OR rl.statut_avant LIKE ?
        OR rl.statut_apres LIKE ?
    )";
    $search_param = "%" . $search_term . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Tri par date (plus récent en premier)
$sql .= " ORDER BY rl.date_action DESC";

// Debug SQL query
if ($DEBUG) {
    error_log("Requête SQL logs: " . $sql);
    error_log("Paramètres: " . print_r($params, true));
}

// Obtenir les résultats
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($DEBUG) {
        error_log("Nombre de logs trouvés: " . count($logs));
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
    $stmt = $pdo->query("SELECT id, full_name as nom FROM users WHERE role = 'technicien' OR role = 'admin' ORDER BY full_name");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $employes = [];
}

// Regrouper les logs par réparation et par employé
$grouped_logs = [];
$employees = [];

// Collecter les données des logs pour la timeline et par employé
foreach ($logs as $log) {
    // Nous gardons seulement les informations nécessaires pour la timeline
    $grouped_logs[] = $log;
    
    // Grouper également par employé pour l'onglet "Réparations par employé"
    $repair_id = $log['reparation_id'];
    $employee_id = $log['employe_id'];
    $employee_name = $log['employe_nom'];
    
    // Enregistrer chaque employé unique
    if (!isset($employees[$employee_id])) {
        $employees[$employee_id] = [
            'id' => $employee_id,
            'name' => $employee_name,
            'role' => $log['employe_role'],
            'repairs' => []
        ];
    }
    
    // Grouper les logs par réparation pour chaque employé
    if (!isset($employees[$employee_id]['repairs'][$repair_id])) {
        $employees[$employee_id]['repairs'][$repair_id] = [
            'id' => $repair_id,
            'type_appareil' => $log['type_appareil'],
            'modele' => $log['modele'],
            'client_nom' => $log['client_nom'],
            'client_id' => $log['client_id'],
            'description' => $log['reparation_description'],
            'logs' => []
        ];
    }
    
    // Ajouter le log à la réparation correspondante
    $employees[$employee_id]['repairs'][$repair_id]['logs'][] = $log;
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
function get_action_color($action_type) {
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

// Fonction pour obtenir une icône en fonction du type d'action
function get_action_icon($action_type) {
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

// Fonction pour obtenir un libellé en fonction du type d'action
function get_action_label($action_type) {
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

    /* Effet de transition lors du changement de mode */
    body, .card, .timeline-content, .form-control, .form-select,
    .table, .nav-tabs, .timeline:before, .badge, .btn, .timeline-icon,
    .card-header, .employee-stats, .inactive-time-row, .inactive-time-badge,
    .repair-timeline-start i, .repair-timeline-end i, .repair-timeline-duration {
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

            <!-- Navigation par onglets -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <ul class="nav nav-tabs" id="logsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline-tab-pane" type="button" role="tab" aria-controls="timeline-tab-pane" aria-selected="true">
                            <i class="fas fa-stream me-2"></i>
                            Timeline des logs
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees-tab-pane" type="button" role="tab" aria-controls="employees-tab-pane" aria-selected="false">
                            <i class="fas fa-users me-2"></i>
                            Réparations par employé
                        </button>
                    </li>
                </ul>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="dateFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Filtrer par date
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dateFilterDropdown">
                            <li><a class="dropdown-item" href="#" onclick="applyQuickDateFilter('aujourd\'hui'); return false;">Aujourd'hui</a></li>
                            <li><a class="dropdown-item" href="#" onclick="applyQuickDateFilter('hier'); return false;">Hier</a></li>
                            <li><a class="dropdown-item" href="#" onclick="applyQuickDateFilter('semaine'); return false;">Cette semaine</a></li>
                            <li><a class="dropdown-item" href="#" onclick="applyQuickDateFilter('mois'); return false;">Ce mois</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="collapse" data-bs-target="#collapseFilters">Filtrage avancé...</a></li>
                        </ul>
                    </div>
                    <div class="search-box">
                        <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Recherche rapide..." style="width: 200px;">
                    </div>
                </div>
            </div>
            
            <div class="tab-content mt-3" id="logsTabContent">
                <!-- Onglet Timeline -->
                <div class="tab-pane fade show active" id="timeline-tab-pane" role="tabpanel" aria-labelledby="timeline-tab" tabindex="0">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">
                                <i class="fas fa-list me-2"></i>
                                <?php echo count($logs); ?> logs trouvés
                            </h5>
                            
                            <?php if (empty($logs)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucun log trouvé pour les critères sélectionnés.
                                </div>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($logs as $log): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-icon bg-<?php echo get_action_color($log['action_type']); ?>">
                                                <i class="fas fa-<?php echo get_action_icon($log['action_type']); ?>"></i>
                                            </div>
                                            <div class="timeline-content log-card">
                                                <span class="timeline-date">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?php echo format_datetime($log['date_action']); ?>
                                                </span>
                                                <h4 class="timeline-title">
                                                    <span class="log-badge bg-<?php echo get_action_color($log['action_type']); ?>">
                                                        <?php echo get_action_label($log['action_type']); ?>
                                                    </span>
                                                    <a href="index.php?page=details_reparation&id=<?php echo $log['reparation_id']; ?>" class="text-decoration-none ms-2">
                                                        Réparation #<?php echo $log['reparation_id']; ?>
                                                    </a>
                                                </h4>
                                                <div class="timeline-details">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p>
                                                                <strong><i class="fas fa-user me-1"></i> Employé:</strong>
                                                                <span class="badge <?php echo get_employe_background_color($log['employe_nom']); ?> px-2 py-1">
                                                                    <?php echo htmlspecialchars($log['employe_nom']); ?>
                                                                </span>
                                                            </p>
                                                            <p>
                                                                <strong><i class="fas fa-user-tie me-1"></i> Client:</strong>
                                                                <a href="#" class="text-decoration-none client-info-link" data-bs-toggle="modal" data-bs-target="#clientModal" data-client-id="<?php echo $log['client_id']; ?>">
                                                                    <?php echo htmlspecialchars($log['client_nom']); ?>
                                                                </a>
                                                            </p>
                                                            <p>
                                                                <strong><i class="fas fa-mobile-alt me-1"></i> Appareil:</strong>
                                                                <?php echo htmlspecialchars($log['type_appareil'] . ' ' . $log['modele']); ?>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <?php if ($log['statut_avant'] || $log['statut_apres']): ?>
                                                                <p>
                                                                    <strong><i class="fas fa-exchange-alt me-1"></i> Statut:</strong>
                                                                    <?php if ($log['statut_avant']): ?>
                                                                        <span class="log-badge bg-secondary"><?php echo htmlspecialchars($log['statut_avant']); ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if ($log['statut_avant'] && $log['statut_apres']): ?>
                                                                        <i class="fas fa-arrow-right mx-1"></i>
                                                                    <?php endif; ?>
                                                                    <?php if ($log['statut_apres']): ?>
                                                                        <span class="log-badge bg-<?php echo get_action_color($log['action_type']); ?>"><?php echo htmlspecialchars($log['statut_apres']); ?></span>
                                                                    <?php endif; ?>
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
                                Réparations par employé
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
                                            <div class="card employee-card shadow-sm">
                                                <div class="card-header <?php echo get_employe_background_color($employee['name']); ?>">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h5 class="mb-0 fw-bold">
                                                            <i class="fas fa-user me-2"></i>
                                                            <?php echo htmlspecialchars($employee['name']); ?>
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
                                                                    <th width="15%">Client / Appareil</th>
                                                                    <th width="25%">Description</th>
                                                                    <th width="20%">Démarrage</th>
                                                                    <th width="20%">Fin</th>
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
                                                                
                                                                // Inverser l'ordre pour afficher du plus ancien au plus récent
                                                                $temp_interventions = [];
                                                                foreach ($employee['repairs'] as $repair) {
                                                                    $sequences = get_all_repair_sequences($repair['logs']);
                                                                    
                                                                    // Pour chaque séquence (démarrage-fin), créer une intervention
                                                                    foreach ($sequences as $sequence) {
                                                                        $temp_interventions[] = [
                                                                            'repair' => $repair,
                                                                            'start' => $sequence['start'],
                                                                            'end' => $sequence['end']
                                                                        ];
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
                                                                    $repair = $intervention['repair'];
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
                                                                        <a href="index.php?page=details_reparation&id=<?php echo $repair['id']; ?>" class="btn btn-sm btn-outline-<?php echo get_employe_color($employee['name']); ?>">
                                                                            #<?php echo $repair['id']; ?>
                                                                        </a>
                                                                    </td>
                                                                    <td>
                                                                        <div>
                                                                            <a href="#" class="text-decoration-none client-info-link" data-bs-toggle="modal" data-bs-target="#clientModal" data-client-id="<?php echo $repair['client_id']; ?>">
                                                                                <i class="fas fa-user-tie me-1 text-muted"></i>
                                                                                <?php echo htmlspecialchars($repair['client_nom']); ?>
                                                                            </a>
                                                                        </div>
                                                                        <div class="text-muted small">
                                                                            <i class="fas fa-<?php echo $repair['type_appareil'] === 'Smartphone' ? 'mobile-alt' : 'laptop'; ?> me-1"></i>
                                                                            <?php echo htmlspecialchars($repair['type_appareil'] . ' ' . $repair['modele']); ?>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="text-wrap small">
                                                                            <?php echo !empty($repair['description']) ? htmlspecialchars($repair['description']) : '<span class="text-muted">Aucune description</span>'; ?>
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

    // Fonction pour appliquer rapidement un filtre de date et soumettre le formulaire
    function applyQuickDateFilter(periode) {
        // Remplir les valeurs du formulaire de filtrage avancé
        document.getElementById('periode').value = periode;
        setPeriode(periode);
        
        // Soumettre le formulaire
        document.querySelector('form[action="index.php"]').submit();
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
</script> 