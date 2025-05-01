<?php
// API pour tester la connexion à SMS Gate
require_once '../includes/functions.php';
require_once '../database.php';

// En-têtes pour l'API
header('Content-Type: application/json');

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupération et validation des données
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$action = $data['action'] ?? '';

// Test de connexion avec les paramètres fournis
if ($action === 'test_connection') {
    $url = $data['url'] ?? '';
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $private_token = $data['private_token'] ?? '';
    
    // Vérifications de base
    if (empty($url)) {
        echo json_encode(['success' => false, 'message' => 'URL de l\'API non spécifiée']);
        exit;
    }
    
    // Vérifier l'URL (endpoint de santé)
    $health_url = rtrim($url, '/') . '/health';
    
    $curl = curl_init($health_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    
    // Ajout du token privé si fourni
    $headers = [];
    if (!empty($private_token)) {
        $headers[] = 'Private-Token: ' . $private_token;
    }
    
    if (!empty($headers)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }
    
    // Authentification si identifiants fournis
    if (!empty($username) && !empty($password)) {
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
    }
    
    // Activer le mode verbeux pour capturer les détails
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($curl, CURLOPT_STDERR, $verbose);
    curl_setopt($curl, CURLOPT_VERBOSE, true);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    // Récupérer les informations verboses
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    fclose($verbose);
    
    curl_close($curl);
    
    // Analyser le résultat
    if ($response === false) {
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur de connexion: ' . $error,
            'details' => "Impossible de se connecter à l'URL spécifiée. Vérifiez que le serveur est accessible."
        ]);
        exit;
    }
    
    if ($http_code == 200) {
        $response_data = json_decode($response, true);
        
        // Extraire la version du serveur si disponible
        $server_version = isset($response_data['version']) ? $response_data['version'] : 'Non spécifiée';
        $server_status = isset($response_data['status']) ? $response_data['status'] : 'Non spécifié';
        
        echo json_encode([
            'success' => true,
            'message' => 'Connexion réussie à SMS Gate',
            'details' => "Version du serveur: $server_version, Statut: $server_status",
            'response' => $response_data
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion (Code HTTP: ' . $http_code . ')',
        'details' => "Vérifiez l'URL et les informations d'authentification.",
        'response' => $response
    ]);
    exit;
}

// Action non reconnue
echo json_encode(['success' => false, 'message' => 'Action non reconnue']); 