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

// Obtenir la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

try {
    // Commencer une transaction
    $shop_pdo->beginTransaction();

    // Mettre à jour la réparation pour assigner le technicien
    $stmt = $shop_pdo->prepare("UPDATE reparations SET employe_id = ?, date_derniere_modification = ? WHERE id = ?");
    $stmt->execute([$user_id, $timestamp, $repair_id]);

    // Mettre à jour le statut de l'utilisateur pour indiquer qu'il a une réparation active
    $stmt = $shop_pdo->prepare("UPDATE users SET techbusy = 1, active_repair_id = ? WHERE id = ?");
    $stmt->execute([$repair_id, $user_id]);

    // Journaliser l'action
    $stmt = $shop_pdo->prepare("INSERT INTO journal_actions (user_id, action_type, target_id, details, date_action) VALUES (?, 'assign_repair', ?, 'Réparation assignée', ?)");
    $stmt->execute([$user_id, $repair_id, $timestamp]);

    // Valider la transaction
    $shop_pdo->commit();

    // Réponse réussie
    $response = [
        'success' => true,
        'message' => 'Réparation assignée avec succès'
    ];

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    $shop_pdo->rollBack();
    
    // Réponse d'erreur
    $response = [
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ];
}

// Renvoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response); 