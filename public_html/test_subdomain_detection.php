<?php
/**
 * Script de test pour le système de détection automatique par sous-domaine
 * GeekBoard Multi-Magasin
 */

require_once __DIR__ . '/config/subdomain_database_detector.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Test Détection Sous-domaines - GeekBoard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .info { color: #004085; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>🔍 Test du Système de Détection Automatique par Sous-domaine</h1>";

// Démarrer la session pour les tests
session_start();

// Instance du détecteur
$detector = new SubdomainDatabaseDetector();

echo "<div class='test-section'>";
echo "<h2>1. Test de Détection du Sous-domaine Actuel</h2>";

$current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$detected_subdomain = $detector->detectSubdomain();

echo "<div class='info'>";
echo "<p><strong>Host actuel :</strong> $current_host</p>";
echo "<p><strong>Sous-domaine détecté :</strong> '$detected_subdomain'</p>";
echo "</div>";

// Test de simulation de différents hosts
echo "<h3>Test de simulation de différents hosts :</h3>";
$test_hosts = [
    'cannesphones.mdgeek.top',
    'pscannes.mdgeek.top', 
    'mdgeek.top',
    'www.mdgeek.top',
    'localhost',
    'newshop.mdgeek.top'
];

echo "<table>";
echo "<tr><th>Host simulé</th><th>Sous-domaine détecté</th><th>Base de données</th></tr>";

foreach ($test_hosts as $host) {
    $_SERVER['HTTP_HOST'] = $host;
    $subdomain = $detector->detectSubdomain();
    $config = $detector->getDatabaseConfig($subdomain);
    
    echo "<tr>";
    echo "<td>$host</td>";
    echo "<td>'$subdomain'</td>";
    echo "<td>{$config['dbname']}</td>";
    echo "</tr>";
}

// Restaurer le host original
$_SERVER['HTTP_HOST'] = $current_host;

echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>2. Test de Connexion aux Bases de Données</h2>";

$connection_results = $detector->testAllConnections();

echo "<table>";
echo "<tr><th>Sous-domaine</th><th>Base de données</th><th>Statut</th><th>Détails</th></tr>";

