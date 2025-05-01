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

// Vérifier si la connexion à la base de données est établie
if (!$pdo) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

// Vérifier si les données nécessaires sont fournies
if (!isset($_POST['repair_id']) || !isset($_POST['problem_description'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Paramètres manquants'
    ]);
    exit;
}

$repair_id = (int)$_POST['repair_id'];
$problem_description = trim($_POST['problem_description']);

try {
    // Mettre à jour la description du problème dans la base de données
    $stmt = $pdo->prepare("
        UPDATE reparations
        SET description_probleme = ?,
            date_modification = NOW()
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$problem_description, $repair_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Description du problème mise à jour avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la mise à jour de la description du problème'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la mise à jour de la description du problème: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur lors de la mise à jour'
    ]);
} catch (Exception $e) {
    error_log("Erreur inattendue: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur inattendue est survenue'
    ]);
} 