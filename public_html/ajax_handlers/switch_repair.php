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

// Vérifier si les IDs de réparation sont fournis
if (!isset($_POST['current_repair_id']) || !isset($_POST['new_repair_id']) || 
    empty($_POST['current_repair_id']) || empty($_POST['new_repair_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'IDs de réparation non fournis']);
    exit;
}

// Récupérer les données
$user_id = $_SESSION['user_id'];
$current_repair_id = intval($_POST['current_repair_id']);
$new_repair_id = intval($_POST['new_repair_id']);
$timestamp = date('Y-m-d H:i:s');

try {
    // Commencer une transaction
    $pdo->beginTransaction();

    // Vérifier si la réparation actuelle appartient bien à l'utilisateur
    $stmt = $pdo->prepare("SELECT employe_id FROM reparations WHERE id = ?");
    $stmt->execute([$current_repair_id]);
    $current_repair = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_repair || $current_repair['employe_id'] != $user_id) {
        throw new Exception('Vous n\'êtes pas autorisé à modifier cette réparation.');
    }

    // Mettre en pause la réparation actuelle (la marquer comme "en_attente")
    $stmt = $pdo->prepare("UPDATE reparations SET statut = 'en_attente_responsable', date_derniere_modification = ? WHERE id = ?");
    $stmt->execute([$timestamp, $current_repair_id]);

    // Assigner la nouvelle réparation à l'utilisateur
    $stmt = $pdo->prepare("UPDATE reparations SET employe_id = ?, date_derniere_modification = ? WHERE id = ?");
    $stmt->execute([$user_id, $timestamp, $new_repair_id]);

    // Mettre à jour le statut de l'utilisateur pour pointer vers la nouvelle réparation
    $stmt = $pdo->prepare("UPDATE users SET active_repair_id = ? WHERE id = ?");
    $stmt->execute([$new_repair_id, $user_id]);

    // Journaliser les actions
    $stmt = $pdo->prepare("INSERT INTO journal_actions (user_id, action_type, target_id, details, date_action) 
                          VALUES (?, 'pause_repair', ?, 'Réparation mise en pause', ?)");
    $stmt->execute([$user_id, $current_repair_id, $timestamp]);

    $stmt = $pdo->prepare("INSERT INTO journal_actions (user_id, action_type, target_id, details, date_action) 
                          VALUES (?, 'assign_repair', ?, 'Nouvelle réparation assignée', ?)");
    $stmt->execute([$user_id, $new_repair_id, $timestamp]);

    // Valider la transaction
    $pdo->commit();

    // Réponse réussie
    $response = [
        'success' => true,
        'message' => 'Changement de réparation effectué avec succès'
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