<?php
/**
 * Script pour créer un superadmin
 * Ce script doit être exécuté une seule fois pour créer le premier superadmin
 */

// Configuration de la base de données
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'geekboard_main';

// Informations du superadmin à créer
$superadmin_data = [
    'username' => 'superadmin',
    'password' => 'Admin123!', // Mot de passe par défaut - À CHANGER après première connexion
    'full_name' => 'Super Administrateur',
    'email' => 'admin@geekboard.fr'
];

try {
    // Connexion à la base de données
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<h2>Connexion à la base de données réussie !</h2>\n";

    // Vérifier si la table superadmins existe
    $tables = $pdo->query("SHOW TABLES LIKE 'superadmins'")->fetchAll();
    
    if (empty($tables)) {
        echo "<h3>Création de la table superadmins...</h3>\n";
        
        // Créer la table superadmins
        $create_table_sql = "
        CREATE TABLE `superadmins` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `full_name` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL,
            `active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($create_table_sql);
        echo "✅ Table superadmins créée avec succès !<br>\n";
    } else {
        echo "✅ Table superadmins existe déjà.<br>\n";
    }

    // Vérifier si un superadmin existe déjà
    $existing_count = $pdo->query("SELECT COUNT(*) FROM superadmins")->fetchColumn();
    
    if ($existing_count > 0) {
        echo "<h3>⚠️ Des superadmins existent déjà dans la base de données.</h3>\n";
        
        // Afficher les superadmins existants
        $existing_superadmins = $pdo->query("SELECT id, username, full_name, email, active, created_at FROM superadmins")->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>ID</th><th>Username</th><th>Nom complet</th><th>Email</th><th>Actif</th><th>Créé le</th></tr>\n";
        
        foreach ($existing_superadmins as $admin) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($admin['id']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
            echo "<td>" . ($admin['active'] ? 'Oui' : 'Non') . "</td>";
            echo "<td>" . htmlspecialchars($admin['created_at']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        $choice = "continue"; // Pour ce script, on continue automatiquement
    }

    // Vérifier si le username existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM superadmins WHERE username = ?");
    $stmt->execute([$superadmin_data['username']]);
    
    if ($stmt->fetchColumn() > 0) {
        echo "<h3>❌ Le nom d'utilisateur '{$superadmin_data['username']}' existe déjà !</h3>\n";
        echo "<p>Veuillez modifier le nom d'utilisateur dans le script et relancer.</p>\n";
    } else {
        // Hasher le mot de passe
        $hashed_password = password_hash($superadmin_data['password'], PASSWORD_DEFAULT);
        
        // Insérer le nouveau superadmin
        $stmt = $pdo->prepare("
            INSERT INTO superadmins (username, password, full_name, email, active) 
            VALUES (?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([
            $superadmin_data['username'],
            $hashed_password,
            $superadmin_data['full_name'],
            $superadmin_data['email']
        ]);
        
        $new_id = $pdo->lastInsertId();
        
        echo "<h3>✅ Superadmin créé avec succès !</h3>\n";
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h4>Informations de connexion :</h4>\n";
        echo "<p><strong>ID :</strong> {$new_id}</p>\n";
        echo "<p><strong>Nom d'utilisateur :</strong> {$superadmin_data['username']}</p>\n";
        echo "<p><strong>Mot de passe :</strong> {$superadmin_data['password']}</p>\n";
        echo "<p><strong>Email :</strong> {$superadmin_data['email']}</p>\n";
        echo "</div>\n";
        
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h4>⚠️ IMPORTANT - Sécurité :</h4>\n";
        echo "<ul>\n";
        echo "<li>Changez immédiatement le mot de passe après la première connexion</li>\n";
        echo "<li>Supprimez ce script après utilisation pour des raisons de sécurité</li>\n";
        echo "<li>Accédez à l'interface d'administration : <a href='../superadmin/login.php'>../superadmin/login.php</a></li>\n";
        echo "</ul>\n";
        echo "</div>\n";
    }

    // Vérifier si la table shops existe
    $shops_tables = $pdo->query("SHOW TABLES LIKE 'shops'")->fetchAll();
    
    if (empty($shops_tables)) {
        echo "<h3>Création de la table shops...</h3>\n";
        
        // Créer la table shops
        $create_shops_sql = "
        CREATE TABLE `shops` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `description` text,
            `subdomain` varchar(50) NOT NULL,
            `address` text,
            `city` varchar(100),
            `postal_code` varchar(20),
            `country` varchar(100) DEFAULT 'France',
            `phone` varchar(20),
            `email` varchar(100),
            `website` varchar(255),
            `logo` varchar(255),
            `active` tinyint(1) DEFAULT 1,
            `db_host` varchar(255) NOT NULL,
            `db_port` varchar(10) DEFAULT '3306',
            `db_name` varchar(100) NOT NULL,
            `db_user` varchar(100) NOT NULL,
            `db_pass` varchar(255) NOT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`),
            UNIQUE KEY `subdomain` (`subdomain`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($create_shops_sql);
        echo "✅ Table shops créée avec succès !<br>\n";
    } else {
        echo "✅ Table shops existe déjà.<br>\n";
    }

} catch (PDOException $e) {
    echo "<h3>❌ Erreur de connexion à la base de données :</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<h4>Vérifiez :</h4>\n";
    echo "<ul>\n";
    echo "<li>L'adresse IP du serveur : localhost</li>\n";
    echo "<li>Le nom d'utilisateur : root</li>\n";
    echo "<li>Le mot de passe : (vide)</li>\n";
    echo "<li>Le nom de la base de données : geekboard_main</li>\n";
    echo "<li>Que la base de données autorise les connexions externes</li>\n";
    echo "</ul>\n";
} catch (Exception $e) {
    echo "<h3>❌ Erreur générale :</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Fin du script</strong> - " . date('Y-m-d H:i:s') . "</p>\n";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création du Superadmin - GeekBoard</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 20px; 
            background-color: #f5f5f5; 
        }
        h2, h3 { color: #333; }
        table { 
            width: 100%; 
            background: white; 
            border-radius: 5px; 
        }
        th { 
            background-color: #007bff; 
            color: white; 
            padding: 10px; 
        }
        td { padding: 8px; }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <h1>🔧 GeekBoard - Création du Superadmin</h1>
</body>
</html> 