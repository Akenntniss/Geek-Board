<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Log des données POST reçues
error_log("POST reçu dans direct_recherche_clients.php: " . print_r($_POST, true));
error_log("SESSION dans direct_recherche_clients.php: " . print_r($_SESSION, true));

// Vérifier que le terme de recherche est fourni
if (!isset($_POST['terme']) || empty($_POST['terme'])) {
    error_log("Terme de recherche manquant");
    echo json_encode(['success' => false, 'message' => 'Terme de recherche manquant']);
    exit;
}

$terme = trim($_POST['terme']);
error_log("Recherche directe de clients avec le terme: " . $terme);

try {
    // Paramètres de connexion directs (constants de database.php)
    $db_host = 'srv931.hstgr.io';
    $db_port = '3306';
    $db_name = 'u139954273_cannesphones'; // Utiliser la bonne base selon votre configuration
    $db_user = 'u139954273_Vscodetest';
    $db_pass = 'Maman01#';
    
    // Création d'une connexion PDO directe
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $direct_pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    error_log("Connexion directe établie avec succès");
    
    // Vérifier la connexion
    $check_stmt = $direct_pdo->query("SELECT DATABASE() as current_db");
    $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $current_db = $check_result['current_db'];
    
    error_log("Base de données directement utilisée pour la recherche: " . $current_db);
    
    // Préparer la requête avec des paramètres positionnels
    $query = "SELECT id, nom, prenom, email, telephone FROM clients WHERE 
              nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR telephone LIKE ?
              ORDER BY nom, prenom LIMIT 10";
    
    $stmt = $direct_pdo->prepare($query);
    $search_term = "%$terme%";
    $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Nombre de clients trouvés (connexion directe): " . count($clients));
    
    // Retourner les résultats en JSON
    echo json_encode([
        'success' => true,
        'clients' => $clients,
        'database' => $current_db,
        'count' => count($clients),
        'direct_connection' => true
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur durant la recherche directe: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la recherche: ' . $e->getMessage()]);
}
?> 