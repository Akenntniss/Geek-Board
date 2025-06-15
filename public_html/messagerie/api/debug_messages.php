<?php
// Script de débogage pour identifier les problèmes avec get_messages.php
header('Content-Type: text/html; charset=utf-8');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Journal de débogage
$log = [];
$log[] = "Début du débogage - " . date('Y-m-d H:i:s');

// Vérifier la session
session_start();
if (!isset($_SESSION['user_id'])) {
    $log[] = "Erreur: Utilisateur non connecté";
} else {
    $log[] = "Utilisateur connecté: " . $_SESSION['user_id'];
}

// Vérifier les paramètres
if (!isset($_GET['conversation_id'])) {
    $log[] = "Erreur: ID de conversation non fourni";
} else {
    $log[] = "Conversation ID: " . $_GET['conversation_id'];
}

try {
    // Inclure les fichiers nécessaires
    require_once('../../config/database.php');
    $log[] = "Fichier database.php inclus avec succès";
    
    // Tester la connexion à la base de données
    $log[] = "Test de connexion à la base de données...";
    if (isset($shop_pdo)) {
        $log[] = "Connexion PDO établie";
        
        // Vérifier si la table messages existe
        $stmt = $shop_pdo->query("SHOW TABLES LIKE 'messages'");
        if ($stmt->rowCount() > 0) {
            $log[] = "Table 'messages' existe";
            
            // Vérifier s'il y a des messages dans la conversation
            if (isset($_GET['conversation_id'])) {
                $conversation_id = (int)$_GET['conversation_id'];
                $stmt = $shop_pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE conversation_id = ?");
                $stmt->execute([$conversation_id]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $log[] = "Nombre de messages dans la conversation $conversation_id: $count";
                
                // Vérifier les participants de la conversation
                $stmt = $shop_pdo->prepare("SELECT user_id FROM conversation_participants WHERE conversation_id = ?");
                $stmt->execute([$conversation_id]);
                $participants = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $log[] = "Participants: " . implode(', ', $participants);
                
                // Vérifier si l'utilisateur est participant
                $is_participant = in_array($_SESSION['user_id'], $participants);
                $log[] = "Utilisateur est participant: " . ($is_participant ? 'Oui' : 'Non');
                
                // Essayer de charger quelques messages directement
                $log[] = "Essai de chargement des messages...";
                $stmt = $shop_pdo->prepare("
                    SELECT m.*, u.full_name as sender_name
                    FROM messages m
                    LEFT JOIN users u ON m.sender_id = u.id
                    WHERE m.conversation_id = ?
                    ORDER BY m.date_envoi ASC
                    LIMIT 5
                ");
                $stmt->execute([$conversation_id]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($messages) > 0) {
                    $log[] = "Messages récupérés: " . count($messages);
                    $log[] = "Premier message: " . json_encode($messages[0], JSON_PRETTY_PRINT);
                } else {
                    $log[] = "Aucun message trouvé";
                }
            }
        } else {
            $log[] = "Erreur: Table 'messages' n'existe pas";
        }
    } else {
        $log[] = "Erreur: Connexion PDO non établie";
    }
    
    // Tester l'inclusion des fonctions de messagerie
    require_once('../includes/messagerie_functions.php');
    $log[] = "Fichier messagerie_functions.php inclus avec succès";
    
    // Tester les fonctions spécifiques
    if (function_exists('get_conversation_messages')) {
        $log[] = "Fonction get_conversation_messages existe";
        
        // Tester la fonction avec les paramètres réels
        if (isset($_SESSION['user_id']) && isset($_GET['conversation_id'])) {
            $conversation_id = (int)$_GET['conversation_id'];
            $user_id = $_SESSION['user_id'];
            $log[] = "Test de get_conversation_messages($conversation_id, $user_id)...";
            
            try {
                $result = get_conversation_messages($conversation_id, $user_id);
                if (isset($result['error'])) {
                    $log[] = "Erreur retournée par get_conversation_messages: " . $result['error'];
                } else {
                    $log[] = "get_conversation_messages a retourné " . count($result) . " messages";
                }
            } catch (Exception $e) {
                $log[] = "Exception dans get_conversation_messages: " . $e->getMessage();
            }
        }
    } else {
        $log[] = "Erreur: Fonction get_conversation_messages n'existe pas";
    }
} catch (Exception $e) {
    $log[] = "Exception générale: " . $e->getMessage();
}

// Afficher les résultats de débogage
echo "<html><head><title>Débogage Messagerie</title>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; }</style>";
echo "</head><body>";
echo "<h1>Débogage du chargement des messages</h1>";
echo "<pre>";
foreach ($log as $entry) {
    if (strpos($entry, 'Erreur') !== false || strpos($entry, 'Exception') !== false) {
        echo "<span class='error'>$entry</span>\n";
    } else {
        echo "<span class='success'>$entry</span>\n";
    }
}
echo "</pre>";
echo "</body></html>";
?> 