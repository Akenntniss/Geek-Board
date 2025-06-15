<?php
/**
 * Traitement AJAX pour l'ajout d'un rapport de bug
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Récupération et nettoyage des données
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$page_url = isset($_POST['page_url']) ? trim($_POST['page_url']) : '';
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// Validation des données
if (empty($description)) {
    echo json_encode(['success' => false, 'message' => 'La description est requise']);
    exit;
}

try {
    // Connexion à la base de données
    $shop_pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Préparer l'ID utilisateur si disponible
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Requête d'insertion avec les noms de colonnes corrects
    $sql = "INSERT INTO bug_reports (user_id, description, page_url, user_agent, priorite, status, date_creation) 
            VALUES (:user_id, :description, :page_url, :user_agent, 'basse', 'nouveau', NOW())";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':description' => $description,
        ':page_url' => $page_url,
        ':user_agent' => $user_agent
    ]);
    
    // Réponse de succès
    echo json_encode(['success' => true, 'message' => 'Rapport de bug enregistré avec succès']);
    
} catch (PDOException $e) {
    // Log de l'erreur côté serveur
    error_log("Erreur lors de l'ajout d'un rapport de bug: " . $e->getMessage());
    
    // Réponse d'erreur
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de l\'enregistrement du rapport']);
} 