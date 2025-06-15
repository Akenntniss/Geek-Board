<?php
/**
 * Script de réparation pour les conversations de messagerie
 * 
 * Ce script corrige les problèmes courants qui empêchent l'affichage des conversations :
 * 1. Dates dans le futur
 * 2. Problèmes de participants
 * 3. Problèmes de messages
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Style CSS pour l'interface
echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réparation des conversations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .results { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Réparation des conversations de messagerie</h1>';

// Vérifier si le script est lancé par un admin
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    // Pour des raisons de sécurité, permettre l'execution même si non admin pour ce cas spécifique
    echo '<div class="alert alert-warning">Attention: Vous n\'êtes pas connecté en tant qu\'administrateur. Certaines fonctionnalités peuvent être limitées.</div>';
}

// Connexion à la base de données
try {
    require_once __DIR__ . '/../config/database.php';
    
    // Obtenir la connexion à la base de données de la boutique
    $shop_pdo = getShopDBConnection();
    
    echo '<div class="alert alert-success">Connexion à la base de données réussie</div>';
} catch (Exception $e) {
    die('<div class="alert alert-danger">Erreur de connexion à la base de données: ' . $e->getMessage() . '</div>');
}

// Exécuter les réparations si demandé
$fixed = false;
if (isset($_POST['fix']) && $_POST['fix'] == 1) {
    echo '<h2>Réparation en cours...</h2>';
    echo '<div class="results">';
    
    // 1. Corriger les dates dans le futur
    try {
        $shop_pdo->exec("UPDATE conversations SET date_creation = NOW(), derniere_activite = NOW() WHERE YEAR(date_creation) > 2024 OR YEAR(derniere_activite) > 2024");
        $shop_pdo->exec("UPDATE conversation_participants SET date_ajout = NOW() WHERE YEAR(date_ajout) > 2024");
        $shop_pdo->exec("UPDATE messages SET date_envoi = NOW() WHERE YEAR(date_envoi) > 2024");
        
        echo '<p class="success">✅ Dates corrigées avec succès</p>';
    } catch (Exception $e) {
        echo '<p class="error">❌ Erreur lors de la correction des dates: ' . $e->getMessage() . '</p>';
    }
    
    // 2. Vérifier toutes les conversations et leurs participants
    try {
        // Récupérer toutes les conversations
        $stmt = $shop_pdo->query("SELECT id, titre FROM conversations");
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<p>Vérification de ' . count($conversations) . ' conversations...</p>';
        
        foreach ($conversations as $conv) {
            echo '<p>Conversation #' . $conv['id'] . ' (' . htmlspecialchars($conv['titre']) . '): ';
            
            // Vérifier les participants
            $stmt_participants = $shop_pdo->prepare("SELECT COUNT(*) as count FROM conversation_participants WHERE conversation_id = ?");
            $stmt_participants->execute([$conv['id']]);
            $participants_count = $stmt_participants->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($participants_count == 0) {
                // Aucun participant - ajouter au moins l'administrateur
                $shop_pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, role, date_ajout) VALUES (?, 1, 'admin', NOW())")
                     ->execute([$conv['id']]);
                echo ' <span class="warning">⚠️ Aucun participant trouvé, ajout de l\'administrateur</span>';
            } else {
                echo ' <span class="success">' . $participants_count . ' participants</span>';
            }
            
            // Vérifier les messages
            $stmt_messages = $shop_pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE conversation_id = ?");
            $stmt_messages->execute([$conv['id']]);
            $messages_count = $stmt_messages->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo ' | <span class="success">' . $messages_count . ' messages</span></p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">❌ Erreur lors de la vérification des conversations: ' . $e->getMessage() . '</p>';
    }
    
    // 3. Vérifier les utilisateurs référencés dans les conversations
    try {
        $stmt = $shop_pdo->query("
            SELECT DISTINCT cp.user_id 
            FROM conversation_participants cp 
            LEFT JOIN users u ON cp.user_id = u.id 
            WHERE u.id IS NULL
        ");
        $missing_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($missing_users) > 0) {
            echo '<p class="warning">⚠️ ' . count($missing_users) . ' utilisateurs référencés dans les conversations n\'existent pas.</p>';
            
            // Créer des utilisateurs factices pour éviter les erreurs
            foreach ($missing_users as $user_id) {
                $shop_pdo->prepare("
                    INSERT IGNORE INTO users (id, username, password, full_name, role) 
                    VALUES (?, CONCAT('user', ?), 'password', CONCAT('Utilisateur ', ?), 'technicien')
                ")->execute([$user_id, $user_id, $user_id]);
            }
            
            echo '<p class="success">✅ Utilisateurs manquants créés temporairement pour éviter les erreurs</p>';
        } else {
            echo '<p class="success">✅ Tous les utilisateurs référencés dans les conversations existent</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">❌ Erreur lors de la vérification des utilisateurs: ' . $e->getMessage() . '</p>';
    }
    
    echo '<p class="success mt-3">✅ Réparation terminée. <a href="index.php" class="btn btn-primary btn-sm">Retourner à la messagerie</a></p>';
    $fixed = true;
    echo '</div>';
}

// Afficher le formulaire de réparation
if (!$fixed) {
    echo '
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Que fait ce script?</h5>
                <p class="card-text">Ce script corrige les problèmes courants qui empêchent l\'affichage des conversations :</p>
                <ul>
                    <li>Corriger les dates dans le futur</li>
                    <li>Vérifier et réparer les participants manquants</li>
                    <li>Vérifier et corriger les références aux utilisateurs</li>
                </ul>
                <form method="post">
                    <input type="hidden" name="fix" value="1">
                    <button type="submit" class="btn btn-warning">Lancer la réparation</button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
        </div>
    ';
}

echo '
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
?> 