<?php
// Script de test pour vérifier la connexion à la base de données localhost
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

echo "<h1>🔍 Diagnostic de la base de données locale</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

// Test 1: Vérifier la configuration actuelle
echo "<div class='section'>";
echo "<h2>1. Configuration actuelle</h2>";
require_once('../config/database.php');

echo "<p><strong>Host:</strong> " . MAIN_DB_HOST . "</p>";
echo "<p><strong>Port:</strong> " . MAIN_DB_PORT . "</p>";
echo "<p><strong>User:</strong> " . MAIN_DB_USER . "</p>";
echo "<p><strong>Base:</strong> " . MAIN_DB_NAME . "</p>";

if (MAIN_DB_HOST === 'localhost' || MAIN_DB_HOST === '127.0.0.1') {
    echo "<p class='success'>✅ Configuration pointant vers localhost</p>";
} else {
    echo "<p class='error'>❌ Configuration pointant vers serveur distant: " . MAIN_DB_HOST . "</p>";
}
echo "</div>";

// Test 2: Connexion à la base de données
echo "<div class='section'>";
echo "<h2>2. Test de connexion</h2>";
try {
    $pdo = getMainDBConnection();
    if ($pdo) {
        echo "<p class='success'>✅ Connexion à la base de données réussie</p>";
        
        // Test d'une requête simple
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "<p class='info'>Version MySQL: " . $version['version'] . "</p>";
        
    } else {
        echo "<p class='error'>❌ Échec de la connexion à la base de données</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur de connexion: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Vérifier l'existence de la base de données
echo "<div class='section'>";
echo "<h2>3. Vérification de la base de données</h2>";
try {
    if ($pdo) {
        // Vérifier si la base geekboard_main existe
        $stmt = $pdo->query("SHOW DATABASES LIKE 'geekboard_main'");
        $db_exists = $stmt->fetch();
        
        if ($db_exists) {
            echo "<p class='success'>✅ Base de données 'geekboard_main' trouvée</p>";
        } else {
            echo "<p class='error'>❌ Base de données 'geekboard_main' non trouvée</p>";
            echo "<p class='info'>Bases de données disponibles:</p>";
            $stmt = $pdo->query("SHOW DATABASES");
            $databases = $stmt->fetchAll();
            echo "<ul>";
            foreach ($databases as $db) {
                echo "<li>" . $db['Database'] . "</li>";
            }
            echo "</ul>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur lors de la vérification: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Vérifier la table shops
echo "<div class='section'>";
echo "<h2>4. Vérification de la table 'shops'</h2>";
try {
    if ($pdo) {
        // Vérifier si la table shops existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'shops'");
        $table_exists = $stmt->fetch();
        
        if ($table_exists) {
            echo "<p class='success'>✅ Table 'shops' trouvée</p>";
            
            // Compter les magasins
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM shops");
            $count = $stmt->fetch();
            echo "<p class='info'>Nombre de magasins: " . $count['count'] . "</p>";
            
            // Lister les magasins
            $stmt = $pdo->query("SELECT id, name, subdomain, active FROM shops ORDER BY id");
            $shops = $stmt->fetchAll();
            
            if ($shops) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>Nom</th><th>Sous-domaine</th><th>Actif</th></tr>";
                foreach ($shops as $shop) {
                    $active_class = $shop['active'] ? 'success' : 'error';
                    echo "<tr>";
                    echo "<td>" . $shop['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($shop['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($shop['subdomain']) . "</td>";
                    echo "<td class='" . $active_class . "'>" . ($shop['active'] ? 'Oui' : 'Non') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warning'>⚠️ Aucun magasin trouvé dans la table</p>";
            }
        } else {
            echo "<p class='error'>❌ Table 'shops' non trouvée</p>";
            echo "<p class='info'>Tables disponibles:</p>";
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll();
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . array_values($table)[0] . "</li>";
            }
            echo "</ul>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur lors de la vérification de la table: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: Recommandations
echo "<div class='section'>";
echo "<h2>5. Recommandations</h2>";
if (MAIN_DB_HOST !== 'localhost') {
    echo "<p class='error'>❌ Utiliser la configuration localhost</p>";
} else {
    echo "<p class='success'>✅ Configuration localhost OK</p>";
}

if (!isset($db_exists) || !$db_exists) {
    echo "<p class='warning'>⚠️ Créer la base de données 'geekboard_main'</p>";
    echo "<p class='info'>Commande SQL: CREATE DATABASE geekboard_main;</p>";
}

if (!isset($table_exists) || !$table_exists) {
    echo "<p class='warning'>⚠️ Créer la table 'shops' avec la structure appropriée</p>";
}

echo "</div>";

echo "<hr>";
echo "<p><strong>Diagnostic terminé:</strong> " . date('Y-m-d H:i:s') . "</p>";
?> 