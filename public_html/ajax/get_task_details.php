<?php
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'ID de la tâche est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de tâche invalide'
    ]);
    exit;
}

$task_id = intval($_GET['id']);

try {
    // Préparer et exécuter la requête
    $stmt = $shop_pdo->prepare("
        SELECT description, statut as status
        FROM taches 
        WHERE id = ?
    ");
    $stmt->execute([$task_id]);
    
    // Récupérer les résultats
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        echo json_encode([
            'success' => true,
            'description' => $task['description'],
            'status' => $task['status']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Tâche non trouvée'
        ]);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des détails de la tâche: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la récupération des détails de la tâche'
    ]);
} 