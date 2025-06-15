<?php
/**
 * Script de test pour la nouvelle API SMS Gateway
 * Migration depuis l'ancienne API sms-gate.app vers http://168.231.85.4:3001/api
 */

// Démarrer le output buffering pour un affichage propre
ob_start();

// Headers pour l'affichage
header('Content-Type: text/html; charset=UTF-8');

// Inclure les fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/sms_functions.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Migration API SMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background-color: #e2e3e5; border-color: #d6d8db; color: #383d41; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; color: #856404; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .form-section { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        input[type='tel'], textarea { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 3px; }
    </style>
</head>
<body>";

echo "<h1>🚀 Test Migration API SMS GeekBoard</h1>";
echo "<p><strong>Ancienne API:</strong> https://api.sms-gate.app/3rdparty/v1/message</p>";
echo "<p><strong>Nouvelle API:</strong> http://168.231.85.4:3001/api/messages/send</p>";

// Test 1 : Connectivité API
echo "<div class='test-section info'>";
echo "<h2>📡 Test 1 : Connectivité avec la nouvelle API</h2>";

$connectivityTest = test_new_sms_api();
if ($connectivityTest['success']) {
    echo "<div class='success'>✅ API accessible (Code HTTP: {$connectivityTest['http_code']})</div>";
} else {
    echo "<div class='error'>❌ Problème de connectivité: {$connectivityTest['message']}</div>";
}
echo "</div>";

// Test 2 : Vérification des fonctions
echo "<div class='test-section info'>";
echo "<h2>🔧 Test 2 : Vérification des fonctions</h2>";

$functions_to_check = ['send_sms', 'send_sms_direct', 'test_new_sms_api', 'log_sms_to_database'];
foreach ($functions_to_check as $func) {
    if (function_exists($func)) {
        echo "<div class='success'>✅ Fonction $func() disponible</div>";
    } else {
        echo "<div class='error'>❌ Fonction $func() manquante</div>";
    }
}
echo "</div>";

// Test 3 : Vérification des classes
echo "<div class='test-section info'>";
echo "<h2>📦 Test 3 : Vérification des classes</h2>";

