<?php
// Test de la connexion à la base de données pour les sous-domaines
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "TEST DES SOUS-DOMAINES ET CONNEXION BDD\n";
echo "=====================================\n\n";

// Inclure la configuration de la base de données
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
} else {
    die("Fichier de configuration de la base de données non trouvé!");
}

// Inclure la configuration des sous-domaines
if (file_exists(__DIR__ . '/config/subdomain_config.php')) {
    require_once __DIR__ . '/config/subdomain_config.php';
} else {
    echo "Fichier de configuration des sous-domaines non trouvé. Utilisation des fonctions intégrées.\n\n";
    
    // Définir les fonctions nécessaires si non disponibles
    if (!function_exists('getSubdomain')) {
        function getSubdomain($domain_base = 'mdgeek.top') {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if ($host === $domain_base) return null;
            if (strpos($host, $domain_base) !== false) {
                $subdomain = str_replace('.' . $domain_base, '', $host);
                if ($subdomain !== $host) return $subdomain;
            }
            return null;
        }
    }
    
    if (!function_exists('loadShopBySubdomain')) {
        function loadShopBySubdomain($subdomain, $pdo) {
            if (empty($subdomain) || !is_string($subdomain)) return null;
            try {
                $stmt = $pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1");
                $stmt->execute([$subdomain]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Erreur: " . $e->getMessage());
                return null;
            }
        }
    }
}

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Détection du sous-domaine
echo "INFORMATIONS SUR LE DOMAINE:\n";
echo "Hôte actuel: " . ($_SERVER['HTTP_HOST'] ?? 'Non défini') . "\n";

$subdomain = getSubdomain();
if ($subdomain) {
    echo "Sous-domaine détecté: $subdomain\n";
} else {
    echo "Aucun sous-domaine détecté (domaine principal ou format non reconnu)\n";
}

// 2. Test de connexion à la base de données principale
echo "\nCONNEXION À LA BASE DE DONNÉES PRINCIPALE:\n";
try {
    $pdo_main = getMainDBConnection();
    echo "Connexion à la base de données principale: SUCCÈS\n";
    
    // Tester si la table shops existe
    $has_shops_table = false;
    try {
        $stmt = $pdo_main->query("SHOW TABLES LIKE 'shops'");
        $has_shops_table = ($stmt->rowCount() > 0);
        echo "Table 'shops' existe: " . ($has_shops_table ? "OUI" : "NON") . "\n";
    } catch (PDOException $e) {
        echo "Erreur lors de la vérification de la table: " . $e->getMessage() . "\n";
    }
    
    // Si nous avons un sous-domaine et la table shops, essayer de charger le magasin
    if ($subdomain && $has_shops_table) {
        echo "\nRECHERCHE DU MAGASIN POUR LE SOUS-DOMAINE '$subdomain':\n";
        $shop = loadShopBySubdomain($subdomain, $pdo_main);
        
        if ($shop) {
            echo "Magasin trouvé!\n";
            echo "ID: {$shop['id']}\n";
            echo "Nom: {$shop['name']}\n";
            echo "Sous-domaine: {$shop['subdomain']}\n";
            echo "Actif: " . ($shop['active'] ? "OUI" : "NON") . "\n";
            
            // Tester la connexion à la base de données du magasin
            echo "\nTENTATIVE DE CONNEXION À LA BASE DE DONNÉES DU MAGASIN:\n";
            echo "Hôte: {$shop['db_host']}\n";
            echo "Base: {$shop['db_name']}\n";
            
            $shop_config = [
                'host' => $shop['db_host'],
                'port' => $shop['db_port'],
                'user' => $shop['db_user'],
                'pass' => $shop['db_pass'],
                'dbname' => $shop['db_name']
            ];
            
            try {
                $shop_pdo = connectToShopDB($shop_config);
                echo "Connexion à la base de données du magasin: SUCCÈS\n";
                
                // Vérifier si nous sommes bien connectés à la bonne base
                $stmt = $shop_pdo->query("SELECT DATABASE()");
                $current_db = $stmt->fetchColumn();
                echo "Base de données actuelle: $current_db\n";
                
                if ($current_db === $shop['db_name']) {
                    echo "Confirmation: nous sommes bien connectés à la base du magasin\n";
                } else {
                    echo "ATTENTION: nous sommes connectés à '$current_db' au lieu de '{$shop['db_name']}'\n";
                }
                
                // Configuration des sessions
                echo "\nCONFIGURATION DE LA SESSION:\n";
                
                // Configurer la session pour ce magasin
                $_SESSION['shop_id'] = $shop['id'];
                $_SESSION['shop_name'] = $shop['name'];
                $_SESSION['shop_subdomain'] = $shop['subdomain'];
                
                echo "Session mise à jour avec les informations du magasin\n";
                echo "shop_id = {$_SESSION['shop_id']}\n";
                echo "shop_name = {$_SESSION['shop_name']}\n";
                echo "shop_subdomain = {$_SESSION['shop_subdomain']}\n";
                
                // Tester la fonction getShopDBConnection
                echo "\nTEST DE LA FONCTION getShopDBConnection():\n";
                $conn = getShopDBConnection();
                $stmt = $conn->query("SELECT DATABASE()");
                $db = $stmt->fetchColumn();
                echo "Base de données retournée: $db\n";
                
                if ($db === $shop['db_name']) {
                    echo "OK: getShopDBConnection() retourne la bonne base de données\n";
                } else {
                    echo "ERREUR: getShopDBConnection() ne retourne pas la bonne base de données\n";
                }
                
            } catch (Exception $e) {
                echo "ERREUR de connexion à la base du magasin: " . $e->getMessage() . "\n";
            }
            
        } else {
            echo "Aucun magasin trouvé pour le sous-domaine '$subdomain'\n";
            echo "Vérifiez que le sous-domaine est correctement configuré dans la base de données\n";
        }
    } elseif (!$subdomain) {
        echo "\nAUCUN SOUS-DOMAINE DÉTECTÉ - AFFICHAGE DES MAGASINS DISPONIBLES:\n";
        
        try {
            $stmt = $pdo_main->query("SELECT id, name, subdomain FROM shops WHERE subdomain IS NOT NULL AND active = 1");
            $shops = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($shops) > 0) {
                echo "Magasins avec sous-domaines configurés:\n";
                foreach ($shops as $s) {
                    echo "- {$s['name']} (ID: {$s['id']}): {$s['subdomain']}.mdgeek.top\n";
                }
            } else {
                echo "Aucun magasin avec sous-domaine n'a été configuré\n";
            }
        } catch (PDOException $e) {
            echo "Erreur lors de la récupération des magasins: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERREUR de connexion à la base de données principale: " . $e->getMessage() . "\n";
}

echo "\nTEST TERMINÉ\n";
?> 