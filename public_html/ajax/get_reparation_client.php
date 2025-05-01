<?php
// Inclure la configuration et la connexion à la base de données
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action'
    ]);
    exit;
}

// Vérifier si l'ID de la réparation est fourni
if (!isset($_GET['reparation_id']) || empty($_GET['reparation_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID réparation non spécifié'
    ]);
    exit;
}

$reparation_id = (int)$_GET['reparation_id'];

try {
    // Récupérer les informations du client associé à la réparation
    $stmt = $pdo->prepare("
        SELECT c.id, c.nom, c.prenom, c.telephone, c.email
        FROM clients c
        JOIN reparations r ON c.id = r.client_id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Client non trouvé pour cette réparation'
        ]);
        exit;
    }
    
    // Renvoyer les résultats au format JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'client' => $client
    ]);
    
} catch (PDOException $e) {
    // Gérer les erreurs de base de données
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des informations du client: ' . $e->getMessage()
    ]);
} 