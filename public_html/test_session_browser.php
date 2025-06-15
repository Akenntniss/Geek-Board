<?php
session_start();
require_once 'config/database.php';

echo "<h2>🔍 DEBUG SESSION NAVIGATEUR</h2>";
echo "<p><strong>Session shop_id:</strong> " . ($_SESSION['shop_id'] ?? 'non défini') . "</p>";
echo "<p><strong>Session shop_name:</strong> " . ($_SESSION['shop_name'] ?? 'non défini') . "</p>";

$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query('SELECT DATABASE() as current_db');
$result = $stmt->fetch();
echo "<p><strong>Base connectée:</strong> " . $result['current_db'] . "</p>";

// Test recherche Diana
$stmt2 = $shop_pdo->prepare('SELECT COUNT(*) as total FROM clients WHERE nom LIKE ? OR prenom LIKE ?');
$stmt2->execute(['%diana%', '%diana%']);
$result2 = $stmt2->fetch();
echo "<p><strong>Clients trouvés avec Diana:</strong> " . $result2['total'] . "</p>";

// Liste des premiers clients pour debug
$stmt3 = $shop_pdo->prepare('SELECT nom, prenom FROM clients LIMIT 10');
$stmt3->execute();
echo "<h3>Premiers clients:</h3><ul>";
while($client = $stmt3->fetch()) {
    echo "<li>" . htmlspecialchars($client['prenom'] . ' ' . $client['nom']) . "</li>";
}
echo "</ul>";
?> 