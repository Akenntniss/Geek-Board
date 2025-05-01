<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Récupérer le terme de recherche
$search = trim($_GET['search'] ?? '');

if (empty($search)) {
    echo json_encode(['success' => true, 'clients' => []]);
    exit;
}

try {
    // Rechercher les clients par nom, prénom ou téléphone
    $sql = "SELECT id, nom, prenom, telephone 
            FROM clients 
            WHERE nom LIKE :search 
            OR prenom LIKE :search 
            OR telephone LIKE :search 
            ORDER BY nom, prenom 
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'clients' => $clients
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la recherche des clients'
    ]);
} 