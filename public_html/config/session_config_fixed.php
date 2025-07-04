<?php
/**
 * Configuration des sessions PHP
 * Ce fichier configure les paramètres de session pour optimiser l'expérience PWA
 * Version compatible avec le système multi-magasin dynamique
 */

// Durée de la session en secondes (3 jours pour tous les utilisateurs)
$session_lifetime = 259200; // 3 jours

// Configurer les cookies de session pour qu'ils durent plus longtemps
ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.cookie_lifetime', $session_lifetime);

// Pour permettre le fonctionnement sur les domaines reconnus
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

// Configuration des cookies pour multi-domaines
if (strpos($host, 'mdgeek.top') !== false) {
    // Configurer le cookie pour qu'il soit accessible sur tous les sous-domaines de mdgeek.top
    ini_set('session.cookie_domain', '.mdgeek.top');
} else {
    // Pour les autres domaines (localhost, etc.)
    ini_set('session.cookie_domain', '');
}

// Configurer la sécurité des sessions
ini_set('session.cookie_secure', '1'); // HTTPS uniquement
ini_set('session.cookie_httponly', '1'); // Protection XSS
ini_set('session.use_strict_mode', '1'); // Mode strict
ini_set('session.cookie_samesite', 'Lax'); // Protection CSRF

// Nom de session personnalisé
ini_set('session.name', 'GEEKBOARD_SESSID');

// Démarrer la session si pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuration timezone
date_default_timezone_set('Europe/Paris');

// Fonction pour nettoyer les sessions expirées
function cleanupExpiredSessions() {
    // Configurer le garbage collector pour nettoyer les sessions expirées
    if (rand(1, 100) == 1) { // 1% de chance de déclencher le nettoyage
        session_gc();
    }
}

// Exécuter le nettoyage
cleanupExpiredSessions();

// Variables de session utiles
if (!isset($_SESSION['session_start_time'])) {
    $_SESSION['session_start_time'] = time();
}

$_SESSION['last_activity'] = time();
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
$_SESSION['remote_addr'] = $_SERVER['REMOTE_ADDR'] ?? '';

// Log pour debugging (commenté pour éviter les conflits de redirection)
// error_log("Session configurée pour host: $host, ID: " . session_id());
?> 
/**
 * Configuration des sessions PHP
 * Ce fichier configure les paramètres de session pour optimiser l'expérience PWA
 * Version compatible avec le système multi-magasin dynamique
 */

// Durée de la session en secondes (3 jours pour tous les utilisateurs)
$session_lifetime = 259200; // 3 jours

// Configurer les cookies de session pour qu'ils durent plus longtemps
ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.cookie_lifetime', $session_lifetime);

// Pour permettre le fonctionnement sur les domaines reconnus
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

// Configuration des cookies pour multi-domaines
if (strpos($host, 'mdgeek.top') !== false) {
    // Configurer le cookie pour qu'il soit accessible sur tous les sous-domaines de mdgeek.top
    ini_set('session.cookie_domain', '.mdgeek.top');
} else {
    // Pour les autres domaines (localhost, etc.)
    ini_set('session.cookie_domain', '');
}

// Configurer la sécurité des sessions
ini_set('session.cookie_secure', '1'); // HTTPS uniquement
ini_set('session.cookie_httponly', '1'); // Protection XSS
ini_set('session.use_strict_mode', '1'); // Mode strict
ini_set('session.cookie_samesite', 'Lax'); // Protection CSRF

// Nom de session personnalisé
ini_set('session.name', 'GEEKBOARD_SESSID');

// Démarrer la session si pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuration timezone
date_default_timezone_set('Europe/Paris');

// Fonction pour nettoyer les sessions expirées
function cleanupExpiredSessions() {
    // Configurer le garbage collector pour nettoyer les sessions expirées
    if (rand(1, 100) == 1) { // 1% de chance de déclencher le nettoyage
        session_gc();
    }
}

// Exécuter le nettoyage
cleanupExpiredSessions();

// Variables de session utiles
if (!isset($_SESSION['session_start_time'])) {
    $_SESSION['session_start_time'] = time();
}

$_SESSION['last_activity'] = time();
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
$_SESSION['remote_addr'] = $_SERVER['REMOTE_ADDR'] ?? '';

// Log pour debugging (commenté pour éviter les conflits de redirection)
// error_log("Session configurée pour host: $host, ID: " . session_id());
?> 