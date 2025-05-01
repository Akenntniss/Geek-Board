<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si les données nécessaires sont présentes
if (empty($_POST['description'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Description requise']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Préparer la requête d'insertion
    $stmt = $pdo->prepare("
        INSERT INTO bug_reports (
            user_id,
            description,
            page_url,
            user_agent,
            date_creation
        ) VALUES (
            :user_id,
            :description,
            :page_url,
            :user_agent,
            NOW()
        )
    ");
    
    // Exécuter la requête avec les données
    $success = $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'description' => $_POST['description'],
        'page_url' => $_POST['page_url'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Bug signalé avec succès']);
    } else {
        throw new Exception('Erreur lors de l\'enregistrement du bug');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 