<?php
// Script de création de la table des magasins pour le système multi-magasin
require_once('../config/database.php');

try {
    $pdo = getMainDBConnection();
    
    // Vérifier si la table existe déjà
    $stmt = $pdo->query("SHOW TABLES LIKE 'shops'");
    if ($stmt->rowCount() > 0) {
        echo "La table 'shops' existe déjà.<br>";
    } else {
        // Création de la table des magasins
        $sql = "CREATE TABLE shops (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            address TEXT,
            city VARCHAR(100),
            postal_code VARCHAR(20),
            country VARCHAR(100) DEFAULT 'France',
            phone VARCHAR(20),
            email VARCHAR(100),
            website VARCHAR(200),
            logo VARCHAR(255),
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Informations de connexion à la base de données
            db_host VARCHAR(255) NOT NULL,
            db_port VARCHAR(10) DEFAULT '3306',
            db_name VARCHAR(100) NOT NULL,
            db_user VARCHAR(100) NOT NULL,
            db_pass VARCHAR(255) NOT NULL,
            subdomain VARCHAR(50) UNIQUE
        )";
        
        $pdo->exec($sql);
        echo "Table 'shops' créée avec succès.<br>";
        
        // Insertion du magasin principal (existant)
        $sql = "INSERT INTO shops (
            name, description, active, 
            db_host, db_port, db_name, db_user, db_pass
        ) VALUES (
            'Magasin Principal', 'Magasin existant migré vers le système multi-magasin', 1,
            ?, ?, ?, ?, ?
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            MAIN_DB_HOST,
            MAIN_DB_PORT,
            MAIN_DB_NAME,
            MAIN_DB_USER,
            MAIN_DB_PASS
        ]);
        
        echo "Magasin principal ajouté avec succès.<br>";
    }
    
    // Vérifier si la table des administrateurs de magasin existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'shop_admins'");
    if ($stmt->rowCount() > 0) {
        echo "La table 'shop_admins' existe déjà.<br>";
    } else {
        // Création de la table des administrateurs de magasin
        $sql = "CREATE TABLE shop_admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (username),
            UNIQUE KEY (email),
            FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($sql);
        echo "Table 'shop_admins' créée avec succès.<br>";
    }
    
    // Vérifier si la table des super administrateurs existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'superadmins'");
    if ($stmt->rowCount() > 0) {
        echo "La table 'superadmins' existe déjà.<br>";
    } else {
        // Création de la table des super administrateurs
        $sql = "CREATE TABLE superadmins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (username),
            UNIQUE KEY (email)
        )";
        
        $pdo->exec($sql);
        echo "Table 'superadmins' créée avec succès.<br>";
        
        // Création d'un super administrateur par défaut (à modifier après installation)
        $default_password = password_hash('SuperAdmin2024!', PASSWORD_DEFAULT);
        $sql = "INSERT INTO superadmins (username, password, full_name, email) 
                VALUES ('superadmin', ?, 'Super Administrateur', 'admin@geekboard.com')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$default_password]);
        
        echo "Super administrateur par défaut créé avec succès.<br>";
        echo "Nom d'utilisateur: superadmin<br>";
        echo "Mot de passe: SuperAdmin2024!<br>";
        echo "<strong>IMPORTANT: Changez ce mot de passe immédiatement après la première connexion!</strong><br>";
    }
    
    echo "<br>Installation de la structure multi-magasins terminée avec succès.";
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?> 