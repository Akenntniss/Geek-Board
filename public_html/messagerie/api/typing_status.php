<?php
/**
 * API - Gestion des statuts de frappe
 * 
 * Cette API permet de notifier les autres utilisateurs qu'un utilisateur
 * est en train d'écrire un message dans une conversation.
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
if (!isset($input['conversation_id']) || !is_numeric($input['conversation_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID de conversation manquant ou invalide']);
    exit;
}

if (!isset($input['is_typing'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Statut de frappe manquant']);
    exit;
}

$conversation_id = (int)$input['conversation_id'];
$is_typing = (bool)$input['is_typing'];

// Inclure les fonctions
require_once '../includes/functions.php';

// Vérifier l'accès à la conversation
if (!user_has_conversation_access($_SESSION['user_id'], $conversation_id)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Accès refusé à cette conversation']);
    exit;
}

// Récupérer les informations de l'utilisateur
$user_info = get_user_info($_SESSION['user_id']);

if (!$user_info) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Impossible de récupérer les informations de l\'utilisateur']);
    exit;
}

try {
    global $shop_pdo;
    
    // Si l'utilisateur est en train d'écrire, stocker l'état
    if ($is_typing) {
        // Utiliser la table des statuts de frappe temporaires
        $query = "
            INSERT INTO typing_status (user_id, conversation_id, timestamp)
            VALUES (:user_id, :conversation_id, NOW())
            ON DUPLICATE KEY UPDATE timestamp = NOW()
        ";
        
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':conversation_id' => $conversation_id
        ]);
    } else {
        // Supprimer l'entrée si l'utilisateur a arrêté d'écrire
        $query = "
            DELETE FROM typing_status
            WHERE user_id = :user_id AND conversation_id = :conversation_id
        ";
        
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':conversation_id' => $conversation_id
        ]);
    }
    
    // Renvoyer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $is_typing ? 'Statut de frappe activé' : 'Statut de frappe désactivé'
    ]);
    
} catch (PDOException $e) {
    log_error('Erreur lors de la mise à jour du statut de frappe', $e->getMessage());
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du statut de frappe']);
    exit;
} 