if (class_exists('NewSmsService')) {
    echo "<div class='success'>✅ Classe NewSmsService disponible</div>";
    
    try {
        $smsService = new NewSmsService();
        echo "<div class='success'>✅ Instance NewSmsService créée avec succès</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erreur lors de la création de NewSmsService: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>❌ Classe NewSmsService manquante</div>";
}

if (class_exists('SmsService')) {
    echo "<div class='warning'>⚠️ Ancienne classe SmsService encore présente (normal en migration)</div>";
} else {
    echo "<div class='info'>ℹ️ Ancienne classe SmsService non trouvée</div>";
}
echo "</div>";

// Test 4 : Historique SMS
echo "<div class='test-section info'>";
echo "<h2>📚 Test 4 : Historique SMS</h2>";

try {
    $history = get_sms_history(5);
    echo "<div class='success'>✅ Fonction get_sms_history() fonctionne</div>";
    echo "<p>Nombre de SMS dans l'historique : " . count($history) . "</p>";
    
    if (count($history) > 0) {
        echo "<pre>" . json_encode(array_slice($history, 0, 2), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur get_sms_history(): " . $e->getMessage() . "</div>";
}
echo "</div>";

// Formulaire de test en temps réel
echo "<div class='form-section'>";
echo "<h2>🧪 Test en temps réel</h2>";

if (isset($_POST['test_sms'])) {
    $test_number = $_POST['test_number'] ?? '';
    $test_message = $_POST['test_message'] ?? '';
    
    if (!empty($test_number) && !empty($test_message)) {
        echo "<div class='test-section'>";
        echo "<h3>Résultat du test SMS</h3>";
        
        try {
            $test_result = send_sms($test_number, $test_message, 'test', null, 1);
            
            if ($test_result['success']) {
                echo "<div class='success'>✅ SMS envoyé avec succès !</div>";
                echo "<p><strong>Message:</strong> {$test_result['message']}</p>";
                if (isset($test_result['data'])) {
                    echo "<pre>Données: " . json_encode($test_result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                }
            } else {
                echo "<div class='error'>❌ Échec de l'envoi SMS</div>";
                echo "<p><strong>Erreur:</strong> {$test_result['message']}</p>";
                if (isset($test_result['http_code'])) {
                    echo "<p><strong>Code HTTP:</strong> {$test_result['http_code']}</p>";
                }
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
        }
        echo "</div>";
    } else {
        echo "<div class='error'>❌ Veuillez remplir tous les champs</div>";
    }
}

echo "<form method='POST'>";
echo "<h3>Envoyer un SMS de test</h3>";
echo "<p>";
echo "<label>Numéro de téléphone (format: +33612345678 ou 0612345678) :</label><br>";
echo "<input type='tel' name='test_number' placeholder='+33612345678' required>";
echo "</p>";
echo "<p>";
echo "<label>Message de test :</label><br>";
echo "<textarea name='test_message' rows='3' placeholder='Message de test GeekBoard' required>Test migration API SMS - " . date('d/m/Y H:i:s') . "</textarea>";
echo "</p>";
echo "<button type='submit' name='test_sms'>📤 Envoyer SMS de test</button>";
echo "</form>";
echo "</div>";

// Informations techniques
echo "<div class='test-section info'>";
echo "<h2>🔧 Informations techniques</h2>";
echo "<p><strong>Version PHP:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Extensions disponibles:</strong></p>";
echo "<ul>";
echo "<li>cURL: " . (extension_loaded('curl') ? '✅ Disponible' : '❌ Manquant') . "</li>";
echo "<li>JSON: " . (extension_loaded('json') ? '✅ Disponible' : '❌ Manquant') . "</li>";
echo "<li>PDO: " . (extension_loaded('pdo') ? '✅ Disponible' : '❌ Manquant') . "</li>";
echo "</ul>";

echo "<p><strong>Logs disponibles:</strong></p>";
$log_dir = __DIR__ . '/logs/';
if (is_dir($log_dir)) {
    $log_files = glob($log_dir . '*sms*.log');
    if (count($log_files) > 0) {
        echo "<ul>";
        foreach ($log_files as $log_file) {
            $filename = basename($log_file);
            $size = filesize($log_file);
            echo "<li>$filename (" . round($size/1024, 2) . " KB)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Aucun fichier de log SMS trouvé</p>";
    }
} else {
    echo "<p>Répertoire logs non trouvé</p>";
}
echo "</div>";

echo "<div class='test-section warning'>";
echo "<h2>⚠️ Instructions post-migration</h2>";
echo "<ol>";
echo "<li><strong>Tester sur numéros réels</strong> : Utilisez le formulaire ci-dessus avec de vrais numéros</li>";
echo "<li><strong>Vérifier les logs</strong> : Consultez les fichiers dans /logs/ pour le debug</li>";
echo "<li><strong>Mettre à jour remaining files</strong> : D'autres fichiers utilisent encore l'ancienne API</li>";
echo "<li><strong>Supprimer l'ancienne API</strong> : Une fois la migration terminée</li>";
echo "<li><strong>Monitoring</strong> : Surveiller les erreurs dans les premiers jours</li>";
echo "</ol>";
echo "</div>";

echo "<div class='test-section info'>";
echo "<h2>📋 Fichiers modifiés dans cette migration</h2>";
echo "<ul>";
echo "<li>✅ <code>classes/NewSmsService.php</code> - Nouvelle classe SMS</li>";
echo "<li>✅ <code>includes/sms_functions.php</code> - Fonctions unifiées</li>";
echo "<li>✅ <code>includes/global.php</code> - Inclusion mise à jour</li>";
echo "<li>✅ <code>classes/SmsService.php</code> - Redirection vers nouvelle API</li>";
echo "<li>✅ <code>ajax/send_sms.php</code> - Migration partielle</li>";
echo "<li>⏳ <code>ajax/*.php</code> - Autres fichiers à migrer</li>";
echo "<li>⏳ <code>pages/*.php</code> - Pages à migrer</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";

// Flush output
ob_end_flush(); 