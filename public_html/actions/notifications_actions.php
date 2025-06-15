<?php
require_once __DIR__.'/../config/database.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Non authentifié']));
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    // Obtenir la connexion à la base de données de la boutique
    $shop_pdo = getShopDBConnection();

    switch($action) {
        case 'get':
            $stmt = $shop_pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($notifications);
            break;

        case 'count':
            $stmt = $shop_pdo->prepare("SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND status IN ('new', 'pending')");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($result);
            break;

        case 'mark_all_read':
            $stmt = $shop_pdo->prepare("UPDATE notifications SET status = 'read', read_at = NOW() WHERE user_id = ? AND status != 'read'");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non valide']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}