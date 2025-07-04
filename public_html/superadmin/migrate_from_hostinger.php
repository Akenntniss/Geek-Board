<?php
// Script de migration des données depuis Hostinger vers la base locale
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

echo "<h1>🔄 Migration depuis Hostinger vers base locale</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .button { background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; }
</style>";

// Configuration Hostinger (source)
$hostinger_config = [
    'host' => 'localhost',
    'port' => '3306',
    'user' => 'root',
    'pass' => '',
    'name' => 'geekboard_main'
];

// Configuration locale (destination)
$local_config = [
    'host' => 'localhost',
    'port' => '3306',
    'user' => 'root',
    'pass' => '',
    'name' => 'geekboard_main'
];

// Fonction pour créer une connexion PDO
function createConnection($config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset=utf8mb4";
        return new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    } catch (PDOException $e) {
        return null;
    }
}

// Traitement de la migration si demandée
if (isset($_POST['migrate'])) {
    echo "<div class='section'>";
    echo "<h2>🚀 Démarrage de la migration</h2>";
    
    // Étape 1: Connexion à Hostinger
    echo "<p>1. Connexion à la base Hostinger...</p>";
    $pdo_hostinger = createConnection($hostinger_config);
    if (!$pdo_hostinger) {
        echo "<p class='error'>❌ Échec de connexion à Hostinger</p>";
        echo "</div>";
        exit;
    }
    echo "<p class='success'>✅ Connexion Hostinger réussie</p>";
    
    // Étape 2: Connexion locale
    echo "<p>2. Connexion à la base locale...</p>";
    $pdo_local = createConnection($local_config);
    if (!$pdo_local) {
        echo "<p class='error'>❌ Échec de connexion locale</p>";
        echo "<p class='info'>Création de la base de données locale...</p>";
        
        // Tenter de créer la base de données
        try {
            $pdo_root = new PDO("mysql:host=localhost;port=3306", 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo_root->exec("CREATE DATABASE IF NOT EXISTS geekboard_main");
            echo "<p class='success'>✅ Base de données 'geekboard_main' créée</p>";
            
            // Essayer de se reconnecter
            $pdo_local = createConnection($local_config);
            if ($pdo_local) {
                echo "<p class='success'>✅ Connexion locale réussie</p>";
            } else {
                echo "<p class='error'>❌ Connexion locale toujours en échec</p>";
                echo "</div>";
                exit;
            }
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Impossible de créer la base: " . $e->getMessage() . "</p>";
            echo "</div>";
            exit;
        }
    } else {
        echo "<p class='success'>✅ Connexion locale réussie</p>";
    }
    
    // Étape 3: Récupération des données depuis Hostinger
    echo "<p>3. Récupération des magasins depuis Hostinger...</p>";
    try {
        $stmt = $pdo_hostinger->query("SELECT * FROM shops ORDER BY id");
        $shops = $stmt->fetchAll();
        echo "<p class='info'>Trouvé " . count($shops) . " magasins sur Hostinger</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Erreur lecture Hostinger: " . $e->getMessage() . "</p>";
        echo "</div>";
        exit;
    }
    
    // Étape 4: Création de la table shops locale
    echo "<p>4. Création de la table shops locale...</p>";
    try {
        $create_table_sql = "
        CREATE TABLE IF NOT EXISTS shops (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            subdomain varchar(100) NOT NULL,
            address text,
            city varchar(100) DEFAULT NULL,
            postal_code varchar(20) DEFAULT NULL,
            country varchar(100) DEFAULT 'France',
            phone varchar(20) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            website varchar(255) DEFAULT NULL,
            db_host varchar(255) DEFAULT 'localhost',
            db_port varchar(10) DEFAULT '3306',
            db_name varchar(255) NOT NULL,
            db_user varchar(255) NOT NULL,
            db_pass varchar(255) NOT NULL,
            active tinyint(1) DEFAULT 1,
            created_at timestamp NOT NULL DEFAULT current_timestamp(),
            updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (id),
            UNIQUE KEY subdomain (subdomain),
            UNIQUE KEY db_name (db_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo_local->exec($create_table_sql);
        echo "<p class='success'>✅ Table shops créée/vérifiée</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Erreur création table: " . $e->getMessage() . "</p>";
        echo "</div>";
        exit;
    }
    
    // Étape 5: Migration des données
    echo "<p>5. Migration des magasins...</p>";
    $migrated = 0;
    $errors = 0;
    
    foreach ($shops as $shop) {
        try {
            // Vérifier si le magasin existe déjà
            $stmt = $pdo_local->prepare("SELECT id FROM shops WHERE id = ? OR subdomain = ?");
            $stmt->execute([$shop['id'], $shop['subdomain']]);
            
            if ($stmt->fetch()) {
                // Mettre à jour
                $stmt = $pdo_local->prepare("
                    UPDATE shops SET 
                    name = ?, description = ?, subdomain = ?, address = ?, 
                    city = ?, postal_code = ?, country = ?, phone = ?, 
                    email = ?, website = ?, db_host = ?, db_port = ?, 
                    db_name = ?, db_user = ?, db_pass = ?, active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $shop['name'], $shop['description'], $shop['subdomain'], $shop['address'],
                    $shop['city'], $shop['postal_code'], $shop['country'], $shop['phone'],
                    $shop['email'], $shop['website'], $shop['db_host'], $shop['db_port'],
                    $shop['db_name'], $shop['db_user'], $shop['db_pass'], $shop['active'],
                    $shop['id']
                ]);
                echo "<p class='info'>↻ Magasin mis à jour: " . htmlspecialchars($shop['name']) . "</p>";
            } else {
                // Insérer nouveau
                $stmt = $pdo_local->prepare("
                    INSERT INTO shops (
                        id, name, description, subdomain, address, city, postal_code, 
                        country, phone, email, website, db_host, db_port, db_name, 
                        db_user, db_pass, active, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $shop['id'], $shop['name'], $shop['description'], $shop['subdomain'],
                    $shop['address'], $shop['city'], $shop['postal_code'], $shop['country'],
                    $shop['phone'], $shop['email'], $shop['website'], $shop['db_host'],
                    $shop['db_port'], $shop['db_name'], $shop['db_user'], $shop['db_pass'],
                    $shop['active'], $shop['created_at'], $shop['updated_at']
                ]);
                echo "<p class='success'>+ Magasin ajouté: " . htmlspecialchars($shop['name']) . "</p>";
            }
            $migrated++;
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Erreur magasin " . htmlspecialchars($shop['name']) . ": " . $e->getMessage() . "</p>";
            $errors++;
        }
    }
    
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
    echo "<p><strong>Résultats de la migration:</strong></p>";
    echo "<p class='success'>✅ Magasins migrés: $migrated</p>";
    if ($errors > 0) {
        echo "<p class='error'>❌ Erreurs: $errors</p>";
    }
    echo "</div>";
    
    echo "</div>";
    
    // Redirection après succès
    if ($migrated > 0 && $errors == 0) {
        echo "<script>
            alert('Migration terminée avec succès ! Redirection vers la page d\\'accueil...');
            setTimeout(function() {
                window.location.href = 'index.php';
            }, 3000);
        </script>";
    }
} else {
    // Affichage du diagnostic et formulaire de migration
    echo "<div class='section'>";
    echo "<h2>🔍 Diagnostic avant migration</h2>";
    
    // Test Hostinger
    echo "<p><strong>Test connexion Hostinger:</strong></p>";
    $pdo_hostinger = createConnection($hostinger_config);
    if ($pdo_hostinger) {
        echo "<p class='success'>✅ Connexion Hostinger OK</p>";
        try {
            $stmt = $pdo_hostinger->query("SELECT COUNT(*) as count FROM shops");
            $count = $stmt->fetch();
            echo "<p class='info'>Magasins sur Hostinger: " . $count['count'] . "</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Erreur lecture Hostinger: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='error'>❌ Connexion Hostinger échouée</p>";
    }
    
    // Test local
    echo "<p><strong>Test connexion locale:</strong></p>";
    $pdo_local = createConnection($local_config);
    if ($pdo_local) {
        echo "<p class='success'>✅ Connexion locale OK</p>";
        try {
            $stmt = $pdo_local->query("SELECT COUNT(*) as count FROM shops");
            $count = $stmt->fetch();
            echo "<p class='info'>Magasins en local: " . $count['count'] . "</p>";
        } catch (PDOException $e) {
            echo "<p class='warning'>⚠️ Table shops locale non trouvée (sera créée)</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ Base locale non trouvée (sera créée)</p>";
    }
    
    echo "</div>";
    
    // Formulaire de migration
    echo "<div class='section'>";
    echo "<h2>🚀 Lancer la migration</h2>";
    echo "<p>Cette opération va:</p>";
    echo "<ul>";
    echo "<li>Créer la base de données locale si nécessaire</li>";
    echo "<li>Créer la table shops si nécessaire</li>";
    echo "<li>Copier tous les magasins depuis Hostinger</li>";
    echo "<li>Mettre à jour les magasins existants</li>";
    echo "</ul>";
    
    echo "<form method='post'>";
    echo "<input type='hidden' name='migrate' value='1'>";
    echo "<button type='submit' class='button' onclick=\"return confirm('Êtes-vous sûr de vouloir lancer la migration ?')\">🔄 Démarrer la migration</button>";
    echo "</form>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Retour au dashboard</a></p>";
echo "<p><a href='test_localhost_db.php'>🔍 Diagnostic base locale</a></p>";
?> 