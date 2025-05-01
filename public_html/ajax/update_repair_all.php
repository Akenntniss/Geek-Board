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

// Vérifier si l'ID de réparation est fourni
if (!isset($_POST['repair_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'ID de réparation manquant'
    ]);
    exit;
}

$repair_id = (int)$_POST['repair_id'];

// Récupérer les données à mettre à jour
$problem_description = isset($_POST['problem_description']) ? trim($_POST['problem_description']) : null;
$notes_content = isset($_POST['notes_content']) ? trim($_POST['notes_content']) : null;
$price = isset($_POST['price']) ? str_replace(',', '.', $_POST['price']) : null;
$price = $price !== null ? floatval($price) : null;

try {
    // Construire la requête SQL en fonction des champs fournis
    $updateFields = [];
    $params = [];
    
    if ($problem_description !== null) {
        $updateFields[] = "description_probleme = ?";
        $params[] = $problem_description;
    }
    
    if ($notes_content !== null) {
        $updateFields[] = "notes_techniques = ?";
        $params[] = $notes_content;
    }
    
    if ($price !== null) {
        $updateFields[] = "prix_reparation = ?";
        $params[] = $price;
    }
    
    // Ajouter toujours la date de modification
    $updateFields[] = "date_modification = NOW()";
    
    // Si aucun champ à mettre à jour
    if (empty($updateFields)) {
        echo json_encode([
            'success' => true,
            'message' => 'Aucune modification à effectuer'
        ]);
        exit;
    }
    
    // Préparer et exécuter la requête
    $sql = "UPDATE reparations SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $params[] = $repair_id;
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Réparation mise à jour avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la mise à jour de la réparation'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la mise à jour de la réparation: " . $e->getMessage());
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