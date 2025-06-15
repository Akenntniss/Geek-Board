<?php
/**
 * Traitement AJAX pour mettre à jour le statut d'un rapport de bug
 */

session_start();
require_once '../config/database.php';

// On désactive temporairement la vérification d'authentification
// pour permettre à tous les utilisateurs d'utiliser cette fonctionnalité
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action']);
//     exit;
// }

header('Content-Type: application/json');

// Récupération des paramètres
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Validation des données
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de rapport invalide']);
    exit;
}

$valid_statuses = ['nouveau', 'en_cours', 'resolu', 'invalide'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
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

    // Mise à jour du statut
    $query = "UPDATE bug_reports SET status = :status";
    
    // Si le statut est "resolu", mettre à jour la date de résolution
    if ($status === 'resolu') {
        $query .= ", date_resolution = NOW()";
    } else {
        // Si le statut n'est plus "resolu", réinitialiser la date de résolution
        $query .= ", date_resolution = NULL";
    }
    
    $query .= " WHERE id = :id";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute([
        ':status' => $status,
        ':id' => $id
    ]);
    
    // Message adapté au nouveau statut
    $message = $status === 'resolu' ? 'Bug marqué comme résolu' : 'Bug marqué comme non résolu';
    
    // Réponse de succès
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (PDOException $e) {
    // Log de l'erreur côté serveur
    error_log("Erreur lors de la mise à jour du statut du bug: " . $e->getMessage());
    
    // Réponse d'erreur
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour du statut']);
} 