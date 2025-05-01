<?php
/**
 * Fonctions pour la gestion des notifications
 */

/**
 * Crée une nouvelle notification
 * 
 * @param int $user_id ID de l'utilisateur destinataire
 * @param string $type Type de notification (reparation, commande, diagnostic, tache, autre)
 * @param string $message Message de la notification
 * @param int $reference_id ID de référence (ID de la réparation, commande, etc.)
 * @return bool Succès ou échec
 */
function create_notification($user_id, $type, $message, $reference_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, reference_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $type, $message, $reference_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la création d'une notification : " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les notifications non lues d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $limit Nombre maximum de notifications à récupérer
 * @return array Tableau des notifications
 */
function get_unread_notifications($user_id, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des notifications : " . $e->getMessage());
        return [];
    }
}

/**
 * Compte le nombre de notifications non lues d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur
 * @return int Nombre de notifications non lues
 */
function count_unread_notifications($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des notifications : " . $e->getMessage());
        return 0;
    }
}

/**
 * Marque une notification comme lue
 * 
 * @param int $notification_id ID de la notification
 * @return bool Succès ou échec
 */
function mark_notification_as_read($notification_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$notification_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de la notification : " . $e->getMessage());
        return false;
    }
}

/**
 * Marque toutes les notifications d'un utilisateur comme lues
 * 
 * @param int $user_id ID de l'utilisateur
 * @return bool Succès ou échec
 */
function mark_all_notifications_as_read($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors du marquage de toutes les notifications : " . $e->getMessage());
        return false;
    }
}