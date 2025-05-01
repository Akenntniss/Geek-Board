<?php
// Inclure la configuration de session
require_once '../config/session_config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Récupérer l'ID de l'utilisateur avant de détruire la session
$user_id = $_SESSION['user_id'] ?? null;

// Si l'utilisateur est connecté, le déconnecter de toutes les sessions
if ($user_id && function_exists('logout_from_all_sessions')) {
    logout_from_all_sessions($user_id);
} else {
    // Sinon, déconnexion classique
    session_destroy();
    
    // Supprimer les cookies
    setcookie('mdgeek_remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    setcookie('pwa_mode', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    setcookie(session_name(), '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

// Rediriger vers la page de connexion
header('Location: /pages/login.php');
exit;