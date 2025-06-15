<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si les données sont présentes
if (!isset($_POST['part_name']) || !isset($_POST['supplier_id']) || !isset($_POST['reparation_id']) || !isset($_POST['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

try {
    // Générer une référence unique
    $reference = 'CMD-' . date('Ymd') . '-' . uniqid();
    
    // Récupérer l'ID du client à partir de la réparation
    $stmt = $shop_pdo->prepare("SELECT client_id FROM reparations WHERE id = ?");
    $stmt->execute([$_POST['reparation_id']]);
    $client_id = $stmt->fetchColumn();
    
    if (!$client_id) {
        throw new Exception('Client non trouvé pour cette réparation');
    }
    
    // Insérer la commande
    $stmt = $shop_pdo->prepare("
        INSERT INTO commandes_pieces (
            reference,
            client_id,
            reparation_id,
            fournisseur_id,
            nom_piece,
            description,
            quantite,
            prix_estime,
            statut,
            date_creation
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $reference,
        $client_id,
        $_POST['reparation_id'],
        $_POST['supplier_id'],
        $_POST['part_name'],
        $_POST['description'] ?? null,
        $_POST['quantity'] ?? 1,
        $_POST['estimated_price'] ?? null,
        $_POST['status']
    ]);

    // Mettre à jour le champ commande_requise de la réparation
    $stmt = $shop_pdo->prepare("UPDATE reparations SET commande_requise = TRUE WHERE id = ?");
    $stmt->execute([$_POST['reparation_id']]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Commande créée avec succès',
        'reference' => $reference
    ]);
} catch (Exception $e) {
    error_log("Erreur lors de la création de la commande : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création de la commande : ' . $e->getMessage()
    ]);
} 