<?php
/**
 * API - Récupérer les utilisateurs en train de taper
 * 
 * Cette API permet de récupérer la liste des utilisateurs actuellement
 * en train de taper un message dans une conversation donnée.
 */

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier les paramètres
if (!isset($_GET['conversation_id']) || !is_numeric($_GET['conversation_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de conversation manquant ou invalide']);
    exit;
}

$conversation_id = (int)$_GET['conversation_id'];

// Inclure les fonctions
require_once '../includes/functions.php';

// Vérifier l'accès à la conversation
if (!user_has_conversation_access($_SESSION['user_id'], $conversation_id)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès refusé à cette conversation']);
    exit;
}

try {
    // Récupérer les utilisateurs en train de taper
    $typing_users = get_typing_users($conversation_id, $_SESSION['user_id']);
    
    // Formater les résultats
    $result = [];
    foreach ($typing_users as $user) {
        $result[$user['user_id']] = [
            'id' => $user['user_id'],
            'name' => $user['full_name'],
            'timestamp' => $user['timestamp']
        ];
    }
    
    // Renvoyer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'typing_users' => $result,
        'count' => count($result)
    ]);
    
} catch (PDOException $e) {
    log_error('Erreur lors de la récupération des statuts de frappe', $e->getMessage());
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des statuts de frappe']);
    exit;
} 