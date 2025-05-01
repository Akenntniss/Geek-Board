<?php
/**
 * API - Créer une nouvelle conversation
 */

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Vérifier les données
if (!isset($input['titre']) || trim($input['titre']) === '') {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Titre de conversation manquant']);
    exit;
}

if (!isset($input['type']) || !in_array($input['type'], ['direct', 'groupe', 'annonce'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Type de conversation invalide']);
    exit;
}

if (!isset($input['participants']) || !is_array($input['participants']) || count($input['participants']) === 0) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Participants manquants']);
    exit;
}

// Extraire les données
$titre = trim($input['titre']);
$type = $input['type'];
$participants = array_map('intval', $input['participants']);
$first_message = isset($input['first_message']) ? trim($input['first_message']) : null;

// Inclure les fonctions
require_once '../includes/functions.php';

// Créer la conversation
$result = create_conversation($titre, $type, $_SESSION['user_id'], $participants);

if (is_array($result) && isset($result['error'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => $result['error']]);
    exit;
}

// ID de la conversation créée ou existante
$conversation_id = $result;

// Envoyer un premier message si fourni
if (!empty($first_message)) {
    $message_result = send_message($conversation_id, $_SESSION['user_id'], $first_message);
    
    if (is_array($message_result) && isset($message_result['error'])) {
        // La conversation a été créée mais le message a échoué - on continue quand même
        log_error('Erreur lors de l\'envoi du premier message', $message_result['error']);
    }
}

// Renvoyer la réponse avec l'ID de la conversation
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Conversation créée avec succès',
    'conversation_id' => $conversation_id
]); 