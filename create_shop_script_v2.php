<?php
/**
 * Script pour créer automatiquement un nouveau magasin - Version 2
 * - Crée la base de données
 * - Copie la structure depuis le modèle (avec gestion des contraintes FK)
 * - Enregistre le magasin dans la base générale
 */

// Inclure la configuration
require_once '/var/www/mdgeek.top/includes/config.php';

/**
 * Créer un nouveau magasin
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
    
    // 2. Copier la structure via mysqldump (plus fiable)
    echo "📋 Copie de la structure via mysqldump...\n";
    if (!copyShopStructureViaDump('cannesphones', $shop_name)) {
        echo "❌ Erreur lors de la copie de la structure\n";
        return false;
    }
    echo "✅ Structure copiée\n";
    
    // 3. Enregistrer dans la base générale
    echo "📝 Enregistrement dans la base générale...\n";
    if (!registerShopInGeneral($shop_name, $shop_display_name, $admin_email)) {
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
    
    return true;
}

/**
 * Copier la structure via mysqldump (plus fiable avec les contraintes)
 */
function copyShopStructureViaDump($source_shop, $target_shop) {
    $source_db = "geekboard_" . $source_shop;
    $target_db = "geekboard_" . $target_shop;
    
    // Créer un dump de la structure seulement (sans les données sensibles)
    $dump_file = "/tmp/shop_structure_{$source_shop}.sql";
    
    $dump_cmd = "mysqldump -u " . GENERAL_DB_USER . " -p'" . GENERAL_DB_PASS . "' " .
                "--no-data --routines --triggers " .
                "--single-transaction {$source_db} > {$dump_file}";
    
    echo "  → Création du dump de structure...\n";
    exec($dump_cmd, $output, $return_code);
    
    if ($return_code !== 0) {
        echo "❌ Erreur lors de la création du dump\n";
        return false;
    }
    
    // Importer dans la nouvelle base
    $import_cmd = "mysql -u " . GENERAL_DB_USER . " -p'" . GENERAL_DB_PASS . "' " .
                  "{$target_db} < {$dump_file}";
    
    echo "  → Import de la structure...\n";
    exec($import_cmd, $output, $return_code);
    
    if ($return_code !== 0) {
        echo "❌ Erreur lors de l'import\n";
        return false;
    }
    
    // Copier les données de référence essentielles
    echo "  → Copie des données de référence...\n";
    copyReferenceData($source_shop, $target_shop);
    
    // Nettoyer le fichier temporaire
    unlink($dump_file);
    
    return true;
}

/**
 * Copier uniquement les données de référence essentielles
 */
function copyReferenceData($source_shop, $target_shop) {
    try {
        $source_pdo = getShopDBConnection($source_shop);
        $target_pdo = getShopDBConnection($target_shop);
        
        // Tables de référence à copier
        $reference_tables = [
            'categories' => 'id',
            'marques' => 'id', 
            'modeles' => 'id'
        ];
        
        foreach ($reference_tables as $table => $id_column) {
            echo "    → {$table}...\n";
            
            // Vérifier si la table existe
            $check = $source_pdo->query("SHOW TABLES LIKE '{$table}'")->rowCount();
            if ($check == 0) continue;
            
            // Copier les données
            $data = $source_pdo->query("SELECT * FROM `{$table}` LIMIT 10")->fetchAll(); // Limiter pour éviter trop de données
            
            if (!empty($data)) {
                $columns = array_keys($data[0]);
                $placeholders = ':' . implode(', :', $columns);
                $columns_str = '`' . implode('`, `', $columns) . '`';
                
                $insert_sql = "INSERT IGNORE INTO `{$table}` ({$columns_str}) VALUES ({$placeholders})";
                $stmt = $target_pdo->prepare($insert_sql);
                
                foreach ($data as $row) {
                    try {
                        $stmt->execute($row);
                    } catch (Exception $e) {
                        // Ignorer les erreurs de duplication
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        echo "⚠️  Avertissement lors de la copie des données: " . $e->getMessage() . "\n";
    }
}

/**
 * Enregistrer le magasin dans la base générale
 */
function registerShopInGeneral($shop_name, $shop_display_name, $admin_email) {
    try {
        $general_pdo = getGeneralDBConnection();
        
        // Créer la table des magasins si elle n'existe pas
        $general_pdo->exec("
            CREATE TABLE IF NOT EXISTS shops (
                id INT AUTO_INCREMENT PRIMARY KEY,
                shop_name VARCHAR(50) UNIQUE NOT NULL,
                display_name VARCHAR(100) NOT NULL,
                admin_email VARCHAR(100) NOT NULL,
                subdomain VARCHAR(50) NOT NULL,
                database_name VARCHAR(100) NOT NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Insérer le nouveau magasin
        $stmt = $general_pdo->prepare("
            INSERT INTO shops (shop_name, display_name, admin_email, subdomain, database_name) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            display_name = VALUES(display_name),
            admin_email = VALUES(admin_email),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            $shop_name,
            $shop_display_name,
            $admin_email,
            $shop_name . '.mdgeek.top',
            'geekboard_' . $shop_name
        ]);
        
    } catch (Exception $e) {
        echo "❌ Erreur enregistrement: " . $e->getMessage() . "\n";
        return false;
    }
}

// Interface en ligne de commande
if (php_sapi_name() === 'cli') {
    if ($argc < 4) {
        echo "Usage: php create_shop_script_v2.php <shop_name> <display_name> <admin_email>\n";
        echo "Exemple: php create_shop_script_v2.php monmagasin \"Mon Magasin\" admin@monmagasin.com\n";
        exit(1);
    }
    
    $shop_name = $argv[1];
    $shop_display_name = $argv[2];
    $admin_email = $argv[3];
    
    createNewShop($shop_name, $shop_display_name, $admin_email);
}

?> 