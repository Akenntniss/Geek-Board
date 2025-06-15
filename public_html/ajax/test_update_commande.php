<?php
// Test de diagnostic pour update_commande_status.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulation des données de session si nécessaire
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 1; // ou votre shop_id approprié
}

require_once '../config/database.php';

echo "<h2>🔍 Test de Diagnostic - Mise à jour Commande</h2>";

try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Connexion à la base de données échouée');
    }
    echo "✅ Connexion à la base de données établie<br>";

    // Afficher les commandes existantes
    echo "<h3>📋 Commandes existantes :</h3>";
    $stmt = $shop_pdo->prepare("SELECT id, reference, statut, date_creation FROM commandes_pieces ORDER BY id");
    $stmt->execute();
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Référence</th><th>Statut</th><th>Date création</th></tr>";
    foreach ($commandes as $cmd) {
        echo "<tr>";
        echo "<td>{$cmd['id']}</td>";
        echo "<td>{$cmd['reference']}</td>";
        echo "<td>{$cmd['statut']}</td>";
        echo "<td>{$cmd['date_creation']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test d'une mise à jour sur une commande existante
    if (!empty($commandes)) {
        $test_id = $commandes[0]['id'];
        $current_status = $commandes[0]['statut'];
        $new_status = ($current_status === 'en_attente') ? 'commande' : 'en_attente';
        
        echo "<h3>🧪 Test de mise à jour :</h3>";
        echo "Commande ID: $test_id<br>";
        echo "Statut actuel: $current_status<br>";
        echo "Nouveau statut: $new_status<br><br>";
        
        $stmt = $shop_pdo->prepare("UPDATE commandes_pieces SET statut = :statut, date_modification = NOW() WHERE id = :id");
        $result = $stmt->execute([
            ':statut' => $new_status,
            ':id' => $test_id
        ]);
        
        echo "Résultat de l'exécution: " . ($result ? "✅ Succès" : "❌ Échec") . "<br>";
        echo "Lignes affectées: " . $stmt->rowCount() . "<br>";
        
        if ($result && $stmt->rowCount() > 0) {
            echo "✅ <strong>Mise à jour réussie !</strong><br>";
        } else {
            echo "❌ <strong>Aucune ligne affectée - problème identifié</strong><br>";
        }
    }

    // Test avec un ID inexistant
    echo "<h3>🚫 Test avec ID inexistant :</h3>";
    $fake_id = 99999;
    $stmt = $shop_pdo->prepare("UPDATE commandes_pieces SET statut = 'en_attente', date_modification = NOW() WHERE id = :id");
    $result = $stmt->execute([':id' => $fake_id]);
    
    echo "Test avec ID $fake_id:<br>";
    echo "Résultat: " . ($result ? "✅ Succès" : "❌ Échec") . "<br>";
    echo "Lignes affectées: " . $stmt->rowCount() . "<br>";
    
    if ($stmt->rowCount() === 0) {
        echo "✅ <strong>Comportement normal - aucune ligne trouvée avec cet ID</strong><br>";
    }

} catch (Exception $e) {
    echo "❌ <strong>Erreur:</strong> " . $e->getMessage() . "<br>";
    echo "Trace: " . $e->getTraceAsString();
}

echo "<hr>";
echo "<h3>💡 Instructions :</h3>";
echo "1. Vérifiez que l'ID de commande dans votre requête AJAX correspond à un ID existant<br>";
echo "2. Consultez les logs du serveur dans les outils de développement (onglet Network)<br>";
echo "3. Assurez-vous que les données JSON sont bien formatées<br>";
echo "4. Vérifiez que shop_id est correctement défini en session<br>";
?> 