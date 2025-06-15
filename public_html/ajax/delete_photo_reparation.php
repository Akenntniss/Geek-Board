<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

// Vérifier les données requises
if (!isset($data['reparation_id']) || !isset($data['photo_path'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$reparation_id = (int)$data['reparation_id'];
$photo_path = $data['photo_path'];

// Charger la configuration de la base de données
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Vérifier si la réparation existe
    $check_stmt = $shop_pdo->prepare("SELECT photos FROM reparations WHERE id = ?");
    $check_stmt->execute([$reparation_id]);
    $reparation = $check_stmt->fetch();
    
    if (!$reparation) {
        throw new Exception('Réparation non trouvée');
    }

    // Récupérer la liste des photos
    $photos = json_decode($reparation['photos'] ?? '[]', true);
    
    // Retirer la photo du tableau
    $photos = array_filter($photos, function($p) use ($photo_path) {
        return $p !== $photo_path;
    });

    // Supprimer le fichier physique
    $file_path = __DIR__ . '/../' . $photo_path;
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Mettre à jour la base de données
    $update_stmt = $shop_pdo->prepare("UPDATE reparations SET photos = ? WHERE id = ?");
    $update_stmt->execute([json_encode(array_values($photos)), $reparation_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Photo supprimée avec succès'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}