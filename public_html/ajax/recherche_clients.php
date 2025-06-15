<?php
// Désactiver l'affichage des erreurs pour éviter de corrompre le JSON
ini_set('display_errors', 0);
// Continuer à logger les erreurs dans le fichier de log
error_reporting(E_ALL);

// Inclure la configuration de session avant de démarrer la session
require_once '../config/session_config.php';
// La session est déjà démarrée dans session_config.php

// Inclure la configuration pour la gestion des sous-domaines
require_once '../config/subdomain_config.php';
// Le sous-domaine est détecté et la session est configurée avec le magasin correspondant

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Log des données POST reçues
error_log("POST reçu dans recherche_clients.php: " . print_r($_POST, true));
error_log("SESSION dans recherche_clients.php: " . print_r($_SESSION, true));

// Vérifier que le terme de recherche est fourni
if (!isset($_POST['terme']) || empty($_POST['terme'])) {
    error_log("Terme de recherche manquant");
    echo json_encode(['success' => false, 'message' => 'Terme de recherche manquant']);
    exit;
}

$terme = trim($_POST['terme']);
error_log("Recherche de clients avec le terme: " . $terme);

try {
    // Utiliser getShopDBConnection() qui gère automatiquement la sélection de la bonne base
    $shop_pdo = getShopDBConnection();
    
    if ($shop_pdo === null) {
        error_log("ERREUR: Impossible d'obtenir une connexion à la base de données du magasin");
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }
    
    // Vérifier la connexion
        $check_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
        $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $current_db = $check_result['current_db'];
        
    error_log("Base de données actuellement utilisée pour la recherche de clients: " . $current_db);
    
    // Préparer la requête avec des paramètres positionnels au lieu de paramètres nommés répétés
    $query = "SELECT id, nom, prenom, email, telephone FROM clients WHERE 
              nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR telephone LIKE ?
              ORDER BY nom, prenom LIMIT 10";
    
    $stmt = $shop_pdo->prepare($query);
    $search_term = "%$terme%";
    $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Nombre de clients trouvés: " . count($clients));
    
    // Retourner les résultats en JSON
    echo json_encode([
        'success' => true,
        'clients' => $clients,
        'database' => $current_db,
        'count' => count($clients)
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur durant la recherche: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la recherche: ' . $e->getMessage()]);
}
?> 