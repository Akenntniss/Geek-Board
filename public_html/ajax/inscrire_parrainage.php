<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once '../config/database.php';
require_once '../includes/functions.php';

// Fonction pour générer un code de parrainage unique
function generateReferralCode($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Démarrer ou récupérer la session existante
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
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

// Vérifier que les données requises sont fournies
if (!isset($input_data['client_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'L\'ID du client est requis'
    ]);
    exit;
}

try {
    // Vérifier si la connexion PDO est disponible
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Connexion à la base de données non disponible");
    }
    
    // Récupérer l'ID du client
    $client_id = (int)$input_data['client_id'];
    
    // Vérifier que le client existe
    $stmt = $pdo->prepare("SELECT id, inscrit_parrainage FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        echo json_encode([
            'success' => false,
            'message' => 'Client non trouvé'
        ]);
        exit;
    }
    
    // Vérifier si le client est déjà inscrit
    if ($client['inscrit_parrainage']) {
        echo json_encode([
            'success' => true,
            'message' => 'Ce client est déjà inscrit au programme de parrainage'
        ]);
        exit;
    }
    
    // Générer un code de parrainage unique
    $code_parrainage = generateReferralCode();
    
    // Vérifier que le code est unique
    $is_unique = false;
    $max_attempts = 10;
    $attempts = 0;
    
    while (!$is_unique && $attempts < $max_attempts) {
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE code_parrainage = ?");
        $stmt->execute([$code_parrainage]);
        
        if ($stmt->rowCount() === 0) {
            $is_unique = true;
        } else {
            $code_parrainage = generateReferralCode();
            $attempts++;
        }
    }
    
    if (!$is_unique) {
        throw new Exception("Impossible de générer un code de parrainage unique après $max_attempts tentatives");
    }
    
    // Inscrire le client au programme
    $stmt = $pdo->prepare("
        UPDATE clients 
        SET inscrit_parrainage = TRUE, 
            code_parrainage = ?, 
            date_inscription_parrainage = NOW() 
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$code_parrainage, $client_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Client inscrit au programme de parrainage avec succès',
            'code_parrainage' => $code_parrainage
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de l\'inscription du client au programme de parrainage'
        ]);
    }
    
} catch (PDOException $e) {
    // Log l'erreur détaillée
    error_log("Erreur PDO lors de l'inscription au programme de parrainage: " . $e->getMessage());
    
    // Retourner une erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    // Log l'erreur détaillée
    error_log("Exception lors de l'inscription au programme de parrainage: " . $e->getMessage());
    
    // Retourner une erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
} 