<?php
// Script pour créer des bases de données d'exemple pour GeekBoard
echo "<h2>🏗️ Création des Bases de Données d'Exemple</h2>";
echo "<hr>";

try {
    // Configuration localhost
    $config = [
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'root',
        'pass' => ''
    ];
    
    // Connexion sans base spécifique
    $pdo = new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4", 
                   $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ <span style='color: green;'>Connexion MySQL réussie</span><br><br>";
    
    // Bases de données à créer
    $databases = [
        'cannesphones' => 'Cannes Phones',
        'psphonac' => 'PS Phone AC', 
        'mdgeek' => 'MD Geek'
    ];
    
    foreach ($databases as $db_name => $shop_name) {
        echo "<h3>🔧 Création de la base '$db_name' ($shop_name)</h3>";
        
        // Créer la base de données
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Base de données '$db_name' créée<br>";
        
        // Se connecter à la nouvelle base
        $shop_pdo = new PDO("mysql:host={$config['host']};port={$config['port']};dbname=$db_name;charset=utf8mb4", 
                           $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Créer les tables principales GeekBoard
        $tables_sql = [
            'clients' => "
                CREATE TABLE IF NOT EXISTS `clients` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `nom` varchar(255) NOT NULL,
                    `prenom` varchar(255) NOT NULL,
                    `telephone` varchar(20) DEFAULT NULL,
                    `email` varchar(255) DEFAULT NULL,
                    `adresse` text,
                    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_nom` (`nom`),
                    INDEX `idx_telephone` (`telephone`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'reparations' => "
                CREATE TABLE IF NOT EXISTS `reparations` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `client_id` int(11) NOT NULL,
                    `appareil` varchar(255) NOT NULL,
                    `probleme` text NOT NULL,
                    `statut` enum('en_attente','en_cours','termine','livre') DEFAULT 'en_attente',
                    `prix` decimal(10,2) DEFAULT NULL,
                    `date_depot` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `date_fin` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    INDEX `idx_client` (`client_id`),
                    INDEX `idx_statut` (`statut`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'employes' => "
                CREATE TABLE IF NOT EXISTS `employes` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `nom` varchar(255) NOT NULL,
                    `prenom` varchar(255) NOT NULL,
                    `email` varchar(255) UNIQUE DEFAULT NULL,
                    `role` enum('admin','technicien','vendeur') DEFAULT 'technicien',
                    `date_embauche` date DEFAULT NULL,
                    `actif` tinyint(1) DEFAULT 1,
                    PRIMARY KEY (`id`),
                    INDEX `idx_role` (`role`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'categories' => "
                CREATE TABLE IF NOT EXISTS `categories` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `nom` varchar(255) NOT NULL,
                    `description` text,
                    `couleur` varchar(7) DEFAULT '#007bff',
                    `actif` tinyint(1) DEFAULT 1,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `nom` (`nom`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'configuration' => "
                CREATE TABLE IF NOT EXISTS `configuration` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `nom_entreprise` varchar(255) DEFAULT '$shop_name',
                    `adresse_entreprise` text,
                    `telephone_entreprise` varchar(20),
                    `email_entreprise` varchar(255),
                    `logo_path` varchar(255),
                    `theme_couleur` varchar(7) DEFAULT '#007bff',
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        foreach ($tables_sql as $table_name => $sql) {
            $shop_pdo->exec($sql);
            echo "&nbsp;&nbsp;📋 Table '$table_name' créée<br>";
        }
        
        // Insérer des données d'exemple
        echo "&nbsp;&nbsp;📊 Insertion de données d'exemple...<br>";
        
        // Configuration
        $shop_pdo->exec("INSERT IGNORE INTO configuration (nom_entreprise, adresse_entreprise, telephone_entreprise) 
                        VALUES ('$shop_name', '123 Rue Example, 06000 Nice', '04.93.00.00.00')");
        
        // Catégories
        $categories = [
            ['Réparation écran', 'Remplacement et réparation d\'écrans', '#e74c3c'],
            ['Réparation batterie', 'Changement de batteries', '#f39c12'],
            ['Réparation logiciel', 'Déblocage et mises à jour', '#3498db'],
            ['Accessoires', 'Vente d\'accessoires', '#2ecc71']
        ];
        
        $stmt = $shop_pdo->prepare("INSERT IGNORE INTO categories (nom, description, couleur) VALUES (?, ?, ?)");
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
        
        // Employés
        $employes = [
            ['Dupont', 'Jean', 'jean.dupont@' . $db_name . '.com', 'admin'],
            ['Martin', 'Sophie', 'sophie.martin@' . $db_name . '.com', 'technicien'],
            ['Bernard', 'Pierre', 'pierre.bernard@' . $db_name . '.com', 'vendeur']
        ];
        
        $stmt = $shop_pdo->prepare("INSERT IGNORE INTO employes (nom, prenom, email, role, date_embauche) VALUES (?, ?, ?, ?, CURDATE())");
        foreach ($employes as $emp) {
            $stmt->execute($emp);
        }
        
        // Clients
        $clients = [
            ['Durand', 'Marie', '06.12.34.56.78', 'marie.durand@email.com', '1 Place Masséna, 06000 Nice'],
            ['Petit', 'Paul', '06.87.65.43.21', 'paul.petit@email.com', '2 Promenade des Anglais, 06000 Nice'],
            ['Moreau', 'Claire', '06.11.22.33.44', 'claire.moreau@email.com', '3 Rue de France, 06000 Nice']
        ];
        
        $stmt = $shop_pdo->prepare("INSERT IGNORE INTO clients (nom, prenom, telephone, email, adresse) VALUES (?, ?, ?, ?, ?)");
        foreach ($clients as $client) {
            $stmt->execute($client);
        }
        
        // Réparations
        $reparations = [
            [1, 'iPhone 12', 'Écran cassé', 'en_cours', 150.00],
            [2, 'Samsung Galaxy S21', 'Batterie défaillante', 'en_attente', 80.00],
            [3, 'iPad Air', 'Problème de charge', 'termine', 95.00],
            [1, 'iPhone 13', 'Réparation bouton power', 'livre', 70.00]
        ];
        
        $stmt = $shop_pdo->prepare("INSERT IGNORE INTO reparations (client_id, appareil, probleme, statut, prix) VALUES (?, ?, ?, ?, ?)");
        foreach ($reparations as $rep) {
            $stmt->execute($rep);
        }
        
        echo "&nbsp;&nbsp;✅ <span style='color: green;'>Base '$db_name' configurée avec succès</span><br><br>";
    }
    
    echo "<h3>🎉 Création terminée !</h3>";
    echo "<p><strong>" . count($databases) . " bases de données</strong> créées avec des données d'exemple</p>";
    
    // Vérifier les bases créées
    echo "<h4>📊 Bases de données créées :</h4>";
    $stmt = $pdo->query("SHOW DATABASES");
    $all_dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($databases as $db_name => $shop_name) {
        if (in_array($db_name, $all_dbs)) {
            // Compter les tables
            $shop_pdo = new PDO("mysql:host={$config['host']};port={$config['port']};dbname=$db_name;charset=utf8mb4", 
                               $config['user'], $config['pass']);
            $stmt = $shop_pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<li>✅ <strong>$shop_name</strong> ($db_name) - " . count($tables) . " tables</li>";
        }
    }
    echo "</ul>";
    
    echo "<br><p><a href='database_manager.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Tester le Gestionnaire de Base de Données</a></p>";
    
} catch (Exception $e) {
    echo "❌ <span style='color: red;'>Erreur: " . $e->getMessage() . "</span>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
hr { margin: 20px 0; }
ul { margin: 10px 0; }
</style> 
// Script pour créer des bases de données d'exemple pour GeekBoard
echo "<h2>🏗️ Création des Bases de Données d'Exemple</h2>";
echo "<hr>";

try {
    // Configuration localhost
    $config = [
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'root',
        'pass' => ''
    ];
    
    // Connexion sans base spécifique
    $pdo = new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4", 
                   $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ <span style='color: green;'>Connexion MySQL réussie</span><br><br>";
    
    // Bases de données à créer
    $databases = [
        'cannesphones' => 'Cannes Phones',
        'psphonac' => 'PS Phone AC', 
        'mdgeek' => 'MD Geek'
    ];
    
    foreach ($databases as $db_name => $shop_name) {
        echo "<h3>🔧 Création de la base '$db_name' ($shop_name)</h3>";
        
        // Créer la base de données
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Base de données '$db_name' créée<br>";
        
        // Se connecter à la nouvelle base
        $shop_pdo = new PDO("mysql:host={$config['host']};port={$config['port']};dbname=$db_name;charset=utf8mb4", 
                           $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Créer les tables principales GeekBoard
        $tables_sql = [
            'clients' => "
                CREATE TABLE IF NOT EXISTS `clients` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `nom` varchar(255) NOT NULL,
                    `prenom` varchar(255) NOT NULL,
                    `telephone` varchar(20) DEFAULT NULL,
                    `email` varchar(255) DEFAULT NULL,
                    `adresse` text,
                    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_nom` (`nom`),
                    INDEX `idx_telephone` (`telephone`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'reparations' => "
                CREATE TABLE IF NOT EXISTS `reparations` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `client_id` int(11) NOT NULL,
                    `appareil` varchar(255) NOT NULL,
                    `probleme` text NOT NULL,
                    `statut` enum('en_attente','en_cours','termine','livre') DEFAULT 'en_attente',
                    `prix` decimal(10,2) DEFAULT NULL,
                    `date_depot` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `date_fin` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    INDEX `idx_client` (`client_id`),
                    INDEX `idx_statut` (`statut`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'employes' => "
                CREATE TABLE IF NOT EXISTS `employes` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `nom` varchar(255) NOT NULL,
                    `prenom` varchar(255) NOT NULL,
                    `email` varchar(255) UNIQUE DEFAULT NULL,
                    `role` enum('admin','technicien','vendeur') DEFAULT 'technicien',
                    `date_embauche` date DEFAULT NULL,
                    `actif` tinyint(1) DEFAULT 1,
                    PRIMARY KEY (`id`),
                    INDEX `idx_role` (`role`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'categories' => "
                CREATE TABLE IF NOT EXISTS `categories` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `nom` varchar(255) NOT NULL,
                    `description` text,
                    `couleur` varchar(7) DEFAULT '#007bff',
                    `actif` tinyint(1) DEFAULT 1,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `nom` (`nom`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'configuration' => "
                CREATE TABLE IF NOT EXISTS `configuration` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `nom_entreprise` varchar(255) DEFAULT '$shop_name',
                    `adresse_entreprise` text,
                    `telephone_entreprise` varchar(20),
                    `email_entreprise` varchar(255),
                    `logo_path` varchar(255),
                    `theme_couleur` varchar(7) DEFAULT '#007bff',
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        foreach ($tables_sql as $table_name => $sql) {
            $shop_pdo->exec($sql);
            echo "&nbsp;&nbsp;📋 Table '$table_name' créée<br>";
        }
        
        // Insérer des données d'exemple
        echo "&nbsp;&nbsp;📊 Insertion de données d'exemple...<br>";
        
        // Configuration
        $shop_pdo->exec("INSERT IGNORE INTO configuration (nom_entreprise, adresse_entreprise, telephone_entreprise) 
                        VALUES ('$shop_name', '123 Rue Example, 06000 Nice', '04.93.00.00.00')");
        
        // Catégories
        $categories = [
            ['Réparation écran', 'Remplacement et réparation d\'écrans', '#e74c3c'],
            ['Réparation batterie', 'Changement de batteries', '#f39c12'],
            ['Réparation logiciel', 'Déblocage et mises à jour', '#3498db'],
            ['Accessoires', 'Vente d\'accessoires', '#2ecc71']
        ];
        
        $stmt = $shop_pdo->prepare("INSERT IGNORE INTO categories (nom, description, couleur) VALUES (?, ?, ?)");
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
        
        // Employés
        $employes = [
            ['Dupont', 'Jean', 'jean.dupont@' . $db_name . '.com', 'admin'],
            ['Martin', 'Sophie', 'sophie.martin@' . $db_name . '.com', 'technicien'],
            ['Bernard', 'Pierre', 'pierre.bernard@' . $db_name . '.com', 'vendeur']
        ];
        
        $stmt = $shop_pdo->prepare("INSERT IGNORE INTO employes (nom, prenom, email, role, date_embauche) VALUES (?, ?, ?, ?, CURDATE())");
        foreach ($employes as $emp) {
            $stmt->execute($emp);
        }
        
        // Clients
        $clients = [
            ['Durand', 'Marie', '06.12.34.56.78', 'marie.durand@email.com', '1 Place Masséna, 06000 Nice'],
            ['Petit', 'Paul', '06.87.65.43.21', 'paul.petit@email.com', '2 Promenade des Anglais, 06000 Nice'],
            ['Moreau', 'Claire', '06.11.22.33.44', 'claire.moreau@email.com', '3 Rue de France, 06000 Nice']
        ];
        
        $stmt = $shop_pdo->prepare("INSERT IGNORE INTO clients (nom, prenom, telephone, email, adresse) VALUES (?, ?, ?, ?, ?)");
        foreach ($clients as $client) {
            $stmt->execute($client);
        }
        
        // Réparations
        $reparations = [
            [1, 'iPhone 12', 'Écran cassé', 'en_cours', 150.00],
            [2, 'Samsung Galaxy S21', 'Batterie défaillante', 'en_attente', 80.00],
            [3, 'iPad Air', 'Problème de charge', 'termine', 95.00],
            [1, 'iPhone 13', 'Réparation bouton power', 'livre', 70.00]
        ];
        
        $stmt = $shop_pdo->prepare("INSERT IGNORE INTO reparations (client_id, appareil, probleme, statut, prix) VALUES (?, ?, ?, ?, ?)");
        foreach ($reparations as $rep) {
            $stmt->execute($rep);
        }
        
        echo "&nbsp;&nbsp;✅ <span style='color: green;'>Base '$db_name' configurée avec succès</span><br><br>";
    }
    
    echo "<h3>🎉 Création terminée !</h3>";
    echo "<p><strong>" . count($databases) . " bases de données</strong> créées avec des données d'exemple</p>";
    
    // Vérifier les bases créées
    echo "<h4>📊 Bases de données créées :</h4>";
    $stmt = $pdo->query("SHOW DATABASES");
    $all_dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($databases as $db_name => $shop_name) {
        if (in_array($db_name, $all_dbs)) {
            // Compter les tables
            $shop_pdo = new PDO("mysql:host={$config['host']};port={$config['port']};dbname=$db_name;charset=utf8mb4", 
                               $config['user'], $config['pass']);
            $stmt = $shop_pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<li>✅ <strong>$shop_name</strong> ($db_name) - " . count($tables) . " tables</li>";
        }
    }
    echo "</ul>";
    
    echo "<br><p><a href='database_manager.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Tester le Gestionnaire de Base de Données</a></p>";
    
} catch (Exception $e) {
    echo "❌ <span style='color: red;'>Erreur: " . $e->getMessage() . "</span>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
hr { margin: 20px 0; }
ul { margin: 10px 0; }
</style> 