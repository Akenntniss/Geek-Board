<?php
session_start();
require_once '../includes/config.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupération et validation des données JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['conversation_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de conversation manquant']);
    exit;
}

$conversation_id = intval($data['conversation_id']);
$user_id = $_SESSION['user_id'];

try {
    // Vérifier si l'utilisateur a accès à cette conversation
    $stmt = $pdo->prepare("
        SELECT role 
        FROM conversation_participants 
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$conversation_id, $user_id]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participant) {
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
        exit;
    }

    // Vérifier si l'utilisateur est admin ou créateur
    if ($participant['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les droits pour supprimer cette conversation']);
        exit;
    }

    // Supprimer les notifications associées
    $stmt = $pdo->prepare("
        DELETE FROM notifications_messages 
        WHERE conversation_id = ?
    ");
    $stmt->execute([$conversation_id]);

    // Supprimer les confirmations de lecture des annonces
    $stmt = $pdo->prepare("
        DELETE la 
        FROM lecture_annonces la
        JOIN messages m ON la.message_id = m.id
        WHERE m.conversation_id = ?
    ");
    $stmt->execute([$conversation_id]);

    // Supprimer les messages
    $stmt = $pdo->prepare("
        DELETE FROM messages 
        WHERE conversation_id = ?
    ");
    $stmt->execute([$conversation_id]);

    // Supprimer les participants
    $stmt = $pdo->prepare("
        DELETE FROM conversation_participants 
        WHERE conversation_id = ?
    ");
    $stmt->execute([$conversation_id]);

    // Supprimer la conversation
    $stmt = $pdo->prepare("
        DELETE FROM conversations 
        WHERE id = ?
    ");
    $stmt->execute([$conversation_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Erreur SQL (delete_conversation.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
} 