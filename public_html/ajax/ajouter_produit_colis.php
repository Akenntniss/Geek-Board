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
if (!isset($_POST['produit_id']) || !isset($_POST['colis_id'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$produit_id = intval($_POST['produit_id']);
$colis_id = intval($_POST['colis_id']);

try {
    // Vérifier si le produit existe et est disponible
    $stmt = $pdo->prepare("
        SELECT id, statut 
        FROM produits_temporaires 
        WHERE id = ? AND statut IN ('en_attente', 'a_retourner')
    ");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produit) {
        throw new Exception('Produit non trouvé ou non disponible pour le retour');
    }

    // Vérifier si le colis existe et est en préparation
    $stmt = $pdo->prepare("
        SELECT id, statut 
        FROM colis_retour 
        WHERE id = ? AND statut = 'en_preparation'
    ");
    $stmt->execute([$colis_id]);
    $colis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$colis) {
        throw new Exception('Colis non trouvé ou non disponible');
    }

    // Vérifier si le produit n'est pas déjà dans un colis
    $stmt = $pdo->prepare("
        SELECT id 
        FROM colis_produits_temporaires 
        WHERE produit_temporaire_id = ?
    ");
    $stmt->execute([$produit_id]);
    if ($stmt->fetch()) {
        throw new Exception('Ce produit est déjà dans un colis');
    }

    // Démarrer la transaction
    $pdo->beginTransaction();

    // Ajouter le produit au colis
    $stmt = $pdo->prepare("
        INSERT INTO colis_produits_temporaires (colis_id, produit_temporaire_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$colis_id, $produit_id]);

    // Mettre à jour le statut du produit
    $stmt = $pdo->prepare("
        UPDATE produits_temporaires 
        SET statut = 'en_transit'
        WHERE id = ?
    ");
    $stmt->execute([$produit_id]);

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