<?php
/**
 * Script AJAX pour récupérer les réparations d'un client spécifique
 * Utilisé par le modal de nouvelle commande de pièces
 */

// Inclure le fichier de configuration
require_once '../config/config.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Vérifier si l'ID du client est fourni
if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID client non fourni']);
    exit;
}

$client_id = intval($_GET['client_id']);

try {
    // Requête pour récupérer les réparations du client
    $stmt = $shop_pdo->prepare("
        SELECT id, type_appareil, marque, modele, statut, date_creation
        FROM reparations
        WHERE client_id = :client_id
        ORDER BY date_creation DESC
    ");
    
    $stmt->execute(['client_id' => $client_id]);
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les résultats en JSON
    echo json_encode([
        'success' => true,
        'repairs' => $repairs
    ]);
    
} catch (PDOException $e) {
    // En cas d'erreur, retourner un message d'erreur
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} 