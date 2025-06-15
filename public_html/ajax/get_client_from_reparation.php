<?php
require_once '../config/database.php';
require_once '../functions.php';

header('Content-Type: application/json');

// Définir DEBUG si non défini
if (!defined('DEBUG')) {
    define('DEBUG', false);
}

// Vérifier si l'ID de la réparation est fourni
if (!isset($_POST['reparation_id']) || empty($_POST['reparation_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID réparation non fourni'
    ]);
    exit;
}

$reparation_id = intval($_POST['reparation_id']);

try {
    // Préparer la requête pour récupérer le client associé à la réparation
    $stmt = $shop_pdo->prepare("
        SELECT c.id, c.nom, c.prenom, c.telephone, c.email
        FROM clients c
        JOIN reparations r ON c.id = r.client_id
        WHERE r.id = :reparation_id
    ");
    
    $stmt->bindParam(':reparation_id', $reparation_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($client) {
        echo json_encode([
            'success' => true,
            'client' => $client
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Client non trouvé pour cette réparation'
        ]);
    }
    
} catch (PDOException $e) {
    error_log('Erreur SQL: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération du client',
        'error' => DEBUG ? $e->getMessage() : null
    ]);
}
?> 