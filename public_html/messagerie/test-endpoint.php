<?php
// Test de différentes variantes d'URL pour déterminer le bon endpoint
header('Content-Type: text/plain');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuration de base
$username = '-GCB75';
$password = 'Mamanmaman06400'; // Nouveau mot de passe correct
$recipient = '+33600000000';
$message = 'Test URL ' . date('H:i:s');

// Variantes d'URL à tester
$urls = [
    'https://api.sms-gate.app/api/v1/messages',
    'https://api.sms-gate.app:443/api/v1/messages',
    'https://api.sms-gate.app/api/v1/message',
    'https://api.sms-gate.app/api/messages',
    'https://sms-gate.app/api/v1/messages',
    'https://api.sms-gate.app',
    'https://app.sms-gate.app/api/v1/messages'
];

// Fonction pour tester une URL
function test_url($url, $username, $password, $recipient, $message) {
    echo "=== Test de l'URL: $url ===\n";
    
    // Préparation des données
    $data = json_encode([
        'phone' => $recipient,
        'message' => $message
    ]);
    
    // Configuration cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    // Capturer les erreurs détaillées
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    // Exécution
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $time_taken = microtime(true) - $start_time;
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Afficher les résultats
    echo "Code HTTP: $http_code\n";
    echo "Temps: " . round($time_taken * 1000) . " ms\n";
    
    if ($response === false) {
        echo "Erreur: " . curl_error($ch) . "\n";
        rewind($verbose);
        echo "Détails: " . stream_get_contents($verbose) . "\n";
    } else {
        echo "Réponse: $response\n";
    }
    
    echo "\n";
    
    curl_close($ch);
    fclose($verbose);
    
    return $http_code;
}

// Tester chaque URL
$results = [];
foreach ($urls as $url) {
    $results[$url] = test_url($url, $username, $password, $recipient, $message);
}

// Afficher un récapitulatif
echo "=== RÉCAPITULATIF ===\n";
foreach ($results as $url => $code) {
    echo "$url: " . ($code >= 200 && $code < 300 ? "OK ($code)" : "ÉCHEC ($code)") . "\n";
}

// Vérifier la documentation de l'API
echo "\n=== VÉRIFICATION DE LA DOCUMENTATION ===\n";
$doc_url = "https://docs.sms-gate.app";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $doc_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Documentation ($doc_url): " . ($http_code >= 200 && $http_code < 300 ? "Accessible" : "Non accessible") . " ($http_code)\n";
curl_close($ch);
?> 