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
if (!isset($data['message_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de message manquant']);
    exit;
}

$message_id = intval($data['message_id']);
$user_id = $_SESSION['user_id'];

try {
    // Vérifier si le message est une annonce
    $stmt = $shop_pdo->prepare("
        SELECT m.*, c.type as conversation_type
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        WHERE m.id = ? AND m.est_annonce = 1
    ");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        echo json_encode(['success' => false, 'message' => 'Message non trouvé ou non annonce']);
        exit;
    }

    // Vérifier si l'utilisateur a déjà lu l'annonce
    $stmt = $shop_pdo->prepare("
        SELECT 1 FROM lecture_annonces 
        WHERE message_id = ? AND user_id = ?
    ");
    $stmt->execute([$message_id, $user_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Annonce déjà lue']);
        exit;
    }

    // Vérifier si l'utilisateur a accès à la conversation
    $stmt = $shop_pdo->prepare("
        SELECT 1 FROM conversation_participants 
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$message['conversation_id'], $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
        exit;
    }

    // Enregistrer la confirmation de lecture
    $stmt = $shop_pdo->prepare("
        INSERT INTO lecture_annonces (message_id, user_id, date_lecture)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$message_id, $user_id]);

    // Récupérer le nombre total de lectures
    $stmt = $shop_pdo->prepare("
        SELECT COUNT(*) as total_lectures
        FROM lecture_annonces
        WHERE message_id = ?
    ");
    $stmt->execute([$message_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total_lectures' => $result['total_lectures']
    ]);

} catch (PDOException $e) {
    error_log("Erreur SQL (confirm_announcement_read.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
} 