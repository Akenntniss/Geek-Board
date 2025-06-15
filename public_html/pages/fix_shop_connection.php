<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once 'config/database.php';

// S'assurer que l'accès est limité
if (!isset($_SERVER['REMOTE_ADDR']) || $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
    // Décommenter cette ligne pour n'autoriser que les accès locaux
    // die("Accès non autorisé");
}

echo "<h1>Outil de correction de la connexion du magasin</h1>";

// Vérifier si un shop_id est spécifié
$shop_id = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : null;

if (!$shop_id) {
    echo "<div style='color:red'>Veuillez spécifier un ID de magasin dans l'URL: ?shop_id=X</div>";
    exit;
}

// Se connecter à la base de données principale
try {
    $pdo_main = getMainDBConnection();
    echo "<p>✓ Connexion à la base de données principale réussie</p>";
    
    // Récupérer les informations du magasin
    $stmt = $pdo_main->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        echo "<div style='color:red'>Aucun magasin trouvé avec l'ID: $shop_id</div>";
        exit;
    }
    
    echo "<h2>Informations du magasin: " . htmlspecialchars($shop['name']) . "</h2>";
    
    // Afficher les informations de connexion actuelles
    echo "<h3>Paramètres de connexion actuels:</h3>";
    echo "<ul>";
    echo "<li>Hôte: " . htmlspecialchars($shop['db_host']) . "</li>";
    echo "<li>Port: " . htmlspecialchars($shop['db_port']) . "</li>";
    echo "<li>Base de données: " . htmlspecialchars($shop['db_name']) . "</li>";
    echo "<li>Utilisateur: " . htmlspecialchars($shop['db_user']) . "</li>";
    echo "<li>Mot de passe: ********</li>";
    echo "</ul>";
    
    // Tester la connexion avec les paramètres actuels
    $shop_config = [
        'host' => $shop['db_host'],
        'port' => $shop['db_port'],
        'dbname' => $shop['db_name'],
        'user' => $shop['db_user'],
        'pass' => $shop['db_pass']
    ];
    
    $shop_db = connectToShopDB($shop_config);
    
    if ($shop_db) {
        echo "<div style='color:green'>✓ Connexion à la base de données du magasin réussie avec les paramètres actuels</div>";
        
        // Vérifier les tables existantes
        $tables = $shop_db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Tables existantes dans la base de données:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
        
        // Vérifier si la table users existe
        if (in_array('users', $tables)) {
            // Compter les utilisateurs
            $user_count = $shop_db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "<div>Nombre d'utilisateurs: $user_count</div>";
            
            if ($user_count == 0) {
                echo "<div style='color:orange'>⚠ Aucun utilisateur trouvé dans la table</div>";
                
                // Proposer de créer un utilisateur admin
                if (isset($_GET['create_admin']) && $_GET['create_admin'] == '1') {
                    $password = 'Admin123!';
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $shop_db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute(['admin', $password_hash, 'Administrateur', 'admin'])) {
                        echo "<div style='color:green'>✓ Utilisateur admin créé avec succès</div>";
                        echo "<div>Identifiants: admin / $password</div>";
                    } else {
                        echo "<div style='color:red'>⚠ Échec de la création de l'utilisateur admin</div>";
                    }
                } else {
                    echo "<div><a href='?shop_id=$shop_id&create_admin=1'>Créer un utilisateur admin par défaut</a></div>";
                }
            } else {
                // Liste des utilisateurs
                $users = $shop_db->query("SELECT id, username, role FROM users")->fetchAll();
                echo "<h4>Liste des utilisateurs:</h4>";
                echo "<ul>";
                foreach ($users as $user) {
                    echo "<li>" . htmlspecialchars($user['username']) . " (Role: " . htmlspecialchars($user['role']) . ")</li>";
                }
                echo "</ul>";
                
                // Option pour réinitialiser le mot de passe de l'utilisateur admin
                if (isset($_GET['reset_admin_password']) && $_GET['reset_admin_password'] == '1') {
                    $password = 'Admin123!';
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $shop_db->prepare("UPDATE users SET password = ? WHERE username = ?");
                    if ($stmt->execute([$password_hash, 'admin'])) {
                        echo "<div style='color:green'>✓ Mot de passe de l'utilisateur admin réinitialisé avec succès</div>";
                        echo "<div>Nouveau mot de passe: $password</div>";
                    } else {
                        echo "<div style='color:red'>⚠ Échec de la réinitialisation du mot de passe</div>";
                    }
                } else {
                    echo "<div><a href='?shop_id=$shop_id&reset_admin_password=1'>Réinitialiser le mot de passe de l'utilisateur admin</a></div>";
                }
            }
        } else {
            echo "<div style='color:red'>⚠ La table 'users' n'existe pas dans la base de données</div>";
            
            // Proposer d'initialiser la base de données
            echo "<div><a href='superadmin/initialize_shop_db.php?id=$shop_id'>Initialiser la base de données</a></div>";
        }
    } else {
        echo "<div style='color:red'>⚠ Échec de la connexion à la base de données du magasin avec les paramètres actuels</div>";
        
        // Formulaire pour mettre à jour les paramètres de connexion
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_connection'])) {
            $new_host = trim($_POST['db_host']);
            $new_port = trim($_POST['db_port']);
            $new_name = trim($_POST['db_name']);
            $new_user = trim($_POST['db_user']);
            $new_pass = trim($_POST['db_pass']);
            
            // Tester la nouvelle connexion
            $new_config = [
                'host' => $new_host,
                'port' => $new_port,
                'dbname' => $new_name,
                'user' => $new_user,
                'pass' => $new_pass
            ];
            
            $test_db = connectToShopDB($new_config);
            
            if ($test_db) {
                echo "<div style='color:green'>✓ Test de connexion réussi avec les nouveaux paramètres</div>";
                
                // Mettre à jour les paramètres dans la base de données principale
                $update_stmt = $pdo_main->prepare("
                    UPDATE shops 
                    SET db_host = ?, db_port = ?, db_name = ?, db_user = ?, db_pass = ? 
                    WHERE id = ?
                ");
                
                if ($update_stmt->execute([$new_host, $new_port, $new_name, $new_user, $new_pass, $shop_id])) {
                    echo "<div style='color:green'>✓ Paramètres de connexion mis à jour avec succès</div>";
                    echo "<div><a href='?shop_id=$shop_id'>Rafraîchir la page</a></div>";
                } else {
                    echo "<div style='color:red'>⚠ Échec de la mise à jour des paramètres de connexion</div>";
                }
            } else {
                echo "<div style='color:red'>⚠ Échec du test de connexion avec les nouveaux paramètres</div>";
            }
        }
        
        // Afficher le formulaire de mise à jour des paramètres
        echo "<h3>Mettre à jour les paramètres de connexion:</h3>";
        echo "<form method='post' action='?shop_id=$shop_id'>";
        echo "<div style='margin-bottom: 10px;'>";
        echo "<label for='db_host'>Hôte:</label><br>";
        echo "<input type='text' id='db_host' name='db_host' value='" . htmlspecialchars($shop['db_host']) . "' style='width: 300px;'>";
        echo "</div>";
        
        echo "<div style='margin-bottom: 10px;'>";
        echo "<label for='db_port'>Port:</label><br>";
        echo "<input type='text' id='db_port' name='db_port' value='" . htmlspecialchars($shop['db_port']) . "' style='width: 300px;'>";
        echo "</div>";
        
        echo "<div style='margin-bottom: 10px;'>";
        echo "<label for='db_name'>Base de données:</label><br>";
        echo "<input type='text' id='db_name' name='db_name' value='" . htmlspecialchars($shop['db_name']) . "' style='width: 300px;'>";
        echo "</div>";
        
        echo "<div style='margin-bottom: 10px;'>";
        echo "<label for='db_user'>Utilisateur:</label><br>";
        echo "<input type='text' id='db_user' name='db_user' value='" . htmlspecialchars($shop['db_user']) . "' style='width: 300px;'>";
        echo "</div>";
        
        echo "<div style='margin-bottom: 10px;'>";
        echo "<label for='db_pass'>Mot de passe:</label><br>";
        echo "<input type='password' id='db_pass' name='db_pass' value='" . htmlspecialchars($shop['db_pass']) . "' style='width: 300px;'>";
        echo "</div>";
        
        echo "<input type='hidden' name='update_connection' value='1'>";
        echo "<button type='submit' style='padding: 5px 15px;'>Mettre à jour</button>";
        echo "</form>";
    }
    
    // Liens de navigation
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='index.php'>Retour à l'accueil</a> | ";
    echo "<a href='pages/login.php?shop_id=$shop_id'>Page de connexion</a> | ";
    echo "<a href='superadmin/index.php'>Administration des magasins</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color:red'>Erreur: " . $e->getMessage() . "</div>";
} 