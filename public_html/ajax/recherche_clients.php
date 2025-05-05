<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

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
    // Vérifier qu'un magasin est sélectionné
    if (!isset($_SESSION['shop_id'])) {
        error_log("ATTENTION: Aucun magasin sélectionné en session, utilisation de la connexion principale");
        // Au lieu de renvoyer une erreur, utiliser la connexion principale
        $shop_pdo = getMainDBConnection();
        
        if ($shop_pdo === null) {
            error_log("ERREUR: Impossible d'obtenir une connexion à la base de données principale");
            echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
            exit;
        }
    } else {
        // Utiliser getShopDBConnection() pour obtenir la connexion à la base du magasin
        $shop_pdo = getShopDBConnection();
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