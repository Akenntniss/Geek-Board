<?php
/**
 * Configuration des sessions PHP
 * Ce fichier configure les paramètres de session pour optimiser l'expérience PWA
 */

// Durée de la session en secondes (3 jours pour tous les utilisateurs)
$session_lifetime = 259200; // 3 jours

// Configurer les cookies de session pour qu'ils durent plus longtemps
ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.cookie_lifetime', $session_lifetime);

// Configurer le cookie pour qu'il soit accessible sur tous les sous-domaines
ini_set('session.cookie_domain', '');

// Pour permettre le fonctionnement sur les domaines reconnus
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

// Liste des domaines connus
$known_domains = ['mdgeek.top'];

// Recherche du domaine principal dans l'hôte
foreach ($known_domains as $domain) {
    if (strpos($host, $domain) !== false) {
        // Configurer le cookie pour ce domaine et ses sous-domaines
        ini_set('session.cookie_domain', '.' . $domain);
        break;
    }
}

// Configurer le cookie pour qu'il soit accessible uniquement via HTTP
ini_set('session.cookie_httponly', 1);

// Fixer le problème de SameSite pour les cookies de session
$is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// Configurer le cookie pour qu'il soit sécurisé si HTTPS est utilisé
ini_set('session.cookie_secure', $is_https ? 1 : 0);

// Configurer SameSite en fonction de HTTPS
if ($is_https) {
    ini_set('session.cookie_samesite', 'None');
} else {
    ini_set('session.cookie_samesite', 'Lax');
}

// Désactiver le mode strict pour les sessions (moins sécurisé mais plus compatible)
ini_set('session.use_strict_mode', 0);

// Définir le nom de la session pour identifier l'application
session_name('MDGEEK_SESSION');

// Configurer le chemin du cookie pour qu'il soit accessible sur tout le site
ini_set('session.cookie_path', '/');

// Activer l'utilisation de cookies pour stocker l'ID de session
ini_set('session.use_cookies', 1);

// Désactiver l'utilisation des IDs de session transmis par URL
ini_set('session.use_only_cookies', 1);

// Configurer le mode de régénération du cookie
ini_set('session.use_trans_sid', 0);

// Fonction pour vérifier si l'utilisateur est en mode PWA
function is_pwa_mode() {
    // Vérifier si les paramètres de test sont présents dans l'URL ou la session
    if ((isset($_GET['test_pwa']) && $_GET['test_pwa'] === 'true') || 
        (isset($_SESSION['test_pwa']) && $_SESSION['test_pwa'] === true)) {
        return true;
    }
    
    // Vérifier si l'en-tête indique une application PWA
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'com.mdgeek.pwa') {
        return true;
    }
    
    // Vérifier si l'application est en mode standalone (iOS/Android)
    if (isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] == 'navigate') {
        if (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'document') {
            return true;
        }
    }
    
    // Vérifier le User-Agent pour détecter une application ajoutée à l'écran d'accueil
    if (isset($_SERVER['HTTP_USER_AGENT']) && (
        strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile Safari') !== false || 
        strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome/') !== false)
    ) {
        // Vérifier le cookie de session PWA (défini par JavaScript côté client)
        if (isset($_COOKIE['pwa_mode']) && $_COOKIE['pwa_mode'] == 'true') {
            return true;
        }
    }
    
    return false;
}

// Si c'est un mode PWA, définir un cookie pour indiquer que c'est une session PWA
if (is_pwa_mode() || isset($_COOKIE['pwa_mode'])) {
    // Définir un cookie pour indiquer que c'est une session PWA
    setcookie('pwa_mode', 'true', time() + $session_lifetime, '/', '', $is_https, true);
}

// Démarrer la session avec les paramètres configurés
session_start();

// Inclure le gestionnaire de fuseau horaire après le démarrage de la session
require_once __DIR__ . '/timezone.php';

// Si la fonction check_remember_token existe, l'appeler pour tenter une reconnexion automatique
if (!isset($_SESSION['user_id']) && function_exists('check_remember_token')) {
    check_remember_token();
}

// Nettoyer les sessions expirées (faible probabilité pour ne pas surcharger)
if (function_exists('cleanup_sessions') && mt_rand(1, 100) <= 5) { // 5% de chance
    cleanup_sessions();
} 