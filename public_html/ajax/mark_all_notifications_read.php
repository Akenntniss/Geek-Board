<?php
/**
 * Point d'entrée AJAX pour marquer toutes les notifications comme lues
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

// Marquer toutes les notifications comme lues
$success = mark_all_notifications_as_read($user_id);

// Préparer la réponse
$response = [
    'success' => $success,
    'message' => $success ? 'Toutes les notifications ont été marquées comme lues' : 'Erreur lors de la mise à jour des notifications'
];

// Envoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response);
exit; 