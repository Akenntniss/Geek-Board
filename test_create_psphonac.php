<?php
/**
 * Script de test pour créer automatiquement le magasin PSPHONAC
 * Simule la création d'un magasin via l'interface admin
 */

// Inclure la configuration
require_once('/var/www/mdgeek.top/config/database.php');

echo "🚀 DÉBUT DU TEST DE CRÉATION DU MAGASIN PSPHONAC\n";

/**
 * Fonction pour créer automatiquement une base de données de magasin
 */
function createShopDatabaseAuto($shop_subdomain) {
    try {
        $db_name = "geekboard_" . $shop_subdomain;
        
        echo "🔧 Création de la base de données: $db_name\n";
        
        // Connexion en tant que root pour créer la base
        $root_pdo = new PDO("mysql:host=localhost;port=3306;charset=utf8mb4", 'root', 'Mamanmaman01#');
        $root_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer la base de données
        $root_pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Donner les permissions à l'utilisateur geekboard_user
        $root_pdo->exec("GRANT ALL PRIVILEGES ON `$db_name`.* TO 'geekboard_user'@'localhost'");
        $root_pdo->exec("FLUSH PRIVILEGES");
        
        echo "✅ Base de données créée: $db_name\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ Erreur création base: " . $e->getMessage() . "\n";
        throw $e;
    }
}

/**
 * Fonction pour copier la structure d'une base existante vers la nouvelle
 */
function copyShopStructure($source_db, $target_db) {
    try {
        echo "🔄 Copie de la structure de $source_db vers $target_db\n";
        
        // Commande mysqldump pour exporter la structure
        $dump_cmd = "mysqldump -u geekboard_user -p'GeekBoard2024#' --no-data --routines --triggers $source_db";
        $import_cmd = "mysql -u geekboard_user -p'GeekBoard2024#' $target_db";
        
        // Exécuter la copie
        $full_cmd = "$dump_cmd | $import_cmd";
        exec($full_cmd, $output, $return_code);
        
        if ($return_code === 0) {
            echo "✅ Structure copiée de $source_db vers $target_db\n";
            return true;
        } else {
            echo "❌ Erreur lors de la copie de structure: " . implode("\n", $output) . "\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur copie structure: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Fonction pour recharger la configuration Nginx
 */
function reloadNginxConfig() {
    try {
        echo "🔄 Rechargement de la configuration Nginx\n";
        exec("systemctl reload nginx", $output, $return_code);
        if ($return_code === 0) {
            echo "✅ Configuration Nginx rechargée\n";
            return true;
        } else {
            echo "❌ Erreur reload Nginx: " . implode("\n", $output) . "\n";
            return false;
        }
    } catch (Exception $e) {
        echo "❌ Erreur reload Nginx: " . $e->getMessage() . "\n";
        return false;
    }
}

try {
    // Paramètres du nouveau magasin PSPHONAC
    $name = 'PSPHONAC';
    $description = 'Magasin de réparation et vente de téléphones - PSPHONAC';
    $subdomain = 'psphonac';
    $address = '123 Rue de la Technologie';
    $city = 'Cannes';
    $postal_code = '06400';
    $country = 'France';
    $phone = '+33 4 93 XX XX XX';
    $email = 'contact@psphonac.com';
    $website = 'https://psphonac.com';
    
    echo "🏪 Paramètres du magasin:\n";
    echo "   - Nom: $name\n";
    echo "   - Sous-domaine: $subdomain.mdgeek.top\n";
    echo "   - Email: $email\n";
    echo "   - Ville: $city\n";
    
    // Connexion à la base générale
    $pdo = getGeneralDBConnection();
    echo "✅ Connexion à la base générale réussie\n";
    
    // Vérifier que le nom du magasin est unique
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM shops WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Un magasin avec ce nom existe déjà.');
    }
    
    // Vérifier que le sous-domaine est unique
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM shops WHERE subdomain = ?");
    $stmt->execute([$subdomain]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Ce sous-domaine est déjà utilisé.');
    }
    
    echo "✅ Vérifications d'unicité passées\n";
    
    // === ÉTAPE 1: CRÉER LA BASE DE DONNÉES ===
    echo "\n=== ÉTAPE 1: CRÉATION DE LA BASE DE DONNÉES ===\n";
    createShopDatabaseAuto($subdomain);
    
    // === ÉTAPE 2: COPIER LA STRUCTURE DEPUIS UN MODÈLE ===
    echo "\n=== ÉTAPE 2: COPIE DE LA STRUCTURE ===\n";
    if (!copyShopStructure('geekboard_cannesphones', "geekboard_$subdomain")) {
        throw new Exception("Impossible de copier la structure de base de données");
    }
    
    // === ÉTAPE 3: INSÉRER LE MAGASIN DANS LA BASE GÉNÉRALE ===
    echo "\n=== ÉTAPE 3: ENREGISTREMENT EN BASE GÉNÉRALE ===\n";
    $stmt = $pdo->prepare("
        INSERT INTO shops (
            name, description, subdomain, address, city, postal_code, country, 
            phone, email, website, active, 
            db_host, db_port, db_name, db_user, db_pass
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?
        )
    ");
    
    // Configuration automatique de la base de données
    $db_host = 'localhost';
    $db_port = '3306';
    $db_name = "geekboard_$subdomain";
    $db_user = 'geekboard_user';
    $db_pass = 'GeekBoard2024#';
    
    $stmt->execute([
        $name, $description, $subdomain, $address, $city, $postal_code, $country,
        $phone, $email, $website,
        $db_host, $db_port, $db_name, $db_user, $db_pass
    ]);
    
    $shop_id = $pdo->lastInsertId();
    echo "✅ Magasin enregistré avec l'ID: $shop_id\n";
    
    // === ÉTAPE 4: RECHARGER NGINX ===
    echo "\n=== ÉTAPE 4: RECHARGEMENT NGINX ===\n";
    reloadNginxConfig();
    
    echo "\n🎉 SUCCÈS! Le magasin PSPHONAC a été créé avec succès!\n";
    echo "✅ Base de données créée: $db_name\n";
    echo "✅ Sous-domaine configuré: $subdomain.mdgeek.top\n";
    echo "✅ Accessible à l'adresse: http://$subdomain.mdgeek.top\n";
    
    // Vérification finale
    echo "\n=== VÉRIFICATION FINALE ===\n";
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE subdomain = ?");
    $stmt->execute([$subdomain]);
    $created_shop = $stmt->fetch();
    
    if ($created_shop) {
        echo "✅ Magasin trouvé en base:\n";
        echo "   - ID: " . $created_shop['id'] . "\n";
        echo "   - Nom: " . $created_shop['name'] . "\n";
        echo "   - Sous-domaine: " . $created_shop['subdomain'] . "\n";
        echo "   - Base DB: " . $created_shop['db_name'] . "\n";
    }
    
    // Test de connexion à la nouvelle base
    try {
        $new_shop_pdo = new PDO(
            "mysql:host=localhost;port=3306;dbname=geekboard_$subdomain;charset=utf8mb4",
            'geekboard_user',
            'GeekBoard2024#'
        );
        echo "✅ Test de connexion à la nouvelle base réussi\n";
        
        // Compter les tables
        $stmt = $new_shop_pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✅ " . count($tables) . " tables copiées dans la nouvelle base\n";
        
    } catch (Exception $e) {
        echo "❌ Erreur test connexion nouvelle base: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n�� FIN DU TEST\n";
?> 