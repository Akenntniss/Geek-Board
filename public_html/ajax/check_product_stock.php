<?php
// Inclure la configuration et la connexion à la base de données
require_once '../includes/config.php';
require_once '../includes/db.php';

// Vérifier que le code-barre est fourni
if (!isset($_GET['barcode']) || empty($_GET['barcode'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Code-barre non fourni'
    ]);
    exit;
}

$barcode = cleanInput($_GET['barcode']);

try {
    // Vérifier si le produit existe dans la table stock
    $stmt = $pdo->prepare("SELECT id, name, quantity, price, category, description FROM stock WHERE barcode = ?");
    $stmt->execute([$barcode]);
    
    if ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Le produit existe
        echo json_encode([
            'success' => true,
            'exists' => true,
            'product' => $product
        ]);
    } else {
        // Le produit n'existe pas
        echo json_encode([
            'success' => true,
            'exists' => false
        ]);
    }
} catch (PDOException $e) {
    // Erreur de base de données
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la vérification du produit: ' . $e->getMessage()
    ]);
} 