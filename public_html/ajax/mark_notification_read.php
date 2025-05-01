<?php
/**
 * Marque une notification comme lue
 */

// Inclure les fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Utilisateur non connecté'
    ]);
    exit;
}

// Vérifier si l'ID de notification est fourni
if (!isset($_POST['notification_id']) || empty($_POST['notification_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de notification manquant'
    ]);
    exit;
}

$notification_id = intval($_POST['notification_id']);

// Marquer la notification comme lue
$success = mark_notification_as_read($notification_id);

// Renvoyer le résultat
echo json_encode([
    'success' => $success
]);