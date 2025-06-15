<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    exit('Non autorisé');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('ID manquant');
}

try {
    $stmt = $shop_pdo->prepare("SELECT * FROM marges_estimees WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $marge = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$marge) {
        http_response_code(404);
        exit('Marge non trouvée');
    }
    
    header('Content-Type: application/json');
    echo json_encode($marge);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 