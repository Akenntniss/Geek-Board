<?php
// Configurer le fichier de log spécifique pour ce script
ini_set('error_log', __DIR__ . '/../logs/debug/search_reparations.log');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Fonction pour envoyer une réponse JSON
function sendJSON($data) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    echo json_encode($data);
    exit;
}

// Démarrer la session avant la vérification, seulement si aucune session n'est active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    error_log("=== DÉBUT SEARCH_REPARATIONS ===");
    error_log("Date: " . date('Y-m-d H:i:s'));
    error_log("Adresse IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'inconnue'));
    error_log("User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'inconnu'));
    error_log("Requête: " . json_encode($_GET));
    
    // Vérifier si une requête a été envoyée
    if (!isset($_GET['query']) || empty($_GET['query'])) {
        error_log("Aucun terme de recherche fourni");
        sendJSON([]);
        exit;
    }
    
    $query = trim($_GET['query']);
    error_log("Recherche de réparations pour la requête: " . $query);
    
    // Connexion directe à la base de données
    try {
        // Paramètres de connexion
        $host = 'srv931.hstgr.io';
        $port = '3306';
        $db = 'u139954273_Vscodetest';
        $user = 'u139954273_Vscodetest';
        $pass = 'Maman01#';
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        error_log("Tentative de connexion avec DSN: " . $dsn);
        $shop_pdo = new PDO($dsn, $user, $pass, $options);
        error_log("Connexion à la base de données réussie");
    } catch (PDOException $e) {
        error_log("Erreur de connexion à la base de données: " . $e->getMessage());
        error_log("Code erreur: " . $e->getCode());
        sendJSON([
            'error' => true,
            'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()
        ]); 
        exit;
    }
    
    // Préparation du terme de recherche avec wildcards
    $searchTerm = '%' . $query . '%';
    error_log("Terme de recherche: " . $searchTerm);
    
    // Requête SQL complète incluant les données clients
    try {
        $sql = "SELECT 
                r.id, 
                r.client_id, 
                r.type_appareil, 
                r.marque, 
                r.modele, 
                r.description_probleme as probleme,
                c.nom as client_nom,
                c.prenom as client_prenom,
                c.telephone
                FROM 
                reparations r
                LEFT JOIN
                clients c ON r.client_id = c.id
                WHERE 
                r.id LIKE ? 
                OR r.type_appareil LIKE ? 
                OR r.marque LIKE ?
                OR r.modele LIKE ?
                OR r.description_probleme LIKE ?
                OR c.nom LIKE ?
                OR c.prenom LIKE ?
                OR c.telephone LIKE ?
                ORDER BY 
                r.date_reception DESC
                LIMIT 20";
        
        error_log("Requête SQL: " . $sql);
        
        $stmt = $shop_pdo->prepare($sql);
        
        // Binding avec positions
        $stmt->bindParam(1, $searchTerm);
        $stmt->bindParam(2, $searchTerm);
        $stmt->bindParam(3, $searchTerm);
        $stmt->bindParam(4, $searchTerm);
        $stmt->bindParam(5, $searchTerm);
        $stmt->bindParam(6, $searchTerm);
        $stmt->bindParam(7, $searchTerm);
        $stmt->bindParam(8, $searchTerm);
        
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        $count = count($results);
        error_log("Nombre de résultats: " . $count);
        
        // En mode test, renvoyer des données factices si aucun résultat
        if ($count == 0 && ($query == 'test' || $query == 'demo')) {
            error_log("Mode test détecté, renvoi de données factices");
            $results = [
                [
                    'id' => '999',
                    'client_id' => '1',
                    'type_appareil' => 'Smartphone',
                    'marque' => 'Apple',
                    'modele' => 'iPhone 13',
                    'probleme' => 'Écran cassé',
                    'client_nom' => 'Dupont',
                    'client_prenom' => 'Jean',
                    'telephone' => '0612345678'
                ]
            ];
        }
        
        sendJSON($results);
        
    } catch (PDOException $e) {
        error_log("Erreur SQL: " . $e->getMessage());
        error_log("Code erreur: " . $e->getCode());
        
        sendJSON([
            'error' => true,
            'message' => 'Erreur lors de la recherche',
            'details' => $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    error_log("Exception non gérée: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    sendJSON([
        'error' => true,
        'message' => 'Erreur interne du serveur',
        'details' => $e->getMessage()
    ]);
} 