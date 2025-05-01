<?php
session_start();
require_once '../includes/config.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification de l'ID de la conversation
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de conversation manquant']);
    exit;
}

$conversation_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

try {
    // Vérifier si l'utilisateur a accès à cette conversation
    $stmt = $pdo->prepare("
        SELECT role 
        FROM conversation_participants 
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$conversation_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
        exit;
    }

    // Récupérer les détails de la conversation
    $stmt = $pdo->prepare("
        SELECT c.*, 
               cp.role as user_role,
               cp.date_derniere_lecture
        FROM conversations c
        JOIN conversation_participants cp ON c.id = cp.conversation_id
        WHERE c.id = ? AND cp.user_id = ?
    ");
    $stmt->execute([$conversation_id, $user_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conversation) {
        echo json_encode(['success' => false, 'message' => 'Conversation non trouvée']);
        exit;
    }

    // Récupérer les messages
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u.nom as sender_nom,
               u.prenom as sender_prenom,
               (SELECT COUNT(*) FROM lecture_annonces WHERE message_id = m.id) as lectures
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ?
        ORDER BY m.date_envoi ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Marquer les messages comme lus
    $stmt = $pdo->prepare("
        UPDATE notifications_messages 
        SET est_lu = 1 
        WHERE conversation_id = ? AND user_id = ? AND est_lu = 0
    ");
    $stmt->execute([$conversation_id, $user_id]);

    // Mettre à jour la date de dernière lecture
    $stmt = $pdo->prepare("
        UPDATE conversation_participants 
        SET date_derniere_lecture = NOW() 
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$conversation_id, $user_id]);

    // Ajouter les messages à la conversation
    $conversation['messages'] = $messages;

    echo json_encode([
        'success' => true,
        'conversation' => $conversation
    ]);

} catch (PDOException $e) {
    error_log("Erreur SQL (get_conversation.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
} 