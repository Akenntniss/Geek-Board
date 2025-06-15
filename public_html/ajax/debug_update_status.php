<?php
// Script de debug avancé pour capturer les problèmes AJAX
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Créer le dossier de logs s'il n'existe pas
if (!file_exists('../logs')) {
    mkdir('../logs', 0755, true);
}

// Fonction de log personnalisée
function debugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= " | Data: " . print_r($data, true);
    }
    error_log($logMessage . "\n", 3, '../logs/ajax_debug.log');
    echo "<div style='background: #f0f0f0; padding: 5px; margin: 2px; border-left: 3px solid #007bff;'>";
    echo "<strong>$timestamp:</strong> $message";
    if ($data !== null) {
        echo "<pre style='margin: 5px 0; font-size: 12px;'>" . print_r($data, true) . "</pre>";
    }
    echo "</div>";
}

echo "<h2>🔍 Debug AJAX - Mise à jour Statut</h2>";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6;'>";

debugLog("=== DÉBUT DEBUG SESSION ===");
debugLog("Méthode de requête", $_SERVER['REQUEST_METHOD']);
debugLog("Content-Type", $_SERVER['CONTENT_TYPE'] ?? 'Non défini');
debugLog("Session actuelle", $_SESSION);

// Vérifier shop_id
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 1;
    debugLog("shop_id défini par défaut", 1);
} else {
    debugLog("shop_id existant", $_SESSION['shop_id']);
}

// Capturer toutes les données d'entrée
debugLog("GET data", $_GET);
debugLog("POST data", $_POST);

$raw_input = file_get_contents('php://input');
debugLog("Raw input", $raw_input);

if (!empty($raw_input)) {
    $json_data = json_decode($raw_input, true);
    debugLog("JSON décodé", $json_data);
    debugLog("Erreur JSON", json_last_error_msg());
}

// Test de connexion DB
require_once '../config/database.php';

try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Connexion échouée');
    }
    debugLog("Connexion DB", "✅ Succès");
    
    // Lister les commandes disponibles
    $stmt = $shop_pdo->prepare("SELECT id, reference, statut FROM commandes_pieces ORDER BY id");
    $stmt->execute();
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debugLog("Commandes disponibles", $commandes);
    
} catch (Exception $e) {
    debugLog("Erreur DB", $e->getMessage());
}

echo "</div>";

// Interface de test
echo "<hr>";
echo "<h3>🧪 Test Interface</h3>";
echo "<div id='test-results'></div>";

if (!empty($commandes)) {
    echo "<h4>Commandes disponibles pour test :</h4>";
    foreach ($commandes as $cmd) {
        echo "<button onclick='testRealUpdate({$cmd['id']}, \"commande\")' style='margin: 5px; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
        echo "Tester ID {$cmd['id']} ({$cmd['statut']} → commande)";
        echo "</button><br>";
    }
}
?>

<script>
function testRealUpdate(commandeId, newStatus) {
    const resultsDiv = document.getElementById('test-results');
    resultsDiv.innerHTML = `⏳ Test en cours pour commande ${commandeId}...`;
    
    console.log('=== DÉBUT TEST AJAX ===');
    console.log('Commande ID:', commandeId);
    console.log('Nouveau statut:', newStatus);
    
    const requestData = {
        commande_id: parseInt(commandeId),
        new_status: newStatus,
        shop_id: 1
    };
    
    console.log('Données à envoyer:', requestData);
    
    fetch('update_commande_status.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(requestData),
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        console.log('Response headers:', [...response.headers.entries()]);
        
        return response.text();
    })
    .then(text => {
        console.log('Response text brut:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('Response JSON:', data);
            
            if (data.success) {
                resultsDiv.innerHTML = `✅ <strong>SUCCÈS!</strong><br>Message: ${data.message}`;
                resultsDiv.style.color = 'green';
            } else {
                resultsDiv.innerHTML = `❌ <strong>ÉCHEC!</strong><br>Message: ${data.message}`;
                resultsDiv.style.color = 'red';
            }
        } catch (e) {
            console.error('Erreur parsing JSON:', e);
            resultsDiv.innerHTML = `❌ <strong>Erreur JSON!</strong><br>Réponse brute: <pre>${text}</pre>`;
            resultsDiv.style.color = 'red';
        }
    })
    .catch(error => {
        console.error('Erreur fetch:', error);
        resultsDiv.innerHTML = `❌ <strong>Erreur réseau!</strong><br>${error.message}`;
        resultsDiv.style.color = 'red';
    });
}

// Test automatique au chargement
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page de debug chargée');
});
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; overflow-x: auto; }
#test-results { margin: 10px 0; padding: 15px; border: 2px solid #ddd; background: #f9f9f9; min-height: 50px; }
</style> 