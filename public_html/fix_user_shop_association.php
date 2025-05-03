<?php
/**
 * Outil pour réparer l'association entre un utilisateur et son magasin
 * Ce script vérifie et corrige les problèmes d'association entre les utilisateurs et leurs magasins
 */
session_start();

// Mode débogage pour administrateurs (à supprimer en production)
$debug_key = 'debug123'; // Clé de sécurité temporaire
$admin_mode = false;

if (isset($_GET['debug']) && $_GET['debug'] === $debug_key) {
    $admin_mode = true;
    // Si l'ID utilisateur est fourni, l'utiliser pour le diagnostic
    if (isset($_GET['user_id'])) {
        $_SESSION['user_id'] = (int)$_GET['user_id'];
    } else {
        // Utiliser un ID administrateur par défaut
        $_SESSION['user_id'] = 1; // ID de l'administrateur par défaut
    }
    
    // Si l'ID magasin est fourni, l'utiliser pour le diagnostic
    if (isset($_GET['shop_id'])) {
        $_SESSION['shop_id'] = (int)$_GET['shop_id'];
    }
}

// Sécurité: l'utilisateur doit être connecté
if (!isset($_SESSION['user_id']) && !$admin_mode) {
    echo "<p>Vous devez être connecté pour utiliser cet outil.</p>";
    echo "<p>Si vous êtes administrateur, utilisez le mode de débogage avec l'URL: <code>fix_user_shop_association.php?debug=debug123</code></p>";
    exit;
}

// Inclure les fichiers nécessaires
require_once 'config/database.php';

// En-tête HTML
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réparation Association Utilisateur-Magasin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .info { background: #e8f4f8; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input[type="submit"] { padding: 8px; width: 100%; box-sizing: border-box; }
        input[type="submit"] { background: #4CAF50; color: white; border: none; cursor: pointer; margin-top: 10px; }
        input[type="submit"]:hover { background: #45a049; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .admin-mode { background-color: #ffe3e3; padding: 10px; margin-bottom: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Réparation Association Utilisateur-Magasin</h1>';

// Afficher bannière mode administrateur
if ($admin_mode) {
    echo '<div class="admin-mode">
        <strong>Mode Administrateur activé</strong> - Vous utilisez l\'accès de débogage.
        <p>Utilisateur ID: ' . $_SESSION['user_id'] . ' / Magasin ID: ' . ($_SESSION['shop_id'] ?? 'Non défini') . '</p>
    </div>';
}

// Connexion à la base de données principale
$main_pdo = getMainDBConnection();

// Récupérer les informations de l'utilisateur actuel
$user_id = $_SESSION['user_id'];
$stmt = $main_pdo->prepare("
    SELECT u.*, s.name as shop_name, s.id as shop_id
    FROM users u
    LEFT JOIN shops s ON u.shop_id = s.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo '<div class="error">Utilisateur non trouvé dans la base de données!</div>';
    exit;
}

// Récupérer la liste des magasins disponibles
$stmt = $main_pdo->query("SELECT id, name FROM shops ORDER BY name");
$shops = $stmt->fetchAll();

// Traitement du formulaire
$message = '';
if (isset($_POST['update_shop'])) {
    $new_shop_id = (int)$_POST['shop_id'];
    
    // Vérifier que le magasin existe
    $stmt = $main_pdo->prepare("SELECT id, name FROM shops WHERE id = ?");
    $stmt->execute([$new_shop_id]);
    $selected_shop = $stmt->fetch();
    
    if ($selected_shop) {
        try {
            // Mettre à jour l'association de l'utilisateur
            $stmt = $main_pdo->prepare("UPDATE users SET shop_id = ? WHERE id = ?");
            $stmt->execute([$new_shop_id, $user_id]);
            
            // Mettre à jour la session
            $_SESSION['shop_id'] = $new_shop_id;
            
            $message = '<div class="success">Association mise à jour avec succès. Votre compte est maintenant lié au magasin: ' . htmlspecialchars($selected_shop['name']) . '</div>';
            
            // Récupérer les informations utilisateur mises à jour
            $stmt = $main_pdo->prepare("
                SELECT u.*, s.name as shop_name, s.id as shop_id
                FROM users u
                LEFT JOIN shops s ON u.shop_id = s.id
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } catch (Exception $e) {
            $message = '<div class="error">Erreur lors de la mise à jour: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        $message = '<div class="error">Le magasin sélectionné n\'existe pas!</div>';
    }
}

// Afficher les informations actuelles de l'utilisateur
echo '<div class="info">
    <h2>Informations Utilisateur</h2>
    <table>
        <tr><th>ID</th><td>' . htmlspecialchars($user['id']) . '</td></tr>
        <tr><th>Nom</th><td>' . htmlspecialchars($user['name'] ?? 'Non défini') . '</td></tr>
        <tr><th>Email</th><td>' . htmlspecialchars($user['email'] ?? 'Non défini') . '</td></tr>
        <tr><th>Magasin actuel</th><td>' . htmlspecialchars($user['shop_name'] ?? 'Non associé') . ' (ID: ' . htmlspecialchars($user['shop_id'] ?? 'Aucun') . ')</td></tr>
        <tr><th>ID Magasin en Session</th><td>' . (isset($_SESSION['shop_id']) ? htmlspecialchars($_SESSION['shop_id']) : 'Non défini') . '</td></tr>
    </table>
</div>';

// Afficher le message de résultat
echo $message;

// Formulaire pour changer l'association
echo '<form method="post" action="">
    <div class="form-group">
        <label for="shop_id">Sélectionner un magasin:</label>
        <select name="shop_id" id="shop_id" required>';

foreach ($shops as $shop) {
    $selected = ($shop['id'] == $user['shop_id']) ? 'selected' : '';
    echo '<option value="' . htmlspecialchars($shop['id']) . '" ' . $selected . '>' . 
         htmlspecialchars($shop['name']) . ' (ID: ' . htmlspecialchars($shop['id']) . ')</option>';
}

echo '</select>
    </div>
    <div class="form-group">
        <input type="submit" name="update_shop" value="Mettre à jour l\'association">
    </div>
</form>';

// Section de vérification de la connexion
echo '<div class="info">
    <h2>Vérification de la Connexion</h2>';

try {
    // Test de la connexion au magasin actuel
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    $current_db = $result['db_name'];
    
    echo '<p>Base de données actuellement utilisée: <strong>' . htmlspecialchars($current_db) . '</strong></p>';
    
    // Vérifier si c'est bien la base attendue
    if (isset($user['shop_id']) && !empty($user['shop_id'])) {
        $stmt = $main_pdo->prepare("SELECT db_name FROM shops WHERE id = ?");
        $stmt->execute([$user['shop_id']]);
        $expected_db = $stmt->fetch();
        
        if ($expected_db && $current_db === $expected_db['db_name']) {
            echo '<p class="success">La connexion utilise la bonne base de données!</p>';
        } else {
            echo '<p class="error">La connexion n\'utilise pas la bonne base de données! ' . 
                 'Attendue: ' . htmlspecialchars($expected_db['db_name'] ?? 'inconnue') . 
                 ', Utilisée: ' . htmlspecialchars($current_db) . '</p>';
        }
    }
} catch (Exception $e) {
    echo '<p class="error">Erreur lors du test de connexion: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '</div>';

// Liens utiles
echo '<div class="info">
    <h2>Liens Utiles</h2>
    <ul>
        <li><a href="debug_shop_connection.php">Diagnostic Complet des Connexions</a></li>
        <li><a href="index.php">Retour à l\'accueil</a></li>
    </ul>
</div>';

echo '</div></body></html>';
?> 