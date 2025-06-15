<?php
// Script de debug pour tester add_multiple_commandes.php
header('Content-Type: text/plain');

echo "=== DEBUG COMMANDES MULTIPLES ===\n\n";

// Vérifier les fichiers requis
echo "1. Vérification des fichiers:\n";
echo "- session_config.php: " . (file_exists(__DIR__ . '/../config/session_config.php') ? 'EXISTE' : 'MANQUANT') . "\n";
echo "- database.php: " . (file_exists(__DIR__ . '/../config/database.php') ? 'EXISTE' : 'MANQUANT') . "\n";
echo "- add_multiple_commandes.php: " . (file_exists(__DIR__ . '/add_multiple_commandes.php') ? 'EXISTE' : 'MANQUANT') . "\n\n";

// Tester l'inclusion des fichiers
echo "2. Test d'inclusion des fichiers:\n";
try {
    require_once __DIR__ . '/../config/session_config.php';
    echo "- session_config.php: OK\n";
} catch (Exception $e) {
    echo "- session_config.php: ERREUR - " . $e->getMessage() . "\n";
}

try {
    require_once __DIR__ . '/../config/database.php';
    echo "- database.php: OK\n";
} catch (Exception $e) {
    echo "- database.php: ERREUR - " . $e->getMessage() . "\n";
}

// Vérifier la session
echo "\n3. Vérification de la session:\n";
echo "- Session démarrée: " . (session_status() === PHP_SESSION_ACTIVE ? 'OUI' : 'NON') . "\n";
echo "- user_id défini: " . (isset($_SESSION['user_id']) ? 'OUI (' . $_SESSION['user_id'] . ')' : 'NON') . "\n";
echo "- shop_id défini: " . (isset($_SESSION['shop_id']) ? 'OUI (' . $_SESSION['shop_id'] . ')' : 'NON') . "\n";

// Tester la connexion à la base de données
echo "\n4. Test de connexion à la base de données:\n";
try {
    if (function_exists('getShopDBConnection')) {
        $shop_pdo = getShopDBConnection();
        echo "- Connexion shop: OK\n";
        
        // Tester une requête simple
        $stmt = $shop_pdo->query("SELECT 1");
        echo "- Requête test: OK\n";
    } else {
        echo "- Fonction getShopDBConnection: MANQUANTE\n";
    }
} catch (Exception $e) {
    echo "- Connexion shop: ERREUR - " . $e->getMessage() . "\n";
}

// Tester le décodage JSON
echo "\n5. Test de décodage JSON:\n";
$testData = [
    'client_id' => '514',
    'fournisseur_id' => '4',
    'statut' => 'en_attente',
    'pieces' => [
        [
            'nom_piece' => 'Test Pièce',
            'code_barre' => '123456789',
            'prix_estime' => '25.50',
            'quantite' => '1',
            'reparation_id' => ''
        ]
    ]
];

$jsonString = json_encode($testData);
$decoded = json_decode($jsonString, true);

echo "- Encodage JSON: " . ($jsonString ? 'OK' : 'ERREUR') . "\n";
echo "- Décodage JSON: " . ($decoded ? 'OK' : 'ERREUR') . "\n";
echo "- Données décodées: " . print_r($decoded, true) . "\n";

// Vérifier les tables de la base de données
echo "\n6. Vérification des tables:\n";
try {
    if (isset($shop_pdo)) {
        $tables = ['clients', 'fournisseurs', 'commandes_pieces'];
        foreach ($tables as $table) {
            $stmt = $shop_pdo->query("SHOW TABLES LIKE '$table'");
            echo "- Table $table: " . ($stmt->rowCount() > 0 ? 'EXISTE' : 'MANQUANTE') . "\n";
        }
    }
} catch (Exception $e) {
    echo "- Erreur vérification tables: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?> 