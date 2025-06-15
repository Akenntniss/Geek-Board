<?php
/**
 * Script de diagnostic et création du superadmin
 * Ce script vérifie l'état de la base de données et crée le superadmin si nécessaire
 */

// Configuration de base de données (mise à jour avec vos informations)
$db_config = [
    'host' => '191.96.63.103',
    'user' => 'u139954273_Vscodetest',
    'pass' => 'Maman01#',
    'name' => 'u139954273_Vscodetest'
];

// Informations du superadmin à créer
$superadmin_data = [
    'username' => 'superadmin',
    'password' => 'Admin123!', // Changez ce mot de passe !
    'full_name' => 'Super Administrateur',
    'email' => 'admin@geekboard.fr'
];

function createTable($pdo, $tableName, $createSql) {
    try {
        $pdo->exec($createSql);
        return "✅ Table {$tableName} créée avec succès";
    } catch (PDOException $e) {
        return "❌ Erreur création table {$tableName}: " . $e->getMessage();
    }
}

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Diagnostic Superadmin - GeekBoard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0; }
        .info { color: #004085; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .credential-box { background: #e7f3ff; padding: 15px; border: 2px solid #007bff; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>";

echo "<h1>🔧 Diagnostic et Création du Superadmin - GeekBoard</h1>";

try {
    // 1. Test de connexion à la base de données
    echo "<h2>1. Test de connexion à la base de données</h2>";
    
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='success'>✅ Connexion à la base de données réussie !</div>";
    echo "<p><strong>Serveur :</strong> {$db_config['host']}</p>";
    echo "<p><strong>Base de données :</strong> {$db_config['name']}</p>";
    echo "<p><strong>Utilisateur :</strong> {$db_config['user']}</p>";

    // 2. Vérifier et créer la table superadmins
    echo "<h2>2. Vérification de la table superadmins</h2>";
    
    $tables = $pdo->query("SHOW TABLES LIKE 'superadmins'")->fetchAll();
    
    if (empty($tables)) {
        echo "<div class='warning'>⚠️ Table superadmins n'existe pas. Création en cours...</div>";
        
        $create_superadmins_sql = "
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
        
        echo "<div class='info'>" . createTable($pdo, 'superadmins', $create_superadmins_sql) . "</div>";
    } else {
        echo "<div class='success'>✅ Table superadmins existe déjà</div>";
    }

    // 3. Vérifier et créer la table shops
    echo "<h2>3. Vérification de la table shops</h2>";
    
    $shops_tables = $pdo->query("SHOW TABLES LIKE 'shops'")->fetchAll();
    
    if (empty($shops_tables)) {
        echo "<div class='warning'>⚠️ Table shops n'existe pas. Création en cours...</div>";
        
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
        
        echo "<div class='info'>" . createTable($pdo, 'shops', $create_shops_sql) . "</div>";
    } else {
        echo "<div class='success'>✅ Table shops existe déjà</div>";
    }

    // 4. Vérifier les superadmins existants
    echo "<h2>4. Vérification des superadmins existants</h2>";
    
    $existing_superadmins = $pdo->query("SELECT id, username, full_name, email, active, created_at FROM superadmins")->fetchAll();
    
    if (empty($existing_superadmins)) {
        echo "<div class='warning'>⚠️ Aucun superadmin trouvé. Création du superadmin par défaut...</div>";
        
        // Créer le superadmin
        $hashed_password = password_hash($superadmin_data['password'], PASSWORD_DEFAULT);
        
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
        
        echo "<div class='success'>✅ Superadmin créé avec succès ! ID: {$new_id}</div>";
        
        // Afficher les informations de connexion
        echo "<div class='credential-box'>";
        echo "<h3>🔑 Informations de connexion</h3>";
        echo "<p><strong>URL :</strong> <a href='login.php'>login.php</a></p>";
        echo "<p><strong>Username :</strong> {$superadmin_data['username']}</p>";
        echo "<p><strong>Password :</strong> {$superadmin_data['password']}</p>";
        echo "<p><strong>Email :</strong> {$superadmin_data['email']}</p>";
        echo "<p style='color: red;'><strong>⚠️ CHANGEZ LE MOT DE PASSE IMMÉDIATEMENT APRÈS LA PREMIÈRE CONNEXION !</strong></p>";
        echo "</div>";
        
    } else {
        echo "<div class='info'>ℹ️ Superadmins existants trouvés :</div>";
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Nom complet</th><th>Email</th><th>Actif</th><th>Créé le</th></tr>";
        
        foreach ($existing_superadmins as $admin) {
            $active_status = $admin['active'] ? '✅ Oui' : '❌ Non';
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td><strong>{$admin['username']}</strong></td>";
            echo "<td>{$admin['full_name']}</td>";
            echo "<td>{$admin['email']}</td>";
            echo "<td>{$active_status}</td>";
            echo "<td>{$admin['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Vérifier si le superadmin par défaut existe
        $default_exists = false;
        foreach ($existing_superadmins as $admin) {
            if ($admin['username'] === $superadmin_data['username']) {
                $default_exists = true;
                break;
            }
        }
        
        if (!$default_exists) {
            echo "<div class='warning'>⚠️ Le superadmin par défaut '{$superadmin_data['username']}' n'existe pas.</div>";
            echo "<div class='info'>";
            echo "<h4>Options disponibles :</h4>";
            echo "<ul>";
            echo "<li>Utilisez un des comptes existants ci-dessus</li>";
            echo "<li>Ou supprimez ce fichier et relancez-le pour créer '{$superadmin_data['username']}'</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div class='success'>✅ Le superadmin par défaut '{$superadmin_data['username']}' existe déjà</div>";
            echo "<div class='credential-box'>";
            echo "<h3>🔑 Tentez de vous connecter avec :</h3>";
            echo "<p><strong>URL :</strong> <a href='login.php'>login.php</a></p>";
            echo "<p><strong>Username :</strong> {$superadmin_data['username']}</p>";
            echo "<p><strong>Password :</strong> {$superadmin_data['password']}</p>";
            echo "</div>";
        }
    }

    // 5. Test de connexion
    echo "<h2>5. Test de connexion superadmin</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM superadmins WHERE username = ? AND active = 1");
    $stmt->execute([$superadmin_data['username']]);
    $test_user = $stmt->fetch();
    
    if ($test_user) {
        $password_valid = password_verify($superadmin_data['password'], $test_user['password']);
        
        if ($password_valid) {
            echo "<div class='success'>✅ Test de connexion réussi ! Vous pouvez maintenant vous connecter.</div>";
        } else {
            echo "<div class='error'>❌ Le mot de passe ne correspond pas. Le superadmin existe mais avec un autre mot de passe.</div>";
        }
    } else {
        echo "<div class='error'>❌ Superadmin '{$superadmin_data['username']}' non trouvé ou inactif.</div>";
    }

    // 6. Vérifier la configuration database.php
    echo "<h2>6. Vérification de la configuration database.php</h2>";
    
    $config_file = '../config/database.php';
    if (file_exists($config_file)) {
        $config_content = file_get_contents($config_file);
        
        if (strpos($config_content, '191.96.63.103') !== false) {
            echo "<div class='success'>✅ Le fichier database.php utilise la bonne adresse IP (191.96.63.103)</div>";
        } else {
            echo "<div class='error'>❌ Le fichier database.php n'utilise pas la bonne adresse IP !</div>";
            echo "<div class='warning'>⚠️ Vous devez mettre à jour public_html/config/database.php avec :</div>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
            echo "define('MAIN_DB_HOST', '191.96.63.103');\n";
            echo "define('MAIN_DB_USER', 'u139954273_Vscodetest');\n";
            echo "define('MAIN_DB_PASS', 'Maman01#');\n";
            echo "define('MAIN_DB_NAME', 'u139954273_Vscodetest');";
            echo "</pre>";
        }
    } else {
        echo "<div class='error'>❌ Fichier database.php non trouvé !</div>";
    }

} catch (PDOException $e) {
    echo "<div class='error'>❌ Erreur de connexion à la base de données :</div>";
    echo "<div class='error'>" . htmlspecialchars($e->getMessage()) . "</div>";
    
    echo "<h3>Vérifications nécessaires :</h3>";
    echo "<ul>";
    echo "<li>Adresse du serveur : {$db_config['host']}</li>";
    echo "<li>Nom d'utilisateur : {$db_config['user']}</li>";
    echo "<li>Nom de la base : {$db_config['name']}</li>";
    echo "<li>Les connexions externes sont-elles autorisées ?</li>";
    echo "<li>Le serveur MySQL est-il accessible ?</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<div class='warning'>";
echo "<h3>🛡️ SÉCURITÉ - Actions à faire après utilisation :</h3>";
echo "<ol>";
echo "<li>Connectez-vous et changez le mot de passe</li>";
echo "<li>Supprimez ce fichier diagnostic_superadmin.php</li>";
echo "<li>Vérifiez les permissions du dossier superadmin</li>";
echo "</ol>";
echo "</div>";

echo "<p><strong>Diagnostic terminé</strong> - " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?> 