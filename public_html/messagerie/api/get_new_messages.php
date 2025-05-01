<?php
/**
 * API - Récupérer les nouveaux messages d'une conversation
 */

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier les paramètres
if (!isset($_GET['conversation_id']) || !is_numeric($_GET['conversation_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de conversation manquant ou invalide']);
    exit;
}

if (!isset($_GET['last_id']) || !is_numeric($_GET['last_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID du dernier message manquant ou invalide']);
    exit;
}

$conversation_id = (int)$_GET['conversation_id'];
$last_id = (int)$_GET['last_id'];

// Inclure les fonctions
require_once '../includes/functions.php';

// Vérifier l'accès à la conversation
if (!user_has_conversation_access($_SESSION['user_id'], $conversation_id)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès refusé à cette conversation']);
    exit;
}

// Récupérer les nouveaux messages
try {
    global $pdo;
    
    $query = "
        SELECT m.*,
               u.full_name AS sender_name,
               (
                   SELECT JSON_ARRAYAGG(
                       JSON_OBJECT(
                           'id', ma.id,
                           'file_path', ma.file_path,
                           'file_name', ma.file_name,
                           'file_type', ma.file_type,
                           'file_size', ma.file_size,
                           'thumbnail_path', ma.thumbnail_path,
                           'est_image', ma.est_image
                       )
                   )
                   FROM message_attachments ma
                   WHERE ma.message_id = m.id
               ) AS attachments
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = :conversation_id
        AND m.id > :last_id
        AND m.est_supprime = 0
        ORDER BY m.date_envoi ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':last_id' => $last_id
    ]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Traiter les messages (ajouter des informations supplémentaires)
    foreach ($messages as &$message) {
        $message['is_mine'] = ($message['sender_id'] == $_SESSION['user_id']);
        
        if ($message['attachments']) {
            $message['attachments'] = json_decode($message['attachments'], true);
        } else {
            $message['attachments'] = [];
        }
        
        // Formater la date pour l'affichage
        $message['formatted_date'] = format_message_date($message['date_envoi']);
        
        // Marquer le message comme lu
        if (!$message['is_mine']) {
            mark_message_as_read($message['id'], $_SESSION['user_id']);
        }
    }
    
    // Mettre à jour la date de dernière lecture
    update_last_read($conversation_id, $_SESSION['user_id']);
    
    // Renvoyer les messages
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages)
    ]);
} catch (PDOException $e) {
    log_error('Erreur lors de la récupération des nouveaux messages', $e->getMessage());
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des nouveaux messages']);
    exit;
}
?> 