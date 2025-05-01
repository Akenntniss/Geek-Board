<?php
require_once '../config/db.php';
require_once '../functions.php';

header('Content-Type: application/json');

// Définir DEBUG si non défini
if (!defined('DEBUG')) {
    define('DEBUG', false);
}

// Vérifier si l'ID du client est fourni
if (!isset($_POST['client_id']) || empty($_POST['client_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID client non fourni'
    ]);
    exit;
}

$client_id = intval($_POST['client_id']);

try {
    // Préparer la requête pour récupérer les réparations du client
    $stmt = $pdo->prepare("
        SELECT r.id, r.date_arrivee, r.type_appareil, r.marque, r.modele, r.numero_serie, r.description_probleme, r.statut
        FROM reparations r
        WHERE r.client_id = :client_id
        ORDER BY r.date_arrivee DESC
    ");
    
    $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reparations' => $reparations
    ]);
    
} catch (PDOException $e) {
    error_log('Erreur SQL: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des réparations',
        'error' => DEBUG ? $e->getMessage() : null
    ]);
}
?> 