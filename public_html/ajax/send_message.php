<?php
session_start();
require_once '../includes/config.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification des données POST
if (!isset($_POST['conversation_id']) || !isset($_POST['message'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$conversation_id = intval($_POST['conversation_id']);
$message = trim($_POST['message']);
$user_id = $_SESSION['user_id'];

// Vérifier que le message n'est pas vide
if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Le message ne peut pas être vide']);
    exit;
}

try {
    // Vérifier si l'utilisateur a accès à cette conversation
    $stmt = $shop_pdo->prepare("
        SELECT role 
        FROM conversation_participants 
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$conversation_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
        exit;
    }

    // Insérer le message
    $stmt = $shop_pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, contenu, type, date_envoi)
        VALUES (?, ?, ?, 'text', NOW())
    ");
    $stmt->execute([$conversation_id, $user_id, $message]);
    $message_id = $shop_pdo->lastInsertId();

    // Créer des notifications pour tous les participants
    $stmt = $shop_pdo->prepare("
        INSERT INTO notifications_messages (user_id, conversation_id, message_id, est_lu, date_creation)
        SELECT user_id, ?, ?, 0, NOW()
        FROM conversation_participants
        WHERE conversation_id = ? AND user_id != ?
    ");
    $stmt->execute([$conversation_id, $message_id, $conversation_id, $user_id]);

    // Récupérer les détails du message envoyé
    $stmt = $shop_pdo->prepare("
        SELECT m.*, 
               u.nom as sender_nom,
               u.prenom as sender_prenom
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = ?
    ");
    $stmt->execute([$message_id]);
    $message_details = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => $message_details
    ]);

} catch (PDOException $e) {
    error_log("Erreur SQL (send_message.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
} 