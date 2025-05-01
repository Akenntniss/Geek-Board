<?php
/**
 * Configuration des sessions pour l'application
 */

// Paramètres des cookies de session
ini_set('session.cookie_httponly', 1); // Empêcher l'accès via JavaScript
ini_set('session.use_only_cookies', 1); // Utiliser uniquement des cookies pour les sessions
ini_set('session.cookie_secure', 0);    // Désactivé pour le développement, activer en production (HTTPS)
ini_set('session.cookie_samesite', 'Lax'); // Protection contre les attaques CSRF

// Durée de vie de la session (2 heures)
ini_set('session.gc_maxlifetime', 7200);
session_set_cookie_params(7200);

// Chemin de stockage des sessions
$session_path = dirname(__DIR__) . '/tmp/sessions';
if (!file_exists($session_path)) {
    mkdir($session_path, 0755, true);
}
ini_set('session.save_path', $session_path);

// Démarrer ou reprendre la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Régénérer l'ID de session périodiquement pour éviter la fixation de session
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} 