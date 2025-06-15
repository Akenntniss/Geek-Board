<?php
/**
 * Script de test pour la fonctionnalité SMS
 * À supprimer après les tests
 */

// Démarrer la session
session_start();

// Initialiser shop_id pour les tests
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 1;
}

require_once 'config/database.php';

echo "<h1>Test de la fonctionnalité SMS</h1>";

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception("Impossible de se connecter à la base de données");
    }
    
    echo "<h2>✅ Connexion à la base de données réussie</h2>";
    
    // Test 1: Vérifier les tables SMS
    echo "<h3>Test 1: Vérification des tables SMS</h3>";
    
    $tables = ['sms_templates', 'sms_logs', 'sms_template_variables'];
    foreach ($tables as $table) {
        $stmt = $shop_pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table $table existe<br>";
        } else {
            echo "❌ Table $table manquante<br>";
        }
    }
    
    // Test 2: Vérifier les templates SMS
    echo "<h3>Test 2: Templates SMS disponibles</h3>";
    
    $stmt = $shop_pdo->query("SELECT id, nom, contenu FROM sms_templates WHERE est_actif = 1");
    $templates = $stmt->fetchAll();
    
    if (count($templates) > 0) {
        echo "✅ " . count($templates) . " template(s) trouvé(s):<br>";
        foreach ($templates as $template) {
            echo "- <strong>{$template['nom']}</strong>: " . substr($template['contenu'], 0, 50) . "...<br>";
        }
    } else {
        echo "❌ Aucun template SMS actif trouvé<br>";
    }
    
    // Test 3: Vérifier les variables de templates
    echo "<h3>Test 3: Variables de templates</h3>";
    
    $stmt = $shop_pdo->query("SELECT nom, description, exemple FROM sms_template_variables");
    $variables = $stmt->fetchAll();
    
    if (count($variables) > 0) {
        echo "✅ " . count($variables) . " variable(s) trouvée(s):<br>";
        foreach ($variables as $var) {
            echo "- <strong>{" . $var['nom'] . "}</strong>: {$var['description']} (ex: {$var['exemple']})<br>";
        }
    } else {
        echo "❌ Aucune variable de template trouvée<br>";
    }
    
    // Test 4: Tester un client avec téléphone
    echo "<h3>Test 4: Clients avec téléphone</h3>";
    
    $stmt = $shop_pdo->query("SELECT id, nom, prenom, telephone FROM clients WHERE telephone IS NOT NULL AND telephone != '' LIMIT 5");
    $clients = $stmt->fetchAll();
    
    if (count($clients) > 0) {
        echo "✅ " . count($clients) . " client(s) avec téléphone trouvé(s):<br>";
        foreach ($clients as $client) {
            echo "- <strong>{$client['nom']} {$client['prenom']}</strong>: {$client['telephone']}<br>";
        }
    } else {
        echo "❌ Aucun client avec téléphone trouvé<br>";
    }
    
    // Test 5: Simuler un remplacement de variables
    echo "<h3>Test 5: Test de remplacement de variables</h3>";
    
    if (count($templates) > 0 && count($clients) > 0) {
        $template = $templates[0];
        $client = $clients[0];
        
        $message = $template['contenu'];
        $message = str_replace('{CLIENT_NOM}', $client['nom'], $message);
        $message = str_replace('{CLIENT_PRENOM}', $client['prenom'], $message);
        $message = str_replace('{DATE}', date('d/m/Y'), $message);
        
        echo "✅ Template: <strong>{$template['nom']}</strong><br>";
        echo "✅ Client: <strong>{$client['nom']} {$client['prenom']}</strong><br>";
        echo "✅ Message final: <em>$message</em><br>";
        echo "✅ Longueur: " . strlen($message) . " caractères<br>";
        
        if (strlen($message) <= 160) {
            echo "✅ Longueur OK (≤ 160 caractères)<br>";
        } else {
            echo "⚠️ Message trop long (> 160 caractères)<br>";
        }
    }
    
    // Test 6: Vérifier les fichiers AJAX
    echo "<h3>Test 6: Fichiers AJAX</h3>";
    
    $ajax_files = [
        'ajax/get_sms_templates.php',
        'ajax/send_client_sms.php'
    ];
    
    foreach ($ajax_files as $file) {
        if (file_exists($file)) {
            echo "✅ Fichier $file existe<br>";
        } else {
            echo "❌ Fichier $file manquant<br>";
        }
    }
    
    echo "<h2>🎉 Tests terminés !</h2>";
    echo "<p><a href='index.php?page=clients'>Aller à la page clients pour tester</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur lors des tests</h2>";
    echo "<p>Erreur: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
h1 {
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}
h3 {
    margin-top: 30px;
    color: #666;
}
</style> 