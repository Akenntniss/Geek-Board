<?php
// Désactiver l'affichage des erreurs PHP
error_reporting(0);
ini_set('display_errors', 0);

// S'assurer que nous envoyons du JSON
header('Content-Type: application/json');

require_once('../config/database.php');

// Vérifier la méthode de la requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée'
    ]);
    exit;
}

// Vérifier si les données nécessaires sont fournies
if (!isset($_POST['repair_id']) || !isset($_POST['photo_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Paramètres manquants'
    ]);
    exit;
}

$repair_id = (int)$_POST['repair_id'];
$photo_id = (int)$_POST['photo_id'];

try {
    // Récupérer les informations de la photo pour pouvoir supprimer le fichier
    $stmt = $pdo->prepare("SELECT url FROM photos_reparation WHERE id = ? AND reparation_id = ?");
    $stmt->execute([$photo_id, $repair_id]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$photo) {
        echo json_encode([
            'success' => false,
            'error' => 'Photo non trouvée'
        ]);
        exit;
    }
    
    // Supprimer le fichier physique s'il existe
    $file_path = '../' . $photo['url'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Supprimer l'entrée de la base de données
    $stmt = $pdo->prepare("DELETE FROM photos_reparation WHERE id = ? AND reparation_id = ?");
    $stmt->execute([$photo_id, $repair_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Photo supprimée avec succès'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur: ' . $e->getMessage()
    ]);
    exit;
} 