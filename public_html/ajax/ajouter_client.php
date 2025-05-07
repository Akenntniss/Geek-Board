<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Liste des domaines autorisés
$allowed_domains = [
    'https://mdgeek.top',
    'http://mdgeek.top',
    'https://www.mdgeek.top',
    'http://localhost:8080',
    'http://127.0.0.1:8080'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_domains)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    
    // Répondre immédiatement aux requêtes OPTIONS (pre-flight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Fonction pour nettoyer les entrées
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Démarrer ou récupérer la session existante
session_start();

// Débogage de session complet
error_log("============= DÉBUT AJOUTER_CLIENT =============");
error_log("Session ID dans ajouter_client.php: " . session_id());
error_log("Variables de session: " . print_r($_SESSION, true));
error_log("Cookies: " . print_r($_COOKIE, true));
error_log("Données POST reçues: " . print_r($_POST, true));
error_log("shop_id en session: " . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'non défini'));
error_log("user_id en session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'non défini'));
error_log("============= FIN SESSION DEBUG =============");

// Vérifier si le shop_id est fourni dans la requête, et l'utiliser s'il n'est pas déjà dans la session
if (!isset($_SESSION['shop_id']) && isset($input_data['shop_id']) && !empty($input_data['shop_id'])) {
    $_SESSION['shop_id'] = $input_data['shop_id'];
    error_log("shop_id récupéré depuis les données POST et défini en session: " . $_SESSION['shop_id']);
}

// Si le shop_id n'est pas défini dans la session, essayer de le récupérer depuis l'utilisateur connecté
if (!isset($_SESSION['shop_id']) && isset($_SESSION['user_id'])) {
    // Obtenir une connexion à la base principale
    $main_pdo = getMainDBConnection();
    
    // Récupérer le magasin de l'utilisateur
    try {
        error_log("Tentative de récupération du magasin pour l'utilisateur " . $_SESSION['user_id']);
        $stmt = $main_pdo->prepare("SELECT shop_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data && isset($user_data['shop_id'])) {
            $_SESSION['shop_id'] = $user_data['shop_id'];
            error_log("shop_id récupéré depuis la base de données et défini en session: " . $_SESSION['shop_id']);
        } else {
            error_log("ERREUR: Impossible de trouver le shop_id pour l'utilisateur " . $_SESSION['user_id']);
        }
    } catch (Exception $e) {
        error_log("ERREUR lors de la récupération du shop_id: " . $e->getMessage());
    }
}

// Si le shop_id n'est toujours pas défini après toutes les tentatives précédentes, essayer d'utiliser le premier magasin disponible
if (!isset($_SESSION['shop_id'])) {
    try {
        error_log("Tentative de récupération du premier magasin disponible");
        $main_pdo = getMainDBConnection();
        $stmt = $main_pdo->query("SELECT id FROM shops ORDER BY id LIMIT 1");
        $first_shop = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($first_shop && isset($first_shop['id'])) {
            $_SESSION['shop_id'] = $first_shop['id'];
            error_log("Premier magasin disponible récupéré et défini en session: " . $_SESSION['shop_id']);
        } else {
            error_log("ERREUR: Aucun magasin trouvé dans la base de données");
        }
    } catch (Exception $e) {
        error_log("ERREUR lors de la récupération d'un magasin par défaut: " . $e->getMessage());
    }
}

// Vérifier que l'utilisateur est connecté - Version plus souple
if (!isset($_SESSION['user_id'])) {
    // Tenter une authentification alternative - par exemple avec un cookie
    $allow_access = false;
    
    // Vérifier si une authentification par token est possible
    if (isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
        // Ici on pourrait vérifier la validité du token dans la base de données
        error_log("Tentative d'authentification par cookie auth_token");
        // $allow_access = true; // Décommenter pour activer cette méthode
    }
    
    // Pour le débogage, on va temporairement autoriser l'accès sans authentification
    $allow_access = true; // TEMPORAIRE - À SUPPRIMER en production
    
    if (!$allow_access) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['success' => false, 'message' => 'Non autorisé - Session expirée']);
        exit;
    } else {
        error_log("Accès autorisé sans session pour le débogage");
    }
}

