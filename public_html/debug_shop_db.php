<?php
// Script pour tester et debug la connexion à la base du magasin
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session pour accéder aux variables de session
session_start();

require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Connexion Magasin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Debug Connexion Base de Données Magasin</h1>
    
    <h2>Informations de Session</h2>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <?php
    // Vérifier la connexion principale
    echo "<h2>Connexion à la Base Principale</h2>";
    try {
        $main_pdo = getMainDBConnection();
        if ($main_pdo) {
            echo "<p class='success'>Connexion à la base principale établie</p>";
            // Vérifier quelle base est active
            $stmt = $main_pdo->query("SELECT DATABASE() as db_name");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p class='info'>Base principale active: <strong>" . $result['db_name'] . "</strong></p>";
        } else {
            echo "<p class='error'>Échec de connexion à la base principale</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Erreur: " . $e->getMessage() . "</p>";
    }
    
    // Vérifier la connexion au magasin
    echo "<h2>Connexion à la Base du Magasin</h2>";
    try {
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo) {
            echo "<p class='success'>Connexion à la base du magasin établie</p>";
            // Vérifier quelle base est active
            $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p class='info'>Base du magasin active: <strong>" . $result['db_name'] . "</strong></p>";
            
            // Liste des tables
            echo "<h3>Tables dans la base du magasin</h3>";
            $tables = $shop_pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "<pre>" . print_r($tables, true) . "</pre>";
            
            // Vérification de la table users
            if (in_array('users', $tables)) {
                echo "<h3>Utilisateurs dans la base du magasin</h3>";
                $users = $shop_pdo->query("SELECT id, username, full_name, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
                echo "<pre>" . print_r($users, true) . "</pre>";
            } else {
                echo "<p class='error'>Table 'users' non trouvée dans la base du magasin!</p>";
            }
        } else {
            echo "<p class='error'>Échec de connexion à la base du magasin</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Erreur: " . $e->getMessage() . "</p>";
    }
    
    // Tester la variable $pdo globale
    echo "<h2>Test de la variable \$pdo globale</h2>";
    try {
        global $pdo;
        if ($pdo) {
            echo "<p class='success'>Variable \$pdo globale disponible</p>";
            // Vérifier quelle base est active dans $pdo
            $stmt = $pdo->query("SELECT DATABASE() as db_name");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p class='info'>Base active dans \$pdo: <strong>" . $result['db_name'] . "</strong></p>";
        } else {
            echo "<p class='error'>Variable \$pdo globale non disponible</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Erreur: " . $e->getMessage() . "</p>";
    }
    ?>
</body>
</html> 