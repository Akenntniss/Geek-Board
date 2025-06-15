<?php
/**
 * 🔍 Script de diagnostic pour analyser le problème de la page reparations.php
 * Ce script teste la connexion à la base de données et les données disponibles
 */

// Inclure la configuration de session
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';
require_once __DIR__ . '/config/database.php';

function debugLogRepair($message) {
    $timestamp = date('Y-m-d H:i:s');
    $shop_id = $_SESSION['shop_id'] ?? 'unknown';
    error_log("[{$timestamp}] [Shop:{$shop_id}] DEBUG: {$message}");
    echo "<p style='color:blue;'>[{$timestamp}] [Shop:{$shop_id}] DEBUG: {$message}</p>";
}

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Diagnostic Réparations Database</title></head><body>";
echo "<h1>🔍 Diagnostic de la Page Réparations</h1>";

// 1. Vérifier la session actuelle
echo "<h2>📋 État de la Session</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Shop ID: " . ($_SESSION['shop_id'] ?? 'NON DÉFINI') . "\n";
echo "Shop Name: " . ($_SESSION['shop_name'] ?? 'NON DÉFINI') . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NON DÉFINI') . "\n";
echo "Sous-domaine détecté: " . (getSubdomain() ?? 'Aucun') . "\n";
echo "URL actuelle: " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "\n";
echo "</pre>";

