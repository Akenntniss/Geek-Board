<?php
/**
 * API - Éditer un message
 * 
 * Cette API permet aux utilisateurs de modifier le contenu d'un message
 * qu'ils ont envoyé précédemment.
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

// Récupérer le corps de la requête
$input = json_decode(file_get_contents('php://input'), true);

// Vérifier les données
if (!isset($input['message_id']) || !is_numeric($input['message_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de message manquant ou invalide']);
    exit;
}

if (!isset($input['content']) || trim($input['content']) === '') {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Contenu du message manquant ou vide']);
    exit;
}

$message_id = (int)$input['message_id'];
$content = trim($input['content']);

// Inclure les fonctions
require_once '../includes/functions.php';

// Vérifier si l'utilisateur a accès au message
$message_info = get_message_info($message_id);

if (!$message_info) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['success' => false, 'message' => 'Message introuvable']);
    exit;
}

// Vérifier que l'utilisateur est l'auteur du message
if ($message_info['sender_id'] != $_SESSION['user_id']) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas modifier ce message']);
    exit;
}

// Vérifier que le message n'est pas trop ancien (limite à 24h par exemple)
$date_envoi = new DateTime($message_info['date_envoi']);
$now = new DateTime();
$diff = $now->diff($date_envoi);

if ($diff->days > 0) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez plus modifier ce message (limite de 24h dépassée)']);
    exit;
}

// Effectuer la modification
$result = edit_message($message_id, $content, $_SESSION['user_id']);

if ($result) {
    // Renvoyer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Message modifié avec succès',
        'content' => $content
    ]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification du message']);
    exit;
} 