<?php
// Inclure la configuration et la connexion à la base de données
require_once '../includes/config.php';
require_once '../includes/db.php';

// Initialiser la connexion à la base de données boutique
$shop_pdo = getShopDBConnection();

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
if (!isset($data['product_id']) || !isset($data['action']) || !isset($data['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Données incomplètes'
    ]);
    exit;
}

// Nettoyer les données entrantes
$productId = (int)$data['product_id'];
$action = cleanInput($data['action']);
$quantity = (int)$data['quantity'];
$note = isset($data['note']) ? cleanInput($data['note']) : '';

// Vérifier les valeurs
if ($productId <= 0 || $quantity <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Valeurs invalides'
    ]);
    exit;
}

// Vérifier que l'action est valide
if (!in_array($action, ['add', 'remove'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Action invalide'
    ]);
    exit;
}

try {
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    // Récupérer le produit actuel
    $stmt = $shop_pdo->prepare("SELECT id, name, quantity FROM stock WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $shop_pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Produit non trouvé'
        ]);
        exit;
    }
    
    // Calculer la nouvelle quantité
    $newQuantity = $action === 'add' 
        ? $product['quantity'] + $quantity 
        : $product['quantity'] - $quantity;
    
    // Vérifier que la nouvelle quantité n'est pas négative
    if ($newQuantity < 0) {
        $shop_pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de retirer plus que la quantité disponible'
        ]);
        exit;
    }
    
    // Mettre à jour le stock
    $stmt = $shop_pdo->prepare("UPDATE stock SET quantity = ? WHERE id = ?");
    $stmt->execute([$newQuantity, $productId]);
    
    // Enregistrer le mouvement dans l'historique
    $stmt = $shop_pdo->prepare("
        INSERT INTO stock_history (product_id, action, quantity, note, user_id, date_created)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $stmt->execute([$productId, $action, $quantity, $note, $userId]);
    
    // Valider la transaction
    $shop_pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock mis à jour avec succès',
        'new_quantity' => $newQuantity
    ]);
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    $shop_pdo->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour du stock: ' . $e->getMessage()
    ]);
} 