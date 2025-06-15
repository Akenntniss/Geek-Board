<?php
/**
 * Script de diagnostic pour vérifier l'état de la session
 */

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de session
require_once '../config/session_config.php';

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Diagnostic complet de la session
$diagnostic = [
    'session_status' => session_status(),
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? [],
    'cookies' => $_COOKIE ?? [],
    'server_info' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'non défini',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'non défini',
        'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'non défini'
    ],
    'checks' => []
];

// Vérifications
$diagnostic['checks']['session_started'] = (session_status() === PHP_SESSION_ACTIVE);
$diagnostic['checks']['user_id_exists'] = isset($_SESSION['user_id']);
$diagnostic['checks']['shop_id_exists'] = isset($_SESSION['shop_id']);

// Si shop_id existe, vérifier sa validité
if (isset($_SESSION['shop_id'])) {
    try {
        $pdo_main = getMainDBConnection();
        $stmt = $pdo_main->prepare("SELECT id, name FROM shops WHERE id = ? AND active = 1");
        $stmt->execute([$_SESSION['shop_id']]);
        $shop = $stmt->fetch();
        
        $diagnostic['checks']['shop_id_valid'] = !!$shop;
        if ($shop) {
            $diagnostic['shop_info'] = $shop;
        }
    } catch (Exception $e) {
        $diagnostic['checks']['shop_id_valid'] = false;
        $diagnostic['shop_error'] = $e->getMessage();
    }
}

// Tester la connexion à la base de données du magasin si possible
if (isset($_SESSION['shop_id'])) {
    try {
        $shop_pdo = getShopDBConnection();
        $diagnostic['checks']['shop_db_connection'] = !!$shop_pdo;
        
        if ($shop_pdo) {
            // Tester une requête simple
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM fournisseurs");
            $result = $stmt->fetch();
            $diagnostic['fournisseurs_count'] = $result['count'];
        }
    } catch (Exception $e) {
        $diagnostic['checks']['shop_db_connection'] = false;
        $diagnostic['shop_db_error'] = $e->getMessage();
    }
}

echo json_encode($diagnostic, JSON_PRETTY_PRINT);
?> 