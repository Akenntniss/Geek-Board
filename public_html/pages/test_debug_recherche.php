<?php
require_once 'config/database.php';

echo "=== DEBUG CONNEXIONS ===\n";

// Test connexion shop
$shop_pdo = getShopDBConnection();
if ($shop_pdo) {
    $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "BASE SHOP: " . ($result['db_name'] ?? 'Inconnue') . "\n";
} else {
    echo "ERREUR: Pas de connexion shop\n";
}

// Test connexion main
$main_pdo = getMainDBConnection();
if ($main_pdo) {
    $stmt = $main_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "BASE MAIN: " . ($result['db_name'] ?? 'Inconnue') . "\n";
} else {
    echo "ERREUR: Pas de connexion main\n";
}

// Vérifier si elles sont identiques
if ($shop_pdo === $main_pdo) {
    echo "⚠️  PROBLÈME: shop_pdo et main_pdo sont IDENTIQUES!\n";
} else {
    echo "✅ shop_pdo et main_pdo sont différents\n";
}

echo "SESSION shop_id: " . ($_SESSION['shop_id'] ?? 'non défini') . "\n";
?> 