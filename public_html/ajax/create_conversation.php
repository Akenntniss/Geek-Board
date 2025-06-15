<?php
session_start();
require_once '../includes/config.php';

// Initialiser la connexion à la base de données boutique
$shop_pdo = getShopDBConnection();

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupération et validation des données JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['titre']) || !isset($data['type']) || !isset($data['participants'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$titre = trim($data['titre']);
$type = $data['type'];
$participants = array_map('intval', $data['participants']);
$user_id = $_SESSION['user_id'];

// Validation du type de conversation
if (!in_array($type, ['direct', 'groupe', 'annonce'])) {
    echo json_encode(['success' => false, 'message' => 'Type de conversation invalide']);
    exit;
}

// Validation du titre
if (empty($titre)) {
    echo json_encode(['success' => false, 'message' => 'Le titre ne peut pas être vide']);
    exit;
}

// Validation des participants
if (empty($participants)) {
    echo json_encode(['success' => false, 'message' => 'Aucun participant sélectionné']);
    exit;
}

try {
    // Démarrer la transaction
    $shop_pdo->beginTransaction();

    // Créer la conversation
    $stmt = $shop_pdo->prepare("
        INSERT INTO conversations (titre, type, createur_id, date_creation)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$titre, $type, $user_id]);
    $conversation_id = $shop_pdo->lastInsertId();

    // Ajouter le créateur comme participant avec le rôle admin
    $stmt = $shop_pdo->prepare("
        INSERT INTO conversation_participants (conversation_id, user_id, role, date_derniere_lecture)
        VALUES (?, ?, 'admin', NOW())
    ");
    $stmt->execute([$conversation_id, $user_id]);

    // Ajouter les autres participants
    $stmt = $shop_pdo->prepare("
        INSERT INTO conversation_participants (conversation_id, user_id, role, date_derniere_lecture)
        VALUES (?, ?, ?, NOW())
    ");
    
    foreach ($participants as $participant_id) {
        if ($participant_id != $user_id) {
            $role = $type === 'annonce' ? 'reader' : 'member';
            $stmt->execute([$conversation_id, $participant_id, $role]);
        }
    }

    // Si c'est une annonce, créer un message initial
    if ($type === 'annonce') {
        $message = isset($data['message']) ? trim($data['message']) : '';
        if (!empty($message)) {
            $stmt = $shop_pdo->prepare("
                INSERT INTO messages (conversation_id, sender_id, contenu, type, est_annonce, date_envoi)
                VALUES (?, ?, ?, 'text', 1, NOW())
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
        }
    }

    // Valider la transaction
    $shop_pdo->commit();

    // Récupérer les détails de la conversation créée
    $stmt = $shop_pdo->prepare("
        SELECT c.*, 
               cp.role as user_role,
               cp.date_derniere_lecture
        FROM conversations c
        JOIN conversation_participants cp ON c.id = cp.conversation_id
        WHERE c.id = ? AND cp.user_id = ?
    ");
    $stmt->execute([$conversation_id, $user_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'conversation' => $conversation
    ]);

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    $shop_pdo->rollBack();
    error_log("Erreur SQL (create_conversation.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
} 