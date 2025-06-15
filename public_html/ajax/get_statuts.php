<?php
// 🔧 Configuration de session et sécurité
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 🗄️ Utiliser la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('❌ Erreur de connexion à la base de données du magasin');
    }
    
    // 📊 Récupérer tous les statuts actifs de la table statuts
    $sql = "SELECT id, nom, code, ordre 
            FROM statuts 
            WHERE est_actif = 1 
            ORDER BY ordre ASC, nom ASC";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute();
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 📝 Log pour debug
    error_log("✅ Récupération des statuts pour shop_id: " . ($_SESSION['shop_id'] ?? 'unknown') . " - " . count($statuts) . " statuts trouvés");
    
    // 🚀 Retourner les statuts
    echo json_encode([
        'success' => true,
        'data' => $statuts,
        'count' => count($statuts)
    ]);

} catch (PDOException $e) {
    error_log("❌ Erreur PDO dans get_statuts.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données',
        'error' => $e->getMessage()
    ]);

} catch (Exception $e) {
    error_log("❌ Erreur générale dans get_statuts.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des statuts',
        'error' => $e->getMessage()
    ]);
}
?> 