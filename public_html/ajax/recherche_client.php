<?php
// Assurer que la sortie sera en JSON
header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier que le paramètre est présent
if (!isset($_GET['telephone']) || empty($_GET['telephone'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Numéro de téléphone requis']);
    exit;
}

// Nettoyer le numéro de téléphone
$telephone = clean_input($_GET['telephone']);

try {
    // Recherche par numéro de téléphone (recherche partielle)
    $stmt = $shop_pdo->prepare("
        SELECT id, nom, prenom, telephone, email, adresse 
        FROM clients 
        WHERE telephone LIKE ? 
        ORDER BY nom, prenom
        LIMIT 10
    ");
    $stmt->execute(['%' . $telephone . '%']);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les résultats en JSON
    echo json_encode($clients);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
    exit;
}
?> 