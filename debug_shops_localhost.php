<?php
// Script de diagnostic pour vérifier la configuration des magasins
echo "<h2>🔍 Diagnostic des Magasins - GeekBoard</h2>";
echo "<hr>";

try {
    // Configuration actuelle (Hostinger)
    $hostinger_host = '191.96.63.103';
    $hostinger_user = 'u139954273_Vscodetest';
    $hostinger_pass = 'Maman01#';
    $hostinger_db = 'u139954273_Vscodetest';
    
    // Configuration localhost
    $localhost_host = 'localhost';
    $localhost_user = 'root';
    $localhost_pass = '';
    $localhost_db = 'geekboard_main';
    
    echo "<h3>📊 Test de connexion Hostinger</h3>";
    try {
        $hostinger_pdo = new PDO(
            "mysql:host=$hostinger_host;port=3306;dbname=$hostinger_db;charset=utf8mb4",
            $hostinger_user,
            $hostinger_pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✅ <span style='color: green;'>Connexion Hostinger réussie</span><br>";
        
        // Vérifier la table shops
        $stmt = $hostinger_pdo->query("SELECT id, name, subdomain, db_host, db_name, db_user FROM shops ORDER BY id");
        $hostinger_shops = $stmt->fetchAll();
        
        echo "<h4>Magasins dans Hostinger (" . count($hostinger_shops) . " magasins) :</h4>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Sous-domaine</th><th>DB Host</th><th>DB Name</th><th>DB User</th></tr>";
        foreach ($hostinger_shops as $shop) {
            echo "<tr>";
            echo "<td>" . $shop['id'] . "</td>";
            echo "<td>" . $shop['name'] . "</td>";
            echo "<td>" . $shop['subdomain'] . "</td>";
            echo "<td>" . $shop['db_host'] . "</td>";
            echo "<td>" . $shop['db_name'] . "</td>";
            echo "<td>" . $shop['db_user'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (PDOException $e) {
        echo "❌ <span style='color: red;'>Erreur Hostinger: " . $e->getMessage() . "</span><br>";
    }
    
    echo "<hr>";
    echo "<h3>🏠 Test de connexion Localhost</h3>";
    
    // Essayer différentes bases de données localhost
    $possible_dbs = ['geekboard', 'geekboard_main', 'admin', 'main', 'test', 'cannesphones', 'psphonac', 'mdgeek'];
    $localhost_found = false;
    
    foreach ($possible_dbs as $db) {
        try {
            $test_pdo = new PDO(
                "mysql:host=$localhost_host;port=3306;dbname=$db;charset=utf8mb4",
                $localhost_user,
                $localhost_pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo "✅ <span style='color: green;'>Base '$db' accessible</span><br>";
            
            // Vérifier les tables
            $stmt = $test_pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "&nbsp;&nbsp;&nbsp;Tables : " . implode(', ', array_slice($tables, 0, 10)) . (count($tables) > 10 ? "... (+" . (count($tables) - 10) . " autres)" : "") . "<br>";
            
            if (in_array('shops', $tables)) {
                $stmt = $test_pdo->query("SELECT COUNT(*) as count FROM shops");
                $count = $stmt->fetch()['count'];
                echo "&nbsp;&nbsp;&nbsp;🎯 <strong>Table 'shops' trouvée avec $count magasins !</strong><br>";
                
                // Afficher les magasins localhost
                $stmt = $test_pdo->query("SELECT id, name, subdomain, db_host, db_name, db_user FROM shops ORDER BY id");
                $localhost_shops = $stmt->fetchAll();
                
                echo "<h4>Magasins dans Localhost '$db' (" . count($localhost_shops) . " magasins) :</h4>";
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr><th>ID</th><th>Nom</th><th>Sous-domaine</th><th>DB Host</th><th>DB Name</th><th>DB User</th></tr>";
                foreach ($localhost_shops as $shop) {
                    echo "<tr>";
                    echo "<td>" . $shop['id'] . "</td>";
                    echo "<td>" . $shop['name'] . "</td>";
                    echo "<td>" . $shop['subdomain'] . "</td>";
                    echo "<td>" . $shop['db_host'] . "</td>";
                    echo "<td>" . $shop['db_name'] . "</td>";
                    echo "<td>" . $shop['db_user'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                $localhost_found = true;
            }
            
        } catch (PDOException $e) {
            echo "❌ <span style='color: red;'>Base '$db' inaccessible</span><br>";
        }
    }
    
    if (!$localhost_found) {
        echo "<h4>🔍 Recherche de bases individuelles des magasins :</h4>";
        $shop_dbs = ['cannesphones', 'psphonac', 'mdgeek', 'atteliergeek', 'geekboard1', 'geekboard2', 'shop1', 'shop2'];
        
        foreach ($shop_dbs as $shop_db) {
            try {
                $shop_pdo = new PDO(
                    "mysql:host=$localhost_host;port=3306;dbname=$shop_db;charset=utf8mb4",
                    $localhost_user,
                    $localhost_pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                echo "✅ <span style='color: green;'>Base de magasin '$shop_db' trouvée</span><br>";
                
                // Vérifier les tables principales
                $stmt = $shop_pdo->query("SHOW TABLES");
                $tables = $shop_pdo->fetchAll(PDO::FETCH_COLUMN);
                $key_tables = array_intersect($tables, ['clients', 'reparations', 'employes', 'categories']);
                
                if (count($key_tables) >= 2) {
                    echo "&nbsp;&nbsp;&nbsp;🎯 <strong>Magasin valide avec tables : " . implode(', ', $key_tables) . "</strong><br>";
                }
                
            } catch (PDOException $e) {
                // Base n'existe pas, normal
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>💡 Recommandations</h3>";
    echo "<ol>";
    echo "<li><strong>Problème identifié :</strong> Le gestionnaire utilise la configuration Hostinger</li>";
    echo "<li><strong>Solution :</strong> Créer une configuration localhost dans config/database.php</li>";
    echo "<li><strong>Étapes :</strong>";
    echo "<ul>";
    echo "<li>Modifier MAIN_DB_HOST vers 'localhost'</li>";
    echo "<li>Créer/Importer la table 'shops' avec tous vos magasins</li>";
    echo "<li>Configurer chaque magasin pour pointer vers sa base localhost</li>";
    echo "</ul>";
    echo "</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "❌ <span style='color: red;'>Erreur générale: " . $e->getMessage() . "</span>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
hr { margin: 20px 0; }
</style> 