<?php
/**
 * API - Récupérer les conversations d'un utilisateur
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ajouter des logs détaillés pour le débogage
file_put_contents(__DIR__ . '/../logs/api_debug.log', '[' . date('Y-m-d H:i:s') . '] GET /api/get_conversations.php - Début de la requête' . PHP_EOL, FILE_APPEND);

// Initialiser la session
session_start();

// Log de l'état de la session
$session_info = 'Session ID: ' . session_id() . ', User ID: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Non défini');
file_put_contents(__DIR__ . '/../logs/api_debug.log', '[' . date('Y-m-d H:i:s') . '] ' . $session_info . PHP_EOL, FILE_APPEND);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    file_put_contents(__DIR__ . '/../logs/api_debug.log', '[' . date('Y-m-d H:i:s') . '] Erreur: Utilisateur non connecté' . PHP_EOL, FILE_APPEND);
    exit;
}

// Inclure les fonctions
require_once '../includes/functions.php';

// Récupérer les filtres
$filters = [];

if (isset($_GET['type']) && in_array($_GET['type'], ['direct', 'groupe', 'annonce'])) {
    $filters['type'] = $_GET['type'];
}

if (isset($_GET['favorites']) && $_GET['favorites'] === '1') {
    $filters['favorites'] = true;
}

if (isset($_GET['archived'])) {
    $filters['archived'] = ($_GET['archived'] === '1');
}

if (isset($_GET['unread']) && $_GET['unread'] === '1') {
    $filters['unread'] = true;
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = trim($_GET['search']);
}

// Log des filtres appliqués
file_put_contents(__DIR__ . '/../logs/api_debug.log', '[' . date('Y-m-d H:i:s') . '] Filtres: ' . json_encode($filters) . PHP_EOL, FILE_APPEND);

try {
    // Récupérer les conversations
    file_put_contents(__DIR__ . '/../logs/api_debug.log', '[' . date('Y-m-d H:i:s') . '] Appel à get_user_conversations avec user_id=' . $_SESSION['user_id'] . PHP_EOL, FILE_APPEND);
    $conversations = get_user_conversations($_SESSION['user_id'], $filters);
    file_put_contents(__DIR__ . '/../logs/api_debug.log', '[' . date('Y-m-d H:i:s') . '] Nombre de conversations récupérées: ' . count($conversations) . PHP_EOL, FILE_APPEND);
    
    // Formater les résultats
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
    
    // Log avant la réponse finale
    file_put_contents(__DIR__ . '/../logs/api_debug.log', '[' . date('Y-m-d H:i:s') . '] Réponse: ' . count($formatted_conversations) . ' conversations formatées' . PHP_EOL, FILE_APPEND);
    
    // Renvoyer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'conversations' => $formatted_conversations,
        'count' => count($formatted_conversations),
        'user_id' => $_SESSION['user_id'],
        'filters' => $filters
    ]);
} catch (Exception $e) {
    // Journaliser l'erreur
    log_error('Erreur lors de la récupération des conversations', $e->getMessage() . ' - ' . $e->getTraceAsString());
    file_put_contents(__DIR__ . '/../logs/api_debug.log', '[' . date('Y-m-d H:i:s') . '] Exception: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL, FILE_APPEND);
    
    // Renvoyer un message d'erreur
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des conversations: ' . $e->getMessage()
    ]);
}

// Log de fin
file_put_contents(__DIR__ . '/../logs/api_debug.log', '[' . date('Y-m-d H:i:s') . '] Fin de la requête' . PHP_EOL, FILE_APPEND);
exit;
?> 