<?php
/**
 * Outil de réparation du système d'authentification
 * Ce script diagnostique et répare les problèmes liés à la gestion des sessions et à l'authentification
 */

// Démarrer une nouvelle session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once 'config/database.php';

// Fonctions utilitaires
function displayHeader() {
    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Réparation du Système d\'Authentification</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            h1, h2 { color: #333; }
            .container { max-width: 800px; margin: 0 auto; }
            .info { background: #e8f4f8; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="text"], input[type="email"], input[type="password"], select { padding: 8px; width: 100%; box-sizing: border-box; }
            input[type="submit"], button { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
            input[type="submit"]:hover, button:hover { background: #45a049; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f2f2f2; }
            code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
        </style>
    </head>
    <body>
    <div class="container">
        <h1>Réparation du Système d\'Authentification</h1>';
}

function displayFooter() {
    echo '</div></body></html>';
}

// Afficher le formulaire de connexion
function displayLoginForm($error = '') {
    if (!empty($error)) {
        echo '<div class="error">' . $error . '</div>';
    }
    
    echo '<div class="info">
        <h2>Connexion</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <input type="submit" name="login" value="Se connecter">
            </div>
        </form>
    </div>';
}

// Afficher les informations de session
function displaySessionInfo() {
    echo '<div class="info">
        <h2>Informations de Session</h2>
        <table>';
    
    // Informations de base sur la session
    echo '<tr><th>ID de session</th><td>' . session_id() . '</td></tr>';
    echo '<tr><th>Statut de la session</th><td>' . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . '</td></tr>';
    echo '<tr><th>Nom de la session</th><td>' . session_name() . '</td></tr>';
    
    // Paramètres de cookie de session
    $sessionParams = session_get_cookie_params();
    echo '<tr><th>Durée de vie du cookie</th><td>' . $sessionParams['lifetime'] . ' secondes</td></tr>';
    echo '<tr><th>Path du cookie</th><td>' . $sessionParams['path'] . '</td></tr>';
    echo '<tr><th>Domaine du cookie</th><td>' . ($sessionParams['domain'] ?: 'Non défini') . '</td></tr>';
    echo '<tr><th>Secure</th><td>' . ($sessionParams['secure'] ? 'Oui' : 'Non') . '</td></tr>';
    echo '<tr><th>HttpOnly</th><td>' . ($sessionParams['httponly'] ? 'Oui' : 'Non') . '</td></tr>';
    
    // Variables de session
    echo '<tr><th colspan="2">Variables de session:</th></tr>';
    if (!empty($_SESSION)) {
        foreach ($_SESSION as $key => $value) {
            // Masquer le mot de passe si présent
            if ($key === 'password' || $key === 'pass' || $key === 'user_password') {
                $value = '********';
            }
            echo '<tr><th>' . htmlspecialchars($key) . '</th><td>' . htmlspecialchars(print_r($value, true)) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="2">Aucune variable de session définie</td></tr>';
    }
    
    echo '</table></div>';
}

// Tester et réparer la configuration PHP
function checkPHPConfiguration() {
    $issues = [];
    $fixes = [];
    
    // Vérifier les paramètres de session PHP
    if (ini_get('session.gc_maxlifetime') < 1800) {
        $issues[] = 'La durée de vie des sessions (session.gc_maxlifetime) est trop courte: ' . ini_get('session.gc_maxlifetime') . ' secondes';
        $fixes[] = 'Augmenter la valeur de session.gc_maxlifetime à au moins 1800 secondes dans php.ini';
    }
    
    if (ini_get('session.cookie_lifetime') > 0 && ini_get('session.cookie_lifetime') < 1800) {
        $issues[] = 'La durée de vie du cookie de session est trop courte: ' . ini_get('session.cookie_lifetime') . ' secondes';
        $fixes[] = 'Augmenter la valeur de session.cookie_lifetime dans php.ini ou la définir à 0 pour une session basée sur le navigateur';
    }
    
    // Vérifier si les sessions sont stockées correctement
    $sessionPath = ini_get('session.save_path');
    if (empty($sessionPath)) {
        $issues[] = 'Le chemin de stockage des sessions n\'est pas défini';
        $fixes[] = 'Définir session.save_path dans php.ini';
    } else if (!is_writable($sessionPath) && $sessionPath !== 'N;MODE;/path') {
        $issues[] = 'Le chemin de stockage des sessions n\'est pas accessible en écriture: ' . $sessionPath;
        $fixes[] = 'Vérifier les permissions du dossier ' . $sessionPath;
    }
    
    echo '<div class="info">
        <h2>Configuration PHP pour les Sessions</h2>';
    
    if (empty($issues)) {
        echo '<p class="success">Aucun problème détecté dans la configuration PHP des sessions.</p>';
    } else {
        echo '<p class="error">Problèmes détectés dans la configuration PHP:</p><ul>';
        foreach ($issues as $issue) {
            echo '<li>' . $issue . '</li>';
        }
        echo '</ul>';
        
        echo '<p>Solutions recommandées:</p><ul>';
        foreach ($fixes as $fix) {
            echo '<li>' . $fix . '</li>';
        }
        echo '</ul>';
        
        // Essayer de corriger certains paramètres si possible
        echo '<p>Tentative de correction temporaire pour cette session:</p>';
        
        try {
            // Augmenter la durée de vie de la session
            ini_set('session.gc_maxlifetime', 3600);
            echo '<p>✓ Durée de vie des sessions temporairement augmentée à 3600 secondes</p>';
            
            // Définir le cookie de session pour qu'il expire à la fermeture du navigateur
            session_set_cookie_params(0);
            echo '<p>✓ Paramètres du cookie de session temporairement ajustés</p>';
            
        } catch (Exception $e) {
            echo '<p class="error">Erreur lors de la tentative de correction: ' . $e->getMessage() . '</p>';
        }
    }
    
    echo '</div>';
}

// Tester la table des utilisateurs
function checkUserTable() {
    $pdo = getMainDBConnection();
    $issues = [];
    $fixes = [];
    
    echo '<div class="info">
        <h2>Vérification de la Table des Utilisateurs</h2>';
    
    try {
        // Vérifier si la table des utilisateurs existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo '<p class="error">La table \'users\' n\'existe pas dans la base de données!</p>';
            return;
        }
        
        // Vérifier la structure de la table
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['id', 'email', 'password', 'shop_id'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            $issues[] = 'Colonnes manquantes dans la table users: ' . implode(', ', $missingColumns);
            $fixes[] = 'Ajouter les colonnes manquantes à la table users';
        }
        
        // Vérifier le nombre d'utilisateurs
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch()['count'];
        
        echo '<p>Nombre total d\'utilisateurs: ' . $userCount . '</p>';
        
        if ($userCount === 0) {
            $issues[] = 'Aucun utilisateur n\'est présent dans la base de données';
            $fixes[] = 'Créez au moins un utilisateur administrateur';
        }
        
        // Vérifier les utilisateurs sans magasin
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE shop_id IS NULL OR shop_id = 0");
        $noShopCount = $stmt->fetch()['count'];
        
        if ($noShopCount > 0) {
            $issues[] = $noShopCount . ' utilisateur(s) n\'ont pas de magasin associé';
            $fixes[] = 'Associer ces utilisateurs à un magasin existant';
            
            // Afficher les détails
            $stmt = $pdo->query("SELECT id, name, email FROM users WHERE shop_id IS NULL OR shop_id = 0 LIMIT 10");
            $users = $stmt->fetchAll();
            
            echo '<p>Utilisateurs sans magasin associé:</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Nom</th><th>Email</th></tr>';
            
            foreach ($users as $user) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($user['id']) . '</td>';
                echo '<td>' . htmlspecialchars($user['name'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            
            if ($noShopCount > 10) {
                echo '<p>... et ' . ($noShopCount - 10) . ' autres utilisateurs</p>';
            }
            
            // Afficher un formulaire pour associer un magasin aux utilisateurs sans magasin
            $stmt = $pdo->query("SELECT id, name FROM shops ORDER BY name");
            $shops = $stmt->fetchAll();
            
            if (!empty($shops)) {
                echo '<form method="post" action="">
                    <h3>Associer tous les utilisateurs sans magasin</h3>
                    <div class="form-group">
                        <label for="default_shop">Sélectionner un magasin par défaut:</label>
                        <select name="default_shop" id="default_shop" required>';
                
                foreach ($shops as $shop) {
                    echo '<option value="' . $shop['id'] . '">' . htmlspecialchars($shop['name']) . '</option>';
                }
                
                echo '</select>
                    </div>
                    <div class="form-group">
                        <input type="submit" name="assign_shop" value="Associer le magasin">
                    </div>
                </form>';
            }
        } else {
            echo '<p class="success">Tous les utilisateurs sont associés à un magasin.</p>';
        }
        
        // Si des problèmes sont détectés, les afficher
        if (!empty($issues)) {
            echo '<div class="warning">';
            echo '<p>Problèmes détectés:</p><ul>';
            
            foreach ($issues as $issue) {
                echo '<li>' . $issue . '</li>';
            }
            
            echo '</ul>';
            
            echo '<p>Solutions recommandées:</p><ul>';
            
            foreach ($fixes as $fix) {
                echo '<li>' . $fix . '</li>';
            }
            
            echo '</ul></div>';
        } else {
            echo '<p class="success">Aucun problème détecté dans la table des utilisateurs.</p>';
        }
        
    } catch (PDOException $e) {
        echo '<p class="error">Erreur lors de la vérification de la table des utilisateurs: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
}

// Traiter l'association d'un magasin par défaut aux utilisateurs sans magasin
function processShopAssignment() {
    if (isset($_POST['assign_shop']) && isset($_POST['default_shop'])) {
        $default_shop_id = (int)$_POST['default_shop'];
        $pdo = getMainDBConnection();
        
        try {
            // Vérifier que le magasin existe
            $stmt = $pdo->prepare("SELECT id, name FROM shops WHERE id = ?");
            $stmt->execute([$default_shop_id]);
            $shop = $stmt->fetch();
            
            if (!$shop) {
                return '<div class="error">Le magasin sélectionné n\'existe pas!</div>';
            }
            
            // Associer le magasin à tous les utilisateurs sans magasin
            $stmt = $pdo->prepare("UPDATE users SET shop_id = ? WHERE shop_id IS NULL OR shop_id = 0");
            $stmt->execute([$default_shop_id]);
            $updatedCount = $stmt->rowCount();
            
            return '<div class="success">' . $updatedCount . ' utilisateur(s) ont été associés au magasin: ' . htmlspecialchars($shop['name']) . '</div>';
            
        } catch (PDOException $e) {
            return '<div class="error">Erreur lors de l\'association du magasin: ' . $e->getMessage() . '</div>';
        }
    }
    
    return '';
}

// Processus de connexion
function processLogin() {
    if (isset($_POST['login'])) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            return 'Veuillez remplir tous les champs.';
        }
        
        $pdo = getMainDBConnection();
        
        try {
            // Vérifier si l'utilisateur existe
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return 'Email ou mot de passe incorrect.';
            }
            
            // Vérifier le mot de passe (assumant qu'il est hashé avec password_hash)
            if (password_verify($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'] ?? '';
                $_SESSION['user_email'] = $user['email'];
                
                // Associer le magasin à la session si disponible
                if (!empty($user['shop_id'])) {
                    $_SESSION['shop_id'] = $user['shop_id'];
                }
                
                return '';
            } else {
                return 'Email ou mot de passe incorrect.';
            }
            
        } catch (PDOException $e) {
            return 'Erreur de base de données: ' . $e->getMessage();
        }
    }
    
    return '';
}

// Vérifier et réparer la table des magasins
function checkShopsTable() {
    $pdo = getMainDBConnection();
    $issues = [];
    $fixes = [];
    
    echo '<div class="info">
        <h2>Vérification de la Table des Magasins</h2>';
    
    try {
        // Vérifier si la table des magasins existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'shops'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo '<p class="error">La table \'shops\' n\'existe pas dans la base de données!</p>';
            return;
        }
        
        // Vérifier la structure de la table
        $stmt = $pdo->query("DESCRIBE shops");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['id', 'name', 'db_host', 'db_name', 'db_user', 'db_pass'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            $issues[] = 'Colonnes manquantes dans la table shops: ' . implode(', ', $missingColumns);
            $fixes[] = 'Ajouter les colonnes manquantes à la table shops';
        }
        
        // Vérifier le nombre de magasins
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM shops");
        $shopCount = $stmt->fetch()['count'];
        
        echo '<p>Nombre total de magasins: ' . $shopCount . '</p>';
        
        if ($shopCount === 0) {
            $issues[] = 'Aucun magasin n\'est présent dans la base de données';
            $fixes[] = 'Créez au moins un magasin';
        }
        
        // Vérifier les magasins avec configuration incomplète
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM shops WHERE 
                             db_host IS NULL OR db_host = '' OR
                             db_name IS NULL OR db_name = '' OR
                             db_user IS NULL OR db_user = ''");
        $incompleteCount = $stmt->fetch()['count'];
        
        if ($incompleteCount > 0) {
            $issues[] = $incompleteCount . ' magasin(s) ont une configuration de base de données incomplète';
            $fixes[] = 'Compléter la configuration de ces magasins';
            
            // Afficher les détails
            $stmt = $pdo->query("SELECT id, name, db_host, db_name, db_user FROM shops WHERE 
                                db_host IS NULL OR db_host = '' OR
                                db_name IS NULL OR db_name = '' OR
                                db_user IS NULL OR db_user = ''");
            $shops = $stmt->fetchAll();
            
            echo '<p>Magasins avec configuration incomplète:</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Nom</th><th>Hôte</th><th>Nom BDD</th><th>Utilisateur BDD</th></tr>';
            
            foreach ($shops as $shop) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($shop['id']) . '</td>';
                echo '<td>' . htmlspecialchars($shop['name']) . '</td>';
                echo '<td>' . (empty($shop['db_host']) ? '<span class="error">Manquant</span>' : htmlspecialchars($shop['db_host'])) . '</td>';
                echo '<td>' . (empty($shop['db_name']) ? '<span class="error">Manquant</span>' : htmlspecialchars($shop['db_name'])) . '</td>';
                echo '<td>' . (empty($shop['db_user']) ? '<span class="error">Manquant</span>' : htmlspecialchars($shop['db_user'])) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p class="success">Tous les magasins ont une configuration complète.</p>';
        }
        
        // Si des problèmes sont détectés, les afficher
        if (!empty($issues)) {
            echo '<div class="warning">';
            echo '<p>Problèmes détectés:</p><ul>';
            
            foreach ($issues as $issue) {
                echo '<li>' . $issue . '</li>';
            }
            
            echo '</ul>';
            
            echo '<p>Solutions recommandées:</p><ul>';
            
            foreach ($fixes as $fix) {
                echo '<li>' . $fix . '</li>';
            }
            
            echo '</ul></div>';
        } else {
            echo '<p class="success">Aucun problème détecté dans la table des magasins.</p>';
        }
        
    } catch (PDOException $e) {
        echo '<p class="error">Erreur lors de la vérification de la table des magasins: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
}

// TRAITEMENT PRINCIPAL
displayHeader();

// Traiter la connexion si le formulaire est soumis
$loginError = processLogin();

// Traiter l'association de magasin si le formulaire est soumis
$assignmentResult = processShopAssignment();
if (!empty($assignmentResult)) {
    echo $assignmentResult;
}

// Si l'utilisateur n'est pas connecté, afficher le formulaire de connexion
if (!isset($_SESSION['user_id'])) {
    echo '<div class="warning">Vous n\'êtes pas connecté. Connectez-vous pour diagnostiquer et réparer votre compte.</div>';
    displayLoginForm($loginError);
} else {
    // Utilisateur connecté, afficher les diagnostics et réparations
    echo '<div class="success">Vous êtes connecté en tant que ' . htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']) . '</div>';
    
    // Bouton de déconnexion
    echo '<form method="post" action="">
        <input type="submit" name="logout" value="Déconnexion" style="margin-bottom: 20px;">
    </form>';
    
    // Traiter la déconnexion
    if (isset($_POST['logout'])) {
        session_unset();
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Afficher les informations de session
    displaySessionInfo();
    
    // Vérifier la configuration PHP
    checkPHPConfiguration();
    
    // Vérifier et réparer la table des utilisateurs
    checkUserTable();
    
    // Vérifier et réparer la table des magasins
    checkShopsTable();
}

// Navigation vers d'autres outils
echo '<div class="info">
    <h2>Outils Connexes</h2>
    <ul>
        <li><a href="debug_shop_connection.php">Diagnostic des Connexions aux Magasins</a></li>
        <li><a href="fix_user_shop_association.php">Réparer l\'Association Utilisateur-Magasin</a></li>
        <li><a href="index.php">Retour à l\'accueil</a></li>
    </ul>
</div>';

displayFooter();
?> 