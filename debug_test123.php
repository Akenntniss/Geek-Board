<?php
/**
 * Script de diagnostic spécifique pour le problème test123
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnostic test123.mdgeek.top</h1>";

// Simuler l'environnement test123
$_SERVER['HTTP_HOST'] = 'test123.mdgeek.top';

echo "<h2>1. Informations de base</h2>";
echo "<strong>HTTP_HOST simulé:</strong> " . $_SERVER['HTTP_HOST'] . "<br>";

// Test du système de détection étape par étape
echo "<h2>2. Test SubdomainDatabaseDetector</h2>";

try {
    require_once '/var/www/mdgeek.top/config/subdomain_database_detector.php';
    echo "✅ SubdomainDatabaseDetector chargé<br>";
    
    $detector = new SubdomainDatabaseDetector();
    
    // Test de détection du sous-domaine
    $subdomain = $detector->detectSubdomain();
    echo "<strong>Sous-domaine détecté:</strong> '$subdomain'<br>";
    
    // Test de la configuration DB
    echo "<h3>Configuration de base de données:</h3>";
    $config = $detector->getDatabaseConfig();
    echo "<strong>Config générée:</strong> " . json_encode($config, JSON_PRETTY_PRINT) . "<br>";
    
    // Test de connexion
    echo "<h3>Test de connexion à la base:</h3>";
    try {
        $pdo = $detector->getConnection();
        if ($pdo) {
            echo "✅ Connexion réussie<br>";
            
            $stmt = $pdo->query("SELECT DATABASE() as current_db");
            $result = $stmt->fetch();
            echo "<strong>Base connectée:</strong> " . $result['current_db'] . "<br>";
        } else {
            echo "❌ Échec de connexion<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur de connexion: " . $e->getMessage() . "<br>";
    }
    
    // Test getCurrentShopInfo
    echo "<h3>Test getCurrentShopInfo:</h3>";
    try {
        $shopInfo = $detector->getCurrentShopInfo();
        if ($shopInfo) {
            echo "✅ Informations magasin trouvées:<br>";
            echo "<pre>" . json_encode($shopInfo, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "❌ Aucune information magasin trouvée<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur getCurrentShopInfo: " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors du chargement: " . $e->getMessage() . "<br>";
}

// Test direct de la base
echo "<h2>3. Test direct base de données</h2>";

try {
    $pdo_general = new PDO(
        'mysql:host=localhost;dbname=geekboard_general',
        'root',
        'Mamanmaman01#',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Connexion directe à geekboard_general réussie<br>";
    
    // Chercher le magasin test123
    $stmt = $pdo_general->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1");
    $stmt->execute(['test123']);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop) {
        echo "✅ Magasin test123 trouvé:<br>";
        echo "<pre>" . json_encode($shop, JSON_PRETTY_PRINT) . "</pre>";
        
        // Test de connexion à la base du magasin
        echo "<h3>Test connexion à " . $shop['db_name'] . ":</h3>";
        try {
            $pdo_shop = new PDO(
                'mysql:host=localhost;dbname=' . $shop['db_name'],
                'root',
                'Mamanmaman01#',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo "✅ Connexion à " . $shop['db_name'] . " réussie<br>";
            
            $stmt = $pdo_shop->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '" . $shop['db_name'] . "'");
            $result = $stmt->fetch();
            echo "<strong>Nombre de tables:</strong> " . $result['count'] . "<br>";
            
        } catch (Exception $e) {
            echo "❌ Erreur connexion " . $shop['db_name'] . ": " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ Aucun magasin trouvé pour test123<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur connexion directe: " . $e->getMessage() . "<br>";
}

// Test des fonctions globales
echo "<h2>4. Test des fonctions globales</h2>";

try {
    require_once '/var/www/mdgeek.top/config/database.php';
    echo "✅ database.php chargé<br>";
    
    if (function_exists('getShopDBConnection')) {
        echo "✅ getShopDBConnection disponible<br>";
        
        $pdo = getShopDBConnection();
        if ($pdo) {
            echo "✅ getShopDBConnection réussie<br>";
            $stmt = $pdo->query("SELECT DATABASE() as db");
            $result = $stmt->fetch();
            echo "<strong>Base via getShopDBConnection:</strong> " . $result['db'] . "<br>";
        } else {
            echo "❌ getShopDBConnection échouée<br>";
        }
    }
    
    if (function_exists('getCurrentShopConfig')) {
        echo "✅ getCurrentShopConfig disponible<br>";
        $config = getCurrentShopConfig();
        echo "<strong>Config via getCurrentShopConfig:</strong> " . json_encode($config) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur fonctions globales: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Recommandations</h2>";
echo "<p>Si le magasin est trouvé mais la connexion échoue, vérifiez :</p>";
echo "<ul>";
echo "<li>Les permissions de la base de données</li>";
echo "<li>La structure de la base de données</li>";
echo "<li>Les paramètres de connexion</li>";
echo "</ul>";

echo "<p><strong>Diagnostic terminé</strong> - " . date('Y-m-d H:i:s') . "</p>";
?> 
/**
 * Script de diagnostic spécifique pour le problème test123
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnostic test123.mdgeek.top</h1>";

// Simuler l'environnement test123
$_SERVER['HTTP_HOST'] = 'test123.mdgeek.top';

echo "<h2>1. Informations de base</h2>";
echo "<strong>HTTP_HOST simulé:</strong> " . $_SERVER['HTTP_HOST'] . "<br>";

// Test du système de détection étape par étape
echo "<h2>2. Test SubdomainDatabaseDetector</h2>";

try {
    require_once '/var/www/mdgeek.top/config/subdomain_database_detector.php';
    echo "✅ SubdomainDatabaseDetector chargé<br>";
    
    $detector = new SubdomainDatabaseDetector();
    
    // Test de détection du sous-domaine
    $subdomain = $detector->detectSubdomain();
    echo "<strong>Sous-domaine détecté:</strong> '$subdomain'<br>";
    
    // Test de la configuration DB
    echo "<h3>Configuration de base de données:</h3>";
    $config = $detector->getDatabaseConfig();
    echo "<strong>Config générée:</strong> " . json_encode($config, JSON_PRETTY_PRINT) . "<br>";
    
    // Test de connexion
    echo "<h3>Test de connexion à la base:</h3>";
    try {
        $pdo = $detector->getConnection();
        if ($pdo) {
            echo "✅ Connexion réussie<br>";
            
            $stmt = $pdo->query("SELECT DATABASE() as current_db");
            $result = $stmt->fetch();
            echo "<strong>Base connectée:</strong> " . $result['current_db'] . "<br>";
        } else {
            echo "❌ Échec de connexion<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur de connexion: " . $e->getMessage() . "<br>";
    }
    
    // Test getCurrentShopInfo
    echo "<h3>Test getCurrentShopInfo:</h3>";
    try {
        $shopInfo = $detector->getCurrentShopInfo();
        if ($shopInfo) {
            echo "✅ Informations magasin trouvées:<br>";
            echo "<pre>" . json_encode($shopInfo, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "❌ Aucune information magasin trouvée<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur getCurrentShopInfo: " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors du chargement: " . $e->getMessage() . "<br>";
}

// Test direct de la base
echo "<h2>3. Test direct base de données</h2>";

try {
    $pdo_general = new PDO(
        'mysql:host=localhost;dbname=geekboard_general',
        'root',
        'Mamanmaman01#',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Connexion directe à geekboard_general réussie<br>";
    
    // Chercher le magasin test123
    $stmt = $pdo_general->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1");
    $stmt->execute(['test123']);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop) {
        echo "✅ Magasin test123 trouvé:<br>";
        echo "<pre>" . json_encode($shop, JSON_PRETTY_PRINT) . "</pre>";
        
        // Test de connexion à la base du magasin
        echo "<h3>Test connexion à " . $shop['db_name'] . ":</h3>";
        try {
            $pdo_shop = new PDO(
                'mysql:host=localhost;dbname=' . $shop['db_name'],
                'root',
                'Mamanmaman01#',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo "✅ Connexion à " . $shop['db_name'] . " réussie<br>";
            
            $stmt = $pdo_shop->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '" . $shop['db_name'] . "'");
            $result = $stmt->fetch();
            echo "<strong>Nombre de tables:</strong> " . $result['count'] . "<br>";
            
        } catch (Exception $e) {
            echo "❌ Erreur connexion " . $shop['db_name'] . ": " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ Aucun magasin trouvé pour test123<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur connexion directe: " . $e->getMessage() . "<br>";
}

// Test des fonctions globales
echo "<h2>4. Test des fonctions globales</h2>";

try {
    require_once '/var/www/mdgeek.top/config/database.php';
    echo "✅ database.php chargé<br>";
    
    if (function_exists('getShopDBConnection')) {
        echo "✅ getShopDBConnection disponible<br>";
        
        $pdo = getShopDBConnection();
        if ($pdo) {
            echo "✅ getShopDBConnection réussie<br>";
            $stmt = $pdo->query("SELECT DATABASE() as db");
            $result = $stmt->fetch();
            echo "<strong>Base via getShopDBConnection:</strong> " . $result['db'] . "<br>";
        } else {
            echo "❌ getShopDBConnection échouée<br>";
        }
    }
    
    if (function_exists('getCurrentShopConfig')) {
        echo "✅ getCurrentShopConfig disponible<br>";
        $config = getCurrentShopConfig();
        echo "<strong>Config via getCurrentShopConfig:</strong> " . json_encode($config) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur fonctions globales: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Recommandations</h2>";
echo "<p>Si le magasin est trouvé mais la connexion échoue, vérifiez :</p>";
echo "<ul>";
echo "<li>Les permissions de la base de données</li>";
echo "<li>La structure de la base de données</li>";
echo "<li>Les paramètres de connexion</li>";
echo "</ul>";

echo "<p><strong>Diagnostic terminé</strong> - " . date('Y-m-d H:i:s') . "</p>";
?> 