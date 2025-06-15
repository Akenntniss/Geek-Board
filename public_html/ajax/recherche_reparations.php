<?php
// Inclure le fichier de connexion à la base de données
require_once dirname(__DIR__) . '/config/database.php';

// Vérifier que l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Récupérer le terme de recherche
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Vérifier que le terme de recherche est valide
if (empty($query)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

try {
    // Préparer la requête SQL
    $sql = "SELECT r.id, r.client_id, r.type_appareil, r.marque, r.modele, r.statut, 
                   c.nom as client_nom, c.prenom as client_prenom
            FROM reparations r
            LEFT JOIN clients c ON r.client_id = c.id
            WHERE (r.id LIKE :query 
                   OR c.nom LIKE :likeQuery 
                   OR c.prenom LIKE :likeQuery 
                   OR r.type_appareil LIKE :likeQuery 
                   OR r.marque LIKE :likeQuery 
                   OR r.modele LIKE :likeQuery)
            AND r.statut NOT IN ('livre', 'annule')
            ORDER BY r.id DESC
            LIMIT 10";
    
    $stmt = $shop_pdo->prepare($sql);
    
    // Le terme de recherche peut être un ID exact ou un terme partiel
    $stmt->bindValue(':query', $query);
    $stmt->bindValue(':likeQuery', '%' . $query . '%');
    
    $stmt->execute();
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Renvoyer les résultats au format JSON
    header('Content-Type: application/json');
    echo json_encode($reparations);
    
} catch (PDOException $e) {
    // En cas d'erreur, renvoyer un message d'erreur
    error_log('Erreur lors de la recherche de réparations: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur lors de la recherche']);
} 