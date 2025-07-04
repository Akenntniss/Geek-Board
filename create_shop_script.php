<?php
/**
 * Script pour créer automatiquement un nouveau magasin
 * - Crée la base de données
 * - Copie la structure depuis le modèle
 * - Enregistre le magasin dans la base générale
 */

// Inclure la configuration
require_once '/var/www/mdgeek.top/includes/config.php';

/**
 * Créer un nouveau magasin
 * @param string $shop_name Nom du magasin
 * @param string $shop_display_name Nom d'affichage du magasin
 * @param string $admin_email Email de l'administrateur
 * @return bool
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
    
    // 2. Copier la structure depuis cannesphones (magasin modèle)
    echo "📋 Copie de la structure depuis le magasin modèle...\n";
    if (!copyShopStructure('cannesphones', $shop_name)) {
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
 * Copier la structure d'un magasin existant vers un nouveau
 */
function copyShopStructure($source_shop, $target_shop) {
    try {
        // Connexions aux bases
        $source_pdo = getShopDBConnection($source_shop);
        $target_pdo = getShopDBConnection($target_shop);
        
        // Obtenir toutes les tables du magasin source
        $tables = $source_pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            echo "  → Copie de la table: {$table}\n";
            
            // Copier la structure
            $create_stmt = $source_pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $create_sql = $create_stmt['Create Table'];
            $target_pdo->exec($create_sql);
            
            // Copier les données pour certaines tables de référence
            $reference_tables = ['categories', 'marques', 'modeles', 'employés'];
            if (in_array($table, $reference_tables)) {
                echo "    → Copie des données de référence\n";
                $data = $source_pdo->query("SELECT * FROM `{$table}`")->fetchAll();
                
                if (!empty($data)) {
                    $columns = array_keys($data[0]);
                    $placeholders = ':' . implode(', :', $columns);
                    $columns_str = '`' . implode('`, `', $columns) . '`';
                    
                    $insert_sql = "INSERT INTO `{$table}` ({$columns_str}) VALUES ({$placeholders})";
                    $stmt = $target_pdo->prepare($insert_sql);
                    
                    foreach ($data as $row) {
                        $stmt->execute($row);
                    }
                }
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ Erreur lors de la copie: " . $e->getMessage() . "\n";
        return false;
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
        echo "Usage: php create_shop_script.php <shop_name> <display_name> <admin_email>\n";
        echo "Exemple: php create_shop_script.php monmagasin \"Mon Magasin\" admin@monmagasin.com\n";
        exit(1);
    }
    
    $shop_name = $argv[1];
    $shop_display_name = $argv[2];
    $admin_email = $argv[3];
    
    createNewShop($shop_name, $shop_display_name, $admin_email);
} else {
    // Interface web (pour intégration future)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $shop_name = $_POST['shop_name'] ?? '';
        $shop_display_name = $_POST['shop_display_name'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        
        header('Content-Type: application/json');
        $result = createNewShop($shop_name, $shop_display_name, $admin_email);
        echo json_encode(['success' => $result]);
    }
}

?> 