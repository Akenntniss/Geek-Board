<?php
// Inclure la configuration de la base de données
$config_path = file_exists('../config/database.php') ? '../config/database.php' : 'config/database.php';
$functions_path = file_exists('../includes/functions.php') ? '../includes/functions.php' : 'includes/functions.php';

require_once($config_path);
require_once($functions_path);

// Vérifier que l'utilisateur est connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier que l'utilisateur est un administrateur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

// Récupérer l'ID de l'employé
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

if ($employee_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID employé invalide']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Récupérer les informations de l'employé
    $stmt = $shop_pdo->prepare("SELECT id, full_name, role FROM users WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employé non trouvé']);
        exit;
    }
    
    // Récupérer tous les logs de réparations pour cet employé
    $repair_logs_sql = "
        SELECT 
            rl.id,
            rl.reparation_id as entity_id,
            rl.employe_id,
            rl.action_type,
            rl.statut_avant,
            rl.statut_apres,
            rl.date_action,
            rl.details,
            'repair' as log_source,
            r.type_appareil,
            r.modele,
            CONCAT(c.nom, ' ', c.prenom) as client_nom,
            r.description_probleme as reparation_description
        FROM reparation_logs rl
        JOIN reparations r ON rl.reparation_id = r.id
        JOIN clients c ON r.client_id = c.id
        WHERE rl.employe_id = ?
        ORDER BY rl.date_action ASC
    ";
    
    $stmt = $shop_pdo->prepare($repair_logs_sql);
    $stmt->execute([$employee_id]);
    $repair_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer tous les logs de tâches pour cet employé
    $task_logs_sql = "
        SELECT 
            tl.id,
            tl.task_id as entity_id,
            tl.user_id as employe_id,
            tl.action_type,
            tl.old_status as statut_avant,
            tl.new_status as statut_apres,
            tl.action_timestamp as date_action,
            tl.task_title,
            tl.details,
            'task' as log_source,
            '' as type_appareil,
            '' as modele,
            '' as client_nom,
            '' as reparation_description
        FROM Log_tasks tl
        WHERE tl.user_id = ?
        ORDER BY tl.action_timestamp ASC
    ";
    
    $stmt = $shop_pdo->prepare($task_logs_sql);
    $stmt->execute([$employee_id]);
    $task_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combiner et trier tous les logs par date
    $all_logs = array_merge($repair_logs, $task_logs);
    usort($all_logs, function($a, $b) {
        return strtotime($a['date_action']) - strtotime($b['date_action']);
    });
    
    // Analyser les séquences de travail (démarrage/fin) pour chaque entité
    $work_sequences = [];
    $current_sequences = [];
    
    foreach ($all_logs as $log) {
        $entity_key = $log['log_source'] . '_' . $log['entity_id'];
        
        // Actions de démarrage
        if (in_array($log['action_type'], ['demarrer', 'reprendre', 'attribuer'])) {
            if (!isset($current_sequences[$entity_key])) {
                $current_sequences[$entity_key] = [
                    'start' => $log,
                    'end' => null,
                    'entity_id' => $log['entity_id'],
                    'log_source' => $log['log_source'],
                    'title' => $log['log_source'] === 'task' ? $log['task_title'] : 
                              ($log['type_appareil'] . ' ' . $log['modele'] . ' - ' . $log['client_nom']),
                    'description' => $log['log_source'] === 'task' ? $log['details'] : $log['reparation_description']
                ];
            }
        }
        
        // Actions de fin
        if (in_array($log['action_type'], ['terminer', 'pause', 'suspendre'])) {
            if (isset($current_sequences[$entity_key])) {
                $current_sequences[$entity_key]['end'] = $log;
                $work_sequences[] = $current_sequences[$entity_key];
                unset($current_sequences[$entity_key]);
            }
        }
    }
    
    // Ajouter les séquences non terminées
    foreach ($current_sequences as $sequence) {
        $work_sequences[] = $sequence;
    }
    
    // Trier les séquences par date de début
    usort($work_sequences, function($a, $b) {
        return strtotime($a['start']['date_action']) - strtotime($b['start']['date_action']);
    });
    
    // Calculer les temps de travail et d'inactivité
    $timeline_data = [];
    $previous_end_time = null;
    
    foreach ($work_sequences as $sequence) {
        $start_time = strtotime($sequence['start']['date_action']);
        $end_time = $sequence['end'] ? strtotime($sequence['end']['date_action']) : null;
        
        // Calculer le temps d'inactivité depuis la fin de la séquence précédente
        $inactive_time = null;
        $inactive_duration_minutes = 0;
        
        if ($previous_end_time && $start_time) {
            $inactive_duration_minutes = ($start_time - $previous_end_time) / 60;
            if ($inactive_duration_minutes > 5) { // Seulement si plus de 5 minutes
                $hours = floor($inactive_duration_minutes / 60);
                $minutes = $inactive_duration_minutes % 60;
                
                if ($hours > 0) {
                    $inactive_time = $hours . 'h ' . round($minutes) . 'min';
                } else {
                    $inactive_time = round($minutes) . 'min';
                }
            }
        }
        
        // Calculer la durée de travail
        $work_duration = null;
        $work_duration_minutes = 0;
        
        if ($end_time) {
            $work_duration_minutes = ($end_time - $start_time) / 60;
            $hours = floor($work_duration_minutes / 60);
            $minutes = $work_duration_minutes % 60;
            
            if ($hours > 0) {
                $work_duration = $hours . 'h ' . round($minutes) . 'min';
            } else {
                $work_duration = round($minutes) . 'min';
            }
        }
        
        $timeline_data[] = [
            'type' => $sequence['log_source'],
            'entity_id' => $sequence['entity_id'],
            'title' => $sequence['title'],
            'description' => $sequence['description'],
            'start_time' => date('d/m/Y H:i', $start_time),
            'end_time' => $end_time ? date('d/m/Y H:i', $end_time) : null,
            'work_duration' => $work_duration,
            'work_duration_minutes' => round($work_duration_minutes),
            'inactive_time' => $inactive_time,
            'inactive_duration_minutes' => round($inactive_duration_minutes),
            'start_action' => $sequence['start']['action_type'],
            'end_action' => $sequence['end'] ? $sequence['end']['action_type'] : null,
            'is_completed' => $sequence['end'] !== null
        ];
        
        if ($end_time) {
            $previous_end_time = $end_time;
        }
    }
    
    // Calculer les statistiques globales
    $total_work_minutes = array_sum(array_column($timeline_data, 'work_duration_minutes'));
    $total_inactive_minutes = array_sum(array_column($timeline_data, 'inactive_duration_minutes'));
    $completed_tasks = count(array_filter($timeline_data, function($item) {
        return $item['is_completed'];
    }));
    $ongoing_tasks = count($timeline_data) - $completed_tasks;
    
    $stats = [
        'total_work_time' => formatDuration($total_work_minutes),
        'total_inactive_time' => formatDuration($total_inactive_minutes),
        'completed_tasks' => $completed_tasks,
        'ongoing_tasks' => $ongoing_tasks,
        'total_tasks' => count($timeline_data),
        'efficiency_rate' => $total_work_minutes > 0 ? 
            round(($total_work_minutes / ($total_work_minutes + $total_inactive_minutes)) * 100, 1) : 0
    ];
    
    echo json_encode([
        'success' => true,
        'employee' => $employee,
        'timeline' => $timeline_data,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Erreur dans get_employee_timeline.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

function formatDuration($minutes) {
    if ($minutes < 60) {
        return round($minutes) . 'min';
    }
    
    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    
    if ($remaining_minutes > 0) {
        return $hours . 'h ' . round($remaining_minutes) . 'min';
    } else {
        return $hours . 'h';
    }
}
?> 