<?php
/**
 * Compte les notifications non lues de l'utilisateur connecté
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

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Compter les notifications non lues
$count = count_unread_notifications($user_id);

// Renvoyer le résultat
echo json_encode([
    'success' => true,
    'count' => $count
]);