<?php
/**
 * API - Ajouter ou supprimer une réaction à un message
 * 
 * Cette API permet aux utilisateurs d'ajouter ou de supprimer
 * des réactions emoji aux messages.
 */

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer le corps de la requête
$input = json_decode(file_get_contents('php://input'), true);

// Vérifier les données
if (!isset($input['message_id']) || !is_numeric($input['message_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de message manquant ou invalide']);
    exit;
}

if (!isset($input['reaction']) || empty($input['reaction'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Réaction manquante ou invalide']);
    exit;
}

$message_id = (int)$input['message_id'];
$reaction = $input['reaction'];

// Limiter la longueur de la réaction
if (mb_strlen($reaction) > 5) {
    $reaction = mb_substr($reaction, 0, 5);
}

// Inclure les fonctions
require_once '../includes/functions.php';

// Vérifier si l'utilisateur a accès au message
$message_info = get_message_info($message_id);

if (!$message_info) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['success' => false, 'message' => 'Message introuvable']);
    exit;
}

if (!user_has_conversation_access($_SESSION['user_id'], $message_info['conversation_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès refusé à ce message']);
    exit;
}

try {
    global $pdo;
    
    // Vérifier si l'utilisateur a déjà réagi avec cet emoji
    $query = "
        SELECT id 
        FROM message_reactions 
        WHERE message_id = :message_id 
        AND user_id = :user_id 
        AND reaction = :reaction
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':message_id' => $message_id,
        ':user_id' => $_SESSION['user_id'],
        ':reaction' => $reaction
    ]);
    
    $existing_reaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_reaction) {
        // Si la réaction existe déjà, la supprimer
        $query = "
            DELETE FROM message_reactions 
            WHERE id = :id
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':id' => $existing_reaction['id']]);
        
        $action = 'removed';
    } else {
        // Sinon, ajouter la réaction
        $query = "
            INSERT INTO message_reactions (message_id, user_id, reaction, date_reaction) 
            VALUES (:message_id, :user_id, :reaction, NOW())
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':message_id' => $message_id,
            ':user_id' => $_SESSION['user_id'],
            ':reaction' => $reaction
        ]);
        
        $action = 'added';
    }
    
    // Renvoyer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'action' => $action,
        'message' => $action === 'added' ? 'Réaction ajoutée' : 'Réaction supprimée'
    ]);
    
} catch (PDOException $e) {
    log_error('Erreur lors de la gestion de la réaction', $e->getMessage());
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la gestion de la réaction']);
    exit;
} 