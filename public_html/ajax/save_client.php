<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Le contenu peut être envoyé soit en x-www-form-urlencoded soit en JSON
// Détecter comment les données sont envoyées et les récupérer en conséquence
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if (strpos($contentType, 'application/json') !== false) {
    // Récupérer le JSON du corps de la requête
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Vérifier si le décodage a réussi
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'Erreur de décodage JSON: ' . json_last_error_msg()]);
        exit;
    }
    
    // Extraire les données du JSON
    $nom = isset($data['nom']) ? $data['nom'] : '';
    $prenom = isset($data['prenom']) ? $data['prenom'] : '';
    $telephone = isset($data['telephone']) ? $data['telephone'] : '';
    $email = isset($data['email']) ? $data['email'] : '';
    $adresse = isset($data['adresse']) ? $data['adresse'] : '';
} else {
    // Récupérer les données du formulaire standard POST
    $nom = isset($_POST['nom']) ? $_POST['nom'] : '';
    $prenom = isset($_POST['prenom']) ? $_POST['prenom'] : '';
    $telephone = isset($_POST['telephone']) ? $_POST['telephone'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $adresse = isset($_POST['adresse']) ? $_POST['adresse'] : '';
}

// Vérifier que les données requises sont fournies
if (empty($nom) || empty($prenom) || empty($telephone)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

// Nettoyer les données
$nom = cleanInput($nom);
$prenom = cleanInput($prenom);
$telephone = cleanInput($telephone);
$email = cleanInput($email);
$adresse = cleanInput($adresse);

// Définir le type de contenu avant toute sortie
header('Content-Type: application/json');

try {
    // Vérifier si la connexion PDO est disponible
    if (!isset($shop_pdo) || !($shop_pdo instanceof PDO)) {
        throw new Exception("Connexion à la base de données non disponible");
    }
    
    // Déboguer les paramètres
    error_log("Ajout client (save_client.php) - Paramètres : " . json_encode([
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
        echo json_encode([
            'success' => true, 
            'client_id' => $client['id'],
            'message' => 'Client existant récupéré'
        ]);
        exit;
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
    
    if (in_array('date_creation', $columns)) {
        $fields[] = 'date_creation';
        $values[] = date('Y-m-d H:i:s');
    }
    
    $sql = "INSERT INTO clients (" . implode(', ', $fields) . ") VALUES (" . str_repeat('?,', count($fields) - 1) . "?)";
    error_log("Requête SQL : " . $sql);
    
    // Insérer le nouveau client
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($values);
    $client_id = $shop_pdo->lastInsertId();
    
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