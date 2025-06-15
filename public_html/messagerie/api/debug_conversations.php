<?php
/**
 * Fichier de débogage pour les conversations
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialiser la session
session_start();

// Informations sur la session
echo "<h2>Informations de session</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Non défini') . "\n";
echo "</pre>";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "<div style='color: red;'>ERREUR: Utilisateur non connecté</div>";
    exit;
}

// Inclure les fonctions
require_once '../includes/functions.php';

// Inclure la connexion à la base de données s'il n'est pas déjà inclus
if (!isset($shop_pdo)) {
    echo "<h2>Connexion à la base de données</h2>";
    try {
        require_once __DIR__ . '/../../config/database.php';
        echo "<div style='color: green;'>Connexion à la base de données réussie</div>";
    } catch (Exception $e) {
        echo "<div style='color: red;'>ERREUR de connexion à la base de données: " . $e->getMessage() . "</div>";
        exit;
    }
}

// Tester la fonction get_user_conversations
echo "<h2>Test de get_user_conversations</h2>";
try {
    // Exécuter une requête simple pour tester la connexion
    $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM conversations");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Total de conversations dans la base: " . $total . "<br>";
    
    echo "<pre>Exécution de: get_user_conversations(" . $_SESSION['user_id'] . ")</pre>";
    
    // Récupérer les conversations
    $conversations = get_user_conversations($_SESSION['user_id']);
    
    echo "<div style='color: green;'>Fonction exécutée avec succès</div>";
    echo "Nombre de conversations récupérées: " . count($conversations) . "<br>";
    
    if (count($conversations) > 0) {
        echo "<h3>Première conversation:</h3>";
        echo "<pre>";
        print_r($conversations[0]);
        echo "</pre>";
    } else {
        echo "<div style='color: orange;'>Aucune conversation trouvée pour cet utilisateur</div>";
    }
    
    // Afficher la requête SQL utilisée dans get_user_conversations
    echo "<h3>Requête SQL utilisée:</h3>";
    echo "<pre>
    SELECT c.*, 
        cp.role,
        cp.est_favoris,
        cp.est_archive,
        cp.notification_mute,
        cp.date_derniere_lecture,
        u.full_name AS created_by_name,
        (
            SELECT COUNT(*)
            FROM messages m
            WHERE m.conversation_id = c.id
            AND m.date_envoi > COALESCE(cp.date_derniere_lecture, '2000-01-01')
            AND (m.sender_id IS NULL OR m.sender_id != :user_id)
            AND m.est_supprime = 0
        ) AS unread_count
    FROM conversations c
    JOIN conversation_participants cp ON c.id = cp.conversation_id
    LEFT JOIN users u ON c.created_by = u.id
    WHERE cp.user_id = :user_id
    ORDER BY cp.est_favoris DESC, c.derniere_activite DESC
    </pre>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>ERREUR: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Tester la récupération des participants d'une conversation
echo "<h2>Test de get_conversation_participants</h2>";
if (isset($conversations) && count($conversations) > 0) {
    $firstConvId = $conversations[0]['id'];
    echo "<pre>Exécution de: get_conversation_participants(" . $firstConvId . ")</pre>";
    
    try {
        $participants = get_conversation_participants($firstConvId);
        echo "<div style='color: green;'>Fonction exécutée avec succès</div>";
        echo "Nombre de participants: " . count($participants) . "<br>";
        
        echo "<pre>";
        print_r($participants);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<div style='color: red;'>ERREUR: " . $e->getMessage() . "</div>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<div style='color: orange;'>Pas de conversation disponible pour tester les participants</div>";
}

// Tester la récupération du dernier message
echo "<h2>Test de get_last_message</h2>";
if (isset($conversations) && count($conversations) > 0) {
    $firstConvId = $conversations[0]['id'];
    echo "<pre>Exécution de: get_last_message(" . $firstConvId . ")</pre>";
    
    try {
        $lastMessage = get_last_message($firstConvId);
        echo "<div style='color: green;'>Fonction exécutée avec succès</div>";
        
        if ($lastMessage) {
            echo "<pre>";
            print_r($lastMessage);
            echo "</pre>";
        } else {
            echo "<div style='color: orange;'>Aucun message trouvé pour cette conversation</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>ERREUR: " . $e->getMessage() . "</div>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<div style='color: orange;'>Pas de conversation disponible pour tester get_last_message</div>";
}

// Afficher les données brutes du frontend
echo "<h2>Test de l'API get_conversations.php</h2>";
echo "<p>Résultat qui sera envoyé au frontend:</p>";

try {
    // Simuler l'appel à get_conversations.php
    $conversations = get_user_conversations($_SESSION['user_id']);
    
    // Formater les résultats comme dans get_conversations.php
    $formatted_conversations = [];
    
    foreach ($conversations as $conversation) {
        $formatted_participants = [];
        
        if (isset($conversation['participants']) && is_array($conversation['participants'])) {
            foreach ($conversation['participants'] as $participant) {
                if ($participant['user_id'] != $_SESSION['user_id']) {
                    $formatted_participants[] = $participant['full_name'] ?? 'Utilisateur';
                }
            }
        }
        
        $formatted_conversations[] = [
            'id' => $conversation['id'],
            'titre' => $conversation['titre'],
            'type' => $conversation['type'],
            'role' => $conversation['role'],
            'est_favoris' => (bool)($conversation['est_favoris'] ?? false),
            'est_archive' => (bool)($conversation['est_archive'] ?? false),
            'notification_mute' => (bool)($conversation['notification_mute'] ?? false),
            'date_creation' => $conversation['date_creation'],
            'derniere_activite' => $conversation['derniere_activite'],
            'created_by' => $conversation['created_by'],
            'created_by_name' => $conversation['created_by_name'] ?? '',
            'unread_count' => (int)($conversation['unread_count'] ?? 0),
            'participants' => $formatted_participants,
            'participants_count' => isset($conversation['participants']) ? count($conversation['participants']) : 0,
            'last_message' => $conversation['last_message'] ?? null
        ];
    }
    
    $response = [
        'success' => true, 
        'conversations' => $formatted_conversations,
        'count' => count($formatted_conversations),
        'user_id' => $_SESSION['user_id']
    ];
    
    echo "<pre>";
    print_r($response);
    echo "</pre>";
} catch (Exception $e) {
    echo "<div style='color: red;'>ERREUR: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} 