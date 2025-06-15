<?php
/**
 * Script de diagnostic pour l'API get_fournisseurs
 */

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de session
require_once '../config/session_config.php';
require_once '../config/database.php';

$diagnostic = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_status' => session_status(),
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? [],
    'user_connected' => isset($_SESSION['user_id']),
    'shop_defined' => isset($_SESSION['shop_id']),
    'main_db_connection' => false,
    'shop_db_connection' => false,
    'fournisseurs_count' => 0,
    'errors' => []
];

// Test connexion base principale
try {
    $pdo_main = getMainDBConnection();
    $diagnostic['main_db_connection'] = true;
    
    // Vérifier le shop_id si défini
    if (isset($_SESSION['shop_id'])) {
        $stmt = $pdo_main->prepare("SELECT id, name FROM shops WHERE id = ? AND active = 1");
        $stmt->execute([$_SESSION['shop_id']]);
        $shop = $stmt->fetch();
        
        if ($shop) {
            $diagnostic['shop_valid'] = true;
            $diagnostic['shop_info'] = $shop;
        } else {
            $diagnostic['shop_valid'] = false;
            $diagnostic['errors'][] = "Shop ID {$_SESSION['shop_id']} non trouvé ou inactif";
        }
    }
} catch (Exception $e) {
    $diagnostic['errors'][] = "Erreur connexion base principale: " . $e->getMessage();
}

// Test connexion base magasin
if (isset($_SESSION['shop_id'])) {
    try {
        $shop_pdo = getShopDBConnection();
        $diagnostic['shop_db_connection'] = true;
        
        // Compter les fournisseurs
        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM fournisseurs");
        $result = $stmt->fetch();
        $diagnostic['fournisseurs_count'] = $result['count'];
        
        // Lister quelques fournisseurs
        $stmt = $shop_pdo->query("SELECT id, nom FROM fournisseurs LIMIT 5");
        $fournisseurs = $stmt->fetchAll();
        $diagnostic['sample_fournisseurs'] = $fournisseurs;
        
    } catch (Exception $e) {
        $diagnostic['errors'][] = "Erreur connexion base magasin: " . $e->getMessage();
    }
} else {
    $diagnostic['errors'][] = "shop_id non défini en session";
}

// Test de l'API get_fournisseurs en interne
try {
    if (isset($_SESSION['user_id']) && isset($_SESSION['shop_id'])) {
        // Simuler l'appel à get_fournisseurs
        $shop_pdo = getShopDBConnection();
        $stmt = $shop_pdo->prepare("SELECT id, nom FROM fournisseurs ORDER BY nom");
        $stmt->execute();
        $fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $diagnostic['api_simulation'] = [
            'success' => true,
            'fournisseurs' => $fournisseurs,
            'count' => count($fournisseurs)
        ];
    } else {
        $diagnostic['api_simulation'] = [
            'success' => false,
            'message' => 'Session invalide pour simulation API'
        ];
    }
} catch (Exception $e) {
    $diagnostic['api_simulation'] = [
        'success' => false,
        'message' => 'Erreur simulation API: ' . $e->getMessage()
    ];
}

echo json_encode($diagnostic, JSON_PRETTY_PRINT);
?> 