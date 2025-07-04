<?php
/**
 * Script de diagnostic avancé pour identifier le problème des pages blanches
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostic Avancé - Pages Blanches</h1>";

$host = $_SERVER['HTTP_HOST'] ?? 'non défini';
echo "<p><strong>HOST:</strong> $host</p>";

echo "<h2>1. Test des includes étape par étape</h2>";

echo "<h3>1.1 Test session_config.php</h3>";
try {
    ob_start();
    require_once '/var/www/mdgeek.top/config/session_config.php';
    $session_output = ob_get_clean();
    echo "✅ session_config.php chargé avec succès<br>";
    if (!empty($session_output)) {
        echo "<div style='background: #ffe6e6; padding: 10px;'>Sortie: " . htmlspecialchars($session_output) . "</div>";
    }
    echo "<strong>Session ID:</strong> " . session_id() . "<br>";
    echo "<strong>Session Status:</strong> " . session_status() . "<br>";
} catch (Exception $e) {
    echo "❌ Erreur session_config.php: " . $e->getMessage() . "<br>";
}

echo "<h3>1.2 Test subdomain_config.php</h3>";
try {
    ob_start();
    require_once '/var/www/mdgeek.top/config/subdomain_config.php';
    $subdomain_output = ob_get_clean();
    echo "✅ subdomain_config.php chargé avec succès<br>";
    if (!empty($subdomain_output)) {
        echo "<div style='background: #ffe6e6; padding: 10px;'>Sortie: " . htmlspecialchars($subdomain_output) . "</div>";
    }
    
    // Vérifier les variables de session
    echo "<strong>Session shop_id:</strong> " . ($_SESSION['shop_id'] ?? 'non défini') . "<br>";
    echo "<strong>Session shop_name:</strong> " . ($_SESSION['shop_name'] ?? 'non défini') . "<br>";
    echo "<strong>Session current_database:</strong> " . ($_SESSION['current_database'] ?? 'non défini') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Erreur subdomain_config.php: " . $e->getMessage() . "<br>";
    echo "<strong>Trace:</strong><br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>2. Test manual de l'index.php</h2>";

// Lire le début de index.php
$index_path = '/var/www/mdgeek.top/index.php';
if (file_exists($index_path)) {
    $content = file_get_contents($index_path);
    $lines = explode("\n", $content);
    
    echo "<h3>Premières lignes de index.php:</h3>";
    echo "<pre>";
    for ($i = 0; $i < min(30, count($lines)); $i++) {
        echo sprintf("%02d: %s\n", $i + 1, htmlspecialchars($lines[$i]));
    }
    echo "</pre>";
    
    // Tenter d'exécuter les premières lignes
    echo "<h3>Test d'exécution étape par étape:</h3>";
    
    echo "<strong>Étape 1:</strong> Test de l'include session_config<br>";
    $test_code = '<?php require_once "/var/www/mdgeek.top/config/session_config.php"; echo "OK session"; ?>';
    file_put_contents('/tmp/test_session.php', $test_code);
    $result = shell_exec('php /tmp/test_session.php 2>&1');
    echo "Résultat: " . htmlspecialchars($result) . "<br>";
    
    echo "<strong>Étape 2:</strong> Test de l'include subdomain_config<br>";
    $test_code = '<?php 
    require_once "/var/www/mdgeek.top/config/session_config.php"; 
    require_once "/var/www/mdgeek.top/config/subdomain_config.php"; 
    echo "OK subdomain"; 
    ?>';
    file_put_contents('/tmp/test_subdomain.php', $test_code);
    $result = shell_exec('php /tmp/test_subdomain.php 2>&1');
    echo "Résultat: " . htmlspecialchars($result) . "<br>";
}

echo "<h2>3. Test des logs d'erreur</h2>";
$error_logs = [
    '/var/log/nginx/error.log',
    '/var/log/php8.1-fpm.log',
    '/var/log/php8.2-fpm.log', 
    '/var/log/apache2/error.log',
    '/var/log/syslog'
];

foreach ($error_logs as $log_file) {
    if (file_exists($log_file) && is_readable($log_file)) {
        echo "<h3>Dernières erreurs de " . basename($log_file) . ":</h3>";
        $recent_errors = shell_exec("tail -10 $log_file | grep -i 'php\\|error\\|fatal'");
        if (!empty($recent_errors)) {
            echo "<pre>" . htmlspecialchars($recent_errors) . "</pre>";
        } else {
            echo "<p>Aucune erreur récente trouvée.</p>";
        }
    }
}

echo "<h2>4. Test de vérification PHP</h2>";
echo "<strong>Version PHP:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Modules chargés:</strong> " . implode(', ', get_loaded_extensions()) . "<br>";

// Test de syntaxe des fichiers critiques
$files_to_check = [
    '/var/www/mdgeek.top/config/session_config.php',
    '/var/www/mdgeek.top/config/subdomain_config.php',
    '/var/www/mdgeek.top/config/database.php',
    '/var/www/mdgeek.top/index.php'
];

echo "<h3>Vérification syntaxe PHP:</h3>";
foreach ($files_to_check as $file) {
    $result = shell_exec("php -l $file 2>&1");
    $status = strpos($result, 'No syntax errors') !== false ? '✅' : '❌';
    echo "$status " . basename($file) . ": " . htmlspecialchars(trim($result)) . "<br>";
}

echo "<p><strong>Diagnostic avancé terminé</strong> - " . date('Y-m-d H:i:s') . "</p>";
?> 
/**
 * Script de diagnostic avancé pour identifier le problème des pages blanches
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostic Avancé - Pages Blanches</h1>";

$host = $_SERVER['HTTP_HOST'] ?? 'non défini';
echo "<p><strong>HOST:</strong> $host</p>";

echo "<h2>1. Test des includes étape par étape</h2>";

echo "<h3>1.1 Test session_config.php</h3>";
try {
    ob_start();
    require_once '/var/www/mdgeek.top/config/session_config.php';
    $session_output = ob_get_clean();
    echo "✅ session_config.php chargé avec succès<br>";
    if (!empty($session_output)) {
        echo "<div style='background: #ffe6e6; padding: 10px;'>Sortie: " . htmlspecialchars($session_output) . "</div>";
    }
    echo "<strong>Session ID:</strong> " . session_id() . "<br>";
    echo "<strong>Session Status:</strong> " . session_status() . "<br>";
} catch (Exception $e) {
    echo "❌ Erreur session_config.php: " . $e->getMessage() . "<br>";
}

echo "<h3>1.2 Test subdomain_config.php</h3>";
try {
    ob_start();
    require_once '/var/www/mdgeek.top/config/subdomain_config.php';
    $subdomain_output = ob_get_clean();
    echo "✅ subdomain_config.php chargé avec succès<br>";
    if (!empty($subdomain_output)) {
        echo "<div style='background: #ffe6e6; padding: 10px;'>Sortie: " . htmlspecialchars($subdomain_output) . "</div>";
    }
    
    // Vérifier les variables de session
    echo "<strong>Session shop_id:</strong> " . ($_SESSION['shop_id'] ?? 'non défini') . "<br>";
    echo "<strong>Session shop_name:</strong> " . ($_SESSION['shop_name'] ?? 'non défini') . "<br>";
    echo "<strong>Session current_database:</strong> " . ($_SESSION['current_database'] ?? 'non défini') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Erreur subdomain_config.php: " . $e->getMessage() . "<br>";
    echo "<strong>Trace:</strong><br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>2. Test manual de l'index.php</h2>";

// Lire le début de index.php
$index_path = '/var/www/mdgeek.top/index.php';
if (file_exists($index_path)) {
    $content = file_get_contents($index_path);
    $lines = explode("\n", $content);
    
    echo "<h3>Premières lignes de index.php:</h3>";
    echo "<pre>";
    for ($i = 0; $i < min(30, count($lines)); $i++) {
        echo sprintf("%02d: %s\n", $i + 1, htmlspecialchars($lines[$i]));
    }
    echo "</pre>";
    
    // Tenter d'exécuter les premières lignes
    echo "<h3>Test d'exécution étape par étape:</h3>";
    
    echo "<strong>Étape 1:</strong> Test de l'include session_config<br>";
    $test_code = '<?php require_once "/var/www/mdgeek.top/config/session_config.php"; echo "OK session"; ?>';
    file_put_contents('/tmp/test_session.php', $test_code);
    $result = shell_exec('php /tmp/test_session.php 2>&1');
    echo "Résultat: " . htmlspecialchars($result) . "<br>";
    
    echo "<strong>Étape 2:</strong> Test de l'include subdomain_config<br>";
    $test_code = '<?php 
    require_once "/var/www/mdgeek.top/config/session_config.php"; 
    require_once "/var/www/mdgeek.top/config/subdomain_config.php"; 
    echo "OK subdomain"; 
    ?>';
    file_put_contents('/tmp/test_subdomain.php', $test_code);
    $result = shell_exec('php /tmp/test_subdomain.php 2>&1');
    echo "Résultat: " . htmlspecialchars($result) . "<br>";
}

echo "<h2>3. Test des logs d'erreur</h2>";
$error_logs = [
    '/var/log/nginx/error.log',
    '/var/log/php8.1-fpm.log',
    '/var/log/php8.2-fpm.log', 
    '/var/log/apache2/error.log',
    '/var/log/syslog'
];

foreach ($error_logs as $log_file) {
    if (file_exists($log_file) && is_readable($log_file)) {
        echo "<h3>Dernières erreurs de " . basename($log_file) . ":</h3>";
        $recent_errors = shell_exec("tail -10 $log_file | grep -i 'php\\|error\\|fatal'");
        if (!empty($recent_errors)) {
            echo "<pre>" . htmlspecialchars($recent_errors) . "</pre>";
        } else {
            echo "<p>Aucune erreur récente trouvée.</p>";
        }
    }
}

echo "<h2>4. Test de vérification PHP</h2>";
echo "<strong>Version PHP:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Modules chargés:</strong> " . implode(', ', get_loaded_extensions()) . "<br>";

// Test de syntaxe des fichiers critiques
$files_to_check = [
    '/var/www/mdgeek.top/config/session_config.php',
    '/var/www/mdgeek.top/config/subdomain_config.php',
    '/var/www/mdgeek.top/config/database.php',
    '/var/www/mdgeek.top/index.php'
];

echo "<h3>Vérification syntaxe PHP:</h3>";
foreach ($files_to_check as $file) {
    $result = shell_exec("php -l $file 2>&1");
    $status = strpos($result, 'No syntax errors') !== false ? '✅' : '❌';
    echo "$status " . basename($file) . ": " . htmlspecialchars(trim($result)) . "<br>";
}

echo "<p><strong>Diagnostic avancé terminé</strong> - " . date('Y-m-d H:i:s') . "</p>";
?> 