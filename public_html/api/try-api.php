<?php
// Test simple pour essayer différentes URLs d'API SMS
header('Content-Type: text/plain');

// Configuration
$username = '-GCB75';
$password = 'Mamanmaman06400';
$recipient = '+33600000000';
$message = 'Test depuis try-api ' . date('H:i:s');

// Variantes d'URLs à tester
$urls = [
    'https://sms-gate.app/api/v1/messages',
    'https://api.sms-gate.app/api/v1/messages',
    'https://api.sms-gate.app/api/messages',
    'https://api.sms-gate.app/api/v1/send',
    'https://api.sms-gate.app/v1/messages'
];

// Tester chaque URL
foreach ($urls as $url) {
    echo "=== Test URL: $url ===\n";
    
    $data = json_encode([
        'phone' => $recipient, 
        'message' => $message
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "Code HTTP: $http_code\n";
    
    if ($response === false) {
        echo "Erreur: " . curl_error($ch) . "\n";
    } else {
        echo "Réponse: $response\n";
    }
    
    curl_close($ch);
    echo "\n";
}

// Essayer avec une requête GET informative
echo "=== Test endpoint informatif ===\n";
$ch = curl_init('https://api.sms-gate.app/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $http_code\n";
echo "Réponse: " . ($response ? $response : "Aucune réponse") . "\n";
?> 