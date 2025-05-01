<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Configuration des headers pour permettre les requêtes AJAX et empêcher la mise en cache
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier que le code-barres est fourni
if (!isset($_POST['code_barre']) || empty($_POST['code_barre'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Code-barres manquant']);
    exit;
}

// Vérifier que la connexion à la base de données est établie
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données', 'exists' => false]);
    exit;
}

try {
    $code_barre = trim($_POST['code_barre']);
    
    $stmt = $pdo->prepare("
        SELECT *
        FROM produits
        WHERE reference = ?
    ");
    
    $stmt->execute([$code_barre]);
    
    if ($stmt->rowCount() === 0) {
        // Produit non trouvé
        echo json_encode([
            'exists' => false,
            'message' => 'Produit non trouvé'
        ]);
        exit;
    }
    
    // Produit trouvé
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Retourner les résultats au format JSON
    echo json_encode([
        'exists' => true,
        'produit' => $produit,
        'message' => 'Produit trouvé'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur de base de données: ' . $e->getMessage(),
        'exists' => false
    ]);
}
?> 