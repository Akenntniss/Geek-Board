<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si la requête est une requête AJAX POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si le paramètre pwa_mode est présent
    if (isset($_POST['pwa_mode'])) {
        // Définir la variable de session en fonction de la valeur reçue
        $_SESSION['pwa_mode'] = ($_POST['pwa_mode'] === 'true');
        
        // Répondre avec un statut de succès
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'pwa_mode' => $_SESSION['pwa_mode']]);
        exit;
    }
}

// Si ce n'est pas une requête POST valide, retourner une erreur
header('Content-Type: application/json');
header('HTTP/1.1 400 Bad Request');
echo json_encode(['success' => false, 'message' => 'Requête invalide']); 