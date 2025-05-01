<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifier l'authentification
session_start();
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Non authentifié']));
}

// Vérifier les données requises
if (!isset($_POST['partenaire_id']) || !isset($_POST['type']) || !isset($_POST['montant'])) {
    die(json_encode(['success' => false, 'message' => 'Données manquantes']));
}

try {
    $pdo->beginTransaction();

    // Insérer la transaction
    $stmt = $pdo->prepare("
        INSERT INTO transactions_partenaires 
        (partenaire_id, type, montant, description, reference_document) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $_POST['partenaire_id'],
        $_POST['type'],
        $_POST['montant'],
        $_POST['description'] ?? null,
        $_POST['reference_document'] ?? null
    ]);

    $transaction_id = $pdo->lastInsertId();

    // Mettre à jour le solde du partenaire
    $montant = floatval($_POST['montant']);
    if ($_POST['type'] === 'REMBOURSEMENT') {
        $montant = -$montant; // Inverser le montant pour un remboursement
    }

    // Récupérer l'ancien solde ou créer une nouvelle entrée
    $stmt = $pdo->prepare("
        SELECT solde_actuel 
        FROM soldes_partenaires 
        WHERE partenaire_id = ?
    ");
    $stmt->execute([$_POST['partenaire_id']]);
    $ancien_solde = $stmt->fetchColumn();

    if ($ancien_solde === false) {
        // Créer une nouvelle entrée
        $stmt = $pdo->prepare("
            INSERT INTO soldes_partenaires (partenaire_id, solde_actuel)
            VALUES (?, ?)
        ");
        $stmt->execute([$_POST['partenaire_id'], $montant]);
        $ancien_solde = 0;
    } else {
        // Mettre à jour le solde existant
        $stmt = $pdo->prepare("
            UPDATE soldes_partenaires 
            SET solde_actuel = solde_actuel + ?,
                derniere_mise_a_jour = CURRENT_TIMESTAMP
            WHERE partenaire_id = ?
        ");
        $stmt->execute([$montant, $_POST['partenaire_id']]);
    }

    // Enregistrer l'historique
    $nouveau_solde = $ancien_solde + $montant;
    $stmt = $pdo->prepare("
        INSERT INTO historique_soldes 
        (partenaire_id, ancien_solde, nouveau_solde, transaction_id)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['partenaire_id'],
        $ancien_solde,
        $nouveau_solde,
        $transaction_id
    ]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()
    ]);
} 