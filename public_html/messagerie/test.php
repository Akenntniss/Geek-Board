<?php
/**
 * Page de test pour diagnostiquer les problèmes de messagerie
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté, sinon simuler une connexion pour le test
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Simuler la connexion avec l'utilisateur ID 1 (admin)
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
}

// Afficher l'état de la session
echo "<h2>État de la session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Inclure la connexion à la base de données
$shop_pdo = null;
try {
    require_once __DIR__ . '/../config/database.php';
    echo "<p style='color:green'>✅ Connexion à la base de données réussie</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur de connexion à la base de données: " . $e->getMessage() . "</p>";
    exit;
}

// Inclure les fonctions
require_once __DIR__ . '/includes/functions.php';

// Récupérer les conversations de l'utilisateur
try {
    $conversations = get_user_conversations($_SESSION['user_id']);
    echo "<h2>Conversations récupérées (" . count($conversations) . ")</h2>";
    
    // Afficher les conversations
    if (count($conversations) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Titre</th><th>Type</th><th>Participants</th><th>Dernier message</th></tr>";
        
        foreach ($conversations as $conv) {
            echo "<tr>";
            echo "<td>" . $conv['id'] . "</td>";
            echo "<td>" . $conv['titre'] . "</td>";
            echo "<td>" . $conv['type'] . "</td>";
            
            // Afficher les participants
            echo "<td>";
            if (isset($conv['participants']) && is_array($conv['participants'])) {
                foreach ($conv['participants'] as $p) {
                    echo "<div>" . ($p['full_name'] ?? 'Inconnu') . " (ID: " . ($p['user_id'] ?? '?') . ")</div>";
                }
            } else {
                echo "Aucun participant";
            }
            echo "</td>";
            
            // Afficher le dernier message
            echo "<td>";
            if (isset($conv['last_message']) && $conv['last_message']) {
                echo "De: " . ($conv['last_message']['sender_name'] ?? 'Inconnu') . "<br>";
                echo "Message: " . (substr($conv['last_message']['contenu'] ?? '', 0, 50)) . "<br>";
                echo "Date: " . ($conv['last_message']['formatted_date'] ?? '-');
            } else {
                echo "Aucun message";
            }
            echo "</td>";
            
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Afficher la structure détaillée pour debug
        echo "<h3>Détails de la première conversation</h3>";
        echo "<pre>";
        print_r($conversations[0]);
        echo "</pre>";
        
    } else {
        echo "<p>Aucune conversation trouvée pour cet utilisateur.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erreur lors de la récupération des conversations: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Formulaire pour créer une nouvelle conversation
echo "<h2>Créer une nouvelle conversation</h2>";
echo "<form method='post' action='api/create_conversation.php'>";
echo "<div style='margin-bottom: 10px;'>";
echo "<label>Titre: </label>";
echo "<input type='text' name='titre' value='Test conversation' required>";
echo "</div>";

echo "<div style='margin-bottom: 10px;'>";
echo "<label>Type: </label>";
echo "<select name='type'>";
echo "<option value='direct'>Direct</option>";
echo "<option value='groupe'>Groupe</option>";
echo "</select>";
echo "</div>";

echo "<div style='margin-bottom: 10px;'>";
echo "<label>Participants (IDs séparés par des virgules): </label>";
echo "<input type='text' name='participants' value='2,3' required>";
echo "</div>";

echo "<div style='margin-bottom: 10px;'>";
echo "<label>Premier message: </label>";
echo "<textarea name='first_message'>Bonjour, ceci est un test</textarea>";
echo "</div>";

echo "<button type='submit'>Créer conversation</button>";
echo "</form>";

echo "<script>
// Script pour tester manuellement l'API
function testApi(endpoint) {
    fetch(`api/${endpoint}.php`)
        .then(r => r.text())
        .then(text => {
            document.getElementById('result').innerHTML = '<pre>' + text + '</pre>';
        })
        .catch(err => {
            document.getElementById('result').innerHTML = 'Erreur: ' + err.message;
        });
}
</script>";

echo "<h2>Tester directement les API</h2>";
echo "<button onclick=\"testApi('get_conversations')\">Test get_conversations</button> ";
echo "<button onclick=\"testApi('check_session')\">Test check_session</button> ";
echo "<button onclick=\"testApi('get_users')\">Test get_users</button>";
echo "<div id='result'></div>";

// Lien vers l'interface
echo "<p><a href='index.php'>Retour à l'interface de messagerie</a></p>";
?> 