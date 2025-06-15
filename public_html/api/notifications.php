<?php
/**
 * API pour le système de notifications
 */

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialiser la connexion à la base de données boutique
$shop_pdo = getShopDBConnection();

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non connecté'
    ]);
    exit;
}

// Récupérer le temps de dernière vérification
$last_check = isset($_GET['last_check']) ? $_GET['last_check'] : null;

// Valider le format de la date de dernière vérification
if ($last_check) {
    try {
        $date = new DateTime($last_check);
        $last_check = $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        $last_check = date('Y-m-d H:i:s', strtotime('-1 day'));
    }
} else {
    $last_check = date('Y-m-d H:i:s', strtotime('-1 day'));
}

// Traiter les différentes routes de l'API
$action = isset($_GET['action']) ? $_GET['action'] : 'get';

switch ($action) {
    case 'get':
        // Récupérer les nouvelles notifications
        getNewNotifications($shop_pdo, $last_check);
        break;
        
    case 'mark_read':
        // Marquer une notification comme lue
        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        markNotificationAsRead($shop_pdo, $notification_id);
        break;
        
    case 'mark_all_read':
        // Marquer toutes les notifications comme lues
        markAllNotificationsAsRead($shop_pdo);
        break;
        
    case 'count':
        // Obtenir le nombre de notifications non lues
        getUnreadCount($shop_pdo);
        break;
        
    default:
        // Action inconnue
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Action inconnue'
        ]);
        break;
}

/**
 * Récupère les nouvelles notifications depuis la dernière vérification
 * @param PDO $shop_pdo Instance PDO de connexion à la base de données
 * @param string $last_check Date de dernière vérification
 */
function getNewNotifications($shop_pdo, $last_check) {
    $user_id = $_SESSION['user_id'];
    $notifications = [];
    
    try {
        // Récupérer les notifications de l'utilisateur qui n'ont pas été vues
        $stmt = $shop_pdo->prepare("
            SELECT n.*, nt.icon, nt.type
            FROM notifications n
            JOIN notification_types nt ON n.type_id = nt.id
            WHERE (n.user_id = ? OR n.user_id IS NULL) 
            AND n.created_at > ?
            AND (n.seen = 0 OR n.seen IS NULL)
            ORDER BY n.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id, $last_check]);
        $db_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les notifications pour le client
        foreach ($db_notifications as $notification) {
            $notifications[] = [
                'id' => $notification['id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'type' => $notification['type'],
                'icon' => $notification['icon'],
                'link' => $notification['link'],
                'timestamp' => $notification['created_at'],
                'isImportant' => $notification['is_important'] == 1
            ];
        }
        
        // Récupérer les notifications système pour tous les utilisateurs
        $stmt = $shop_pdo->prepare("
            SELECT n.*, nt.icon, nt.type
            FROM notifications n
            JOIN notification_types nt ON n.type_id = nt.id
            WHERE n.user_id IS NULL 
            AND n.created_at > ?
            ORDER BY n.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$last_check]);
        $system_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ajouter les notifications système si elles ne sont pas déjà incluses
        foreach ($system_notifications as $notification) {
            $already_included = false;
            foreach ($notifications as $n) {
                if ($n['id'] == $notification['id']) {
                    $already_included = true;
                    break;
                }
            }
            
            if (!$already_included) {
                $notifications[] = [
                    'id' => $notification['id'],
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'type' => $notification['type'],
                    'icon' => $notification['icon'],
                    'link' => $notification['link'],
                    'timestamp' => $notification['created_at'],
                    'isImportant' => $notification['is_important'] == 1
                ];
            }
        }
        
        // Vérifier les notifications de réparation
        $notifications = array_merge($notifications, getRepairNotifications($shop_pdo, $last_check));
        
        // Vérifier les notifications de tâches
        $notifications = array_merge($notifications, getTaskNotifications($shop_pdo, $last_check));
        
        // Vérifier les notifications de messages
        $notifications = array_merge($notifications, getMessageNotifications($shop_pdo, $last_check));
        
        // Trier les notifications par date (plus récentes en premier)
        usort($notifications, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Retourner les notifications
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
    } catch (PDOException $e) {
        // En cas d'erreur
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la récupération des notifications: ' . $e->getMessage()
        ]);
    }
}

