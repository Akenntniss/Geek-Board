<?php
// Test de connexion pour vérifier que le système fonctionne
session_start();

try {
    // Test avec l'ID du magasin 63
    $shop_id = 63;
    
    echo "=== Test de connexion GeekBoard ===\n";
    echo "Shop ID: $shop_id\n";
    
    // Inclure la configuration de base de données
    require_once __DIR__ . '/config/database.php';
    
    // Tester la nouvelle fonction
    $pdo = getShopDBConnectionById($shop_id);
    
    if (!$pdo) {
        echo "❌ ERREUR: Impossible d'obtenir la connexion\n";
        exit;
    }
    
    echo "✅ Connexion réussie !\n";
    
    // Vérifier la base de données connectée
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Base de données: " . $result['db_name'] . "\n";
    
    // Tester une requête sur les réparations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reparations");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Nombre de réparations: " . $count['count'] . "\n";
    
    // Tester une requête spécifique
    $stmt = $pdo->prepare("SELECT id, statut FROM reparations WHERE id = ? LIMIT 1");
    $stmt->execute([985]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($repair) {
        echo "Test réparation ID 985: statut = " . $repair['statut'] . "\n";
    } else {
        echo "Réparation ID 985 non trouvée\n";
    }
    
    echo "✅ Tous les tests passés !\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?> 