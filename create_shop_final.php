<?php
/**
 * Script final pour créer automatiquement un nouveau magasin
 * Compatible avec la structure existante de la table shops
 */

// Inclure la configuration
require_once '/var/www/mdgeek.top/includes/config.php';

/**
 * Créer un nouveau magasin avec la structure existante
 */
function createNewShop($shop_name, $shop_display_name, $admin_email) {
    // Normaliser le nom du magasin
    $shop_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $shop_name));
    
    if (empty($shop_name)) {
        echo "❌ Nom de magasin invalide\n";
        return false;
    }
    
    echo "🚀 Création du magasin: {$shop_name}\n";
    
    // 1. Créer la base de données
    echo "📦 Création de la base de données...\n";
    if (!createShopDatabase($shop_name)) {
        echo "❌ Erreur lors de la création de la base de données\n";
        return false;
    }
    echo "✅ Base de données créée: geekboard_{$shop_name}\n";
    
    // 2. Copier la structure (version simplifiée)
    echo "📋 Copie de la structure de base...\n";
    if (!createBasicShopStructure($shop_name)) {
        echo "❌ Erreur lors de la création de la structure\n";
        return false;
    }
    echo "✅ Structure de base créée\n";
    
    // 3. Enregistrer dans la table shops existante
    echo "📝 Enregistrement dans la table shops...\n";
    if (!registerShopInExistingTable($shop_name, $shop_display_name, $admin_email)) {
        echo "❌ Erreur lors de l'enregistrement\n";
        return false;
    }
    echo "✅ Magasin enregistré\n";
    
    // 4. Afficher les informations
    echo "\n🎉 MAGASIN CRÉÉ AVEC SUCCÈS !\n";
    echo "=====================================\n";
    echo "Nom du magasin: {$shop_name}\n";
    echo "Nom d'affichage: {$shop_display_name}\n";
    echo "URL: http://{$shop_name}.mdgeek.top\n";
    echo "Base de données: geekboard_{$shop_name}\n";
    echo "Email admin: {$admin_email}\n";
    echo "=====================================\n";
    echo "\n📋 PROCHAINES ÉTAPES :\n";
    echo "1. Configurez les DNS sur Hostinger (wildcard *.mdgeek.top)\n";
    echo "2. Testez l'accès : http://{$shop_name}.mdgeek.top\n";
    echo "3. Importez les données si nécessaire\n";
    
    return true;
}

/**
 * Créer une structure de base simple pour le magasin
 */
function createBasicShopStructure($shop_name) {
    try {
        $target_pdo = getShopDBConnection($shop_name);
        
        // Créer quelques tables de base essentielles
        $basic_tables = [
            "CREATE TABLE IF NOT EXISTS clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100),
                prenom VARCHAR(100),
                telephone VARCHAR(20),
                email VARCHAR(100),
                adresse TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS reparations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT,
                appareil VARCHAR(100),
                probleme TEXT,
                statut VARCHAR(50) DEFAULT 'en_attente',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id)
            )",
            "CREATE TABLE IF NOT EXISTS employes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100),
                prenom VARCHAR(100),
                email VARCHAR(100),
                role VARCHAR(50) DEFAULT 'technicien',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($basic_tables as $sql) {
            $target_pdo->exec($sql);
        }
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ Erreur structure: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Enregistrer le magasin dans la table shops existante
 */
function registerShopInExistingTable($shop_name, $shop_display_name, $admin_email) {
    try {
        // Connexion directe avec les paramètres root pour éviter les problèmes
        $dsn = "mysql:host=localhost;port=3306;dbname=geekboard_general;charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', 'Mamanmaman01#', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Insérer dans la table shops avec la structure existante
        $stmt = $pdo->prepare("
            INSERT INTO shops (
                name, 
                description, 
                email, 
                subdomain, 
                db_host, 
                db_port, 
                db_name, 
                db_user, 
                db_pass,
                active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            description = VALUES(description),
            email = VALUES(email),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            $shop_display_name,                    // name
            "Magasin {$shop_display_name}",        // description
            $admin_email,                          // email
            $shop_name,                            // subdomain
            'localhost',                           // db_host
            '3306',                               // db_port
            'geekboard_' . $shop_name,            // db_name
            'geekboard_user',                     // db_user
            'GeekBoard2024#',                     // db_pass
            1                                     // active
        ]);
        
    } catch (Exception $e) {
        echo "❌ Erreur enregistrement shops: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Lister tous les magasins existants
 */
function listExistingShops() {
    try {
        $dsn = "mysql:host=localhost;port=3306;dbname=geekboard_general;charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', 'Mamanmaman01#');
        
        $shops = $pdo->query("SELECT id, name, subdomain, db_name, active, created_at FROM shops ORDER BY id")->fetchAll();
        
        echo "\n📋 MAGASINS EXISTANTS :\n";
        echo "================================\n";
        foreach ($shops as $shop) {
            $status = $shop['active'] ? '✅' : '❌';
            echo "{$status} {$shop['name']} (ID: {$shop['id']})\n";
            echo "   → URL: http://{$shop['subdomain']}.mdgeek.top\n";
            echo "   → Base: {$shop['db_name']}\n";
            echo "   → Créé: {$shop['created_at']}\n\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur listage: " . $e->getMessage() . "\n";
    }
}

// Interface en ligne de commande
if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Usage:\n";
        echo "  php create_shop_final.php list\n";
        echo "  php create_shop_final.php create <shop_name> <display_name> <admin_email>\n";
        echo "\nExemples:\n";
        echo "  php create_shop_final.php list\n";
        echo "  php create_shop_final.php create monmagasin \"Mon Magasin\" admin@monmagasin.com\n";
        exit(1);
    }
    
    $action = $argv[1];
    
    if ($action === 'list') {
        listExistingShops();
    } elseif ($action === 'create' && $argc >= 5) {
        $shop_name = $argv[2];
        $shop_display_name = $argv[3];
        $admin_email = $argv[4];
        
        createNewShop($shop_name, $shop_display_name, $admin_email);
    } else {
        echo "❌ Arguments invalides\n";
        exit(1);
    }
}

?> 