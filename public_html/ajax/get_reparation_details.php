<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Vérifier que l'ID de la réparation est fourni
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de réparation manquant']);
    exit;
}

$id = intval($_POST['id']);

try {
    // Vérifier la connexion à la base de données
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Connexion à la base de données non disponible');
    }
    
    // Récupérer les détails de la réparation
    $sql = "
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.id = :id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        echo json_encode(['success' => false, 'message' => 'Réparation non trouvée']);
        exit;
    }
    
    // Formatage des données pour JSON
    $reparation = array_map(function($value) {
        // Éviter les problèmes d'encodage
        return is_string($value) ? $value : $value;
    }, $reparation);
    
    // Renvoyer les résultats
    echo json_encode([
        'success' => true,
        'reparation' => $reparation
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération de la réparation: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération de la réparation: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception lors de la récupération de la réparation: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?> 