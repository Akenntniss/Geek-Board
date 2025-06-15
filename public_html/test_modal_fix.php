<?php
/**
 * Script de test pour vérifier la correction du modal de recherche universelle
 * Ce script simule une requête AJAX et vérifie la configuration de la database
 */

// Utiliser la même configuration que les fichiers AJAX corrigés
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';
require_once __DIR__ . '/config/database.php';

echo "<html><head><title>Test Correction Modal Database</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; background: #f0fff0; padding: 10px; border-left: 4px solid green; margin: 10px 0; }
.error { color: red; background: #fff0f0; padding: 10px; border-left: 4px solid red; margin: 10px 0; }
.info { color: blue; background: #f0f0ff; padding: 10px; border-left: 4px solid blue; margin: 10px 0; }
.debug { background: #f8f8f8; padding: 10px; border: 1px solid #ddd; margin: 10px 0; font-family: monospace; }
</style></head><body>";

echo "<h1>🔍 Test Correction Modal Database</h1>";
echo "<p>Vérification que la configuration AJAX hérite bien de la configuration du magasin principal.</p>";

// Informations de session
echo "<div class='info'>";
echo "<h3>📋 Informations de Session</h3>";
echo "<strong>Shop ID:</strong> " . ($_SESSION['shop_id'] ?? 'NON DÉFINI') . "<br>";
echo "<strong>Shop Name:</strong> " . ($_SESSION['shop_name'] ?? 'NON DÉFINI') . "<br>";
echo "<strong>Sous-domaine détecté:</strong> " . ($_SESSION['shop_subdomain'] ?? 'NON DÉFINI') . "<br>";
echo "<strong>URL actuelle:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Inconnue') . "<br>";
echo "</div>";

// Test de connexion à la database
try {
    $pdo = getShopDBConnection();
    
    if (!$pdo) {
        throw new Exception('Impossible d\'obtenir une connexion à la base de données');
    }
    
    // Vérifier quelle database est connectée
    $db_stmt = $pdo->query("SELECT DATABASE() as current_db");
    $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
    $dbname = $db_info['current_db'] ?? 'Inconnue';
    
    echo "<div class='success'>";
    echo "<h3>✅ Connexion Database Réussie</h3>";
    echo "<strong>Database connectée:</strong> " . $dbname . "<br>";
    echo "</div>";
    
    // Test de requête simple pour vérifier l'accès aux données
    try {
        $test_stmt = $pdo->query("SELECT COUNT(*) as count FROM clients LIMIT 1");
        $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
        $client_count = $test_result['count'] ?? 0;
        
        echo "<div class='success'>";
        echo "<h3>📊 Test Données Magasin</h3>";
        echo "<strong>Nombre de clients dans cette database:</strong> " . $client_count . "<br>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h3>❌ Erreur d'accès aux données</h3>";
        echo "<strong>Erreur:</strong> " . $e->getMessage() . "<br>";
        echo "</div>";
    }
    
    // Test similaire pour les réparations
    try {
        $rep_stmt = $pdo->query("SELECT COUNT(*) as count FROM reparations LIMIT 1");
        $rep_result = $rep_stmt->fetch(PDO::FETCH_ASSOC);
        $rep_count = $rep_result['count'] ?? 0;
        
        echo "<div class='success'>";
        echo "<strong>Nombre de réparations:</strong> " . $rep_count . "<br>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<strong>Erreur réparations:</strong> " . $e->getMessage() . "<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Erreur de Connexion Database</h3>";
    echo "<strong>Erreur:</strong> " . $e->getMessage() . "<br>";
    echo "</div>";
}

// Simulation de la recherche universelle
echo "<div class='info'>";
echo "<h3>🧪 Simulation Recherche Universelle</h3>";
echo "<p>Test de la même logique que ajax/recherche_universelle.php</p>";
echo "</div>";

if (isset($pdo)) {
    try {
        // Test de recherche avec un terme générique
        $searchTerm = "%test%";
        
        $clientsSQL = "SELECT COUNT(*) as count FROM clients WHERE nom LIKE ? OR prenom LIKE ? LIMIT 1";
        $clientsStmt = $pdo->prepare($clientsSQL);
        $clientsStmt->execute([$searchTerm, $searchTerm]);
        $clients_search = $clientsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='success'>";
        echo "<strong>Test recherche clients:</strong> " . ($clients_search['count'] ?? 0) . " résultats potentiels<br>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<strong>Erreur test recherche:</strong> " . $e->getMessage() . "<br>";
        echo "</div>";
    }
}

// Informations de debug
echo "<div class='debug'>";
echo "<h3>🐛 Informations de Debug</h3>";
echo "<pre>";
echo "Session complète:\n";
print_r($_SESSION);
echo "\nServeur:\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Non défini') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Non défini') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'Non défini') . "\n";
echo "</pre>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>📋 Conclusion</h3>";
echo "<p>Si vous voyez la bonne database connectée et que les compteurs de données correspondent à votre magasin, ";
echo "alors la correction du modal de recherche universelle fonctionne correctement.</p>";
echo "<p><strong>Prochaines étapes :</strong></p>";
echo "<ul>";
echo "<li>Tester le modal de recherche universelle sur la page d'accueil</li>";
echo "<li>Vérifier que les résultats correspondent bien au magasin actuel</li>";
echo "<li>Appliquer la même correction aux autres fichiers AJAX si nécessaire</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?> 