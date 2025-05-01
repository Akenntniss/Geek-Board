<?php
require_once '../config/session_config.php';
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
$response = [
    'logged_in' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null
];

echo json_encode($response); 