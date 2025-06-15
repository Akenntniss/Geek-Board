<?php
// Test direct de la requête AJAX pour update_commande_status.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulation des données de session
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 1;
}

echo "<h2>🧪 Test AJAX - Mise à jour Commande</h2>";
echo "<h3>📋 Commandes disponibles :</h3>";

// Connecter à la base pour voir les commandes disponibles
require_once '../config/database.php';

try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Connexion à la base de données échouée');
    }
    
    $stmt = $shop_pdo->prepare("SELECT id, reference, statut FROM commandes_pieces ORDER BY id");
    $stmt->execute();
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Référence</th><th>Statut</th><th>Action</th></tr>";
    foreach ($commandes as $cmd) {
        echo "<tr>";
        echo "<td>{$cmd['id']}</td>";
        echo "<td>{$cmd['reference']}</td>";
        echo "<td>{$cmd['statut']}</td>";
        echo "<td><button onclick='testUpdate({$cmd['id']}, \"commande\")'>Tester</button></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}

echo "<hr>";
echo "<h3>🧪 Test en direct :</h3>";
echo "<div id='result'></div>";

?>

<script>
function testUpdate(commandeId, newStatus) {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = `⏳ Test en cours pour commande ${commandeId}...`;
    
    console.log('Test AJAX pour:', { commandeId, newStatus });
    
    fetch('update_commande_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            commande_id: commandeId, 
            new_status: newStatus
        }),
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data);
            
            if (data.success) {
                resultDiv.innerHTML = `✅ <strong>Succès!</strong> ${data.message}`;
                resultDiv.style.color = 'green';
            } else {
                resultDiv.innerHTML = `❌ <strong>Échec:</strong> ${data.message}`;
                resultDiv.style.color = 'red';
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            resultDiv.innerHTML = `❌ <strong>Erreur JSON:</strong> ${text}`;
            resultDiv.style.color = 'red';
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        resultDiv.innerHTML = `❌ <strong>Erreur réseau:</strong> ${error.message}`;
        resultDiv.style.color = 'red';
    });
}

// Test automatique avec le premier ID disponible
document.addEventListener('DOMContentLoaded', function() {
    const firstButton = document.querySelector('button[onclick*="testUpdate"]');
    if (firstButton) {
        console.log('Test automatique démarré');
        firstButton.click();
    }
});
</script>

<style>
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
button { background: #007bff; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; }
button:hover { background: #0056b3; }
#result { margin: 10px 0; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; }
</style> 