// 2. Tester getShopDBConnection()
echo "<h2>🗄️ Test de la Connexion Magasin</h2>";
try {
    debugLogRepair("Appel de getShopDBConnection()");
    $shop_pdo = getShopDBConnection();
    
    if ($shop_pdo === null) {
        echo "<p style='color:red;'>❌ ERREUR: getShopDBConnection() a retourné NULL</p>";
    } else {
        echo "<p style='color:green;'>✅ getShopDBConnection() a retourné une connexion valide</p>";
        
        // Vérifier quelle base est connectée
        try {
            $stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
            $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
            $current_db = $db_info['current_db'] ?? 'Inconnue';
            echo "<p><strong>Base de données connectée:</strong> {$current_db}</p>";
            
            // Vérifier si la table reparations existe
            $tables_stmt = $shop_pdo->query("SHOW TABLES LIKE 'reparations'");
            $has_reparations = $tables_stmt->rowCount() > 0;
            echo "<p><strong>Table 'reparations' existe:</strong> " . ($has_reparations ? '✅ Oui' : '❌ Non') . "</p>";
            
            if ($has_reparations) {
                // Compter les réparations
                $count_stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM reparations");
                $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p><strong>Nombre de réparations:</strong> {$count_result['total']}</p>";
                
                // Afficher quelques réparations récentes
                $recent_stmt = $shop_pdo->query("SELECT id, date_reception, type_appareil, statut FROM reparations ORDER BY date_reception DESC LIMIT 5");
                $recent_repairs = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($recent_repairs)) {
                    echo "<h3>🔧 Réparations Récentes</h3>";
                    echo "<table border='1' cellpadding='5'>";
                    echo "<tr><th>ID</th><th>Date</th><th>Appareil</th><th>Statut</th></tr>";
                    foreach ($recent_repairs as $repair) {
                        echo "<tr>";
                        echo "<td>{$repair['id']}</td>";
                        echo "<td>{$repair['date_reception']}</td>";
                        echo "<td>{$repair['type_appareil']}</td>";
                        echo "<td>{$repair['statut']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p style='color:orange;'>⚠️ Aucune réparation trouvée dans cette base</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Erreur lors de la vérification de la base: " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Exception lors de getShopDBConnection(): " . $e->getMessage() . "</p>";
}

// 3. Tester getMainDBConnection()
echo "<h2>🏢 Test de la Connexion Principale</h2>";
try {
    $main_pdo = getMainDBConnection();
    
    if ($main_pdo === null) {
        echo "<p style='color:red;'>❌ ERREUR: getMainDBConnection() a retourné NULL</p>";
    } else {
        echo "<p style='color:green;'>✅ getMainDBConnection() a retourné une connexion valide</p>";
        
        // Vérifier quelle base est connectée
        $stmt = $main_pdo->query("SELECT DATABASE() as current_db");
        $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_db = $db_info['current_db'] ?? 'Inconnue';
        echo "<p><strong>Base de données principale:</strong> {$current_db}</p>";
        
        // Lister les magasins disponibles
        try {
            $shops_stmt = $main_pdo->query("SELECT id, name, subdomain, db_name FROM shops WHERE active = 1");
            $shops = $shops_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($shops)) {
                echo "<h3>🏪 Magasins Configurés</h3>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Nom</th><th>Sous-domaine</th><th>Database</th><th>Actuel?</th></tr>";
                foreach ($shops as $shop) {
                    $is_current = ($_SESSION['shop_id'] ?? null) == $shop['id'];
                    $current_indicator = $is_current ? '👈 ACTUEL' : '';
                    echo "<tr style='" . ($is_current ? 'background-color: #ffffcc;' : '') . "'>";
                    echo "<td>{$shop['id']}</td>";
                    echo "<td>{$shop['name']}</td>";
                    echo "<td>{$shop['subdomain']}</td>";
                    echo "<td>{$shop['db_name']}</td>";
                    echo "<td>{$current_indicator}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color:orange;'>⚠️ Aucun magasin configuré trouvé</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Erreur lors de la récupération des magasins: " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Exception lors de getMainDBConnection(): " . $e->getMessage() . "</p>";
}

// 4. Test avec une connexion directe si on connaît le shop_id
if (isset($_SESSION['shop_id'])) {
    echo "<h2>🔧 Test de Connexion Directe</h2>";
    $shop_id = $_SESSION['shop_id'];
    echo "<p>Tentative de connexion directe pour le magasin ID: {$shop_id}</p>";
    
    try {
        $main_pdo = getMainDBConnection();
        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop_config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shop_config) {
            echo "<p><strong>Configuration trouvée:</strong></p>";
            echo "<ul>";
            echo "<li>Host: " . ($shop_config['db_host'] ?? 'Non défini') . "</li>";
            echo "<li>Database: " . ($shop_config['db_name'] ?? 'Non défini') . "</li>";
            echo "<li>User: " . ($shop_config['db_user'] ?? 'Non défini') . "</li>";
            echo "</ul>";
            
            // Test de connexion directe
            $dsn = "mysql:host={$shop_config['db_host']};dbname={$shop_config['db_name']};charset=utf8mb4";
            $test_pdo = new PDO($dsn, $shop_config['db_user'], $shop_config['db_pass']);
            $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<p style='color:green;'>✅ Connexion directe réussie!</p>";
            
            // Tester la table réparations
            $test_stmt = $test_pdo->query("SELECT COUNT(*) as total FROM reparations");
            $test_count = $test_stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p><strong>Réparations dans cette base:</strong> {$test_count['total']}</p>";
            
        } else {
            echo "<p style='color:red;'>❌ Configuration du magasin non trouvée</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Erreur lors du test direct: " . $e->getMessage() . "</p>";
    }
}

// 5. Recommandations
echo "<h2>💡 Recommandations</h2>";
echo "<div style='background-color: #f0f0f0; padding: 10px; border-left: 4px solid #007cba;'>";
echo "<h4>🔍 Que Vérifier:</h4>";
echo "<ul>";
echo "<li>Assurez-vous que le shop_id est bien défini dans la session</li>";
echo "<li>Vérifiez que le sous-domaine correspond bien à un magasin configuré</li>";
echo "<li>Confirmez que la base de données du magasin contient des données</li>";
echo "<li>Testez l'accès direct à la page reparations.php après ce diagnostic</li>";
echo "</ul>";

echo "<h4>🛠️ Actions Possibles:</h4>";
echo "<ul>";
echo "<li><a href='?action=reset_session' style='color: orange;'>🔄 Réinitialiser la session</a></li>";
echo "<li><a href='pages/reparations.php' style='color: blue;'>📋 Tester la page réparations</a></li>";
echo "<li><a href='.' style='color: green;'>🏠 Retour à l'accueil</a></li>";
echo "</ul>";
echo "</div>";

// Action de réinitialisation de session
if (isset($_GET['action']) && $_GET['action'] === 'reset_session') {
    session_destroy();
    echo "<p style='color:green;'>✅ Session réinitialisée. <a href='debug_reparations.php'>Recharger le diagnostic</a></p>";
}

echo "</body></html>";
?> 