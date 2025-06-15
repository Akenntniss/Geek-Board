<?php
// Test rapide de la correction shop_id
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>🧪 Test de la Correction shop_id</h2>";

// Simuler les données JSON comme dans l'AJAX réel
$test_data = [
    'commande_id' => 190,
    'new_status' => 'commande',
    'shop_id' => 4  // Cannes Phones
];

echo "<h3>📋 Données de test :</h3>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

// Simuler le processus de correction
if (isset($test_data['shop_id'])) {
    $_SESSION['shop_id'] = intval($test_data['shop_id']);
    echo "<p>✅ shop_id FORCÉ depuis les données JSON: " . $_SESSION['shop_id'] . "</p>";
} elseif (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 1;
    echo "<p>⚠️ shop_id défini par défaut: " . $_SESSION['shop_id'] . "</p>";
}

echo "<h3>🔗 Test de connexion :</h3>";

require_once '../config/database.php';

try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Connexion échouée');
    }
    
    echo "<p>✅ Connexion établie pour shop_id: " . $_SESSION['shop_id'] . "</p>";
    
    // Vérifier quelle base de données nous utilisons
    $stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>🗄️ Base de données actuelle: <strong>" . $result['current_db'] . "</strong></p>";
    
    // Vérifier si la commande 190 existe
    $check_stmt = $shop_pdo->prepare("SELECT id, statut FROM commandes_pieces WHERE id = :id");
    $check_stmt->execute([':id' => $test_data['commande_id']]);
    $existing_commande = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_commande) {
        echo "<p>✅ Commande ID {$test_data['commande_id']} trouvée !</p>";
        echo "<p>📊 Statut actuel: <strong>{$existing_commande['statut']}</strong></p>";
        
        // Test de mise à jour
        $update_stmt = $shop_pdo->prepare("UPDATE commandes_pieces SET statut = :statut, date_modification = NOW() WHERE id = :id");
        $result = $update_stmt->execute([
            ':statut' => $test_data['new_status'],
            ':id' => $test_data['commande_id']
        ]);
        
        echo "<p>🔄 Test de mise à jour: " . ($result ? "✅ Succès" : "❌ Échec") . "</p>";
        echo "<p>📈 Lignes affectées: " . $update_stmt->rowCount() . "</p>";
        
        if ($update_stmt->rowCount() > 0) {
            echo "<p style='color: green; font-weight: bold;'>🎉 CORRECTION RÉUSSIE !</p>";
        } else {
            echo "<p style='color: orange; font-weight: bold;'>⚠️ Aucune ligne affectée (peut-être même statut)</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Commande ID {$test_data['commande_id']} NON trouvée dans cette base !</p>";
        echo "<p>🔍 Vérifiez que vous êtes connecté à la bonne base de données.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>💡 Instructions :</h3>";
echo "<p>1. Si la commande est trouvée et la mise à jour réussit, le problème est résolu !</p>";
echo "<p>2. Testez maintenant dans l'interface normale</p>";
echo "<p>3. Vérifiez les logs avec le script check_logs.php</p>";
?> 