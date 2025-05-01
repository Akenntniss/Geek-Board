<?php
// Démarrer la session
session_start();

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Inclure les fichiers de configuration
require_once '../config/database.php';

// Récupérer les données POST
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Valider les données
if (empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez fournir une description du bug']);
    exit;
}

try {
    // Préparer la requête d'insertion
    $stmt = $conn->prepare("INSERT INTO bug_reports (user_id, description, status) VALUES (?, ?, 'nouveau')");
    $stmt->bind_param("is", $_SESSION['user_id'], $description);
    
    // Exécuter la requête
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Bug signalé avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du bug']);
    }
    
    // Fermer la requête
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}

// Fermer la connexion à la base de données
$conn->close();
?> 