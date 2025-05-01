<?php
/**
 * API - Vérifier l'état de la session utilisateur
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
$logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Récupérer le nom d'utilisateur si disponible
$user_name = null;
if ($logged_in && isset($_SESSION['username'])) {
    $user_name = $_SESSION['username'];
} else if ($logged_in) {
    // Inclure les fonctions et la connexion à la base de données pour récupérer le nom d'utilisateur
    try {
        require_once __DIR__ . '/../../config/database.php';
        require_once __DIR__ . '/../includes/functions.php';
        
        // Récupérer les infos utilisateur
        $user_info = get_user_info($_SESSION['user_id']);
        if ($user_info) {
            $user_name = $user_info['full_name'] ?: $user_info['username'];
        }
    } catch (Exception $e) {
        // Ignorer les erreurs
    }
}

// Renvoyer les informations de session
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'logged_in' => $logged_in,
    'session_id' => session_id(),
    'user_id' => $logged_in ? $_SESSION['user_id'] : null,
    'user_name' => $user_name,
    'session_data' => array_map(function($value) {
        // Filtrer les données sensibles (mot de passe, etc.)
        return is_string($value) ? (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) : gettype($value);
    }, $_SESSION)
]);
exit; 