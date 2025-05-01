<?php
// Initialisation de la session (si ce n'est pas déjà fait)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
    exit;
}

// Vérification si l'ID de la tâche est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de tâche manquant']);
    exit;
}

// Inclusion des fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';

// Nettoyage de l'ID
$tache_id = (int)$_GET['id'];

try {
    // Récupération des détails de la tâche
    $stmt = $pdo->prepare("
        SELECT t.*, 
               u.full_name as employe_nom,
               c.full_name as createur_nom
        FROM taches t 
        LEFT JOIN users u ON t.employe_id = u.id 
        LEFT JOIN users c ON t.created_by = c.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$tache_id]);
    $tache = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tache) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Tâche non trouvée']);
        exit;
    }
    
    // Récupération des commentaires
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name as user_nom
        FROM commentaires_tache c
        JOIN users u ON c.user_id = u.id
        WHERE c.tache_id = ?
        ORDER BY c.date_creation DESC
    ");
    $stmt->execute([$tache_id]);
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer la réponse JSON
    $response = [
        'success' => true,
        'tache' => $tache,
        'commentaires' => $commentaires
    ];
    
    // Renvoyer la réponse
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    // En cas d'erreur, renvoyer un message d'erreur
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
    ]);
    
    // Journaliser l'erreur
    error_log('Erreur dans get_tache_details.php: ' . $e->getMessage());
}
?> 