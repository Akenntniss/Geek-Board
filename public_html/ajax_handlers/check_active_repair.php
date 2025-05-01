<?php
// Inclusion des fichiers nécessaires
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

try {
    // Vérifier si l'utilisateur a déjà une réparation active
    $stmt = $pdo->prepare("SELECT techbusy, active_repair_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['techbusy'] == 1 && !empty($user['active_repair_id'])) {
        // L'utilisateur a une réparation active
        $response = [
            'success' => true,
            'has_active_repair' => true,
            'active_repair_id' => $user['active_repair_id']
        ];
    } else {
        // L'utilisateur n'a pas de réparation active
        $response = [
            'success' => true,
            'has_active_repair' => false
        ];
    }
} catch (PDOException $e) {
    // En cas d'erreur
    $response = [
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ];
}

// Renvoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response); 