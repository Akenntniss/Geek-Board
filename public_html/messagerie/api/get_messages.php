<?php
/**
 * API - Récupérer les messages d'une conversation
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de base de données
require_once '../../config/database.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

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

$conversation_id = (int)$_GET['conversation_id'];
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Inclure les fonctions
require_once '../includes/functions.php';

// Vérifier l'accès à la conversation
if (!user_has_conversation_access($_SESSION['user_id'], $conversation_id)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès refusé à cette conversation']);
    exit;
}

// Version simplifiée pour le débogage - récupérer les messages sans les fonctionnalités complexes
try {
    global $shop_pdo;
    
    // Mettre à jour la date de dernière lecture
    update_last_read($conversation_id, $_SESSION['user_id']);
    
    // Requête simplifiée sans les fonctions JSON
    $query = "
        SELECT m.*, u.full_name AS sender_name
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = :conversation_id
        AND m.est_supprime = 0
        ORDER BY m.date_envoi DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->bindValue(':conversation_id', $conversation_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Traiter les résultats de base
    foreach ($messages as &$message) {
        $message['is_mine'] = $message['sender_id'] == $_SESSION['user_id'];
        $message['formatted_date'] = format_message_date($message['date_envoi']);
        $message['attachments'] = []; // Initialiser un tableau vide pour les pièces jointes
        $message['reactions'] = []; // Initialiser un tableau vide pour les réactions
    }
    
    // Inverser pour avoir du plus ancien au plus récent
    $messages = array_reverse($messages);
    
    // Récupérer les informations de la conversation avec une requête simplifiée
    $query = "
        SELECT c.*, u.full_name AS created_by_name, cp.role
        FROM conversations c
        LEFT JOIN users u ON c.created_by = u.id
        JOIN conversation_participants cp ON c.id = cp.conversation_id AND cp.user_id = :user_id
        WHERE c.id = :conversation_id
    ";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    $conversation_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conversation_info) {
        // Liste simplifiée des participants
        $query = "
            SELECT u.id, u.full_name
            FROM conversation_participants cp
            JOIN users u ON cp.user_id = u.id
            WHERE cp.conversation_id = :conversation_id
        ";
        
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([':conversation_id' => $conversation_id]);
        $conversation_info['participants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $conversation_info = [];
    }
    
    // Renvoyer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'messages' => $messages,
        'conversation' => $conversation_info,
        'offset' => $offset,
        'limit' => $limit,
        'total_count' => count($messages)
    ]);
    
} catch (Exception $e) {
    // Journaliser l'erreur
    log_error('Erreur dans get_messages.php', $e->getMessage() . ' - ' . $e->getTraceAsString());
    
    // Réponse d'erreur
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la récupération des messages: ' . $e->getMessage()
    ]);
}
exit; 