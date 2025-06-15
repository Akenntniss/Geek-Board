<?php
// Script de test pour déboguer send_status_sms.php
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test send_status_sms.php</h1>";

// Test 1: Vérifier si les fichiers existent
echo "<h2>1. Vérification des fichiers</h2>";
$files_to_check = [
    'config/database.php',
    'includes/functions.php',
    'includes/sms_functions.php',
    'ajax/send_status_sms.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    echo "<p>📁 {$file}: " . ($exists ? "✅ Existe" : "❌ N'existe pas") . "</p>";
}

// Test 2: Test de récupération de template (GET)
echo "<h2>2. Test récupération template (GET)</h2>";
$test_repair_id = 1; // ID de test
$test_status_id = 11; // Statut "Restitué"

$get_url = "ajax/send_status_sms.php?repair_id={$test_repair_id}&status_id={$test_status_id}";
echo "<p>🔗 URL de test: <a href='{$get_url}' target='_blank'>{$get_url}</a></p>";

// Test avec cURL
echo "<h3>Résultat cURL GET:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . "/" . $get_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>Code HTTP:</strong> {$http_code}</p>";
echo "<p><strong>Réponse:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test 3: Test d'envoi de SMS (POST)
echo "<h2>3. Test envoi SMS (POST)</h2>";
$post_data = json_encode([
    'repair_id' => $test_repair_id,
    'status_id' => $test_status_id
]);

echo "<p><strong>Données POST:</strong></p>";
echo "<pre>" . htmlspecialchars($post_data) . "</pre>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . "/ajax/send_status_sms.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($post_data)
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response_post = curl_exec($ch);
$http_code_post = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>Code HTTP POST:</strong> {$http_code_post}</p>";
echo "<p><strong>Réponse POST:</strong></p>";
echo "<pre>" . htmlspecialchars($response_post) . "</pre>";

// Test 4: Vérifier les logs
echo "<h2>4. Logs d'erreur</h2>";
$log_file = $_SERVER['DOCUMENT_ROOT'] . '/logs/sms_status_' . date('Y-m-d') . '.log';
if (file_exists($log_file)) {
    echo "<p><strong>Contenu du log:</strong></p>";
    echo "<pre>" . htmlspecialchars(file_get_contents($log_file)) . "</pre>";
} else {
    echo "<p>Aucun fichier de log trouvé: {$log_file}</p>";
}

echo "<hr>";
echo "<p><em>Test terminé - " . date('Y-m-d H:i:s') . "</em></p>";
?> 