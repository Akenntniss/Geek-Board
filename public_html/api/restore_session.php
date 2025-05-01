<?php
require_once '../config/session_config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false];

// Vérifier si un token est fourni
$token = $_POST['token'] ?? $_COOKIE['mdgeek_remember'] ?? null;

if ($token) {
    try {
        // Rechercher le token dans la base de données
        $stmt = $pdo->prepare('SELECT u.* FROM users u 
                              JOIN user_sessions s ON u.id = s.user_id 
                              WHERE s.token = ? AND s.expiry > NOW()');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Restaurer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Mettre à jour la date d'expiration du token
            $stmt = $pdo->prepare('UPDATE user_sessions SET expiry = DATE_ADD(NOW(), INTERVAL 30 DAY) 
                                  WHERE token = ?');
            $stmt->execute([$token]);
            
            $response['success'] = true;
            $response['user'] = [
                'id' => $user['id'],
                'username' => $user['username']
            ];
        }
    } catch (PDOException $e) {
        $response['error'] = 'Erreur lors de la restauration de la session';
    }
}

echo json_encode($response); 