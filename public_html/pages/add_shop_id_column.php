<?php
/**
 * Script pour ajouter la colonne shop_id à la table users
 * et associer les utilisateurs à leurs magasins respectifs
 */
session_start();

// Inclure les fichiers nécessaires
require_once 'config/database.php';

// Fonction d'affichage
function showMessage($message, $type = 'info') {
    $class = match($type) {
        'success' => 'success',
        'error' => 'error',
        'warning' => 'warning',
        default => 'info'
    };
    
    echo "<div class='$class'>$message</div>";
}

// En-tête HTML
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajout de la colonne shop_id</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .info { background: #e8f4f8; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        button, input[type="submit"] { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover, input[type="submit"]:hover { background: #45a049; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        select { padding: 8px; width: 100%; }
        .form-group { margin-bottom: 15px; }
        form { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ajout de la colonne shop_id à la table users</h1>';

// Connexion à la base de données
$pdo = getMainDBConnection();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    showMessage("Vous devez être connecté pour utiliser cet outil.", 'error');
    echo '<p><a href="index.php">Retour à l\'accueil</a></p>';
    echo '</div></body></html>';
    exit;
}

// Traitement des actions
$action = $_POST['action'] ?? '';
$step = $_GET['step'] ?? 1;

// Vérifier si la colonne existe déjà
$columnExists = false;

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'shop_id'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        showMessage("La colonne shop_id existe déjà dans la table users.", 'success');
        $step = 2; // Passer à l'étape d'association
    }
} catch (PDOException $e) {
    showMessage("Erreur lors de la vérification de la colonne: " . $e->getMessage(), 'error');
}

// Étape 1: Ajouter la colonne
if ($step == 1) {
    echo '<div class="info">
        <h2>Étape 1: Ajouter la colonne shop_id</h2>';
    
    if ($action == 'add_column') {
        try {
            // Ajouter la colonne shop_id à la table users
            $pdo->exec("ALTER TABLE users ADD COLUMN shop_id INT NULL");
            $pdo->exec("ALTER TABLE users ADD INDEX idx_shop_id (shop_id)");
            
            showMessage("La colonne shop_id a été ajoutée avec succès à la table users!", 'success');
            echo '<p><a href="?step=2">Continuer vers l\'étape 2: Associer les utilisateurs aux magasins</a></p>';
        } catch (PDOException $e) {
            showMessage("Erreur lors de l'ajout de la colonne: " . $e->getMessage(), 'error');
        }
    } else {
        echo '<p>Cette étape va ajouter la colonne shop_id à la table users.</p>';
        echo '<form method="post" action="?step=1">
            <input type="hidden" name="action" value="add_column">
            <input type="submit" value="Ajouter la colonne shop_id">
        </form>';
    }
    
    echo '</div>';
}

