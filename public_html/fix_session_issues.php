<?php
/**
 * Script de diagnostic et de réparation des problèmes de session PHP
 * Cet outil permet de vérifier et réparer les problèmes de session pour l'outil add_shop_id_column.php
 */

// Démarrer la session avec des paramètres de base
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir un nom de session explicite
session_name('MDGEEK_SESSION');

// Démarrer la session
session_start();

// En-tête HTML
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réparation des problèmes de session</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .info { background: #e8f4f8; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        pre { background: #f4f4f4; padding: 10px; overflow: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; width: 30%; }
        code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
        button, input[type="submit"] { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover, input[type="submit"]:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Réparation des problèmes de session</h1>';

// Inclusion des fichiers nécessaires si disponibles
if (file_exists(__DIR__ . '/config/database.php')) {
    try {
        require_once __DIR__ . '/config/database.php';
        $db_included = true;
    } catch (Exception $e) {
        echo '<div class="error">Erreur lors de l\'inclusion de database.php: ' . $e->getMessage() . '</div>';
        $db_included = false;
    }
} else {
    echo '<div class="warning">Le fichier database.php n\'a pas été trouvé.</div>';
    $db_included = false;
}

// Afficher les informations sur la session
echo '<div class="info">
    <h2>Informations de Session</h2>
    <table>
        <tr><th>ID de session</th><td>' . session_id() . '</td></tr>
        <tr><th>Nom de session</th><td>' . session_name() . '</td></tr>
        <tr><th>Statut de session</th><td>' . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . '</td></tr>
    </table>
</div>';

// Afficher le contenu de $_SESSION
echo '<div class="info">
    <h2>Contenu de $_SESSION</h2>';

if (empty($_SESSION)) {
    echo '<p class="warning">La session est vide.</p>';
} else {
    echo '<pre>' . print_r($_SESSION, true) . '</pre>';
}

echo '</div>';

// Vérifier si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['user_id']);

echo '<div class="info">
    <h2>État de Connexion</h2>';

if ($is_logged_in) {
    echo '<p class="success">Vous êtes connecté en tant qu\'utilisateur ID: ' . $_SESSION['user_id'] . '</p>';
    
    // Vérifier si shop_id est défini
    if (isset($_SESSION['shop_id'])) {
        echo '<p class="success">Vous êtes associé au magasin ID: ' . $_SESSION['shop_id'] . '</p>';
    } else {
        echo '<p class="warning">Vous n\'êtes associé à aucun magasin.</p>';
    }
} else {
    echo '<p class="error">Vous n\'êtes pas connecté.</p>';
}

echo '</div>';

// Traitement des actions
$action = $_POST['action'] ?? '';

if ($action === 'force_login' && $db_included) {
    $user_id = (int)$_POST['user_id'];
    $shop_id = (int)$_POST['shop_id'];
    
    if ($user_id > 0) {
        // Vérifier si l'utilisateur existe
        $pdo = getMainDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Créer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Ajouter shop_id si fourni
            if ($shop_id > 0) {
                $stmt = $pdo->prepare("SELECT id, name FROM shops WHERE id = ?");
                $stmt->execute([$shop_id]);
                $shop = $stmt->fetch();
                
                if ($shop) {
                    $_SESSION['shop_id'] = $shop['id'];
                    $_SESSION['shop_name'] = $shop['name'];
                    
                    // Mettre à jour l'utilisateur avec le shop_id
                    $stmt = $pdo->prepare("UPDATE users SET shop_id = ? WHERE id = ?");
                    $stmt->execute([$shop_id, $user_id]);
                    
                    echo '<div class="success">Session créée pour l\'utilisateur ID: ' . $user['id'] . ' avec le magasin ID: ' . $shop['id'] . '</div>';
                } else {
                    echo '<div class="error">Le magasin ID: ' . $shop_id . ' n\'existe pas.</div>';
                }
            } else {
                echo '<div class="success">Session créée pour l\'utilisateur ID: ' . $user['id'] . ' sans magasin associé.</div>';
            }
            
            // Redirection vers la page d'origine
            echo '<div class="info">
                <p>Redirection dans 3 secondes...</p>
                <p><a href="add_shop_id_column.php">Cliquez ici si vous n\'êtes pas redirigé automatiquement</a></p>
            </div>';
            echo '<script>
                setTimeout(function() {
                    window.location.href = "add_shop_id_column.php";
                }, 3000);
            </script>';
        } else {
            echo '<div class="error">L\'utilisateur ID: ' . $user_id . ' n\'existe pas.</div>';
        }
    } else {
        echo '<div class="error">Veuillez fournir un ID d\'utilisateur valide.</div>';
    }
}

// Formulaire pour forcer la création de session
if ($db_included) {
    echo '<div class="info">
        <h2>Forcer la Création de Session</h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="force_login">
            <div style="margin-bottom: 15px;">
                <label for="user_id">ID Utilisateur:</label>
                <input type="number" id="user_id" name="user_id" min="1" required style="padding: 5px; margin-left: 10px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label for="shop_id">ID Magasin:</label>
                <input type="number" id="shop_id" name="shop_id" min="1" style="padding: 5px; margin-left: 10px;">
                <small>(Laisser vide si aucun magasin)</small>
            </div>
            <input type="submit" value="Créer Session">
        </form>
    </div>';
    
    // Afficher la liste des utilisateurs pour aider
    try {
        $pdo = getMainDBConnection();
        $stmt = $pdo->query("SELECT id, username, full_name, shop_id FROM users ORDER BY id LIMIT 10");
        $users = $stmt->fetchAll();
        
        if (count($users) > 0) {
            echo '<div class="info">
                <h2>Utilisateurs Disponibles</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Nom d\'utilisateur</th>
                        <th>Nom complet</th>
                        <th>ID Magasin</th>
                    </tr>';
            
            foreach ($users as $user) {
                echo '<tr>
                    <td>' . htmlspecialchars($user['id']) . '</td>
                    <td>' . htmlspecialchars($user['username']) . '</td>
                    <td>' . htmlspecialchars($user['full_name']) . '</td>
                    <td>' . (empty($user['shop_id']) ? 'Non défini' : htmlspecialchars($user['shop_id'])) . '</td>
                </tr>';
            }
            
            echo '</table>
            </div>';
        }
        
        // Afficher la liste des magasins
        $stmt = $pdo->query("SELECT id, name FROM shops ORDER BY id LIMIT 10");
        $shops = $stmt->fetchAll();
        
        if (count($shops) > 0) {
            echo '<div class="info">
                <h2>Magasins Disponibles</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                    </tr>';
            
            foreach ($shops as $shop) {
                echo '<tr>
                    <td>' . htmlspecialchars($shop['id']) . '</td>
                    <td>' . htmlspecialchars($shop['name']) . '</td>
                </tr>';
            }
            
            echo '</table>
            </div>';
        }
    } catch (Exception $e) {
        echo '<div class="error">Erreur lors de la récupération des données: ' . $e->getMessage() . '</div>';
    }
}

// Liens utiles
echo '<div class="info">
    <h2>Liens Utiles</h2>
    <ul>
        <li><a href="add_shop_id_column.php">Outil d\'ajout de la colonne shop_id</a></li>
        <li><a href="check_session.php">Vérification de Session PHP</a></li>
        <li><a href="debug_shop_connection.php">Diagnostic des Connexions aux Magasins</a></li>
        <li><a href="index.php">Retour à l\'accueil</a></li>
    </ul>
</div>';

echo '</div></body></html>'; 