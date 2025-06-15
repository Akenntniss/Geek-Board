<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Afficher les informations de session
    $session_info = [
        'session_id' => session_id(),
        'session_data' => $_SESSION ?? [],
        'shop_id' => $_SESSION['shop_id'] ?? 'NON DÉFINI',
        'get_params' => $_GET,
        'post_params' => $_POST
    ];
    
    // Tester la connexion à la base de données
    $config_path = realpath(__DIR__ . '/config/database.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable: ' . $config_path);
    }
    
    require_once $config_path;
    
    // Tester getShopDBConnection
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('getShopDBConnection() a retourné null');
    }
    
    // Vérifier quelle base de données est connectée
    $db_stmt = $pdo->query("SELECT DATABASE() as current_db");
    $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Tester une requête simple
    $test_stmt = $pdo->query("SHOW TABLES LIKE 'clients'");
    $has_clients_table = $test_stmt->rowCount() > 0;
    
    $test_stmt = $pdo->query("SHOW TABLES LIKE 'reparations'");
    $has_reparations_table = $test_stmt->rowCount() > 0;
    
    // Compter les enregistrements
    $client_count = 0;
    $reparation_count = 0;
    
    if ($has_clients_table) {
        $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
        $result = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $client_count = $result['count'];
    }
    
    if ($has_reparations_table) {
        $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM reparations");
        $result = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $reparation_count = $result['count'];
    }
    
    echo json_encode([
        'success' => true,
        'session_info' => $session_info,
        'database_info' => [
            'current_database' => $db_info['current_db'] ?? 'Inconnue',
            'tables' => [
                'clients_exists' => $has_clients_table,
                'reparations_exists' => $has_reparations_table
            ],
            'counts' => [
                'clients' => $client_count,
                'reparations' => $reparation_count
            ]
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'session_info' => $session_info ?? null,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?> 