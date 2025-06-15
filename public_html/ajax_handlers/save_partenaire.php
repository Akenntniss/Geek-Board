<?php
require_once __DIR__ . '/../config/database.php';
require_once '../includes/auth_check.php';
$shop_pdo = getShopDBConnection();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    // Validation des données
    if (empty($_POST['nom']) || empty($_POST['prenom']) || empty($_POST['telephone'])) {
        throw new Exception('Veuillez remplir tous les champs obligatoires');
    }

    // Préparation de la requête
    $stmt = $shop_pdo->prepare("INSERT INTO partenaires (nom, prenom, societe, telephone, email, adresse) VALUES (?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête');
    }

    // MySQLi code - needs manual conversion

    // Exécution de la requête
    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de l\'enregistrement du partenaire');
    }

    $partenaire_id = $stmt->insert_id;

    // Création d'une entrée dans soldes_partenaires
    $stmt = $shop_pdo->prepare("INSERT INTO soldes_partenaires (partenaire_id, solde_actuel) VALUES (?, 0)");
    
    if (!$stmt) {
        throw new Exception('Erreur de préparation de la requête pour le solde');
    }

    // MySQLi code - needs manual conversion

    if (!$stmt->execute()) {
        throw new Exception('Erreur lors de l\'initialisation du solde');
    }

    echo json_encode(['success' => true, 'message' => 'Partenaire enregistré avec succès']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 