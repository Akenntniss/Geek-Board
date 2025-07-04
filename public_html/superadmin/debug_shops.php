<?php
// Script de débogage pour diagnostiquer les problèmes d'affichage des magasins
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

$pdo = getMainDBConnection();

echo "<h1>Diagnostic des magasins - GeekBoard</h1>";
echo "<hr>";

// 1. Vérifier la connexion à la base de données
echo "<h2>1. Test de connexion à la base de données</h2>";
try {
    $test = $pdo->query("SELECT 1")->fetch();
    echo "<p style='color: green;'>✅ Connexion à la base de données principale réussie</p>";
    
    // Afficher les informations de connexion
    echo "<p><strong>Base de données :</strong> " . MAIN_DB_NAME . "</p>";
    echo "<p><strong>Hôte :</strong> " . MAIN_DB_HOST . "</p>";
    echo "<p><strong>Utilisateur :</strong> " . MAIN_DB_USER . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur de connexion : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 2. Vérifier l'existence de la table shops
echo "<h2>2. Vérification de la table 'shops'</h2>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'shops'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p style='color: green;'>✅ La table 'shops' existe</p>";
    } else {
        echo "<p style='color: red;'>❌ La table 'shops' n'existe pas</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur lors de la vérification de la table : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 3. Afficher la structure de la table shops
echo "<h2>3. Structure de la table 'shops'</h2>";
try {
    $structure = $pdo->query("DESCRIBE shops")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    foreach ($structure as $field) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($field['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($field['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($field['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur lors de la récupération de la structure : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 4. Compter le nombre total de magasins
echo "<h2>4. Nombre total de magasins</h2>";
try {
    $count = $pdo->query("SELECT COUNT(*) as total FROM shops")->fetch();
    echo "<p><strong>Nombre total de magasins :</strong> " . $count['total'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur lors du comptage : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 5. Lister tous les magasins avec détails
echo "<h2>5. Liste complète des magasins</h2>";
try {
    $shops = $pdo->query("SELECT * FROM shops ORDER BY id DESC")->fetchAll();
    
    if (count($shops) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Sous-domaine</th><th>Actif</th><th>Date création</th><th>DB Name</th><th>DB User</th></tr>";
        
        foreach ($shops as $shop) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($shop['id']) . "</td>";
            echo "<td>" . htmlspecialchars($shop['name']) . "</td>";
            echo "<td>" . htmlspecialchars($shop['subdomain'] ?? 'NON DÉFINI') . "</td>";
            echo "<td>" . ($shop['active'] ? '✅ Actif' : '❌ Inactif') . "</td>";
            echo "<td>" . htmlspecialchars($shop['created_at'] ?? 'Non défini') . "</td>";
            echo "<td>" . htmlspecialchars($shop['db_name'] ?? 'Non défini') . "</td>";
            echo "<td>" . htmlspecialchars($shop['db_user'] ?? 'Non défini') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Aucun magasin trouvé dans la base de données</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur lors de la récupération des magasins : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 6. Tester la requête utilisée dans index.php
echo "<h2>6. Test de la requête d'index.php</h2>";
try {
    $shops_index = $pdo->query("SELECT * FROM shops ORDER BY name")->fetchAll();
    echo "<p><strong>Nombre de magasins retournés par la requête d'index :</strong> " . count($shops_index) . "</p>";
    
    if (count($shops_index) > 0) {
        echo "<p style='color: green;'>✅ La requête d'index fonctionne correctement</p>";
        echo "<ul>";
        foreach ($shops_index as $shop) {
            echo "<li>" . htmlspecialchars($shop['name']) . " (ID: " . $shop['id'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ La requête d'index ne retourne aucun résultat</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur avec la requête d'index : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 7. Vérifier les derniers magasins créés
echo "<h2>7. Derniers magasins créés (5 derniers)</h2>";
try {
    $recent = $pdo->query("SELECT * FROM shops ORDER BY id DESC LIMIT 5")->fetchAll();
    
    if (count($recent) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Sous-domaine</th><th>Statut</th><th>Créé le</th></tr>";
        
        foreach ($recent as $shop) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($shop['id']) . "</td>";
            echo "<td>" . htmlspecialchars($shop['name']) . "</td>";
            echo "<td>" . htmlspecialchars($shop['subdomain'] ?? 'NON DÉFINI') . "</td>";
            echo "<td>" . ($shop['active'] ? 'Actif' : 'Inactif') . "</td>";
            echo "<td>" . htmlspecialchars($shop['created_at'] ?? 'Non défini') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucun magasin récent trouvé</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 8. Informations de session
echo "<h2>8. Informations de session</h2>";
echo "<p><strong>Superadmin ID :</strong> " . ($_SESSION['superadmin_id'] ?? 'Non défini') . "</p>";
echo "<p><strong>Autres variables de session :</strong></p>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<hr>";
echo '<p><a href="index.php">← Retour à l\'index</a></p>';
echo '<p><a href="create_shop.php">→ Créer un nouveau magasin</a></p>';

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
    try {
        $localhost_pdo = new PDO(
            "mysql:host=$localhost_host;port=3306;dbname=$localhost_db;charset=utf8mb4",
            $localhost_user,
            $localhost_pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✅ <span style='color: green;'>Connexion Localhost réussie</span><br>";
        
        // Vérifier la table shops
        $stmt = $localhost_pdo->query("SHOW TABLES LIKE 'shops'");
        $table_exists = $stmt->fetch();
        
        if ($table_exists) {
            $stmt = $localhost_pdo->query("SELECT id, name, subdomain, db_host, db_name, db_user FROM shops ORDER BY id");
            $localhost_shops = $stmt->fetchAll();
            
            echo "<h4>Magasins dans Localhost (" . count($localhost_shops) . " magasins) :</h4>";
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
        } else {
            echo "⚠️ <span style='color: orange;'>Table 'shops' non trouvée dans localhost</span><br>";
        }
        
    } catch (PDOException $e) {
        echo "❌ <span style='color: red;'>Erreur Localhost: " . $e->getMessage() . "</span><br>";
        
        // Essayer différentes bases de données localhost
        $possible_dbs = ['geekboard', 'geekboard_main', 'admin', 'main'];
        echo "<h4>🔍 Test de bases de données alternatives :</h4>";
        
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
                echo "&nbsp;&nbsp;&nbsp;Tables : " . implode(', ', $tables) . "<br>";
                
                if (in_array('shops', $tables)) {
                    $stmt = $test_pdo->query("SELECT COUNT(*) as count FROM shops");
                    $count = $stmt->fetch()['count'];
                    echo "&nbsp;&nbsp;&nbsp;🎯 <strong>Table 'shops' trouvée avec $count magasins !</strong><br>";
                }
                
            } catch (PDOException $e) {
                echo "❌ <span style='color: red;'>Base '$db' inaccessible</span><br>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>💡 Recommandations</h3>";
    echo "<ol>";
    echo "<li><strong>Pour utiliser localhost :</strong> Modifier config/database.php pour pointer vers localhost</li>";
    echo "<li><strong>Créer/Importer la table shops :</strong> S'assurer que tous les magasins sont présents</li>";
    echo "<li><strong>Vérifier les bases individuelles :</strong> Chaque magasin doit avoir sa propre base</li>";
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
</style> 