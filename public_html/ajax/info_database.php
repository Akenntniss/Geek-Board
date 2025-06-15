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
    // Vérifier la connexion à la base de données
    $stmt = $shop_pdo->prepare("SELECT VERSION() AS version");
    $stmt->execute();
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer la liste des tables
    $stmt = $shop_pdo->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Récupérer la structure de la table 'clients' si elle existe
    $clientsStructure = [];
    if (in_array('clients', $tables)) {
        $stmt = $shop_pdo->prepare("DESCRIBE clients");
        $stmt->execute();
        $clientsStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Récupérer la structure de la table 'reparations' si elle existe
    $reparationsStructure = [];
    if (in_array('reparations', $tables)) {
        $stmt = $shop_pdo->prepare("DESCRIBE reparations");
        $stmt->execute();
        $reparationsStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Vérifier si un client avec ID 23
    $clientExists = false;
    if (in_array('clients', $tables)) {
        $stmt = $shop_pdo->prepare("SELECT COUNT(*) AS count FROM clients WHERE id = 23");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $clientExists = $result['count'] > 0;
    }
    
    // Afficher les informations
    echo json_encode([
        'success' => true,
        'version' => $version,
        'tables' => $tables,
        'clients_structure' => $clientsStructure,
        'reparations_structure' => $reparationsStructure,
        'client_23_exists' => $clientExists
    ]);
    
} catch (PDOException $e) {
    // Log l'erreur et renvoyer un message d'erreur
    error_log("Erreur PDO dans info_database.php: " . $e->getMessage());
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
}
?> 