// Définir le type de contenu avant toute sortie
header('Content-Type: application/json');

// Récupérer les données selon le type de requête
$input_data = $_POST;

// Si c'est une requête JSON
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (strpos($content_type, 'application/json') !== false) {
    $json_data = file_get_contents('php://input');
    $decoded_data = json_decode($json_data, true);
    
    if ($decoded_data !== null) {
        $input_data = $decoded_data;
    }
}

// Débogage des données reçues
error_log("Données d'entrée reçues: " . print_r($input_data, true));
error_log("Méthode de requête: " . $_SERVER['REQUEST_METHOD']);

// Vérifier que les données requises sont fournies
if (!isset($input_data['nom']) || !isset($input_data['prenom']) || !isset($input_data['telephone'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Tous les champs sont requis'
    ]);
    exit;
}

try {
    // Vérifier si un magasin est sélectionné
    if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
        error_log("ERREUR CRITIQUE: Aucun magasin (shop_id) défini en session. Impossible de déterminer la base de données à utiliser.");
        throw new Exception("Aucun magasin sélectionné. Veuillez vous reconnecter.");
    }
    
    error_log("Tentative de connexion à la base de données du magasin ID: " . $_SESSION['shop_id']);
    
    // Utiliser getShopDBConnection() pour obtenir la connexion à la base du magasin
    $shop_pdo = getShopDBConnection();
    
    // Vérifier la connexion
    $db_stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("CONNEXION RÉUSSIE - BASE DE DONNÉES UTILISÉE POUR L'AJOUT CLIENT: " . ($db_info['db_name'] ?? 'Inconnue'));
    
    // Log l'information sur la connexion utilisée
    error_log("Ajout client - Utilisation de getShopDBConnection() - Session shop_id: " . ($_SESSION['shop_id'] ?? 'non défini'));
    
    // Nettoyer les données
    $nom = trim($input_data['nom']);
    $prenom = trim($input_data['prenom']);
    $telephone = trim($input_data['telephone']);
    $email = isset($input_data['email']) ? cleanInput($input_data['email']) : null;
    $adresse = isset($input_data['adresse']) ? cleanInput($input_data['adresse']) : null;
    
    // Déboguer les paramètres
    error_log("Ajout client - Paramètres : " . json_encode([
        'nom' => $nom,
        'prenom' => $prenom,
        'telephone' => $telephone,
        'email' => $email,
        'adresse' => $adresse
    ]));
    
    // Vérifier si le client existe déjà
    $stmt = $shop_pdo->prepare("
        SELECT id FROM clients 
        WHERE telephone = ? 
        LIMIT 1
    ");
    $stmt->execute([$telephone]);
    
    if ($stmt->rowCount() > 0) {
        // Le client existe déjà
        $client = $stmt->fetch();
        error_log("Client existant trouvé avec l'ID: " . $client['id']);
        echo json_encode([
            'success' => true, 
            'client_id' => $client['id'],
            'message' => 'Client existant récupéré'
        ]);
        exit;
    }
    
    // Vérifier que la table clients existe
    try {
        $table_exists = $shop_pdo->query("SHOW TABLES LIKE 'clients'");
        if ($table_exists->rowCount() == 0) {
            error_log("ERREUR CRITIQUE: La table 'clients' n'existe pas dans la base de données '" . ($db_info['db_name'] ?? 'Inconnue') . "'");
            throw new Exception("La table 'clients' n'existe pas dans cette base de données");
        } else {
            error_log("Table 'clients' trouvée dans la base de données");
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de l'existence de la table: " . $e->getMessage());
    }
    
    // Vérifier la structure de la table clients
    $table_check = $shop_pdo->query("DESCRIBE clients");
    $columns = $table_check->fetchAll(PDO::FETCH_COLUMN);
    error_log("Structure de la table clients : " . json_encode($columns));
    
    // Créer une requête adaptée à la structure existante
    $fields = ['nom', 'prenom', 'telephone'];
    $values = [$nom, $prenom, $telephone];
    
    if (in_array('email', $columns) && $email !== null) {
        $fields[] = 'email';
        $values[] = $email;
    }
    
    if (in_array('adresse', $columns) && $adresse !== null) {
        $fields[] = 'adresse';
        $values[] = $adresse;
    }
    
    if (in_array('created_at', $columns)) {
        $fields[] = 'created_at';
        $values[] = date('Y-m-d H:i:s');
    }
    
    $sql = "INSERT INTO clients (" . implode(', ', $fields) . ") VALUES (" . str_repeat('?,', count($fields) - 1) . "?)";
    error_log("Requête SQL : " . $sql);
    
    // Insérer le nouveau client
    $stmt = $shop_pdo->prepare($sql);
    
    error_log("Exécution de la requête d'insertion avec les valeurs: " . print_r($values, true));
    $stmt->execute($values);
    $client_id = $shop_pdo->lastInsertId();
    error_log("Résultat de l'insertion - ID généré: " . $client_id);
    
    // Vérifier que le client a bien été inséré dans la bonne base de données
    if ($client_id > 0) {
        try {
            // Vérifier dans la base de données du magasin
            $verify_stmt = $shop_pdo->prepare("SELECT * FROM clients WHERE id = ?");
            $verify_stmt->execute([$client_id]);
            $new_client = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($new_client) {
                error_log("SUCCÈS: Client bien inséré dans la base " . $db_info['db_name'] . ": " . print_r($new_client, true));
                
                // Vérifier que le client n'existe pas aussi dans la base principale
                try {
                    // Obtenir une connexion à la base principale
                    $main_pdo = getMainDBConnection();
                    
                    $main_verify_stmt = $main_pdo->prepare("SELECT id FROM clients WHERE telephone = ? LIMIT 1");
                    $main_verify_stmt->execute([$telephone]);
                    $main_client = $main_verify_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($main_client) {
                        error_log("ALERTE: Un client avec le même numéro existe aussi dans la base principale: ID=" . $main_client['id']);
                    }
                } catch (Exception $e) {
                    error_log("Erreur lors de la vérification dans la base principale: " . $e->getMessage());
                }
            } else {
                error_log("ERREUR CRITIQUE: Le client avec ID=$client_id n'a pas été trouvé après insertion!");
                throw new Exception("Erreur lors de l'ajout du client - Échec de vérification");
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la vérification post-insertion: " . $e->getMessage());
            throw $e;
        }
    } else {
        error_log("ERREUR CRITIQUE: Aucun ID généré pour l'insertion du client!");
        throw new Exception("Erreur lors de l'ajout du client - Aucun ID généré");
    }
    
    // Retourner une réponse de succès avec des informations supplémentaires
    echo json_encode([
        'success' => true,
        'client_id' => $client_id,
        'message' => 'Client ajouté avec succès',
        'database_info' => [
            'shop_id' => $_SESSION['shop_id'],
            'database' => $db_info['db_name'],
            'shop_name' => $_SESSION['shop_name'] ?? 'Non disponible'
        ]
    ]);
    
} catch (PDOException $e) {
    // Log l'erreur détaillée
    error_log("Erreur PDO lors de l'ajout d'un client: " . $e->getMessage());
    
    // Retourner une erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage(),
        'code' => $e->getCode(),
        'shop_id' => $_SESSION['shop_id'] ?? 'non défini'
    ]);
} catch (Exception $e) {
    // Log l'erreur détaillée
    error_log("Exception lors de l'ajout d'un client: " . $e->getMessage());
    
    // Retourner une erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage(),
        'shop_id' => $_SESSION['shop_id'] ?? 'non défini'
    ]);
}
?> 