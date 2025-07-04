<?php
// ===================================================
// CONFIGURATION GEEKBOARD MULTI-MAGASIN
// ===================================================

// Configuration de la base de données générale (pour l'interface super admin)
define('GENERAL_DB_HOST', 'localhost');
define('GENERAL_DB_PORT', '3306');
define('GENERAL_DB_NAME', 'geekboard_general');
define('GENERAL_DB_USER', 'geekboard_user');
define('GENERAL_DB_PASS', 'GeekBoard2024#');

// Configuration pour le multi-magasin
// Les magasins auront des bases de données nommées : geekboard_{nom_magasin}

/**
 * Fonction pour obtenir la connexion à la base générale
 */
function getGeneralDBConnection() {
    static $general_pdo = null;
    
    if ($general_pdo === null) {
        try {
            $dsn = "mysql:host=" . GENERAL_DB_HOST . ";port=" . GENERAL_DB_PORT . ";dbname=" . GENERAL_DB_NAME . ";charset=utf8mb4";
            $general_pdo = new PDO($dsn, GENERAL_DB_USER, GENERAL_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch(PDOException $e) {
            error_log("Erreur connexion BDD générale: " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données générale");
        }
    }
    
    return $general_pdo;
}

/**
 * Fonction pour obtenir la connexion à la base d'un magasin spécifique
 */
function getShopDBConnection($shop_name = null) {
    static $shop_connections = [];
    
    // Déterminer le nom du magasin
    if ($shop_name === null) {
        // Essayer de détecter depuis le sous-domaine
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            if (preg_match('/^([^.]+)\.mdgeek\.top$/', $host, $matches)) {
                $shop_name = $matches[1];
            } elseif (isset($_SESSION['shop_name'])) {
                $shop_name = $_SESSION['shop_name'];
            } else {
                // Par défaut, utiliser cannesphones pour la compatibilité
                $shop_name = 'cannesphones';
            }
        } else {
            $shop_name = 'cannesphones';
        }
    }
    
    // Normaliser le nom du magasin
    $shop_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $shop_name));
    
    if (!isset($shop_connections[$shop_name])) {
        try {
            $db_name = "geekboard_" . $shop_name;
            $dsn = "mysql:host=" . GENERAL_DB_HOST . ";port=" . GENERAL_DB_PORT . ";dbname=" . $db_name . ";charset=utf8mb4";
            
            $shop_connections[$shop_name] = new PDO($dsn, GENERAL_DB_USER, GENERAL_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
            // Log pour debugging
            error_log("Connexion réussie à la base: " . $db_name);
            
        } catch(PDOException $e) {
            error_log("Erreur connexion BDD magasin {$shop_name}: " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données du magasin: " . $shop_name);
        }
    }
    
    return $shop_connections[$shop_name];
}

/**
 * Fonction pour créer une nouvelle base de données pour un magasin
 */
function createShopDatabase($shop_name) {
    $shop_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $shop_name));
    $db_name = "geekboard_" . $shop_name;
    
    try {
        // Connexion en tant que root pour créer la base
        $root_dsn = "mysql:host=" . GENERAL_DB_HOST . ";port=" . GENERAL_DB_PORT . ";charset=utf8mb4";
        $root_pdo = new PDO($root_dsn, 'root', 'Mamanmaman01#');
        
        // Créer la base de données
        $root_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Donner les permissions à l'utilisateur GeekBoard
        $root_pdo->exec("GRANT ALL PRIVILEGES ON `{$db_name}`.* TO '" . GENERAL_DB_USER . "'@'localhost'");
        $root_pdo->exec("FLUSH PRIVILEGES");
        
        return true;
        
    } catch(PDOException $e) {
        error_log("Erreur création BDD magasin {$shop_name}: " . $e->getMessage());
        return false;
    }
}

// Compatibilité avec l'ancien code
try {
    $shop_pdo = getShopDBConnection();
} catch(Exception $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Variables pour la compatibilité
$pdo = $shop_pdo;

// Configuration timezone
date_default_timezone_set('Europe/Paris');

?> 