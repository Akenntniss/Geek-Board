<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Script de test pour l'appel AJAX de la modal
echo "<!DOCTYPE html>
<html>
<head>
    <title>🧪 Test Modal AJAX</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { background: #e3f2fd; padding: 10px; border-left: 4px solid #2196f3; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
        #results { margin-top: 20px; }
    </style>
</head>
<body>";

echo "<h1>🧪 Test de l'Appel AJAX Modal</h1>";

// Vérifier la session
echo "<div class='section'>
    <h2>📋 État de la Session</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? '<span class="error">NON DÉFINI</span>') . "</p>";
echo "<p><strong>Shop ID:</strong> " . ($_SESSION['shop_id'] ?? '<span class="error">NON DÉFINI</span>') . "</p>";
echo "<p><strong>Shop Name:</strong> " . ($_SESSION['shop_name'] ?? '<span class="error">NON DÉFINI</span>') . "</p>";
echo "</div>";

// Interface de test
echo "<div class='section'>
    <h2>🧪 Test en Direct</h2>
    <div class='info'>
        <p>Cliquez sur le bouton pour tester l'appel AJAX vers get_client_details.php</p>
    </div>
    
    <label for='client_id'>ID du client à tester:</label>
    <input type='number' id='client_id' value='1' min='1' style='padding: 5px; margin: 10px;'>
    <button onclick='testModalAjax()'>🔍 Tester Modal AJAX</button>
    <button onclick='testSearchAjax()'>🏠 Tester Recherche AJAX</button>
    
    <div id='results'></div>
</div>";

echo "<script>
async function testModalAjax() {
    const clientId = document.getElementById('client_id').value;
    const resultsDiv = document.getElementById('results');
    
    resultsDiv.innerHTML = '<p>⏳ Test en cours...</p>';
    
    try {
        console.log('Test modal AJAX avec client_id:', clientId);
        
        const response = await fetch('ajax/get_client_details.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'same-origin',
            body: 'client_id=' + encodeURIComponent(clientId)
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const data = await response.json();
        console.log('Response data:', data);
        
        let html = '<h3>📝 Résultats du Test Modal</h3>';
        
        if (data.success) {
            html += '<p class=\"success\">✅ Succès!</p>';
            if (data.debug) {
                html += '<p><strong>Base utilisée:</strong> ' + data.debug.database_used + '</p>';
                html += '<p><strong>Shop ID:</strong> ' + data.debug.shop_id + '</p>';
            }
            html += '<p><strong>Client:</strong> ' + data.client.nom + ' ' + data.client.prenom + '</p>';
            html += '<p><strong>Réparations:</strong> ' + data.counts.reparations + '</p>';
            html += '<p><strong>Commandes:</strong> ' + data.counts.commandes + '</p>';
        } else {
            html += '<p class=\"error\">❌ Échec: ' + data.message + '</p>';
        }
        
        html += '<h4>Réponse complète:</h4>';
        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        
        resultsDiv.innerHTML = html;
        
    } catch (error) {
        console.error('Erreur:', error);
        resultsDiv.innerHTML = '<p class=\"error\">❌ Erreur: ' + error.message + '</p>';
    }
}

async function testSearchAjax() {
    const resultsDiv = document.getElementById('results');
    
    resultsDiv.innerHTML = '<p>⏳ Test recherche en cours...</p>';
    
    try {
        console.log('Test recherche AJAX');
        
        const response = await fetch('ajax/recherche_universelle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'same-origin',
            body: 'terme=test'
        });
        
        console.log('Search response status:', response.status);
        
        const data = await response.json();
        console.log('Search response data:', data);
        
        let html = '<h3>🏠 Résultats du Test Recherche</h3>';
        
        if (data.success) {
            html += '<p class=\"success\">✅ Succès!</p>';
            if (data.debug) {
                html += '<p><strong>Base utilisée:</strong> ' + data.debug.database_used + '</p>';
            }
            html += '<p><strong>Clients trouvés:</strong> ' + (data.results.clients ? data.results.clients.length : 0) + '</p>';
            html += '<p><strong>Réparations trouvées:</strong> ' + (data.results.reparations ? data.results.reparations.length : 0) + '</p>';
        } else {
            html += '<p class=\"error\">❌ Échec: ' + data.message + '</p>';
        }
        
        html += '<h4>Réponse complète:</h4>';
        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        
        resultsDiv.innerHTML = html;
        
    } catch (error) {
        console.error('Erreur:', error);
        resultsDiv.innerHTML = '<p class=\"error\">❌ Erreur: ' + error.message + '</p>';
    }
}
</script>";

echo "</body></html>";
?> 