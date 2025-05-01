<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Vérifier l'ID de la photo
    if (!isset($_POST['photo_id']) || !is_numeric($_POST['photo_id'])) {
        throw new Exception('ID de photo invalide');
    }

    $photo_id = (int)$_POST['photo_id'];

    // Récupérer les informations de la photo
    $stmt = $pdo->prepare("SELECT url FROM photos_reparation WHERE id = ?");
    $stmt->execute([$photo_id]);
    $photo = $stmt->fetch();

    if (!$photo) {
        throw new Exception('Photo non trouvée');
    }

    // Supprimer le fichier physique
    $file_path = '../' . $photo['url'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Supprimer l'entrée dans la base de données
    $stmt = $pdo->prepare("DELETE FROM photos_reparation WHERE id = ?");
    $stmt->execute([$photo_id]);

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 