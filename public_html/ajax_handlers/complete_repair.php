<?php
// Inclusion des fichiers nécessaires
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier si l'ID de réparation est fourni
if (!isset($_POST['repair_id']) || empty($_POST['repair_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de réparation non fourni']);
    exit;
}

// Récupérer les données
$user_id = $_SESSION['user_id'];
$repair_id = intval($_POST['repair_id']);
$timestamp = date('Y-m-d H:i:s');

try {
    // Commencer une transaction
    $pdo->beginTransaction();

    // Vérifier si la réparation appartient bien à l'utilisateur
    $stmt = $pdo->prepare("SELECT employe_id FROM reparations WHERE id = ?");
    $stmt->execute([$repair_id]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$repair || $repair['employe_id'] != $user_id) {
        throw new Exception('Vous n\'êtes pas autorisé à terminer cette réparation.');
    }

    // Mettre à jour le statut de la réparation à "reparation_effectue"
    $stmt = $pdo->prepare("UPDATE reparations SET statut = 'reparation_effectue', date_derniere_modification = ? WHERE id = ?");
    $stmt->execute([$timestamp, $repair_id]);

    // Mettre à jour le statut de l'utilisateur
    $stmt = $pdo->prepare("UPDATE users SET techbusy = 0, active_repair_id = NULL WHERE id = ? AND active_repair_id = ?");
    $stmt->execute([$user_id, $repair_id]);

    // Journaliser l'action
    $stmt = $pdo->prepare("INSERT INTO journal_actions (user_id, action_type, target_id, details, date_action) VALUES (?, 'complete_repair', ?, 'Réparation terminée', ?)");
    $stmt->execute([$user_id, $repair_id, $timestamp]);

    // Valider la transaction
    $pdo->commit();

    // Réponse réussie
    $response = [
        'success' => true,
        'message' => 'Réparation terminée avec succès'
    ];

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Réponse d'erreur
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Renvoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response); 