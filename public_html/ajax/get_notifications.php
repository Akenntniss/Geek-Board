<?php
/**
 * Point d'entrée AJAX pour récupérer les notifications non lues
 */

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/notification_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Récupérer l'ID de l'utilisateur
$user_id = $_SESSION['user_id'];

// Récupérer les notifications non lues
$notifications = get_unread_notifications($user_id, 5); // Limiter à 5 notifications

// Compter le total des notifications non lues
$count = count_unread_notifications($user_id);

// Préparer la réponse
$response = [
    'success' => true,
    'count' => $count,
    'notifications' => $notifications
];

// Envoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response);
exit; 