// Étape 2: Associer les utilisateurs aux magasins
if ($step == 2) {
    echo '<div class="info">
        <h2>Étape 2: Associer les utilisateurs aux magasins</h2>';
    
    // Récupérer la liste des magasins
    try {
        $stmt = $pdo->query("SELECT id, name FROM shops ORDER BY name");
        $shops = $stmt->fetchAll();
        
        if (empty($shops)) {
            showMessage("Aucun magasin n'a été trouvé dans la base de données.", 'warning');
        } else {
            // Récupérer la liste des utilisateurs
            $stmt = $pdo->query("SELECT id, name, email, shop_id FROM users ORDER BY id");
            $users = $stmt->fetchAll();
            
            if (empty($users)) {
                showMessage("Aucun utilisateur n'a été trouvé dans la base de données.", 'warning');
            } else {
                if ($action == 'update_associations') {
                    $updates = 0;
                    $errors = 0;
                    
                    foreach ($_POST['shop_id'] as $userId => $shopId) {
                        try {
                            if (!empty($shopId)) {
                                $stmt = $pdo->prepare("UPDATE users SET shop_id = ? WHERE id = ?");
                                $stmt->execute([$shopId, $userId]);
                                $updates += $stmt->rowCount();
                            }
                        } catch (PDOException $e) {
                            $errors++;
                            showMessage("Erreur lors de la mise à jour de l'utilisateur #$userId: " . $e->getMessage(), 'error');
                        }
                    }
                    
                    if ($updates > 0) {
                        showMessage("$updates utilisateur(s) ont été associés à leurs magasins respectifs.", 'success');
                    }
                    
                    if ($errors == 0) {
                        echo '<p><a href="?step=3">Continuer vers l\'étape 3: Mise à jour de la session</a></p>';
                    }
                }
                
                // Afficher le formulaire d'association
                echo '<form method="post" action="?step=2">
                    <input type="hidden" name="action" value="update_associations">
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Magasin</th>
                        </tr>';
                
                foreach ($users as $user) {
                    echo '<tr>
                        <td>' . htmlspecialchars($user['id']) . '</td>
                        <td>' . htmlspecialchars($user['name'] ?? 'Non défini') . '</td>
                        <td>' . htmlspecialchars($user['email']) . '</td>
                        <td>
                            <select name="shop_id[' . $user['id'] . ']">
                                <option value="">-- Sélectionner un magasin --</option>';
                    
                    foreach ($shops as $shop) {
                        $selected = ($user['shop_id'] == $shop['id']) ? 'selected' : '';
                        echo '<option value="' . $shop['id'] . '" ' . $selected . '>' . htmlspecialchars($shop['name']) . '</option>';
                    }
                    
                    echo '</select>
                        </td>
                    </tr>';
                }
                
                echo '</table>
                    <input type="submit" value="Enregistrer les associations">
                </form>';
            }
        }
    } catch (PDOException $e) {
        showMessage("Erreur lors de la récupération des données: " . $e->getMessage(), 'error');
    }
    
    echo '</div>';
}

// Étape 3: Mise à jour de la session
if ($step == 3) {
    echo '<div class="info">
        <h2>Étape 3: Mise à jour de la session</h2>';
    
    if ($action == 'update_session') {
        try {
            // Récupérer le shop_id de l'utilisateur connecté
            $stmt = $pdo->prepare("SELECT shop_id FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && !empty($user['shop_id'])) {
                $_SESSION['shop_id'] = $user['shop_id'];
                showMessage("Votre session a été mise à jour avec l'ID du magasin: " . $user['shop_id'], 'success');
                
                // Récupérer le nom du magasin
                $stmt = $pdo->prepare("SELECT name FROM shops WHERE id = ?");
                $stmt->execute([$user['shop_id']]);
                $shop = $stmt->fetch();
                
                if ($shop) {
                    showMessage("Vous êtes maintenant associé au magasin: " . $shop['name'], 'success');
                }
                
                echo '<p>Félicitations! Vous avez terminé la configuration. Les tâches devraient maintenant être enregistrées dans la base de données de votre magasin.</p>';
            } else {
                showMessage("Votre utilisateur n'est pas associé à un magasin. Veuillez revenir à l'étape 2.", 'warning');
                echo '<p><a href="?step=2">Retour à l\'étape 2</a></p>';
            }
        } catch (PDOException $e) {
            showMessage("Erreur lors de la mise à jour de la session: " . $e->getMessage(), 'error');
        }
    } else {
        echo '<p>Cette étape va mettre à jour votre session avec l\'ID du magasin associé à votre compte.</p>';
        echo '<form method="post" action="?step=3">
            <input type="hidden" name="action" value="update_session">
            <input type="submit" value="Mettre à jour ma session">
        </form>';
    }
    
    echo '</div>';
}

// Navigation entre les étapes
echo '<div class="info">
    <h2>Navigation</h2>
    <ul>
        <li><a href="?step=1">Étape 1: Ajouter la colonne shop_id</a></li>
        <li><a href="?step=2">Étape 2: Associer les utilisateurs aux magasins</a></li>
        <li><a href="?step=3">Étape 3: Mise à jour de la session</a></li>
    </ul>
</div>';

// Liens utiles
echo '<div class="info">
    <h2>Liens Utiles</h2>
    <ul>
        <li><a href="check_session.php">Vérification de Session PHP</a></li>
        <li><a href="repair_auth_system.php">Réparation du Système d\'Authentification</a></li>
        <li><a href="debug_shop_connection.php">Diagnostic des Connexions aux Magasins</a></li>
        <li><a href="index.php">Retour à l\'accueil</a></li>
    </ul>
</div>';

echo '</div></body></html>';
?> 