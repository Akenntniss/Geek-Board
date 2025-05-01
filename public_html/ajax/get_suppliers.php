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

try {
    // Récupérer la liste des fournisseurs
    $stmt = $pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
    $fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($fournisseurs);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des fournisseurs : " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des fournisseurs']);
} 