<?php
// Test de la nouvelle URL API suggérée
header('Content-Type: text/plain');

// Configuration
$username = '-GCB75';
$password = 'Mamanmaman06400';
$recipient = '+33600000000';
$message = 'Test URL nouvelle ' . date('H:i:s');

// URL suggérée à tester
$url = 'https://api.sms-gate.app/mobile/v1';

echo "=== Test de la nouvelle URL: $url ===\n";
echo "Destinataire: $recipient\n";
echo "Message: $message\n\n";

// Tester différents endpoints
$endpoints = [
    '/messages',
    '/message',
    '/send',
    '/sms',
    '' // Test de l'URL de base
];

foreach ($endpoints as $endpoint) {
    $full_url = $url . $endpoint;
    echo "--- Test endpoint: $full_url ---\n";
    
    // Préparation des données
    $data = json_encode([
        'phone' => $recipient,
        'message' => $message
    ]);
    
    // Configuration cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
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
    } else {
        echo "Réponse: $response\n";
    }
    
    // Afficher les détails si réussite
    if ($http_code >= 200 && $http_code < 300) {
        rewind($verbose);
        $verbose_log = stream_get_contents($verbose);
        echo "Détails complets:\n$verbose_log\n";
    }
    
    curl_close($ch);
    fclose($verbose);
    echo "\n";
}

// Enregistrer si un endpoint fonctionne
echo "=== RÉSULTAT FINAL ===\n";
echo "Si un endpoint a fonctionné, utilisez cette URL dans votre configuration.\n";
?> 