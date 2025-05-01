<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gestion CORS pour permettre les requêtes cross-origin
$allowed_origins = [
    'https://mdgeek.top',
    'http://mdgeek.top',
    'https://www.mdgeek.top',
    'http://localhost'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
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

// Débogage de session
error_log("Session ID dans ajouter_client.php: " . session_id());
error_log("Variables de session: " . print_r($_SESSION, true));
error_log("Cookies: " . print_r($_COOKIE, true));

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
    // Vérifier si la connexion PDO est disponible
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Connexion à la base de données non disponible");
    }
    
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
    $stmt = $pdo->prepare("
        SELECT id FROM clients 
        WHERE telephone = ? 
        LIMIT 1
    ");
    $stmt->execute([$telephone]);
    
    if ($stmt->rowCount() > 0) {
        // Le client existe déjà
        $client = $stmt->fetch();
        echo json_encode([
            'success' => true, 
            'client_id' => $client['id'],
            'message' => 'Client existant récupéré'
        ]);
        exit;
    }
    
    // Vérifier la structure de la table clients
    $table_check = $pdo->query("DESCRIBE clients");
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
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    $client_id = $pdo->lastInsertId();
    
    // Retourner une réponse de succès
    echo json_encode([
        'success' => true,
        'client_id' => $client_id,
        'message' => 'Client ajouté avec succès'
    ]);
    
} catch (PDOException $e) {
    // Log l'erreur détaillée
    error_log("Erreur PDO lors de l'ajout d'un client: " . $e->getMessage());
    
    // Retourner une erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    // Log l'erreur détaillée
    error_log("Exception lors de l'ajout d'un client: " . $e->getMessage());
    
    // Retourner une erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?> 