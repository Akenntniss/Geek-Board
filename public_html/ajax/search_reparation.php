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

// Récupérer le terme de recherche et l'ID du client
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$clientId = isset($_GET['client_id']) ? trim($_GET['client_id']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Terme de recherche requis']);
    exit;
}

try {
    // Préparer la requête SQL de base
    $sql = "SELECT r.id, r.type_appareil, r.marque, r.modele, r.date_depot, r.statut
            FROM reparations r
            WHERE r.archive = 'NON'
            AND (r.id LIKE :query 
                OR r.type_appareil LIKE :query 
                OR r.marque LIKE :query 
                OR r.modele LIKE :query)";
    
    // Ajouter la condition du client si spécifié
    if (!empty($clientId)) {
        $sql .= " AND r.client_id = :client_id";
    }
    
    // Ajouter l'ordre et la limite
    $sql .= " ORDER BY r.date_depot DESC LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    
    // Préparer les paramètres
    $params = ['query' => "%$query%"];
    if (!empty($clientId)) {
        $params['client_id'] = $clientId;
    }
    
    $stmt->execute($params);
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reparations' => $reparations
    ]);
} catch (PDOException $e) {
    error_log("Erreur lors de la recherche de réparations : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la recherche des réparations'
    ]);
} 