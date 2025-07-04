<?php
/**
 * Configuration des sous-domaines pour GeekBoard
 * Ce fichier gère la détection automatique du magasin basé sur le sous-domaine
 * Version compatible avec SubdomainDatabaseDetector
 */

// Inclure notre système de détection si pas déjà fait
if (!class_exists('SubdomainDatabaseDetector')) {
    require_once __DIR__ . '/subdomain_database_detector.php';
}

// Fonction pour détecter le magasin basé sur le sous-domaine
function detectShopFromSubdomain() {
    try {
        // Créer une instance du détecteur
        $subdomain_detector = new SubdomainDatabaseDetector();
        
        // Utiliser notre nouveau système de détection
        $shop_info = $subdomain_detector->getCurrentShopInfo();
        
        if ($shop_info) {
            return [
                'shop_id' => $shop_info['id'],
                'shop_name' => $shop_info['name'],
                'subdomain' => $shop_info['subdomain'],
                'db_name' => $shop_info['db_name']
            ];
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Erreur détection sous-domaine: " . $e->getMessage());
        return null;
    }
}

// Fonction pour configurer la session avec le magasin détecté
function configureShopSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $shop_data = detectShopFromSubdomain();
    
    if ($shop_data) {
        $_SESSION['shop_id'] = $shop_data['shop_id'];
        $_SESSION['shop_name'] = $shop_data['shop_name'];
        $_SESSION['current_subdomain'] = $shop_data['subdomain'];
        $_SESSION['current_database'] = $shop_data['db_name'];
        
        // Log pour debugging (commenté pour éviter les conflits de redirection)
        // error_log("Session configurée pour magasin: {$shop_data['shop_name']} (ID: {$shop_data['shop_id']})");
    }
    
    return $shop_data;
}

// Exécuter la configuration automatiquement
$current_shop = configureShopSession();

// Variables globales pour compatibilité
if ($current_shop) {
    $GLOBALS['current_shop_id'] = $current_shop['shop_id'];
    $GLOBALS['current_shop_name'] = $current_shop['shop_name'];
    $GLOBALS['current_subdomain'] = $current_shop['subdomain'];
}
?> 
/**
 * Configuration des sous-domaines pour GeekBoard
 * Ce fichier gère la détection automatique du magasin basé sur le sous-domaine
 * Version compatible avec SubdomainDatabaseDetector
 */

// Inclure notre système de détection si pas déjà fait
if (!class_exists('SubdomainDatabaseDetector')) {
    require_once __DIR__ . '/subdomain_database_detector.php';
}

// Fonction pour détecter le magasin basé sur le sous-domaine
function detectShopFromSubdomain() {
    try {
        // Créer une instance du détecteur
        $subdomain_detector = new SubdomainDatabaseDetector();
        
        // Utiliser notre nouveau système de détection
        $shop_info = $subdomain_detector->getCurrentShopInfo();
        
        if ($shop_info) {
            return [
                'shop_id' => $shop_info['id'],
                'shop_name' => $shop_info['name'],
                'subdomain' => $shop_info['subdomain'],
                'db_name' => $shop_info['db_name']
            ];
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Erreur détection sous-domaine: " . $e->getMessage());
        return null;
    }
}

// Fonction pour configurer la session avec le magasin détecté
function configureShopSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $shop_data = detectShopFromSubdomain();
    
    if ($shop_data) {
        $_SESSION['shop_id'] = $shop_data['shop_id'];
        $_SESSION['shop_name'] = $shop_data['shop_name'];
        $_SESSION['current_subdomain'] = $shop_data['subdomain'];
        $_SESSION['current_database'] = $shop_data['db_name'];
        
        // Log pour debugging (commenté pour éviter les conflits de redirection)
        // error_log("Session configurée pour magasin: {$shop_data['shop_name']} (ID: {$shop_data['shop_id']})");
    }
    
    return $shop_data;
}

// Exécuter la configuration automatiquement
$current_shop = configureShopSession();

// Variables globales pour compatibilité
if ($current_shop) {
    $GLOBALS['current_shop_id'] = $current_shop['shop_id'];
    $GLOBALS['current_shop_name'] = $current_shop['shop_name'];
    $GLOBALS['current_subdomain'] = $current_shop['subdomain'];
}
?> 