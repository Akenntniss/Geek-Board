<?php
// API pour rechercher des messages
session_start();
header('Content-Type: application/json');

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Inclure les fichiers nécessaires
require_once('../../config/database.php');
require_once('../includes/messagerie_functions.php');

$user_id = $_SESSION['user_id'];
$search_term = isset($_GET['term']) ? trim($_GET['term']) : '';

// Vérifier que le terme de recherche est fourni
if (empty($search_term)) {
    echo json_encode(['success' => false, 'message' => 'Terme de recherche requis']);
    exit;
}

// Rechercher les messages
$messages = search_messages($user_id, $search_term);

// Formater les résultats
$results = [];
foreach ($messages as $message) {
    $date = new DateTime($message['date_envoi']);
    
    $results[] = [
        'id' => $message['id'],
        'conversation_id' => $message['conversation_id'],
        'conversation_title' => $message['conversation_title'],
        'contenu' => $message['contenu'],
        'sender_name' => $message['sender_name'],
        'date' => $date->format('d/m/Y H:i'),
        'type' => $message['type']
    ];
}

echo json_encode(['success' => true, 'results' => $results]);
?> 