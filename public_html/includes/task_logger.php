<?php
/**
 * Système de logging pour les actions sur les tâches
 */

/**
 * Enregistre une action effectuée sur une tâche dans la table Log_tasks
 * 
 * @param int $task_id ID de la tâche
 * @param string $action_type Type d'action (demarrer, terminer, pause, reprendre, modifier, creer, supprimer)
 * @param string $old_status Ancien statut de la tâche (optionnel)
 * @param string $new_status Nouveau statut de la tâche (optionnel)
 * @param string $details Détails supplémentaires (optionnel)
 * @return bool True si l'enregistrement réussit, False sinon
 */
function logTaskAction($task_id, $action_type, $old_status = null, $new_status = null, $details = null) {
    try {
        // Obtenir la connexion à la base de données
        $shop_pdo = getShopDBConnection();
        if (!$shop_pdo) {
            error_log("TASK_LOGGER: Impossible d'obtenir la connexion à la base de données");
            return false;
        }
        
        // Récupérer les informations de l'utilisateur connecté
        $user_id = $_SESSION['user_id'] ?? null;
        $user_name = null;
        
        if ($user_id) {
            try {
                $stmt = $shop_pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $user_name = $user ? $user['full_name'] : null;
            } catch (Exception $e) {
                error_log("TASK_LOGGER: Erreur lors de la récupération du nom d'utilisateur: " . $e->getMessage());
            }
        }
        
        // Récupérer le titre de la tâche
        $task_title = null;
        try {
            $stmt = $shop_pdo->prepare("SELECT titre FROM taches WHERE id = ?");
            $stmt->execute([$task_id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            $task_title = $task ? $task['titre'] : null;
        } catch (Exception $e) {
            error_log("TASK_LOGGER: Erreur lors de la récupération du titre de la tâche: " . $e->getMessage());
        }
        
        // Récupérer l'adresse IP et le user agent
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Préparer la requête d'insertion
        $sql = "
            INSERT INTO Log_tasks 
            (task_id, user_id, action_type, old_status, new_status, user_name, task_title, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $shop_pdo->prepare($sql);
        $result = $stmt->execute([
            $task_id,
            $user_id,
            $action_type,
            $old_status,
            $new_status,
            $user_name,
            $task_title,
            $details,
            $ip_address,
            $user_agent
        ]);
        
        if ($result) {
            // Log de succès
            $log_message = sprintf(
                "TASK_LOG: Action '%s' enregistrée pour la tâche #%d par utilisateur %s (ID: %s)",
                $action_type,
                $task_id,
                $user_name ?? 'Inconnu',
                $user_id ?? 'N/A'
            );
            error_log($log_message);
            
            return true;
        } else {
            error_log("TASK_LOGGER: Échec de l'insertion dans Log_tasks");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("TASK_LOGGER: Erreur lors de l'enregistrement du log: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère l'historique des actions pour une tâche spécifique
 * 
 * @param int $task_id ID de la tâche
 * @param int $limit Nombre maximum d'enregistrements à retourner (défaut: 50)
 * @return array Tableau des actions ou tableau vide en cas d'erreur
 */
function getTaskActionHistory($task_id, $limit = 50) {
    try {
        $shop_pdo = getShopDBConnection();
        if (!$shop_pdo) {
            return [];
        }
        
        $sql = "
            SELECT 
                id,
                action_type,
                old_status,
                new_status,
                action_timestamp,
                user_name,
                details,
                ip_address
            FROM Log_tasks 
            WHERE task_id = ? 
            ORDER BY action_timestamp DESC 
            LIMIT ?
        ";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([$task_id, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("TASK_LOGGER: Erreur lors de la récupération de l'historique: " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les statistiques d'actions pour un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param string $period Période (today, week, month, year)
 * @return array Statistiques des actions
 */
function getUserTaskStats($user_id, $period = 'today') {
    try {
        $shop_pdo = getShopDBConnection();
        if (!$shop_pdo) {
            return [];
        }
        
        // Définir la condition de date selon la période
        $date_condition = '';
        switch ($period) {
            case 'today':
                $date_condition = "AND DATE(action_timestamp) = CURDATE()";
                break;
            case 'week':
                $date_condition = "AND action_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $date_condition = "AND action_timestamp >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $date_condition = "AND action_timestamp >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
        
        $sql = "
            SELECT 
                action_type,
                COUNT(*) as count
            FROM Log_tasks 
            WHERE user_id = ? $date_condition
            GROUP BY action_type
            ORDER BY count DESC
        ";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("TASK_LOGGER: Erreur lors de la récupération des statistiques: " . $e->getMessage());
        return [];
    }
}

/**
 * Nettoie les anciens logs (pour maintenance)
 * 
 * @param int $days Nombre de jours à conserver (défaut: 90)
 * @return int Nombre d'enregistrements supprimés
 */
function cleanOldTaskLogs($days = 90) {
    try {
        $shop_pdo = getShopDBConnection();
        if (!$shop_pdo) {
            return 0;
        }
        
        $sql = "DELETE FROM Log_tasks WHERE action_timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([$days]);
        
        return $stmt->rowCount();
        
    } catch (Exception $e) {
        error_log("TASK_LOGGER: Erreur lors du nettoyage des logs: " . $e->getMessage());
        return 0;
    }
}
?> 