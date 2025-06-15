<?php
// Inclure la configuration et la connexion à la base de données
require_once '../includes/config.php';
require_once '../includes/db.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

// Récupérer les données JSON envoyées
$data = json_decode(file_get_contents('php://input'), true);

// Vérifier que toutes les données requises sont présentes
if (!isset($data['barcode']) || !isset($data['name']) || !isset($data['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Données incomplètes'
    ]);
    exit;
}

// Nettoyer les données entrantes
$barcode = cleanInput($data['barcode']);
$name = cleanInput($data['name']);
$category = isset($data['category']) ? cleanInput($data['category']) : '';
$quantity = (int)$data['quantity'];
$price = isset($data['price']) && !empty($data['price']) ? (float)$data['price'] : 0.00;
$description = isset($data['description']) ? cleanInput($data['description']) : '';

// Vérifier les valeurs
if (empty($barcode) || empty($name) || $quantity < 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Valeurs invalides'
    ]);
    exit;
}

try {
    // Vérifier si le produit existe déjà
    $stmt = $shop_pdo->prepare("SELECT id FROM stock WHERE barcode = ?");
    $stmt->execute([$barcode]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Un produit avec ce code-barre existe déjà'
        ]);
        exit;
    }
    
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    // Ajouter le nouveau produit
    $stmt = $shop_pdo->prepare("
        INSERT INTO stock (barcode, name, category, quantity, price, description, date_created)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$barcode, $name, $category, $quantity, $price, $description]);
    
    $productId = $shop_pdo->lastInsertId();
    
    // Enregistrer le mouvement initial dans l'historique
    $stmt = $shop_pdo->prepare("
        INSERT INTO stock_history (product_id, action, quantity, note, user_id, date_created)
        VALUES (?, 'initial', ?, 'Création initiale du produit', ?, NOW())
    ");
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $stmt->execute([$productId, $quantity, $userId]);
    
    // Valider la transaction
    $shop_pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Produit ajouté avec succès',
        'product_id' => $productId
    ]);
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout du produit: ' . $e->getMessage()
    ]);
} 