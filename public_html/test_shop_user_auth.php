<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once 'config/database.php';

echo "<h1>Test d'authentification d'un utilisateur de magasin</h1>";

// Vérifier si un shop_id est spécifié
$shop_id = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : null;

if (!$shop_id) {
    echo "<div style='color:red'>Veuillez spécifier un ID de magasin dans l'URL: ?shop_id=X</div>";
    exit;
}

// Utiliser un mot de passe simple pour le test
$test_username = 'admin';
$test_password = 'Admin123!';

try {
    // Se connecter à la base de données principale pour récupérer les infos du magasin
    $pdo_main = getMainDBConnection();
    echo "<p>✓ Connexion à la base de données principale réussie</p>";
    
    // 1. Vérifier si le magasin existe
    echo "<h2>1. Vérification de l'existence du magasin #$shop_id</h2>";
    $stmt = $pdo_main->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        echo "<p style='color:red'>⚠ Aucun magasin trouvé avec l'ID $shop_id!</p>";
        exit;
    }
    
    echo "<p style='color:green'>✓ Magasin trouvé: <strong>" . htmlspecialchars($shop['name']) . "</strong></p>";
    echo "<pre>" . print_r($shop, true) . "</pre>";
    
    // 2. Tester la connexion à la base de données du magasin
    echo "<h2>2. Test de connexion à la base de données du magasin</h2>";
    
    $shop_config = [
        'host' => $shop['db_host'],
        'port' => $shop['db_port'],
        'dbname' => $shop['db_name'],
        'user' => $shop['db_user'],
        'pass' => $shop['db_pass']
    ];
    
    echo "<p>Tentative de connexion avec les paramètres suivants:</p>";
    echo "<ul>";
    echo "<li>Hôte: " . htmlspecialchars($shop_config['host']) . "</li>";
    echo "<li>Port: " . htmlspecialchars($shop_config['port']) . "</li>";
    echo "<li>Base de données: " . htmlspecialchars($shop_config['dbname']) . "</li>";
    echo "<li>Utilisateur: " . htmlspecialchars($shop_config['user']) . "</li>";
    echo "</ul>";
    
    $shop_pdo = connectToShopDB($shop_config);
    
    if ($shop_pdo) {
        echo "<p style='color:green'>✓ Connexion à la base de données du magasin réussie</p>";
        
        // 3. Tester l'authentification d'un utilisateur
        echo "<h2>3. Test d'authentification d'un utilisateur</h2>";
        echo "<p>Tentative de connexion avec l'utilisateur: <strong>$test_username</strong></p>";
        
        $stmt = $shop_pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$test_username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "<p style='color:red'>⚠ Aucun utilisateur trouvé avec le nom d'utilisateur '$test_username'</p>";
            
            // Optionnel: créer l'utilisateur admin s'il n'existe pas
            if (isset($_GET['create_admin']) && $_GET['create_admin'] == '1') {
                $password_hash = password_hash($test_password, PASSWORD_DEFAULT);
                $stmt = $shop_pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$test_username, $password_hash, 'Administrateur', 'admin'])) {
                    echo "<p style='color:green'>✓ Utilisateur admin créé avec succès</p>";
                } else {
                    echo "<p style='color:red'>⚠ Échec de la création de l'utilisateur admin</p>";
                }
            } else {
                echo "<p>Pour créer l'utilisateur admin, ajoutez <a href='?shop_id=$shop_id&create_admin=1'>?shop_id=$shop_id&create_admin=1</a> à l'URL</p>";
            }
        } else {
            echo "<p>Utilisateur trouvé, ID: " . $user['id'] . "</p>";
            
            // Vérifier le mot de passe
            if (password_verify($test_password, $user['password'])) {
                echo "<p style='color:green'>✓ Authentification réussie avec password_verify()</p>";
            } else {
                echo "<p style='color:red'>⚠ Échec de l'authentification avec password_verify()</p>";
                echo "<p>Password hash stocké: " . $user['password'] . "</p>";
                
                // Information de débogage supplémentaire
                echo "<p>PHP version: " . PHP_VERSION . "</p>";
                echo "<p>Password hash info: " . print_r(password_get_info($user['password']), true) . "</p>";
                
                // Optionnel: mettre à jour le mot de passe
                if (isset($_GET['update_password']) && $_GET['update_password'] == '1') {
                    $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
                    echo "<p>Nouveau hash de mot de passe: " . $new_hash . "</p>";
                    
                    $stmt = $shop_pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
                    if ($stmt->execute([$new_hash, $test_username])) {
                        echo "<p style='color:green'>✓ Mot de passe mis à jour avec succès</p>";
                    } else {
                        echo "<p style='color:red'>⚠ Échec de la mise à jour du mot de passe</p>";
                    }
                } else {
                    echo "<p>Pour mettre à jour le mot de passe, cliquez <a href='?shop_id=$shop_id&update_password=1'>ici</a></p>";
                }
            }
        }
    } else {
        echo "<p style='color:red'>⚠ Échec de la connexion à la base de données du magasin!</p>";
        echo "<p>Vérifiez les informations de connexion.</p>";
    }
    
    echo "<h2>5. Lien pour tester la connexion via le formulaire normal</h2>";
    echo "<p>Utilisez ces identifiants pour vous connecter:</p>";
    echo "<ul>";
    echo "<li>Nom d'utilisateur: <strong>$test_username</strong></li>";
    echo "<li>Mot de passe: <strong>$test_password</strong></li>";
    echo "</ul>";
    
    echo "<p><a href='pages/login.php?shop_id=$shop_id' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Se connecter maintenant</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Erreur: " . $e->getMessage() . "</p>";
} 