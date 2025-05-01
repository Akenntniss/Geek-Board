<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Récupérer le terme de recherche
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Terme de recherche requis']);
    exit;
}

try {
    // Préparer la requête SQL
    $sql = "SELECT id, nom, prenom, telephone, email 
            FROM clients 
            WHERE (nom LIKE :query 
               OR prenom LIKE :query 
               OR telephone LIKE :query 
               OR email LIKE :query)
            AND archive = 'NON'
            ORDER BY nom, prenom 
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['query' => "%$query%"]);
    
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'clients' => $clients
    ]);
} catch (PDOException $e) {
    error_log("Erreur lors de la recherche de clients : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la recherche des clients'
    ]);
} 