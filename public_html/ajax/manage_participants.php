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
if (!isset($data['conversation_id']) || !isset($data['action']) || !isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$conversation_id = intval($data['conversation_id']);
$user_id = $_SESSION['user_id'];
$target_user_id = intval($data['user_id']);
$action = $data['action'];

// Validation de l'action
if (!in_array($action, ['add', 'remove', 'promote', 'demote'])) {
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit;
}

try {
    // Démarrer la transaction
    $pdo->beginTransaction();

    // Vérifier si l'utilisateur a les droits d'administration
    $stmt = $pdo->prepare("
        SELECT role 
        FROM conversation_participants 
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$conversation_id, $user_id]);
    $admin_role = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin_role || $admin_role['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les droits pour gérer les participants']);
        exit;
    }

    // Vérifier si l'utilisateur cible existe
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit;
    }

    switch ($action) {
        case 'add':
            // Vérifier si l'utilisateur n'est pas déjà participant
            $stmt = $pdo->prepare("
                SELECT 1 FROM conversation_participants 
                WHERE conversation_id = ? AND user_id = ?
            ");
            $stmt->execute([$conversation_id, $target_user_id]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'L\'utilisateur est déjà participant']);
                exit;
            }

            // Ajouter le participant
            $stmt = $pdo->prepare("
                INSERT INTO conversation_participants (conversation_id, user_id, role, date_derniere_lecture)
                VALUES (?, ?, 'member', NOW())
            ");
            $stmt->execute([$conversation_id, $target_user_id]);
            break;

        case 'remove':
            // Vérifier si l'utilisateur est participant
            $stmt = $pdo->prepare("
                SELECT role FROM conversation_participants 
                WHERE conversation_id = ? AND user_id = ?
            ");
            $stmt->execute([$conversation_id, $target_user_id]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$participant) {
                echo json_encode(['success' => false, 'message' => 'L\'utilisateur n\'est pas participant']);
                exit;
            }

            // Ne pas permettre de retirer le dernier administrateur
            if ($participant['role'] === 'admin') {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as admin_count 
                    FROM conversation_participants 
                    WHERE conversation_id = ? AND role = 'admin'
                ");
                $stmt->execute([$conversation_id]);
                $admin_count = $stmt->fetch(PDO::FETCH_ASSOC)['admin_count'];

                if ($admin_count <= 1) {
                    echo json_encode(['success' => false, 'message' => 'Impossible de retirer le dernier administrateur']);
                    exit;
                }
            }

            // Supprimer le participant
            $stmt = $pdo->prepare("
                DELETE FROM conversation_participants 
                WHERE conversation_id = ? AND user_id = ?
            ");
            $stmt->execute([$conversation_id, $target_user_id]);
            break;

        case 'promote':
            // Vérifier si l'utilisateur est participant
            $stmt = $pdo->prepare("
                SELECT role FROM conversation_participants 
                WHERE conversation_id = ? AND user_id = ?
            ");
            $stmt->execute([$conversation_id, $target_user_id]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$participant || $participant['role'] === 'admin') {
                echo json_encode(['success' => false, 'message' => 'Action impossible']);
                exit;
            }

            // Promouvoir en administrateur
            $stmt = $pdo->prepare("
                UPDATE conversation_participants 
                SET role = 'admin' 
                WHERE conversation_id = ? AND user_id = ?
            ");
            $stmt->execute([$conversation_id, $target_user_id]);
            break;

        case 'demote':
            // Vérifier si l'utilisateur est participant
            $stmt = $pdo->prepare("
                SELECT role FROM conversation_participants 
                WHERE conversation_id = ? AND user_id = ?
            ");
            $stmt->execute([$conversation_id, $target_user_id]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$participant || $participant['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Action impossible']);
                exit;
            }

            // Vérifier qu'il reste au moins un administrateur
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as admin_count 
                FROM conversation_participants 
                WHERE conversation_id = ? AND role = 'admin'
            ");
            $stmt->execute([$conversation_id]);
            $admin_count = $stmt->fetch(PDO::FETCH_ASSOC)['admin_count'];

            if ($admin_count <= 1) {
                echo json_encode(['success' => false, 'message' => 'Impossible de rétrograder le dernier administrateur']);
                exit;
            }

            // Rétrograder en membre
            $stmt = $pdo->prepare("
                UPDATE conversation_participants 
                SET role = 'member' 
                WHERE conversation_id = ? AND user_id = ?
            ");
            $stmt->execute([$conversation_id, $target_user_id]);
            break;
    }

    // Valider la transaction
    $pdo->commit();

    // Récupérer la liste mise à jour des participants
    $stmt = $pdo->prepare("
        SELECT cp.*, 
               u.nom, 
               u.prenom,
               u.email
        FROM conversation_participants cp
        JOIN users u ON cp.user_id = u.id
        WHERE cp.conversation_id = ?
        ORDER BY cp.role DESC, u.nom ASC
    ");
    $stmt->execute([$conversation_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'participants' => $participants
    ]);

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    $pdo->rollBack();
    error_log("Erreur SQL (manage_participants.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
} 