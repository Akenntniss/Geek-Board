<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier si l'ID du colis est fourni
if (!isset($_POST['colis_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID du colis non fourni']);
    exit;
}

$colis_id = intval($_POST['colis_id']);

try {
    // Récupérer les détails du colis
    $query = "SELECT c.*, u.nom as cree_par_nom 
              FROM colis c 
              JOIN utilisateurs u ON c.cree_par = u.id 
              WHERE c.id = :colis_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['colis_id' => $colis_id]);
    $colis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$colis) {
        echo json_encode(['success' => false, 'message' => 'Colis non trouvé']);
        exit;
    }
    
    // Récupérer l'historique des statuts
    $query = "SELECT cs.*, u.nom as modifie_par_nom 
              FROM colis_statuts cs 
              JOIN utilisateurs u ON cs.modifie_par = u.id 
              WHERE cs.colis_id = :colis_id 
              ORDER BY cs.date_modification DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['colis_id' => $colis_id]);
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer la liste des produits dans le colis
    $query = "SELECT pt.*, p.nom as nom_produit, p.reference 
              FROM produits_temporaires pt 
              JOIN produits p ON pt.produit_id = p.id 
              WHERE pt.colis_id = :colis_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['colis_id' => $colis_id]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'data' => [
            'numero_suivi' => $colis['numero_suivi'],
            'transporteur' => $colis['transporteur'],
            'statut' => $colis['statut'],
            'date_creation' => $colis['date_creation'],
            'cree_par' => $colis['cree_par_nom'],
            'statuts' => $statuts,
            'produits' => $produits
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des données']);
} 