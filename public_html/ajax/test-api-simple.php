<?php
// Test API simple qui reproduit le test réussi
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Méthode non autorisée']);
    exit;
}

$terme = isset($_POST['terme']) ? trim($_POST['terme']) : '';

if (empty($terme)) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Terme manquant']);
    exit;
}

try {
    // Connexion à la base principale (EXACTEMENT comme le test)
    $main_pdo = new PDO(
        'mysql:host=localhost;port=3306;dbname=geekboard_general;charset=utf8mb4',
        'root',
        'Mamanmaman01#',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Configuration shop 63 (EXACTEMENT comme le test)
    $stmt = $main_pdo->prepare("SELECT db_host, db_port, db_name, db_user, db_pass FROM shops WHERE id = 63");
    $stmt->execute();
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        http_response_code(404);
        echo json_encode(['error' => true, 'message' => 'Shop introuvable']);
        exit;
    }
    
    // Connexion à la base du shop (EXACTEMENT comme le test)
    $dsn = "mysql:host=" . $shop['db_host'] . ";port=" . $shop['db_port'] . ";dbname=" . $shop['db_name'] . ";charset=utf8mb4";
    $shop_pdo = new PDO(
        $dsn,
        $shop['db_user'],
        $shop['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Requête de recherche (EXACTEMENT comme le test)
    $terme_like = '%' . $terme . '%';
    $sql = "SELECT id, nom, prenom FROM clients WHERE nom LIKE ? OR prenom LIKE ? OR CONCAT(nom, ' ', prenom) LIKE ?";
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$terme_like, $terme_like, $terme_like]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Réponse
    echo json_encode([
        'clients' => $clients,
        'total' => count($clients),
        'terme' => $terme,
        'shop_id' => 63,
        'shop_db' => $shop['db_name']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 