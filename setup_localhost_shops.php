<?php
// Script de configuration pour gérer les magasins localhost
echo "<h2>🔧 Configuration des Magasins Localhost - GeekBoard</h2>";
echo "<hr>";

try {
    // Configuration localhost
    $localhost_config = [
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'root',
        'pass' => '',
        'main_db' => 'geekboard_main'
    ];
    
    // Connexion à la base principale localhost
    $dsn = "mysql:host={$localhost_config['host']};port={$localhost_config['port']};dbname={$localhost_config['main_db']};charset=utf8mb4";
    $main_pdo = new PDO($dsn, $localhost_config['user'], $localhost_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ <span style='color: green;'>Connexion à geekboard_main réussie</span><br><br>";
    
    // Vérifier si la table shops existe
    $stmt = $main_pdo->query("SHOW TABLES LIKE 'shops'");
    $shops_exists = $stmt->fetch();
    
    if (!$shops_exists) {
        echo "<h3>📋 Création de la table 'shops'</h3>";
        
        $create_shops_sql = "
        CREATE TABLE `shops` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `subdomain` varchar(100) DEFAULT NULL,
            `db_host` varchar(255) DEFAULT 'localhost',
            `db_port` int(11) DEFAULT 3306,
            `db_name` varchar(255) NOT NULL,
            `db_user` varchar(255) DEFAULT 'root',
            `db_pass` varchar(255) DEFAULT '',
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `subdomain` (`subdomain`),
            UNIQUE KEY `db_name` (`db_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $main_pdo->exec($create_shops_sql);
        echo "✅ <span style='color: green;'>Table 'shops' créée avec succès</span><br>";
    } else {
        echo "<h3>📋 Table 'shops' existe déjà</h3>";
    }
    
    // Découvrir toutes les bases de données disponibles
    echo "<h3>🔍 Découverte des bases de données</h3>";
    
    $base_pdo = new PDO("mysql:host={$localhost_config['host']};port={$localhost_config['port']};charset=utf8mb4", 
                       $localhost_config['user'], $localhost_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $stmt = $base_pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Bases à ignorer
    $ignore_dbs = ['information_schema', 'performance_schema', 'mysql', 'sys', 'phpmyadmin', 'geekboard_main'];
    
    $discovered_shops = [];
    
    foreach ($databases as $db_name) {
        if (in_array($db_name, $ignore_dbs)) {
            continue;
        }
        
        try {
            // Tester la connexion à cette base
            $test_pdo = new PDO("mysql:host={$localhost_config['host']};port={$localhost_config['port']};dbname={$db_name};charset=utf8mb4", 
                               $localhost_config['user'], $localhost_config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Vérifier si c'est une base GeekBoard
            $stmt = $test_pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $geekboard_tables = ['clients', 'reparations', 'employes', 'categories'];
            $matches = array_intersect($tables, $geekboard_tables);
            
            if (count($matches) >= 2) {
                // C'est une base GeekBoard valide
                $shop_name = ucfirst($db_name);
                
                // Essayer de récupérer le nom depuis la configuration
                try {
                    $config_stmt = $test_pdo->query("SELECT * FROM configuration LIMIT 1");
                    if ($config_stmt) {
                        $config_data = $config_stmt->fetch();
                        if ($config_data && isset($config_data['nom_entreprise'])) {
                            $shop_name = $config_data['nom_entreprise'];
                        }
                    }
                } catch (Exception $e) {
                    // Pas grave, on garde le nom de la base
                }
                
                $discovered_shops[] = [
                    'name' => $shop_name,
                    'db_name' => $db_name,
                    'tables_count' => count($tables),
                    'geekboard_tables' => $matches
                ];
                
                echo "✅ <span style='color: green;'>Magasin trouvé: <strong>$shop_name</strong> ($db_name) - " . count($tables) . " tables</span><br>";
            }
            
        } catch (Exception $e) {
            // Base inaccessible, ignorer
        }
    }
    
    // Si aucun magasin découvert, créer des exemples
    if (empty($discovered_shops)) {
        echo "<h3>⚠️ Aucun magasin détecté - Création d'exemples</h3>";
        
        $sample_shops = [
            ['name' => 'Cannes Phones', 'db_name' => 'cannesphones'],
            ['name' => 'PS Phone AC', 'db_name' => 'psphonac'],
            ['name' => 'MD Geek', 'db_name' => 'mdgeek'],
            ['name' => 'Atelier Geek', 'db_name' => 'atteliergeek'],
            ['name' => 'GeekBoard Demo', 'db_name' => 'geekboard_demo'],
        ];
        
        foreach ($sample_shops as $shop) {
            $discovered_shops[] = [
                'name' => $shop['name'],
                'db_name' => $shop['db_name'],
                'tables_count' => 0,
                'geekboard_tables' => []
            ];
            echo "📝 <span style='color: orange;'>Exemple créé: <strong>{$shop['name']}</strong> ({$shop['db_name']}) - À configurer</span><br>";
        }
    }
    
    // Insérer/Mettre à jour les magasins dans la table shops
    echo "<h3>💾 Configuration de la table 'shops'</h3>";
    
    // Vider la table shops existante
    $main_pdo->exec("DELETE FROM shops");
    $main_pdo->exec("ALTER TABLE shops AUTO_INCREMENT = 1");
    echo "🗑️ Table 'shops' vidée<br>";
    
    // Insérer les magasins découverts
    $insert_stmt = $main_pdo->prepare("
        INSERT INTO shops (name, subdomain, db_host, db_port, db_name, db_user, db_pass, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($discovered_shops as $shop) {
        $subdomain = strtolower($shop['db_name']);
        $insert_stmt->execute([
            $shop['name'],
            $subdomain,
            $localhost_config['host'],
            $localhost_config['port'],
            $shop['db_name'],
            $localhost_config['user'],
            $localhost_config['pass'],
            'active'
        ]);
        echo "✅ <span style='color: green;'>Magasin ajouté: <strong>{$shop['name']}</strong></span><br>";
    }
    
    // Vérifier le résultat
    echo "<h3>📊 Résultat Final</h3>";
    $stmt = $main_pdo->query("SELECT * FROM shops ORDER BY id");
    $final_shops = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'><th>ID</th><th>Nom</th><th>Sous-domaine</th><th>Base de données</th><th>Status</th></tr>";
    
    foreach ($final_shops as $shop) {
        echo "<tr>";
        echo "<td>{$shop['id']}</td>";
        echo "<td><strong>{$shop['name']}</strong></td>";
        echo "<td>{$shop['subdomain']}</td>";
        echo "<td>{$shop['db_name']}</td>";
        echo "<td><span style='color: " . ($shop['status'] === 'active' ? 'green' : 'red') . ";'>{$shop['status']}</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h3>🎉 Configuration terminée !</h3>";
    echo "<p><strong>" . count($final_shops) . " magasin(s)</strong> configuré(s) dans la base geekboard_main</p>";
    
    echo "<h4>📝 Prochaines étapes :</h4>";
    echo "<ol>";
    echo "<li><strong>Modifier config/database.php</strong> pour utiliser localhost au lieu de Hostinger</li>";
    echo "<li><strong>Créer les bases de données manquantes</strong> si nécessaire</li>";
    echo "<li><strong>Importer les données</strong> des magasins depuis Hostinger si besoin</li>";
    echo "<li><strong>Tester le gestionnaire de base de données</strong> avec la nouvelle configuration</li>";
    echo "</ol>";
    
    echo "<br><p><a href='database_manager.php' class='btn' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Tester le Gestionnaire de Base de Données</a></p>";
    
} catch (Exception $e) {
    echo "❌ <span style='color: red;'>Erreur: " . $e->getMessage() . "</span>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
hr { margin: 20px 0; }
.btn { display: inline-block; margin: 5px; }
</style> 