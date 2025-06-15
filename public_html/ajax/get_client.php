<?php
require_once '../config/database.php';

// Vérifier si l'ID du client est fourni
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID du client non fourni'
    ]);
    exit;
}

$client_id = intval($_GET['id']);

try {
    // Récupérer les informations du client
    $stmt = $shop_pdo->prepare("
        SELECT id, nom, prenom, telephone, email
        FROM clients
        WHERE id = ?
    ");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($client) {
        echo json_encode([
            'success' => true,
            'client' => $client
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Client non trouvé'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des informations du client: ' . $e->getMessage()
    ]);
} 