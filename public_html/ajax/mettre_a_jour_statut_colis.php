<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier l'authentification
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier les données POST
if (!isset($_POST['colis_id']) || !isset($_POST['statut'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$colis_id = intval($_POST['colis_id']);
$nouveau_statut = $_POST['statut'];

// Liste des statuts valides
$statuts_valides = ['en_preparation', 'expedie', 'en_transit', 'livre', 'verifie'];

if (!in_array($nouveau_statut, $statuts_valides)) {
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit;
}

try {
    // Vérifier si le colis existe
    $stmt = $pdo->prepare("SELECT id, statut FROM colis_retour WHERE id = ?");
    $stmt->execute([$colis_id]);
    $colis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$colis) {
        throw new Exception('Colis non trouvé');
    }

    // Démarrer la transaction
    $pdo->beginTransaction();

    // Mettre à jour le statut du colis
    $stmt = $pdo->prepare("
        UPDATE colis_retour 
        SET statut = ?,
            date_expedition = CASE 
                WHEN ? = 'expedie' THEN CURRENT_TIMESTAMP 
                ELSE date_expedition 
            END,
            date_reception = CASE 
                WHEN ? = 'livre' THEN CURRENT_TIMESTAMP 
                ELSE date_reception 
            END
        WHERE id = ?
    ");
    $stmt->execute([$nouveau_statut, $nouveau_statut, $nouveau_statut, $colis_id]);

    // Si le colis est marqué comme livré, mettre à jour le statut des produits
    if ($nouveau_statut === 'livre') {
        $stmt = $pdo->prepare("
            UPDATE produits_temporaires pt
            JOIN colis_produits_temporaires cpt ON pt.id = cpt.produit_temporaire_id
            SET pt.statut = 'retourne'
            WHERE cpt.colis_id = ?
        ");
        $stmt->execute([$colis_id]);
    }

    // Valider la transaction
    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 