<?php
// Test de connexion simple
header('Content-Type: application/json');

try {
    // Test 1: Connexion à la base principale
    echo "=== TEST 1: Connexion base principale ===\n";
    $main_pdo = new PDO(
        'mysql:host=localhost;port=3306;dbname=geekboard_general;charset=utf8mb4',
        'root',
        'Mamanmaman01#',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connexion principale réussie\n";
    
    // Test 2: Récupération configuration shop 63
    echo "\n=== TEST 2: Configuration shop 63 ===\n";
    $stmt = $main_pdo->prepare("SELECT db_host, db_port, db_name, db_user, db_pass FROM shops WHERE id = 63");
    $stmt->execute();
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop) {
        echo "✅ Configuration trouvée:\n";
        echo "- Host: " . $shop['db_host'] . "\n";
        echo "- Port: " . $shop['db_port'] . "\n";
        echo "- DB: " . $shop['db_name'] . "\n";
        echo "- User: " . $shop['db_user'] . "\n";
        echo "- Pass: " . (substr($shop['db_pass'], 0, 3) . "...") . "\n";
    } else {
        echo "❌ Configuration introuvable\n";
        exit;
    }
    
    // Test 3: Connexion à la base du shop
    echo "\n=== TEST 3: Connexion base shop ===\n";
    $dsn = "mysql:host=" . $shop['db_host'] . ";port=" . $shop['db_port'] . ";dbname=" . $shop['db_name'] . ";charset=utf8mb4";
    $shop_pdo = new PDO(
        $dsn,
        $shop['db_user'],
        $shop['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connexion shop réussie\n";
    
    // Test 4: Requête de recherche
    echo "\n=== TEST 4: Requête de recherche ===\n";
    $terme = 'iu';
    $terme_like = '%' . $terme . '%';
    
    $sql = "SELECT id, nom, prenom FROM clients WHERE nom LIKE ? OR prenom LIKE ? OR CONCAT(nom, ' ', prenom) LIKE ?";
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute([$terme_like, $terme_like, $terme_like]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Requête exécutée\n";
    echo "Résultats trouvés: " . count($clients) . "\n";
    
    if (count($clients) > 0) {
        echo "Clients:\n";
        foreach ($clients as $client) {
            echo "- ID: " . $client['id'] . ", Nom: " . $client['nom'] . ", Prénom: " . $client['prenom'] . "\n";
        }
    }
    
    echo "\n=== TOUS LES TESTS RÉUSSIS ===\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
}
?> 