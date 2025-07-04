<?php
// ===================================================
// CONFIGURATION GEEKBOARD MULTI-MAGASIN - DATABASE
// ===================================================

// Configuration de la base de données générale (pour l'interface super admin)
define('GENERAL_DB_HOST', 'localhost');
define('GENERAL_DB_PORT', '3306');
define('GENERAL_DB_NAME', 'geekboard_general');
define('GENERAL_DB_USER', 'geekboard_user');
define('GENERAL_DB_PASS', 'GeekBoard2024#');

// Configuration pour le multi-magasin
// Les magasins auront des bases de données nommées : geekboard_{nom_magasin}

// Variables globales pour les connexions PDO
$main_pdo = null;   // Connexion à la base principale (équivalent général)
$shop_pdo = null;   // Connexion à la base du magasin actuel

// Configuration pour les tentatives de connexion
$max_attempts = 3;  // Nombre maximum de tentatives
$wait_time = 2;     // Temps d'attente initial (secondes)

// Fonction pour le débogage des opérations de base de données
function dbDebugLog($message) {
    // Activer le journal de débogage DB pour diagnostiquer les problèmes
    $debug_enabled = true;
    
    if ($debug_enabled) {
        // Ajouter un horodatage
        $timestamp = date('Y-m-d H:i:s');
        $formatted_message = "[{$timestamp}] DB_LOCAL: {$message}";
        error_log($formatted_message);
    }
}

/**
 * Fonction pour obtenir la connexion à la base générale
 */
function getGeneralDBConnection() {
    static $general_pdo = null;
    
    if ($general_pdo === null) {
        try {
            dbDebugLog("Tentative de connexion à la base générale: " . GENERAL_DB_NAME);
            
            $dsn = "mysql:host=" . GENERAL_DB_HOST . ";port=" . GENERAL_DB_PORT . ";dbname=" . GENERAL_DB_NAME . ";charset=utf8mb4";
            $general_pdo = new PDO($dsn, GENERAL_DB_USER, GENERAL_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            dbDebugLog("✅ Connexion réussie à la base générale");
        } catch (PDOException $e) {
            dbDebugLog("❌ Erreur connexion base générale: " . $e->getMessage());
            throw $e;
        }
    }
    
    return $general_pdo;
}

/**
 * Détecte automatiquement le nom du magasin depuis le sous-domaine
 */
function detectShopFromSubdomain() {
    // Vérifier d'abord la variable d'environnement FastCGI (depuis Nginx)
    if (isset($_SERVER['SHOP_SUBDOMAIN']) && !empty($_SERVER['SHOP_SUBDOMAIN'])) {
        dbDebugLog("Magasin détecté via FastCGI: " . $_SERVER['SHOP_SUBDOMAIN']);
        return $_SERVER['SHOP_SUBDOMAIN'];
    }
    
    // Sinon, analyser l'en-tête HTTP_HOST
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        dbDebugLog("Analyse de HTTP_HOST: " . $host);
        
        // Retirer le www. si présent
        $host = preg_replace('/^www\./', '', $host);
        
        // Extraire le sous-domaine
        if (preg_match('/^([^.]+)\.mdgeek\.top$/', $host, $matches)) {
            $shop = $matches[1];
            dbDebugLog("Sous-domaine détecté: " . $shop);
            return $shop;
        }
    }
    
    dbDebugLog("Aucun sous-domaine détecté, utilisation de 'cannesphones' par défaut");
    return 'cannesphones'; // Magasin par défaut
}

/**
 * Obtient les informations du magasin depuis la base générale
 */
function getShopInfo($shop_subdomain) {
    try {
        $general_pdo = getGeneralDBConnection();
        
        dbDebugLog("Recherche des informations pour le magasin: " . $shop_subdomain);
        
        $stmt = $general_pdo->prepare("SELECT * FROM shops WHERE subdomain = :subdomain LIMIT 1");
        $stmt->execute(['subdomain' => $shop_subdomain]);
        $shop = $stmt->fetch();
        
        if ($shop) {
            dbDebugLog("✅ Magasin trouvé: " . $shop['name']);
            return $shop;
        } else {
            dbDebugLog("❌ Magasin non trouvé pour subdomain: " . $shop_subdomain);
            return null;
        }
    } catch (Exception $e) {
        dbDebugLog("❌ Erreur lors de la recherche du magasin: " . $e->getMessage());
        return null;
    }
}

/**
 * Crée une connexion à la base de données d'un magasin spécifique
 */
function connectToShopDB($shop_name) {
    try {
        // Construire le nom de la base de données
        $db_name = "geekboard_" . $shop_name;
        
        dbDebugLog("Tentative de connexion à la base du magasin: " . $db_name);
        
        $dsn = "mysql:host=localhost;port=3306;dbname=" . $db_name . ";charset=utf8mb4";
        $pdo = new PDO($dsn, GENERAL_DB_USER, GENERAL_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        dbDebugLog("✅ Connexion réussie à la base du magasin: " . $db_name);
        return $pdo;
        
    } catch (PDOException $e) {
        dbDebugLog("❌ Erreur connexion base magasin " . $db_name . ": " . $e->getMessage());
        throw $e;
    }
}

/**
 * Fonction principale pour obtenir la connexion à la base du magasin actuel
 */
function getShopDBConnection() {
    static $shop_pdo = null;
    
    if ($shop_pdo === null) {
        try {
            // Détecter le magasin depuis le sous-domaine
            $shop_subdomain = detectShopFromSubdomain();
            
            if (!$shop_subdomain) {
                throw new PDOException("Impossible de détecter le magasin depuis le sous-domaine");
            }
            
            // Obtenir les informations du magasin
            $shop_info = getShopInfo($shop_subdomain);
            
            if (!$shop_info) {
                throw new PDOException("Magasin non trouvé pour le sous-domaine: " . $shop_subdomain);
            }
            
            // Établir la connexion
            $shop_pdo = connectToShopDB($shop_subdomain);
            
            dbDebugLog("✅ Connexion établie pour le magasin: " . $shop_info['name']);
            
        } catch (Exception $e) {
            dbDebugLog("❌ Erreur lors de la connexion au magasin: " . $e->getMessage());
            throw new PDOException("Erreur lors de la connexion au magasin: " . $e->getMessage());
        }
    }
    
    return $shop_pdo;
}

/**
 * Fonction pour créer une nouvelle base de données de magasin
 */
function createShopDatabase($shop_name) {
    try {
        $db_name = "geekboard_" . $shop_name;
        
        // Connexion en tant que root pour créer la base
        $root_pdo = new PDO("mysql:host=localhost;port=3306;charset=utf8mb4", 'root', 'Mamanmaman01#');
        $root_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer la base de données
        $root_pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Donner les permissions à l'utilisateur geekboard_user
        $root_pdo->exec("GRANT ALL PRIVILEGES ON `$db_name`.* TO 'geekboard_user'@'localhost'");
        $root_pdo->exec("FLUSH PRIVILEGES");
        
        dbDebugLog("✅ Base de données créée: " . $db_name);
        
        return true;
        
    } catch (Exception $e) {
        dbDebugLog("❌ Erreur création base: " . $e->getMessage());
        throw $e;
    }
}

// Fonction de compatibilité avec l'ancien système (équivalent à getShopDBConnection)
function getMainDBConnection() {
    return getShopDBConnection();
}

// Alias pour la compatibilité
$main_pdo = function() {
    return getShopDBConnection();
};

dbDebugLog("🔧 Configuration database.php LOCAL chargée");
?> 