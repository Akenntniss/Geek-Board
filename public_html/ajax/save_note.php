<?php
// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(0);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

try {
    require_once '../config/database.php';
    require_once '../includes/functions.php';

    // Vérifier si la requête est en POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupérer et valider les données
    $reparation_id = isset($_POST['reparation_id']) ? (int)$_POST['reparation_id'] : 0;
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';

    if ($reparation_id <= 0) {
        throw new Exception('ID de réparation invalide');
    }

    if (empty($note)) {
        throw new Exception('La note ne peut pas être vide');
    }

    // Insérer la note
    $stmt = $shop_pdo->prepare("
        INSERT INTO notes_reparation (reparation_id, contenu, date_creation) 
        VALUES (?, ?, NOW())
    ");
    
    if (!$stmt->execute([$reparation_id, $note])) {
        throw new Exception('Erreur lors de la sauvegarde de la note');
    }

    // Récupérer la note qui vient d'être insérée
    $stmt = $shop_pdo->prepare("
        SELECT id, contenu, DATE_FORMAT(date_creation, '%d/%m/%Y %H:%i') as date_creation 
        FROM notes_reparation 
        WHERE id = ?
    ");
    
    if (!$stmt->execute([$shop_pdo->lastInsertId()])) {
        throw new Exception('Erreur lors de la récupération de la note');
    }

    $newNote = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Note enregistrée avec succès',
        'note' => $newNote
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 