/**
 * Récupère les notifications liées aux réparations
 * @param PDO $shop_pdo Instance PDO de connexion à la base de données
 * @param string $last_check Date de dernière vérification
 * @return array Liste des notifications de réparation
 */
function getRepairNotifications($shop_pdo, $last_check) {
    $notifications = [];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Réparations modifiées depuis la dernière vérification
        // Pour les techniciens, seulement leurs réparations assignées
        // Pour les admins, toutes les réparations
        if ($_SESSION['role'] === 'admin') {
            $stmt = $shop_pdo->prepare("
                SELECT r.id, r.statut, r.marque, r.modele, r.type_appareil, 
                       c.nom AS client_nom, c.prenom AS client_prenom,
                       r.date_modification
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                WHERE r.date_modification > ?
                ORDER BY r.date_modification DESC
                LIMIT 10
            ");
            $stmt->execute([$last_check]);
        } else {
            $stmt = $shop_pdo->prepare("
                SELECT r.id, r.statut, r.marque, r.modele, r.type_appareil, 
                       c.nom AS client_nom, c.prenom AS client_prenom,
                       r.date_modification
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                WHERE r.employe_id = ? AND r.date_modification > ?
                ORDER BY r.date_modification DESC
                LIMIT 10
            ");
            $stmt->execute([$user_id, $last_check]);
        }
        
        $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($repairs as $repair) {
            $title = 'Réparation mise à jour';
            $message = 'La réparation ' . htmlspecialchars($repair['type_appareil'] . ' ' . $repair['marque'] . ' ' . $repair['modele']) . 
                      ' de ' . htmlspecialchars($repair['client_nom'] . ' ' . $repair['client_prenom']) . 
                      ' est passée au statut "' . htmlspecialchars($repair['statut']) . '"';
            
            $notifications[] = [
                'id' => 'repair_' . $repair['id'] . '_' . strtotime($repair['date_modification']),
                'title' => $title,
                'message' => $message,
                'type' => 'repair',
                'icon' => 'fa-tools',
                'link' => 'index.php?page=details_reparation&id=' . $repair['id'],
                'timestamp' => $repair['date_modification'],
                'isImportant' => false
            ];
        }
        
        return $notifications;
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération des notifications de réparation: ' . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les notifications liées aux tâches
 * @param PDO $shop_pdo Instance PDO de connexion à la base de données
 * @param string $last_check Date de dernière vérification
 * @return array Liste des notifications de tâches
 */
function getTaskNotifications($shop_pdo, $last_check) {
    $notifications = [];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Tâches assignées à l'utilisateur ou créées depuis la dernière vérification
        $stmt = $shop_pdo->prepare("
            SELECT t.id, t.titre, t.description, t.priorite, t.statut, t.date_creation, t.date_echeance,
                   u.full_name AS assigne_nom
            FROM taches t
            LEFT JOIN users u ON t.assigne_a = u.id
            WHERE (t.assigne_a = ? OR t.cree_par = ?)
            AND (t.date_creation > ? OR t.date_modification > ?)
            ORDER BY t.date_creation DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id, $user_id, $last_check, $last_check]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tasks as $task) {
            $title = 'Nouvelle tâche';
            if ($task['date_creation'] <= $last_check) {
                $title = 'Tâche mise à jour';
            }
            
            $message = htmlspecialchars($task['titre']);
            if ($task['priorite'] == 'haute') {
                $message .= ' (Priorité haute)';
            }
            
            $notifications[] = [
                'id' => 'task_' . $task['id'] . '_' . strtotime($task['date_creation']),
                'title' => $title,
                'message' => $message,
                'type' => 'task',
                'icon' => 'fa-tasks',
                'link' => 'index.php?page=modifier_tache&id=' . $task['id'],
                'timestamp' => $task['date_creation'],
                'isImportant' => $task['priorite'] == 'haute'
            ];
        }
        
        // Vérifier les tâches qui arrivent à échéance
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $stmt = $shop_pdo->prepare("
            SELECT t.id, t.titre, t.priorite, t.date_echeance
            FROM taches t
            WHERE t.assigne_a = ?
            AND t.statut != 'terminee'
            AND t.date_echeance BETWEEN ? AND ?
            LIMIT 5
        ");
        $stmt->execute([$user_id, $today, $tomorrow]);
        $due_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($due_tasks as $task) {
            $title = 'Tâche à échéance';
            $message = 'La tâche "' . htmlspecialchars($task['titre']) . '" doit être terminée aujourd\'hui';
            
            $notifications[] = [
                'id' => 'task_due_' . $task['id'] . '_' . strtotime($today),
                'title' => $title,
                'message' => $message,
                'type' => 'warning',
                'icon' => 'fa-clock',
                'link' => 'index.php?page=modifier_tache&id=' . $task['id'],
                'timestamp' => date('Y-m-d H:i:s'),
                'isImportant' => true
            ];
        }
        
        return $notifications;
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération des notifications de tâches: ' . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les notifications liées aux messages
 * @param PDO $shop_pdo Instance PDO de connexion à la base de données
 * @param string $last_check Date de dernière vérification
 * @return array Liste des notifications de messages
 */
function getMessageNotifications($shop_pdo, $last_check) {
    $notifications = [];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Nouveaux messages non lus
        $stmt = $shop_pdo->prepare("
            SELECT m.id, m.contenu, m.date_creation,
                   c.id as conversation_id,
                   u.full_name as expediteur_nom
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            JOIN conversation_participants cp ON c.id = cp.conversation_id
            JOIN users u ON m.expediteur_id = u.id
            LEFT JOIN message_reads mr ON m.id = mr.message_id AND mr.user_id = ?
            WHERE cp.user_id = ?
            AND m.expediteur_id != ?
            AND m.date_creation > ?
            AND mr.id IS NULL
            ORDER BY m.date_creation DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $last_check]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($messages as $message) {
            $contenu = strip_tags($message['contenu']);
            if (strlen($contenu) > 50) {
                $contenu = substr($contenu, 0, 47) . '...';
            }
            
            $notifications[] = [
                'id' => 'message_' . $message['id'],
                'title' => 'Message de ' . htmlspecialchars($message['expediteur_nom']),
                'message' => $contenu,
                'type' => 'message',
                'icon' => 'fa-envelope',
                'link' => 'index.php?page=messagerie&conversation=' . $message['conversation_id'],
                'timestamp' => $message['date_creation'],
                'isImportant' => false
            ];
        }
        
        return $notifications;
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération des notifications de messages: ' . $e->getMessage());
        return [];
    }
}

/**
 * Marque une notification comme lue
 * @param PDO $shop_pdo Instance PDO de connexion à la base de données
 * @param int $notification_id ID de la notification
 */
function markNotificationAsRead($shop_pdo, $notification_id) {
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $shop_pdo->prepare("
            UPDATE notifications 
            SET seen = 1
            WHERE id = ? AND (user_id = ? OR user_id IS NULL)
        ");
        $stmt->execute([$notification_id, $user_id]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour de la notification: ' . $e->getMessage()
        ]);
    }
}

/**
 * Marque toutes les notifications de l'utilisateur comme lues
 * @param PDO $shop_pdo Instance PDO de connexion à la base de données
 */
function markAllNotificationsAsRead($shop_pdo) {
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $shop_pdo->prepare("
            UPDATE notifications 
            SET seen = 1
            WHERE user_id = ? OR user_id IS NULL
        ");
        $stmt->execute([$user_id]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour des notifications: ' . $e->getMessage()
        ]);
    }
}

/**
 * Récupère le nombre de notifications non lues
 * @param PDO $shop_pdo Instance PDO de connexion à la base de données
 */
function getUnreadCount($shop_pdo) {
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as count
            FROM notifications
            WHERE (user_id = ? OR user_id IS NULL)
            AND seen = 0
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'count' => $result['count']
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la récupération du nombre de notifications: ' . $e->getMessage()
        ]);
    }
} 