<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Initialiser la connexion à la base de données boutique
$shop_pdo = getShopDBConnection();

// Vérifier l'authentification
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier les données POST
if (!isset($_POST['produit_id']) || !isset($_POST['montant_rembourse']) || !isset($_POST['montant_rembourse_client'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$produit_id = intval($_POST['produit_id']);
$montant_rembourse = floatval($_POST['montant_rembourse']);
$montant_rembourse_client = floatval($_POST['montant_rembourse_client']);

try {
    // Vérifier si le produit existe et est dans un état approprié
    $stmt = $shop_pdo->prepare("
        SELECT id, statut, montant_rembourse, montant_rembourse_client
        FROM produits_temporaires 
        WHERE id = ? AND statut = 'retourne'
    ");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produit) {
        throw new Exception('Produit non trouvé ou non dans un état approprié pour la vérification');
    }

    // Démarrer la transaction
    $shop_pdo->beginTransaction();

    // Mettre à jour les montants et le statut
    $stmt = $shop_pdo->prepare("
        UPDATE produits_temporaires 
        SET statut = 'verifie',
            montant_rembourse = ?,
            montant_rembourse_client = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$montant_rembourse, $montant_rembourse_client, $produit_id]);

    // Enregistrer dans l'historique des vérifications
    $stmt = $shop_pdo->prepare("
        INSERT INTO historique_verifications_retour (
            produit_temporaire_id,
            montant_rembourse,
            montant_rembourse_client,
            date_verification,
            verifie_par
        ) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?)
    ");
    $stmt->execute([$produit_id, $montant_rembourse, $montant_rembourse_client, $_SESSION['user_id']]);

    // Valider la transaction
    $shop_pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Vérification effectuée avec succès'
    ]);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 