foreach ($connection_results as $subdomain => $result) {
    $status_class = ($result['status'] === 'OK') ? 'success' : 'error';
    echo "<tr class='$status_class'>";
    echo "<td>" . ($subdomain ?: '(principal)') . "</td>";
    echo "<td>{$result['database']}</td>";
    echo "<td>{$result['status']}</td>";
    echo "<td>" . ($result['error'] ?? 'Connexion réussie') . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>3. Test de Récupération des Informations Magasin</h2>";

try {
    $shop_info = $detector->getCurrentShopInfo();
    
    if ($shop_info) {
        echo "<div class='success'>✅ Informations magasin trouvées</div>";
        echo "<table>";
        echo "<tr><th>Propriété</th><th>Valeur</th></tr>";
        foreach ($shop_info as $key => $value) {
            echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ Aucune information magasin trouvée pour le sous-domaine actuel</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur : " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>4. Test de Connexion Dynamique</h2>";

try {
    $shop_connection = $detector->getConnection();
    
    if ($shop_connection) {
        echo "<div class='success'>✅ Connexion dynamique réussie</div>";
        
        // Tester quelques requêtes
        $stmt = $shop_connection->query("SELECT DATABASE() as current_db");
        $db_info = $stmt->fetch();
        echo "<p><strong>Base connectée :</strong> {$db_info['current_db']}</p>";
        
        // Lister les tables disponibles
        $stmt = $shop_connection->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p><strong>Tables disponibles :</strong></p>";
        echo "<div class='code'>" . implode(', ', $tables) . "</div>";
        
        // Test d'une requête sur une table commune
        if (in_array('configuration', $tables)) {
            try {
                $stmt = $shop_connection->query("SELECT COUNT(*) as count FROM configuration");
                $config_count = $stmt->fetch();
                echo "<p><strong>Nombre d'entrées dans configuration :</strong> {$config_count['count']}</p>";
            } catch (Exception $e) {
                echo "<div class='warning'>⚠️ Impossible de lire la table configuration : " . $e->getMessage() . "</div>";
            }
        }
        
    } else {
        echo "<div class='error'>❌ Échec de la connexion dynamique</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur de connexion : " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>5. Test des Fonctions Helper</h2>";

echo "<h3>getShopConnection() :</h3>";
try {
    $helper_connection = getShopConnection();
    if ($helper_connection) {
        $stmt = $helper_connection->query("SELECT DATABASE() as db");
        $result = $stmt->fetch();
        echo "<div class='success'>✅ getShopConnection() fonctionne - Base: {$result['db']}</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ getShopConnection() échoue: " . $e->getMessage() . "</div>";
}

echo "<h3>getCurrentShopConfig() :</h3>";
try {
    $config = getCurrentShopConfig();
    echo "<div class='success'>✅ getCurrentShopConfig() fonctionne</div>";
    echo "<div class='code'>" . json_encode($config, JSON_PRETTY_PRINT) . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ getCurrentShopConfig() échoue: " . $e->getMessage() . "</div>";
}

echo "<h3>getCurrentShop() :</h3>";
try {
    $shop = getCurrentShop();
    if ($shop) {
        echo "<div class='success'>✅ getCurrentShop() fonctionne</div>";
        echo "<p><strong>Magasin :</strong> {$shop['name']} (ID: {$shop['id']})</p>";
    } else {
        echo "<div class='warning'>⚠️ getCurrentShop() ne retourne aucun résultat</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ getCurrentShop() échoue: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>6. Test avec Différents Sous-domaines</h2>";

$test_subdomains = ['cannesphones', 'pscannes', 'mdgeek', 'newtest'];

echo "<table>";
echo "<tr><th>Sous-domaine</th><th>Base de données</th><th>Connexion</th><th>Infos magasin</th></tr>";

foreach ($test_subdomains as $test_subdomain) {
    echo "<tr>";
    echo "<td>$test_subdomain</td>";
    
    try {
        $config = $detector->getDatabaseConfig($test_subdomain);
        echo "<td>{$config['dbname']}</td>";
        
        try {
            $connection = $detector->getConnection($test_subdomain);
            echo "<td class='success'>✅ OK</td>";
        } catch (Exception $e) {
            echo "<td class='error'>❌ Échec</td>";
        }
        
        // Simuler le host pour getCurrentShopInfo
        $_SERVER['HTTP_HOST'] = "$test_subdomain.mdgeek.top";
        $shop_info = $detector->getCurrentShopInfo();
        if ($shop_info) {
            echo "<td class='success'>✅ {$shop_info['name']}</td>";
        } else {
            echo "<td class='warning'>⚠️ Non trouvé</td>";
        }
        
    } catch (Exception $e) {
        echo "<td class='error'>❌ Erreur</td>";
        echo "<td class='error'>❌ Erreur</td>";
        echo "<td class='error'>❌ Erreur</td>";
    }
    
    echo "</tr>";
}

// Restaurer le host original
$_SERVER['HTTP_HOST'] = $current_host;

echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>7. Résumé et Recommandations</h2>";

$all_tests_passed = true;
$issues = [];

// Vérifier les connexions
foreach ($connection_results as $subdomain => $result) {
    if ($result['status'] !== 'OK') {
        $all_tests_passed = false;
        $issues[] = "Connexion échouée pour '$subdomain': {$result['error']}";
    }
}

if ($all_tests_passed && empty($issues)) {
    echo "<div class='success'>";
    echo "<h3>🎉 Tous les tests sont passés !</h3>";
    echo "<p>Le système de détection automatique par sous-domaine fonctionne correctement.</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠️ Problèmes détectés :</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<div class='info'>";
echo "<h3>🔧 Configuration recommandée :</h3>";
echo "<ol>";
echo "<li>Assurez-vous que toutes les bases de données (geekboard_*) existent</li>";
echo "<li>Vérifiez que la table 'shops' contient les bonnes correspondances sous-domaine/base</li>";
echo "<li>Testez avec de vrais sous-domaines configurés dans votre serveur web</li>";
echo "<li>Activez les logs de débogage pour le troubleshooting</li>";
echo "</ol>";
echo "</div>";

echo "</div>";

echo "<p><strong>Test terminé</strong> - " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?> 
/**
 * Script de test pour le système de détection automatique par sous-domaine
 * GeekBoard Multi-Magasin
 */

require_once __DIR__ . '/config/subdomain_database_detector.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Test Détection Sous-domaines - GeekBoard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .info { color: #004085; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>🔍 Test du Système de Détection Automatique par Sous-domaine</h1>";

// Démarrer la session pour les tests
session_start();

// Instance du détecteur
$detector = new SubdomainDatabaseDetector();

echo "<div class='test-section'>";
echo "<h2>1. Test de Détection du Sous-domaine Actuel</h2>";

$current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$detected_subdomain = $detector->detectSubdomain();

echo "<div class='info'>";
echo "<p><strong>Host actuel :</strong> $current_host</p>";
echo "<p><strong>Sous-domaine détecté :</strong> '$detected_subdomain'</p>";
echo "</div>";

// Test de simulation de différents hosts
echo "<h3>Test de simulation de différents hosts :</h3>";
$test_hosts = [
    'cannesphones.mdgeek.top',
    'pscannes.mdgeek.top', 
    'mdgeek.top',
    'www.mdgeek.top',
    'localhost',
    'newshop.mdgeek.top'
];

echo "<table>";
echo "<tr><th>Host simulé</th><th>Sous-domaine détecté</th><th>Base de données</th></tr>";

foreach ($test_hosts as $host) {
    $_SERVER['HTTP_HOST'] = $host;
    $subdomain = $detector->detectSubdomain();
    $config = $detector->getDatabaseConfig($subdomain);
    
    echo "<tr>";
    echo "<td>$host</td>";
    echo "<td>'$subdomain'</td>";
    echo "<td>{$config['dbname']}</td>";
    echo "</tr>";
}

// Restaurer le host original
$_SERVER['HTTP_HOST'] = $current_host;

echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>2. Test de Connexion aux Bases de Données</h2>";

$connection_results = $detector->testAllConnections();

echo "<table>";
echo "<tr><th>Sous-domaine</th><th>Base de données</th><th>Statut</th><th>Détails</th></tr>";

foreach ($connection_results as $subdomain => $result) {
    $status_class = ($result['status'] === 'OK') ? 'success' : 'error';
    echo "<tr class='$status_class'>";
    echo "<td>" . ($subdomain ?: '(principal)') . "</td>";
    echo "<td>{$result['database']}</td>";
    echo "<td>{$result['status']}</td>";
    echo "<td>" . ($result['error'] ?? 'Connexion réussie') . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>3. Test de Récupération des Informations Magasin</h2>";

try {
    $shop_info = $detector->getCurrentShopInfo();
    
    if ($shop_info) {
        echo "<div class='success'>✅ Informations magasin trouvées</div>";
        echo "<table>";
        echo "<tr><th>Propriété</th><th>Valeur</th></tr>";
        foreach ($shop_info as $key => $value) {
            echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ Aucune information magasin trouvée pour le sous-domaine actuel</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur : " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>4. Test de Connexion Dynamique</h2>";

try {
    $shop_connection = $detector->getConnection();
    
    if ($shop_connection) {
        echo "<div class='success'>✅ Connexion dynamique réussie</div>";
        
        // Tester quelques requêtes
        $stmt = $shop_connection->query("SELECT DATABASE() as current_db");
        $db_info = $stmt->fetch();
        echo "<p><strong>Base connectée :</strong> {$db_info['current_db']}</p>";
        
        // Lister les tables disponibles
        $stmt = $shop_connection->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p><strong>Tables disponibles :</strong></p>";
        echo "<div class='code'>" . implode(', ', $tables) . "</div>";
        
        // Test d'une requête sur une table commune
        if (in_array('configuration', $tables)) {
            try {
                $stmt = $shop_connection->query("SELECT COUNT(*) as count FROM configuration");
                $config_count = $stmt->fetch();
                echo "<p><strong>Nombre d'entrées dans configuration :</strong> {$config_count['count']}</p>";
            } catch (Exception $e) {
                echo "<div class='warning'>⚠️ Impossible de lire la table configuration : " . $e->getMessage() . "</div>";
            }
        }
        
    } else {
        echo "<div class='error'>❌ Échec de la connexion dynamique</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur de connexion : " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>5. Test des Fonctions Helper</h2>";

echo "<h3>getShopConnection() :</h3>";
try {
    $helper_connection = getShopConnection();
    if ($helper_connection) {
        $stmt = $helper_connection->query("SELECT DATABASE() as db");
        $result = $stmt->fetch();
        echo "<div class='success'>✅ getShopConnection() fonctionne - Base: {$result['db']}</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ getShopConnection() échoue: " . $e->getMessage() . "</div>";
}

echo "<h3>getCurrentShopConfig() :</h3>";
try {
    $config = getCurrentShopConfig();
    echo "<div class='success'>✅ getCurrentShopConfig() fonctionne</div>";
    echo "<div class='code'>" . json_encode($config, JSON_PRETTY_PRINT) . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ getCurrentShopConfig() échoue: " . $e->getMessage() . "</div>";
}

echo "<h3>getCurrentShop() :</h3>";
try {
    $shop = getCurrentShop();
    if ($shop) {
        echo "<div class='success'>✅ getCurrentShop() fonctionne</div>";
        echo "<p><strong>Magasin :</strong> {$shop['name']} (ID: {$shop['id']})</p>";
    } else {
        echo "<div class='warning'>⚠️ getCurrentShop() ne retourne aucun résultat</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ getCurrentShop() échoue: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>6. Test avec Différents Sous-domaines</h2>";

$test_subdomains = ['cannesphones', 'pscannes', 'mdgeek', 'newtest'];

echo "<table>";
echo "<tr><th>Sous-domaine</th><th>Base de données</th><th>Connexion</th><th>Infos magasin</th></tr>";

foreach ($test_subdomains as $test_subdomain) {
    echo "<tr>";
    echo "<td>$test_subdomain</td>";
    
    try {
        $config = $detector->getDatabaseConfig($test_subdomain);
        echo "<td>{$config['dbname']}</td>";
        
        try {
            $connection = $detector->getConnection($test_subdomain);
            echo "<td class='success'>✅ OK</td>";
        } catch (Exception $e) {
            echo "<td class='error'>❌ Échec</td>";
        }
        
        // Simuler le host pour getCurrentShopInfo
        $_SERVER['HTTP_HOST'] = "$test_subdomain.mdgeek.top";
        $shop_info = $detector->getCurrentShopInfo();
        if ($shop_info) {
            echo "<td class='success'>✅ {$shop_info['name']}</td>";
        } else {
            echo "<td class='warning'>⚠️ Non trouvé</td>";
        }
        
    } catch (Exception $e) {
        echo "<td class='error'>❌ Erreur</td>";
        echo "<td class='error'>❌ Erreur</td>";
        echo "<td class='error'>❌ Erreur</td>";
    }
    
    echo "</tr>";
}

// Restaurer le host original
$_SERVER['HTTP_HOST'] = $current_host;

echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>7. Résumé et Recommandations</h2>";

$all_tests_passed = true;
$issues = [];

// Vérifier les connexions
foreach ($connection_results as $subdomain => $result) {
    if ($result['status'] !== 'OK') {
        $all_tests_passed = false;
        $issues[] = "Connexion échouée pour '$subdomain': {$result['error']}";
    }
}

if ($all_tests_passed && empty($issues)) {
    echo "<div class='success'>";
    echo "<h3>🎉 Tous les tests sont passés !</h3>";
    echo "<p>Le système de détection automatique par sous-domaine fonctionne correctement.</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠️ Problèmes détectés :</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<div class='info'>";
echo "<h3>🔧 Configuration recommandée :</h3>";
echo "<ol>";
echo "<li>Assurez-vous que toutes les bases de données (geekboard_*) existent</li>";
echo "<li>Vérifiez que la table 'shops' contient les bonnes correspondances sous-domaine/base</li>";
echo "<li>Testez avec de vrais sous-domaines configurés dans votre serveur web</li>";
echo "<li>Activez les logs de débogage pour le troubleshooting</li>";
echo "</ol>";
echo "</div>";

echo "</div>";

echo "<p><strong>Test terminé</strong> - " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?> 