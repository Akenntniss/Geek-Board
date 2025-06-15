<?php
/**
 * Page de diagnostic pour la messagerie
 */

// Activer l'affichage complet des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialiser la session
session_start();

// Vérifier la session
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Non connecté';

// Inclure les fonctions et la base de données
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

// Style CSS pour une meilleure présentation
echo '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Messagerie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 8px; }
        h2 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { padding: 8px; border: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .fixed-logs { height: 200px; overflow-y: auto; background-color: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; }
        pre { margin: 0; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnostic de la Messagerie</h1>
        <p>Cette page vous aide à diagnostiquer les problèmes avec le module de messagerie.</p>
';

// Section 1: Informations de session
echo '<div class="section">';
echo '<h2>1. Informations de session</h2>';
if (!$user_id) {
    echo '<p class="error">⚠️ Vous n\'êtes pas connecté. La messagerie ne fonctionnera pas sans connexion.</p>';
} else {
    echo '<p class="success">✅ Connecté en tant que: ' . htmlspecialchars($username) . ' (ID: ' . $user_id . ')</p>';
}
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
echo '</div>';

// Section 2: Connexion à la base de données
echo '<div class="section">';
echo '<h2>2. Connexion à la base de données</h2>';
try {
    $shop_pdo->query("SELECT 1");
    echo '<p class="success">✅ Connexion à la base de données réussie</p>';
    
    // Informations sur la version de MariaDB/MySQL
    $version = $shop_pdo->query("SELECT VERSION() as version")->fetch(PDO::FETCH_ASSOC);
    echo '<p>Version de la base de données: ' . $version['version'] . '</p>';
} catch (PDOException $e) {
    echo '<p class="error">❌ Erreur de connexion à la base de données: ' . $e->getMessage() . '</p>';
}
echo '</div>';

// Section 3: Vérification des tables
echo '<div class="section">';
echo '<h2>3. Vérification des tables</h2>';
$tables = [
    'conversations' => 'Conversations',
    'conversation_participants' => 'Participants',
    'messages' => 'Messages',
    'message_attachments' => 'Pièces jointes',
    'message_reactions' => 'Réactions',
    'message_reads' => 'Lectures',
    'typing_status' => 'Statuts de frappe',
    'users' => 'Utilisateurs'
];

echo '<table>';
echo '<tr><th>Table</th><th>Nom</th><th>Nombre d\'enregistrements</th><th>État</th></tr>';

foreach ($tables as $table => $name) {
    echo '<tr>';
    echo '<td>' . $table . '</td>';
    echo '<td>' . $name . '</td>';
    
    try {
        $count = $shop_pdo->query("SELECT COUNT(*) as count FROM $table")->fetch(PDO::FETCH_ASSOC)['count'];
        echo '<td>' . $count . '</td>';
        echo '<td class="success">✅ OK</td>';
    } catch (PDOException $e) {
        echo '<td>-</td>';
        echo '<td class="error">❌ Erreur: ' . $e->getMessage() . '</td>';
    }
    
    echo '</tr>';
}

echo '</table>';
echo '</div>';

// Section 4: Conversations de l'utilisateur
if ($user_id) {
    echo '<div class="section">';
    echo '<h2>4. Vos conversations</h2>';
    
    try {
        $conversations = get_user_conversations($user_id);
        echo '<p>Nombre de conversations trouvées: <strong>' . count($conversations) . '</strong></p>';
        
        if (count($conversations) > 0) {
            echo '<table>';
            echo '<tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Type</th>
                <th>Créé par</th>
                <th>Date création</th>
                <th>Dernière activité</th>
                <th>Messages</th>
                <th>Participants</th>
                <th>Actions</th>
            </tr>';
            
            foreach ($conversations as $conv) {
                // Compter les messages
                $message_count = $shop_pdo->query("SELECT COUNT(*) as count FROM messages WHERE conversation_id = " . $conv['id'])->fetch(PDO::FETCH_ASSOC)['count'];
                
                echo '<tr>';
                echo '<td>' . $conv['id'] . '</td>';
                echo '<td>' . htmlspecialchars($conv['titre']) . '</td>';
                echo '<td>' . $conv['type'] . '</td>';
                echo '<td>' . ($conv['created_by_name'] ?? ('ID: ' . $conv['created_by'])) . '</td>';
                echo '<td>' . $conv['date_creation'] . '</td>';
                echo '<td>' . $conv['derniere_activite'] . '</td>';
                echo '<td>' . $message_count . '</td>';
                
                // Participants
                echo '<td>';
                if (isset($conv['participants']) && is_array($conv['participants'])) {
                    foreach ($conv['participants'] as $p) {
                        echo htmlspecialchars($p['full_name'] ?? 'Inconnu') . ' (ID: ' . $p['user_id'] . ')<br>';
                    }
                } else {
                    echo 'Aucun participant trouvé';
                }
                echo '</td>';
                
                // Actions
                echo '<td>';
                echo '<a href="diagnostic.php?action=fix_conversation&id=' . $conv['id'] . '" class="btn btn-sm btn-warning">Réparer</a> ';
                echo '<a href="diagnostic.php?action=view_messages&id=' . $conv['id'] . '" class="btn btn-sm btn-info">Voir messages</a>';
                echo '</td>';
                
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p class="warning">Aucune conversation trouvée. Cela peut être normal si vous n\'avez pas encore créé de conversation.</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Erreur lors de la récupération des conversations: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
}

// Section 5: Actions de réparation
echo '<div class="section">';
echo '<h2>5. Actions de réparation</h2>';

// Gérer les actions demandées
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Réparer une conversation spécifique
    if ($action === 'fix_conversation' && isset($_GET['id'])) {
        $conv_id = (int)$_GET['id'];
        
        try {
            // 1. Mettre à jour les dates
            $shop_pdo->exec("UPDATE conversations SET date_creation = NOW(), derniere_activite = NOW() WHERE id = $conv_id");
            $shop_pdo->exec("UPDATE conversation_participants SET date_ajout = NOW() WHERE conversation_id = $conv_id");
            
            // 2. Vérifier les participants
            $participants = $shop_pdo->query("SELECT user_id FROM conversation_participants WHERE conversation_id = $conv_id")->fetchAll(PDO::FETCH_COLUMN);
            
            echo '<p class="success">✅ Conversation #' . $conv_id . ' réparée avec succès!</p>';
            echo '<p>Dates mises à jour et ' . count($participants) . ' participants vérifiés.</p>';
        } catch (Exception $e) {
            echo '<p class="error">❌ Erreur lors de la réparation: ' . $e->getMessage() . '</p>';
        }
    }
    
    // Voir les messages d'une conversation
    else if ($action === 'view_messages' && isset($_GET['id'])) {
        $conv_id = (int)$_GET['id'];
        
        try {
            $messages = get_conversation_messages($conv_id, $user_id);
            
            echo '<h3>Messages de la conversation #' . $conv_id . '</h3>';
            
            if (is_array($messages) && !isset($messages['error'])) {
                echo '<p>Nombre de messages: ' . count($messages) . '</p>';
                
                if (count($messages) > 0) {
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Expéditeur</th><th>Contenu</th><th>Type</th><th>Date</th></tr>';
                    
                    foreach ($messages as $msg) {
                        echo '<tr>';
                        echo '<td>' . $msg['id'] . '</td>';
                        echo '<td>' . ($msg['sender_name'] ?? ('ID: ' . $msg['sender_id'])) . '</td>';
                        echo '<td>' . htmlspecialchars(substr($msg['contenu'], 0, 100)) . '</td>';
                        echo '<td>' . $msg['type'] . '</td>';
                        echo '<td>' . $msg['date_envoi'] . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                } else {
                    echo '<p class="warning">Cette conversation ne contient aucun message.</p>';
                }
            } else {
                $error = isset($messages['error']) ? $messages['error'] : 'Erreur inconnue';
                echo '<p class="error">❌ Erreur: ' . $error . '</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Erreur lors de la récupération des messages: ' . $e->getMessage() . '</p>';
        }
    }
}

// Actions de réparation disponibles
echo '<h3>Actions disponibles</h3>';
echo '<a href="diagnostic.php?action=fix_all_dates" class="btn btn-warning">Réparer toutes les dates</a> ';
echo '<a href="diagnostic.php?action=test_api" class="btn btn-primary">Tester l\'API</a> ';
echo '<a href="../index.php" class="btn btn-success">Retour à la messagerie</a>';

// Gestion des actions globales
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Réparer toutes les dates
    if ($action === 'fix_all_dates') {
        try {
            $shop_pdo->exec("UPDATE conversations SET date_creation = NOW(), derniere_activite = NOW()");
            $shop_pdo->exec("UPDATE conversation_participants SET date_ajout = NOW()");
            $shop_pdo->exec("UPDATE messages SET date_envoi = NOW()");
            
            echo '<p class="success mt-3">✅ Toutes les dates ont été mises à jour avec succès!</p>';
        } catch (Exception $e) {
            echo '<p class="error mt-3">❌ Erreur lors de la mise à jour des dates: ' . $e->getMessage() . '</p>';
        }
    }
    
    // Tester l'API
    else if ($action === 'test_api') {
        echo '<h3 class="mt-3">Test de l\'API</h3>';
        
        // Test 1: get_conversations.php
        echo '<h4>Test de get_conversations.php</h4>';
        
        $api_url = 'api/get_conversations.php';
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo '<p>Statut de la réponse: ' . $status . '</p>';
        echo '<pre class="fixed-logs">' . htmlspecialchars($response) . '</pre>';
    }
}

echo '</div>';

// Section 6: Logs et débogage
echo '<div class="section">';
echo '<h2>6. Logs et débogage</h2>';

// Afficher les erreurs PHP récentes
$error_log_path = ini_get('error_log');
echo '<h3>Erreurs PHP récentes</h3>';
if (file_exists($error_log_path)) {
    $errors = shell_exec('tail -n 50 ' . escapeshellarg($error_log_path));
    echo '<div class="fixed-logs"><pre>' . ($errors ?: 'Aucune erreur récente.') . '</pre></div>';
} else {
    echo '<p class="warning">Le fichier de log PHP n\'a pas été trouvé.</p>';
}

// Afficher les logs de la messagerie
$messagerie_log = __DIR__ . '/logs/messagerie_errors.log';
echo '<h3>Logs de la messagerie</h3>';
if (file_exists($messagerie_log)) {
    $logs = shell_exec('tail -n 50 ' . escapeshellarg($messagerie_log));
    echo '<div class="fixed-logs"><pre>' . ($logs ?: 'Aucun log récent.') . '</pre></div>';
} else {
    echo '<p class="warning">Le fichier de log de la messagerie n\'a pas été trouvé.</p>';
}

// Logs de l'API
$api_log = __DIR__ . '/logs/api_debug.log';
echo '<h3>Logs de l\'API</h3>';
if (file_exists($api_log)) {
    $logs = shell_exec('tail -n 50 ' . escapeshellarg($api_log));
    echo '<div class="fixed-logs"><pre>' . ($logs ?: 'Aucun log d\'API récent.') . '</pre></div>';
} else {
    echo '<p class="warning">Le fichier de log de l\'API n\'a pas été trouvé.</p>';
}

echo '</div>';

// Pied de page
echo '
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
';
?> 