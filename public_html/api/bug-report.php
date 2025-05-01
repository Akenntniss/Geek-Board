<?php
/**
 * API Endpoint pour recevoir et traiter les rapports de bugs
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/database.php';

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Vérification des données reçues
if (!isset($input['description']) || empty(trim($input['description']))) {
    echo json_encode(['success' => false, 'message' => 'Description obligatoire']);
    exit;
}

// Nettoyage et validation des données
$description = htmlspecialchars(trim($input['description']));
$page_url = isset($input['page_url']) ? htmlspecialchars(trim($input['page_url'])) : '';
$user_agent = isset($input['user_agent']) ? htmlspecialchars(trim($input['user_agent'])) : '';

try {
    // Connexion à la base de données
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Préparation de la requête
    $query = "INSERT INTO bug_reports (description, page_url, user_agent) VALUES (:description, :page_url, :user_agent)";
    $stmt = $db->prepare($query);
    
    // Exécution de la requête
    $result = $stmt->execute([
        ':description' => $description,
        ':page_url' => $page_url,
        ':user_agent' => $user_agent
    ]);
    
    // Vérification du résultat
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Rapport de bug enregistré avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du rapport']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur lors de la soumission du bug : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de l\'enregistrement du rapport']);
} 