<?php
/**
 * Fichier de gestion d'une clé d'authentification alternative
 * Cette solution permet de s'authentifier même si les sessions ne fonctionnent pas correctement
 */

// Fonction pour générer une clé d'authentification
function generate_auth_key() {
    // Clé basée sur adresse IP et user agent
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $salt = 'MdGeekTopSecretKey2023!'; // Clé secrète pour renforcer la sécurité
    
    // Date au format YYYYMMDD pour avoir une clé qui expire chaque jour
    $date = date('Ymd');
    
    // Générer un hash à partir de ces informations
    $auth_key = hash('sha256', $ip . $user_agent . $salt . $date);
    
    return $auth_key;
}

// Fonction pour valider une clé d'authentification
function is_valid_auth_key($key) {
    // Générer la clé attendue
    $expected_key = generate_auth_key();
    
    // Vérifier que la clé fournie correspond
    return $key === $expected_key;
}

// Si ce fichier est appelé directement, générer et renvoyer une clé d'authentification
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    // Vérifier si l'utilisateur est connecté via session
    session_start();
    $is_session_valid = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'auth_key' => generate_auth_key(),
        'has_session' => $is_session_valid,
        'session_id' => session_id(),
        'timestamp' => time()
    ]);
}
?> 