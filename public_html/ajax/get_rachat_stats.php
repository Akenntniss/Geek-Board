<?php
// Protection contre l'accès direct
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die("Accès interdit");
}

require_once __DIR__ . '/../config/database.php';

// Initialisation de la réponse
$response = [
    'success' => false,
    'total' => 0,
    'fonctionnels' => 0,
    'non_fonctionnels' => 0,
    'montant_total' => 0,
    'error' => '',
];

try {
    // Nombre total de rachats
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rachat_appareils");
    $response['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Nombre d'appareils fonctionnels
    $stmt = $pdo->query("SELECT COUNT(*) as fonctionnels FROM rachat_appareils WHERE fonctionnel = 1");
    $response['fonctionnels'] = $stmt->fetch(PDO::FETCH_ASSOC)['fonctionnels'];

    // Nombre d'appareils non fonctionnels
    $stmt = $pdo->query("SELECT COUNT(*) as non_fonctionnels FROM rachat_appareils WHERE fonctionnel = 0");
    $response['non_fonctionnels'] = $stmt->fetch(PDO::FETCH_ASSOC)['non_fonctionnels'];

    // Montant total des rachats
    $stmt = $pdo->query("SELECT SUM(prix) as montant_total FROM rachat_appareils");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['montant_total'] = round($result['montant_total'] ?? 0, 2);

    // Si tout va bien, on marque la réponse comme réussie
    $response['success'] = true;
} catch (PDOException $e) {
    $response['error'] = "Erreur lors de la récupération des statistiques: " . $e->getMessage();
    error_log("Erreur SQL (get_rachat_stats.php): " . $e->getMessage());
}

// Envoyer la réponse en JSON
header('Content-Type: application/json');
echo json_encode($response); 