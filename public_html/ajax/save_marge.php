<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    exit('Non autorisé');
}

// Vérifier les données requises
if (!isset($_POST['categorie']) || !isset($_POST['description']) || !isset($_POST['prix_estime']) || !isset($_POST['marge_recommandee'])) {
    http_response_code(400);
    exit('Données manquantes');
}

try {
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Mise à jour
        $stmt = $pdo->prepare("UPDATE marges_estimees SET categorie = ?, description = ?, prix_estime = ?, marge_recommandee = ? WHERE id = ?");
        $stmt->execute([
            $_POST['categorie'],
            $_POST['description'],
            $_POST['prix_estime'],
            $_POST['marge_recommandee'],
            $_POST['id']
        ]);
    } else {
        // Création
        $stmt = $pdo->prepare("INSERT INTO marges_estimees (categorie, description, prix_estime, marge_recommandee) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_POST['categorie'],
            $_POST['description'],
            $_POST['prix_estime'],
            $_POST['marge_recommandee']
        ]);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 