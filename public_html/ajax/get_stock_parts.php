<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Récupérer les pièces disponibles dans le stock (quantité > 0)
    $sql = "SELECT id, nom, quantite, reference FROM pieces_detachees WHERE quantite > 0 ORDER BY nom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($pieces);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des pièces: ' . $e->getMessage()]);
} 