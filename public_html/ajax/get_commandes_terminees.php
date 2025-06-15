<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT cp.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone, f.nom as fournisseur_nom, r.id as reparation_id, r.type_appareil, r.marque, r.modele 
            FROM commandes_pieces cp 
            LEFT JOIN clients c ON cp.client_id = c.id 
            LEFT JOIN fournisseurs f ON cp.fournisseur_id = f.id 
            LEFT JOIN reparations r ON cp.reparation_id = r.id 
            WHERE cp.statut IN ('termine', 'annule') 
            ORDER BY cp.date_creation DESC";
    
    $stmt = $shop_pdo->query($sql);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($commandes);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des commandes terminées: ' . $e->getMessage()]);
} 