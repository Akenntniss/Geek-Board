<?php
/**
 * Fonctions de gestion des notifications
 * Ce fichier contient toutes les fonctions liées à la gestion des notifications
 */

/**
 * Définit un message flash qui sera affiché sur la page suivante
 * @param string $text Le texte du message
 * @param string $title Le titre du message (par défaut "Information")
 * @param string $type Le type de message (success, warning, danger, info)
 */
function set_flash_message($text, $title = 'Information', $type = 'success') {
    $_SESSION['flash_message'] = [
        'text' => $text,
        'title' => $title,
        'type' => $type
    ];
}

/**
 * Envoie une notification à l'utilisateur
 * @param int $user_id ID de l'utilisateur cible
 * @param string $title Titre de la notification
 * @param string $message Contenu de la notification
 * @param string $link Lien optionnel
 * @param string $type Type de notification (info, success, warning, danger)
 * @return bool Succès ou échec
 */
function send_notification($user_id, $title, $message, $link = null, $type = 'info') {
    $shop_pdo = getShopDBConnection();
    
    try {
        $sql = "INSERT INTO notifications (user_id, title, message, link, type, created_at) 
                VALUES (:user_id, :title, :message, :link, :type, NOW())";
        
$stmt = $shop_pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':title' => $title,
            ':message' => $message,
            ':link' => $link,
            ':type' => $type
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur lors de l'envoi de la notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les notifications non lues d'un utilisateur
 * @param int $user_id ID de l'utilisateur
 * @param int $limit Nombre maximum de notifications à récupérer
 * @return array Tableau de notifications
 */
function get_unread_notifications($user_id, $limit = 10) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $sql = "SELECT id, title, message, link, type, created_at, is_read 
                FROM notifications 
                WHERE user_id = :user_id AND is_read = 0
                ORDER BY created_at DESC
                LIMIT :limit";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les dates pour l'affichage
        foreach ($notifications as &$notification) {
            $notification['time_ago'] = format_time_ago(strtotime($notification['created_at']));
        }
        
        return $notifications;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Marque une notification comme lue
 * @param int $notification_id ID de la notification
 * @param int $user_id ID de l'utilisateur (pour vérification)
 * @return bool Succès ou échec
 */
function mark_notification_as_read($notification_id, $user_id) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $sql = "UPDATE notifications 
                SET is_read = 1 
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([
            ':id' => $notification_id,
            ':user_id' => $user_id
        ]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de la notification comme lue: " . $e->getMessage());
        return false;
    }
}

/**
 * Marque toutes les notifications d'un utilisateur comme lues
 * @param int $user_id ID de l'utilisateur
 * @return bool Succès ou échec
 */
function mark_all_notifications_as_read($user_id) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $sql = "UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de toutes les notifications comme lues: " . $e->getMessage());
        return false;
    }
}

/**
 * Formate un timestamp en texte indiquant le temps écoulé
 * @param int $timestamp Timestamp à formater
 * @return string Texte formaté (ex: "il y a 5 minutes")
 */
function format_time_ago($timestamp) {
    $current_time = time();
    $time_difference = $current_time - $timestamp;
    
    if ($time_difference < 60) {
        return "À l'instant";
    } elseif ($time_difference < 3600) {
        $minutes = floor($time_difference / 60);
        return "Il y a " . $minutes . " minute" . ($minutes > 1 ? "s" : "");
    } elseif ($time_difference < 86400) {
        $hours = floor($time_difference / 3600);
        return "Il y a " . $hours . " heure" . ($hours > 1 ? "s" : "");
    } elseif ($time_difference < 604800) {
        $days = floor($time_difference / 86400);
        return "Il y a " . $days . " jour" . ($days > 1 ? "s" : "");
    } elseif ($time_difference < 2592000) {
        $weeks = floor($time_difference / 604800);
        return "Il y a " . $weeks . " semaine" . ($weeks > 1 ? "s" : "");
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Compte le nombre de notifications non lues pour un utilisateur
 * @param int $user_id ID de l'utilisateur
 * @return int Nombre de notifications non lues
 */
function count_unread_notifications($user_id) {
    $shop_pdo = getShopDBConnection();
    
    try {
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des notifications non lues: " . $e->getMessage());
        return 0;
    }
} 