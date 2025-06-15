<?php
require_once __DIR__ . '/../config/database.php';
require_once '../includes/auth_check.php';
$shop_pdo = getShopDBConnection();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    // Validation des données
    if (empty($_POST['partenaire_id']) || empty($_POST['type']) || empty($_POST['montant']) || empty($_POST['description'])) {
        throw new Exception('Veuillez remplir tous les champs obligatoires');
    }

    $partenaire_id = intval($_POST['partenaire_id']);
    $type = $_POST['type'];
    $montant = floatval($_POST['montant']);
    $description = $_POST['description'];
    $reference_piece = $_POST['reference_piece'] ?? '';
    $created_by = $_SESSION['user_id'];

    if ($montant <= 0) {
        throw new Exception('Le montant doit être supérieur à 0');
    }

    if (!in_array($type, ['debit', 'credit'])) {
        throw new Exception('Type de transaction invalide');
    }

    // Début de la transaction
    $shop_pdo->begin_transaction();

    try {
        // Insertion de la transaction
        $stmt = $shop_pdo->prepare("INSERT INTO transactions_partenaires 
                              (partenaire_id, type, montant, description, reference_piece, created_by, statut) 
                              VALUES (?, ?, ?, ?, ?, ?, 'validee')");
        
        if (!$stmt) {
            throw new Exception('Erreur de préparation de la requête');
        }

        // MySQLi code - needs manual conversion

        if (!$stmt->execute()) {
            throw new Exception('Erreur lors de l\'enregistrement de la transaction');
        }

        // Mise à jour du solde
        $update_sql = "UPDATE soldes_partenaires 
                      SET solde_actuel = solde_actuel " . ($type === 'debit' ? '-' : '+') . " ? 
                      WHERE partenaire_id = ?";
        
        $stmt = $shop_pdo->prepare($update_sql);
        
        if (!$stmt) {
            throw new Exception('Erreur de préparation de la requête de mise à jour du solde');
        }

        // MySQLi code - needs manual conversion

        if (!$stmt->execute()) {
            throw new Exception('Erreur lors de la mise à jour du solde');
        }

        // Validation de la transaction
        $shop_pdo->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Transaction enregistrée avec succès'
        ]);

    } catch (Exception $e) {
        $shop_pdo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} 