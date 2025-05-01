<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupérer les paramètres de recherche
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';

try {
    // Construire la requête de base
    $query = "SELECT c.*, u.nom as modifie_par_nom 
              FROM colis c 
              LEFT JOIN users u ON c.modifie_par = u.id 
              WHERE 1=1";
    $params = [];
    
    // Ajouter les conditions de recherche
    if (!empty($search)) {
        $query .= " AND (c.numero_suivi LIKE :search OR c.transporteur LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    if (!empty($status)) {
        $query .= " AND c.statut = :status";
        $params['status'] = $status;
    }
    
    if (!empty($date_debut)) {
        $query .= " AND c.date_creation >= :date_debut";
        $params['date_debut'] = $date_debut;
    }
    
    if (!empty($date_fin)) {
        $query .= " AND c.date_creation <= :date_fin";
        $params['date_fin'] = $date_fin;
    }
    
    // Ajouter le tri
    $query .= " ORDER BY c.date_creation DESC";
    
    // Exécuter la requête
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $colis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $colis
    ]);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la recherche']);
} 