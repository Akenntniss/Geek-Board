<?php
/**
 * Configuration pour la gestion des sous-domaines
 * Ce fichier détecte le sous-domaine et configure l'application en conséquence
 */

if (!function_exists('getSubdomain')) {
    /**
     * Fonction pour récupérer le sous-domaine à partir de l'hôte actuel
     * 
     * @param string $domain_base Domaine de base (ex: geekboard.fr)
     * @return string|null Le sous-domaine ou null si non trouvé
     */
    function getSubdomain($domain_base = 'mdgeek.top') {
        // Récupérer l'hôte depuis le serveur
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Si l'hôte correspond exactement au domaine de base, il n'y a pas de sous-domaine
        if ($host === $domain_base) {
            return null;
        }
        
        // Vérifier si l'hôte contient le domaine de base
        if (strpos($host, $domain_base) !== false) {
            // Récupérer le sous-domaine en supprimant le domaine de base
            $subdomain = str_replace('.' . $domain_base, '', $host);
            
            // Vérifier si c'est bien un sous-domaine et pas juste une variante du domaine
            if ($subdomain !== $host) {
                return $subdomain;
            }
        }
        
        return null;
    }
}

if (!function_exists('loadShopBySubdomain')) {
    /**
     * Fonction pour charger le magasin correspondant au sous-domaine
     * 
     * @param string $subdomain Le sous-domaine à rechercher
     * @param PDO $pdo Connexion à la base de données principale
     * @return array|null Données du magasin ou null si non trouvé
     */
    function loadShopBySubdomain($subdomain, $pdo) {
        // Valider le sous-domaine
        if (empty($subdomain) || !is_string($subdomain)) {
            return null;
        }
        
        try {
            // Préparer la requête pour récupérer le magasin
            $stmt = $pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1");
            $stmt->execute([$subdomain]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($shop) {
                // Journaliser pour le débogage
                error_log("Magasin trouvé pour le sous-domaine '{$subdomain}': ID={$shop['id']}, Nom={$shop['name']}");
                return $shop;
            } else {
                error_log("Aucun magasin trouvé pour le sous-domaine: {$subdomain}");
                return null;
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du magasin par sous-domaine: " . $e->getMessage());
            return null;
        }
    }
}

// Si ce fichier est inclus avant le démarrage de la session, on démarre la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer le sous-domaine
$current_subdomain = getSubdomain();

// Si un sous-domaine est détecté et qu'on n'est pas déjà en mode superadmin
if ($current_subdomain && (!isset($_SESSION['superadmin_mode']) || $_SESSION['superadmin_mode'] !== true)) {
    // Inclure la configuration de la base de données si nécessaire
    if (!function_exists('getMainDBConnection')) {
        require_once __DIR__ . '/database.php';
    }
    
    // Obtenir une connexion à la base de données principale
    $pdo_main = getMainDBConnection();
    
    // Charger le magasin correspondant au sous-domaine
    $shop = loadShopBySubdomain($current_subdomain, $pdo_main);
    
    // Si un magasin est trouvé, configurer la session
    if ($shop) {
        $_SESSION['shop_id'] = $shop['id'];
        $_SESSION['shop_name'] = $shop['name'];
        $_SESSION['shop_subdomain'] = $shop['subdomain'];
        
        // Définir un cookie pour le sous-domaine
        setcookie('current_shop', $shop['id'], time() + 86400 * 30, '/', '.' . $_SERVER['HTTP_HOST']);
        
        error_log("Session configurée pour le magasin: {$shop['name']} (ID: {$shop['id']})");
    } else {
        // Le sous-domaine ne correspond à aucun magasin
        error_log("Sous-domaine non reconnu: {$current_subdomain}");
        
        // Option 1: Rediriger vers la page d'erreur
        // header('Location: /templates/shop_not_found.php');
        // exit;
        
        // Option 2: Continuer sans définir de magasin (utilisera la base principale)
    }
} else {
    // Pas de sous-domaine détecté ou mode superadmin actif
    error_log("Pas de sous-domaine détecté ou mode superadmin actif");
}
?> 