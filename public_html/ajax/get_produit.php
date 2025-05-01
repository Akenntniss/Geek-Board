<?php
// Inclure la configuration et les fonctions avant tout output
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration des headers pour les requêtes AJAX
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Vérifier que l'utilisateur est connecté - On désactive temporairement cette vérification pour les appels AJAX
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Non autorisé']);
//     exit;
// }

// Vérifier que l'ID du produit est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de produit manquant']);
    exit;
}

// Vérifier que la connexion à la base de données est établie
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit;
}

try {
    $id = (int)$_GET['id'];
    
    $stmt = $pdo->prepare("
        SELECT *
        FROM produits
        WHERE id = ?
    ");
    
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Produit non trouvé']);
        exit;
    }
    
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Retourner les résultats au format JSON
    echo json_encode([
        'success' => true,
        'produit' => $produit
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?> 