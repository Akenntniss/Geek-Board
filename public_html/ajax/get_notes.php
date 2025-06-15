<?php
// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(0);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

try {
    require_once '../config/database.php';
    require_once '../includes/functions.php';

    // Vérifier si l'ID est fourni
    $reparation_id = isset($_GET['reparation_id']) ? (int)$_GET['reparation_id'] : 0;
    
    if ($reparation_id <= 0) {
        throw new Exception('ID de réparation invalide');
    }

    // Récupérer les notes
    $stmt = $shop_pdo->prepare("
        SELECT id, contenu, DATE_FORMAT(date_creation, '%d/%m/%Y %H:%i') as date_creation 
        FROM notes_reparation 
        WHERE reparation_id = ? 
        ORDER BY date_creation DESC
    ");
    
    if (!$stmt->execute([$reparation_id])) {
        throw new Exception('Erreur lors de la récupération des notes');
    }

    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'notes' => $notes
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 