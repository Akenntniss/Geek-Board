<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé'
    ]);
    exit;
}

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID invalide'
    ]);
    exit;
}

$colis_id = (int)$_GET['id'];

try {
    // Récupérer les informations du colis
    $stmt = $shop_pdo->prepare("
        SELECT c.*, COUNT(r.id) as nombre_produits
        FROM colis_retour c
        LEFT JOIN retours r ON c.id = r.colis_id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$colis_id]);
    $colis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colis) {
        echo json_encode([
            'success' => false,
            'message' => 'Colis non trouvé'
        ]);
        exit;
    }
    
    // Récupérer les produits associés au colis
    $stmt = $shop_pdo->prepare("
        SELECT r.*, s.name as produit_nom, s.barcode
        FROM retours r
        JOIN stock s ON r.produit_id = s.id
        WHERE r.colis_id = ?
    ");
    $stmt->execute([$colis_id]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $colis['produits'] = $produits;
    
    echo json_encode([
        'success' => true,
        'colis' => $colis
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération du colis: ' . $e->getMessage()
    ]);
} 