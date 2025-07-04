<?php
// API de Recherche Universelle - Version Simple
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
    // Connexion à la base principale
    $main_pdo = new PDO(
        'mysql:host=localhost;port=3306;dbname=geekboard_general;charset=utf8mb4',
        'root',
        'Mamanmaman01#',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Détecter le sous-domaine depuis l'URL
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $subdomain = '';
    
    if (preg_match('/^([^.]+)\.mdgeek\.top$/', $host, $matches)) {
        $subdomain = $matches[1];
    }
    
    // Si pas de sous-domaine détecté, fallback sur mkmkmk pour les tests
    if (empty($subdomain)) {
        $subdomain = 'mkmkmk';
    }
    
    // Configuration du shop par sous-domaine
    $stmt = $main_pdo->prepare("SELECT id, db_host, db_port, db_name, db_user, db_pass FROM shops WHERE subdomain = ? AND active = 1");
    $stmt->execute([$subdomain]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        http_response_code(404);
        echo json_encode(['error' => true, 'message' => "Shop introuvable pour $subdomain"]);
        exit;
    }
    
    // Connexion à la base du shop
    $dsn = "mysql:host=" . $shop['db_host'] . ";port=" . $shop['db_port'] . ";dbname=" . $shop['db_name'] . ";charset=utf8mb4";
    $shop_pdo = new PDO(
        $dsn,
        $shop['db_user'],
        $shop['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Recherche uniquement dans les clients pour l'instant
    $terme_like = '%' . $terme . '%';
    
    // CLIENTS
    $sql_clients = "SELECT id, nom, prenom, telephone, email FROM clients WHERE nom LIKE ? OR prenom LIKE ? OR CONCAT(nom, ' ', prenom) LIKE ? LIMIT 50";
    $stmt = $shop_pdo->prepare($sql_clients);
    $stmt->execute([$terme_like, $terme_like, $terme_like]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Réponse
    echo json_encode([
        'clients' => $clients,
        'reparations' => [],
        'commandes' => [],
        'total' => count($clients),
        'terme' => $terme,
        'shop_id' => (int)$shop['id'],
        'shop_db' => $shop['db_name'],
        'subdomain' => $subdomain
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