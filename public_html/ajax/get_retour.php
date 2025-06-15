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

$retour_id = (int)$_GET['id'];

try {
    // Récupérer les informations du retour
    $stmt = $shop_pdo->prepare("
        SELECT r.*, s.name as produit_nom, s.barcode
        FROM retours r
        JOIN stock s ON r.produit_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$retour_id]);
    $retour = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$retour) {
        echo json_encode([
            'success' => false,
            'message' => 'Retour non trouvé'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'retour' => $retour
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération du retour: ' . $e->getMessage()
    ]);
} 