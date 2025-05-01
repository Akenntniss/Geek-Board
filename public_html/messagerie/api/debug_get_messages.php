<?php
// Script de débogage pour identifier les problèmes de récupération des messages
header('Content-Type: application/json');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Journal de débogage
$debug_log = [];
function add_log($message, $data = null, $type = 'info') {
    global $debug_log;
    $debug_log[] = [
        'time' => date('H:i:s'),
        'type' => $type,
        'message' => $message,
        'data' => $data
    ];
}

add_log("Démarrage du script de débogage");

// Vérification des variables de session
session_start();
add_log("Session démarrée", ['session_id' => session_id(), 'user_id' => $_SESSION['user_id'] ?? 'non défini']);

if (!isset($_SESSION['user_id'])) {
    add_log("Erreur : Utilisateur non connecté", null, 'error');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté', 'debug' => $debug_log]);
    exit;
}

// Vérification des paramètres de requête
add_log("Paramètres de la requête", $_GET);

if (!isset($_GET['conversation_id']) || !is_numeric($_GET['conversation_id'])) {
    add_log("Erreur : ID de conversation invalide", null, 'error');
    echo json_encode(['success' => false, 'message' => 'ID de conversation invalide', 'debug' => $debug_log]);
    exit;
}

// Inclure les fichiers nécessaires
add_log("Chargement des dépendances");
try {
    require_once('../../database.php');
    add_log("Connexion à la base de données chargée");
} catch (Exception $e) {
    add_log("Erreur lors du chargement de database.php", $e->getMessage(), 'error');
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données', 'debug' => $debug_log]);
    exit;
}

try {
    require_once('../includes/messagerie_functions.php');
    add_log("Fonctions de messagerie chargées");
} catch (Exception $e) {
    add_log("Erreur lors du chargement des fonctions de messagerie", $e->getMessage(), 'error');
    echo json_encode(['success' => false, 'message' => 'Erreur de chargement des fonctions', 'debug' => $debug_log]);
    exit;
}

// Récupération des paramètres
$user_id = $_SESSION['user_id'];
$conversation_id = (int)$_GET['conversation_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

add_log("Paramètres extraits", [
    'user_id' => $user_id,
    'conversation_id' => $conversation_id,
    'limit' => $limit,
    'offset' => $offset
]);

// Vérification de l'existence de la conversation
try {
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo) {
        add_log("Erreur : Objet PDO non disponible", null, 'error');
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données', 'debug' => $debug_log]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ?");
    $stmt->execute([$conversation_id]);
    $conversation_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    add_log("Vérification de la conversation", [
        'exists' => !empty($conversation_exists),
        'data' => $conversation_exists ?: null
    ]);
    
    if (!$conversation_exists) {
        add_log("Erreur : Conversation non trouvée", null, 'error');
        echo json_encode(['success' => false, 'message' => 'Conversation non trouvée', 'debug' => $debug_log]);
        exit;
    }
    
    // Vérification des participants
    $stmt = $pdo->prepare("SELECT * FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $stmt->execute([$conversation_id, $user_id]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    add_log("Vérification du participant", [
        'is_participant' => !empty($participant),
        'data' => $participant ?: null
    ]);
    
    if (!$participant) {
        add_log("Erreur : Utilisateur non autorisé à accéder à cette conversation", null, 'error');
        echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas accès à cette conversation', 'debug' => $debug_log]);
        exit;
    }
    
    // Vérification des messages
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM messages WHERE conversation_id = ?");
    $stmt->execute([$conversation_id]);
    $messages_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    add_log("Nombre de messages dans la conversation", $messages_count);
    
    // Test direct de la requête SQL
    $query = "
        SELECT m.*, 
               u.full_name as sender_name
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = :conversation_id
        ORDER BY m.date_envoi DESC
        LIMIT :limit OFFSET :offset
    ";
    
    add_log("Requête SQL à exécuter", $query);
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':conversation_id', $conversation_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $messages_direct = $stmt->fetchAll(PDO::FETCH_ASSOC);
    add_log("Résultat direct de la requête", [
        'count' => count($messages_direct),
        'first_message' => !empty($messages_direct) ? $messages_direct[0] : null
    ]);
    
    // Appel à la fonction get_conversation_messages
    try {
        add_log("Appel de la fonction get_conversation_messages");
        $messages = get_conversation_messages($conversation_id, $user_id, $limit, $offset);
        
        add_log("Résultat de get_conversation_messages", [
            'is_error' => isset($messages['error']),
            'error' => isset($messages['error']) ? $messages['error'] : null,
            'count' => is_array($messages) && !isset($messages['error']) ? count($messages) : 0,
            'first_message' => is_array($messages) && !isset($messages['error']) && !empty($messages) ? $messages[0] : null
        ]);
        
        if (isset($messages['error'])) {
            echo json_encode(['success' => false, 'message' => $messages['error'], 'debug' => $debug_log]);
            exit;
        }
    } catch (Exception $e) {
        add_log("Exception lors de l'appel à get_conversation_messages", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 'error');
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des messages: ' . $e->getMessage(), 'debug' => $debug_log]);
        exit;
    }
    
    // Tester la récupération des détails de la conversation
    try {
        add_log("Appel de la fonction get_conversation_details");
        $conversation = get_conversation_details($conversation_id, $user_id);
        
        add_log("Résultat de get_conversation_details", [
            'is_error' => isset($conversation['error']),
            'error' => isset($conversation['error']) ? $conversation['error'] : null,
            'data' => !isset($conversation['error']) ? $conversation : null
        ]);
        
        if (isset($conversation['error'])) {
            echo json_encode(['success' => false, 'message' => $conversation['error'], 'debug' => $debug_log]);
            exit;
        }
    } catch (Exception $e) {
        add_log("Exception lors de l'appel à get_conversation_details", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 'error');
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des détails de la conversation: ' . $e->getMessage(), 'debug' => $debug_log]);
        exit;
    }
    
    // Test de formatage des messages
    add_log("Formatage des messages");
    try {
        if (is_array($messages) && !empty($messages)) {
            foreach ($messages as &$message) {
                $message['is_mine'] = ($message['sender_id'] == $user_id);
                
                $date = new DateTime($message['date_envoi']);
                $message['time'] = $date->format('H:i');
                $message['date'] = $date->format('d/m/Y');
                
                if ($message['type'] == 'fichier' && !empty($message['fichier_url'])) {
                    $message['fichier_url'] = '../../' . $message['fichier_url'];
                }
            }
            add_log("Messages formatés avec succès", [
                'count' => count($messages),
                'first_message' => !empty($messages) ? $messages[0] : null
            ]);
        } else {
            add_log("Aucun message à formater", null, 'warning');
        }
    } catch (Exception $e) {
        add_log("Erreur lors du formatage des messages", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 'error');
    }
    
    // Préparation de la réponse finale
    add_log("Préparation de la réponse JSON finale");
    $response = [
        'success' => true,
        'messages' => $messages,
        'conversation' => $conversation,
        'debug' => $debug_log
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    add_log("Erreur PDO", [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 'error');
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage(), 'debug' => $debug_log]);
} catch (Exception $e) {
    add_log("Exception générale", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 'error');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage(), 'debug' => $debug_log]);
}
?> 