<?php
// Désactiver l'affichage d'erreurs pour éviter de corrompre le JSON
error_reporting(0);
ini_set('display_errors', 0);

// Enregistrer les erreurs dans un fichier de log
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Inclure les fichiers requis
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Fonction pour envoyer une réponse JSON et terminer le script
function send_json_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['users'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    send_json_response(false, 'Vous devez être connecté pour effectuer cette action.');
}

try {
    // Vérifier que la connexion à la base de données est établie
    if (!isset($shop_pdo) || !$shop_pdo) {
        error_log("Connexion PDO non disponible globalement, création d'une nouvelle connexion");
        
        // Recréer une connexion directement
        $db_pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        // Log la nouvelle connexion
        error_log("Nouvelle connexion PDO créée avec succès");
    } else {
        $db_pdo = $shop_pdo;
        error_log("Utilisation de la connexion PDO existante");
    }
    
    // Récupérer la liste des utilisateurs actifs
    $stmt = $db_pdo->query("SELECT id, full_name FROM users ORDER BY full_name ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les données au format JSON
    send_json_response(true, 'Utilisateurs récupérés avec succès', $users);
    
} catch (PDOException $e) {
    // Log l'erreur pour le débogage
    error_log("Erreur dans get_users.php: " . $e->getMessage());
    send_json_response(false, 'Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
} 