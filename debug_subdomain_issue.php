<?php
/**
 * Script de diagnostic pour identifier le problème des pages blanches
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostic Système Multi-Magasin</h1>";

echo "<h2>1. Test de base PHP</h2>";
echo "✅ PHP fonctionne<br>";

echo "<h2>2. Variables serveur</h2>";
echo "<strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'non défini') . "<br>";
echo "<strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'non défini') . "<br>";
echo "<strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'non défini') . "<br>";

echo "<h2>3. Test inclusion config/database.php</h2>";
try {
    require_once '/var/www/mdgeek.top/config/database.php';
    echo "✅ config/database.php inclus avec succès<br>";
} catch (Exception $e) {
    echo "❌ Erreur config/database.php: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Test SubdomainDatabaseDetector</h2>";
try {
    if (class_exists('SubdomainDatabaseDetector')) {
        echo "✅ Classe SubdomainDatabaseDetector disponible<br>";
        
        $detector = new SubdomainDatabaseDetector();
        $subdomain = $detector->detectSubdomain();
        echo "<strong>Sous-domaine détecté:</strong> '$subdomain'<br>";
        
        $config = $detector->getDatabaseConfig();
        echo "<strong>Configuration DB:</strong> " . json_encode($config) . "<br>";
        
    } else {
        echo "❌ Classe SubdomainDatabaseDetector non trouvée<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur SubdomainDatabaseDetector: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Test connexion base de données</h2>";
try {
    if (function_exists('getShopDBConnection')) {
        echo "✅ Fonction getShopDBConnection disponible<br>";
        
        $pdo = getShopDBConnection();
        if ($pdo) {
            echo "✅ Connexion base de données réussie<br>";
            
            $stmt = $pdo->query("SELECT DATABASE() as db");
            $result = $stmt->fetch();
            echo "<strong>Base connectée:</strong> " . $result['db'] . "<br>";
        } else {
            echo "❌ Connexion base de données échouée<br>";
        }
    } else {
        echo "❌ Fonction getShopDBConnection non disponible<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur connexion DB: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Test session</h2>";
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    echo "✅ Session démarrée<br>";
    echo "<strong>Session ID:</strong> " . session_id() . "<br>";
} catch (Exception $e) {
    echo "❌ Erreur session: " . $e->getMessage() . "<br>";
}

echo "<h2>7. Test fichier index.php</h2>";
if (file_exists('/var/www/mdgeek.top/index.php')) {
    echo "✅ index.php existe<br>";
    echo "<strong>Taille:</strong> " . filesize('/var/www/mdgeek.top/index.php') . " bytes<br>";
} else {
    echo "❌ index.php n'existe pas<br>";
}

echo "<h2>8. Test pages importantes</h2>";
$important_files = [
    '/var/www/mdgeek.top/pages/login.php',
    '/var/www/mdgeek.top/pages/accueil.php',
    '/var/www/mdgeek.top/includes/config.php'
];

foreach ($important_files as $file) {
    if (file_exists($file)) {
        echo "✅ " . basename($file) . " existe<br>";
    } else {
        echo "❌ " . basename($file) . " MANQUANT<br>";
    }
}

echo "<h2>9. Contenu début index.php</h2>";
if (file_exists('/var/www/mdgeek.top/index.php')) {
    $content = file_get_contents('/var/www/mdgeek.top/index.php');
    echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "...</pre>";
}

echo "<p><strong>Diagnostic terminé</strong> - " . date('Y-m-d H:i:s') . "</p>";
?> 
/**
 * Script de diagnostic pour identifier le problème des pages blanches
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostic Système Multi-Magasin</h1>";

echo "<h2>1. Test de base PHP</h2>";
echo "✅ PHP fonctionne<br>";

echo "<h2>2. Variables serveur</h2>";
echo "<strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'non défini') . "<br>";
echo "<strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'non défini') . "<br>";
echo "<strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'non défini') . "<br>";

echo "<h2>3. Test inclusion config/database.php</h2>";
try {
    require_once '/var/www/mdgeek.top/config/database.php';
    echo "✅ config/database.php inclus avec succès<br>";
} catch (Exception $e) {
    echo "❌ Erreur config/database.php: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Test SubdomainDatabaseDetector</h2>";
try {
    if (class_exists('SubdomainDatabaseDetector')) {
        echo "✅ Classe SubdomainDatabaseDetector disponible<br>";
        
        $detector = new SubdomainDatabaseDetector();
        $subdomain = $detector->detectSubdomain();
        echo "<strong>Sous-domaine détecté:</strong> '$subdomain'<br>";
        
        $config = $detector->getDatabaseConfig();
        echo "<strong>Configuration DB:</strong> " . json_encode($config) . "<br>";
        
    } else {
        echo "❌ Classe SubdomainDatabaseDetector non trouvée<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur SubdomainDatabaseDetector: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Test connexion base de données</h2>";
try {
    if (function_exists('getShopDBConnection')) {
        echo "✅ Fonction getShopDBConnection disponible<br>";
        
        $pdo = getShopDBConnection();
        if ($pdo) {
            echo "✅ Connexion base de données réussie<br>";
            
            $stmt = $pdo->query("SELECT DATABASE() as db");
            $result = $stmt->fetch();
            echo "<strong>Base connectée:</strong> " . $result['db'] . "<br>";
        } else {
            echo "❌ Connexion base de données échouée<br>";
        }
    } else {
        echo "❌ Fonction getShopDBConnection non disponible<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur connexion DB: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Test session</h2>";
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    echo "✅ Session démarrée<br>";
    echo "<strong>Session ID:</strong> " . session_id() . "<br>";
} catch (Exception $e) {
    echo "❌ Erreur session: " . $e->getMessage() . "<br>";
}

echo "<h2>7. Test fichier index.php</h2>";
if (file_exists('/var/www/mdgeek.top/index.php')) {
    echo "✅ index.php existe<br>";
    echo "<strong>Taille:</strong> " . filesize('/var/www/mdgeek.top/index.php') . " bytes<br>";
} else {
    echo "❌ index.php n'existe pas<br>";
}

echo "<h2>8. Test pages importantes</h2>";
$important_files = [
    '/var/www/mdgeek.top/pages/login.php',
    '/var/www/mdgeek.top/pages/accueil.php',
    '/var/www/mdgeek.top/includes/config.php'
];

foreach ($important_files as $file) {
    if (file_exists($file)) {
        echo "✅ " . basename($file) . " existe<br>";
    } else {
        echo "❌ " . basename($file) . " MANQUANT<br>";
    }
}

echo "<h2>9. Contenu début index.php</h2>";
if (file_exists('/var/www/mdgeek.top/index.php')) {
    $content = file_get_contents('/var/www/mdgeek.top/index.php');
    echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "...</pre>";
}

echo "<p><strong>Diagnostic terminé</strong> - " . date('Y-m-d H:i:s') . "</p>";
?> 