<?php
/**
 * 🔄 Script pour changer de magasin et accéder aux bonnes données
 */

require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';
require_once __DIR__ . '/config/database.php';

// Traitement du changement de magasin
if (isset($_POST['switch_to_shop'])) {
    $new_shop_id = (int)$_POST['switch_to_shop'];
    
    // Mettre à jour la session
    $_SESSION['shop_id'] = $new_shop_id;
    
    // Récupérer le nom du magasin
    $main_pdo = getMainDBConnection();
    $stmt = $main_pdo->prepare("SELECT name FROM shops WHERE id = ?");
    $stmt->execute([$new_shop_id]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop) {
        $_SESSION['shop_name'] = $shop['name'];
        echo "<script>alert('Magasin changé vers : {$shop['name']}'); window.location.href='pages/reparations.php';</script>";
    }
}

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Changement de Magasin</title></head><body>";
echo "<h1>🔄 Changement de Magasin</h1>";

echo "<h2>📍 Magasin Actuel</h2>";
echo "<p><strong>ID:</strong> " . ($_SESSION['shop_id'] ?? 'Non défini') . "</p>";
echo "<p><strong>Nom:</strong> " . ($_SESSION['shop_name'] ?? 'Non défini') . "</p>";

// Lister tous les magasins avec le nombre de réparations
$main_pdo = getMainDBConnection();
$shops_stmt = $main_pdo->query("SELECT id, name, subdomain, db_name, db_host, db_user, db_pass FROM shops WHERE active = 1");
$shops = $shops_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>🏪 Magasins Disponibles</h2>";
echo "<form method='POST'>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #e9e9e9;'>";
echo "<th>Sélectionner</th><th>ID</th><th>Nom</th><th>Base</th><th>Réparations</th><th>Action</th>";
echo "</tr>";

foreach ($shops as $shop) {
    $is_current = ($_SESSION['shop_id'] ?? null) == $shop['id'];
    $bg_color = $is_current ? 'background-color: #d4edda;' : '';
    
    echo "<tr style='{$bg_color}'>";
    
    // Radio button pour sélection
    echo "<td style='text-align: center;'>";
    if (!$is_current) {
        echo "<input type='radio' name='switch_to_shop' value='{$shop['id']}'>";
    } else {
        echo "👈 ACTUEL";
    }
    echo "</td>";
    
    echo "<td>{$shop['id']}</td>";
    echo "<td><strong>{$shop['name']}</strong></td>";
    echo "<td>{$shop['db_name']}</td>";
    
    // Compter les réparations dans cette base
    try {
        $dsn = "mysql:host={$shop['db_host']};dbname={$shop['db_name']};charset=utf8mb4";
        $test_pdo = new PDO($dsn, $shop['db_user'], $shop['db_pass']);
        $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $count_stmt = $test_pdo->query("SELECT COUNT(*) as total FROM reparations");
        $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $total_reparations = $count_result['total'];
        
        if ($total_reparations > 0) {
            echo "<td style='color: green; font-weight: bold;'>🎯 {$total_reparations}</td>";
        } else {
            echo "<td style='color: orange;'>⚠️ 0</td>";
        }
        
    } catch (Exception $e) {
        echo "<td style='color: red;'>❌ Erreur</td>";
    }
    
    // Actions spécifiques
    echo "<td>";
    if ($total_reparations > 0 && !$is_current) {
        echo "<strong style='color: green;'>👈 RECOMMANDÉ</strong>";
    } elseif ($is_current) {
        echo "<a href='pages/reparations.php' style='color: blue;'>📋 Voir réparations</a>";
    } else {
        echo "<em style='color: gray;'>Vide</em>";
    }
    echo "</td>";
    
    echo "</tr>";
}

echo "</table>";

echo "<div style='margin: 20px 0;'>";
echo "<button type='submit' style='background-color: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px;'>";
echo "🔄 Changer vers le magasin sélectionné";
echo "</button>";
echo "</div>";

echo "</form>";

echo "<h2>💡 Explications</h2>";
echo "<div style='background-color: #f0f8ff; padding: 15px; border-left: 4px solid #007cba;'>";
echo "<h4>🔍 Problème identifié :</h4>";
$current_shop_name = $_SESSION['shop_name'] ?? 'Magasin inconnu';
echo "<p>Vous êtes actuellement connecté au magasin <strong>'{$current_shop_name}'</strong> ";
echo "mais cette base ne contient aucune réparation.</p>";

echo "<h4>✅ Solution :</h4>";
echo "<p>Sélectionnez un magasin qui contient des réparations (marqué 🎯) et cliquez sur 'Changer'.</p>";
echo "<p>Vous serez automatiquement redirigé vers la page des réparations avec les bonnes données.</p>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='debug_reparations.php' style='color: blue;'>🔙 Retour diagnostic</a> | ";
echo "<a href='check_all_databases.php' style='color: purple;'>🔍 Voir toutes les bases</a> | ";
echo "<a href='pages/reparations.php' style='color: green;'>📋 Page réparations</a>";
echo "</div>";

echo "</body></html>";
?> 