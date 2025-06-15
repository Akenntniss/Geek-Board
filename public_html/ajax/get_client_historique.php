<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

// Inclure la configuration de base de données
require_once '../config/database.php';

// Ajouter l'en-tête Content-Type
header('Content-Type: application/json');

try {
    // Test de base de la connexion - requête simple
    $stmt = $shop_pdo->prepare("SELECT 1");
    $stmt->execute();
    
    // Si nous arrivons ici, la connexion fonctionne
    echo json_encode([
        'success' => true,
        'message' => 'Connexion à la base de données réussie',
        'client' => ['id' => $_GET['id'], 'nom' => 'Test', 'prenom' => 'Client'],
        'reparations' => []
    ]);
    
} catch (PDOException $e) {
    // Log l'erreur et renvoyer un message d'erreur
    error_log("Erreur PDO dans get_client_historique.php: " . $e->getMessage());
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
?> 