<?php
// API de Recherche Universelle - Version Finale
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
    
    // Recherche clients
    $terme_like = '%' . $terme . '%';
    $clients = [];
    $reparations = [];
    $commandes = [];
    
    // CLIENTS
    $sql_clients = "SELECT id, nom, prenom, telephone, email FROM clients WHERE nom LIKE ? OR prenom LIKE ? OR CONCAT(nom, ' ', prenom) LIKE ? LIMIT 50";
    $stmt = $shop_pdo->prepare($sql_clients);
    $stmt->execute([$terme_like, $terme_like, $terme_like]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // RÉPARATIONS
    $sql_reparations = "
        SELECT r.id, r.type_appareil, r.modele, r.probleme_declare, r.statut, 
               c.nom as client_nom, c.prenom as client_prenom
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.type_appareil LIKE ? OR r.modele LIKE ? OR c.nom LIKE ? OR c.prenom LIKE ? 
        LIMIT 50";
    $stmt = $shop_pdo->prepare($sql_reparations);
    $stmt->execute([$terme_like, $terme_like, $terme_like, $terme_like]);
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // COMMANDES
    if ($shop_pdo->query("SHOW TABLES LIKE 'commandes_pieces'")->rowCount() > 0) {
        $sql_commandes = "
            SELECT cp.id, cp.nom_piece, cp.reference, cp.fournisseur, cp.statut,
                   c.nom as client_nom, c.prenom as client_prenom
            FROM commandes_pieces cp
            LEFT JOIN reparations r ON cp.reparation_id = r.id
            LEFT JOIN clients c ON r.client_id = c.id
            WHERE cp.nom_piece LIKE ? OR cp.reference LIKE ? OR c.nom LIKE ? OR c.prenom LIKE ?
            LIMIT 50";
        $stmt = $shop_pdo->prepare($sql_commandes);
        $stmt->execute([$terme_like, $terme_like, $terme_like, $terme_like]);
        $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Réponse
    echo json_encode([
        'clients' => $clients,
        'reparations' => $reparations,
        'commandes' => $commandes,
        'total' => count($clients) + count($reparations) + count($commandes),
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