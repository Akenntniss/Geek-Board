<?php
/**
 * API pour récupérer l'historique des commandes de pièces d'un client
 */

// Inclure la configuration et la connexion à la base de données
require_once '../config/database.php';

// Définir l'en-tête pour la réponse JSON
header('Content-Type: application/json');

// Vérifier si l'ID du client est fourni
if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID client non fourni'
    ]);
    exit;
}

// Récupérer l'ID du client
$clientId = intval($_GET['client_id']);

try {
    // Requête pour récupérer les commandes du client
    $stmt = $pdo->prepare("SELECT 
        cp.id, cp.nom_piece, cp.quantite, cp.prix_estime, cp.code_barre, cp.statut, 
        cp.date_creation, cp.reparation_id, f.nom as fournisseur_nom
        FROM commandes_pieces cp
        LEFT JOIN fournisseurs f ON cp.fournisseur_id = f.id
        WHERE cp.client_id = :client_id
        ORDER BY cp.date_creation DESC
        LIMIT 20");
    
    $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
    $stmt->execute();
    
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Réponse réussie
    echo json_encode([
        'success' => true,
        'commandes' => $commandes
    ]);
    
} catch (PDOException $e) {
    // Réponse d'erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
    
    // Log de l'erreur
    error_log('Erreur lors de la récupération des commandes: ' . $e->getMessage());
} 