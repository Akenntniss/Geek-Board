<?php
// Test de différents formats d'authentification avec l'API SMS
header('Content-Type: text/plain');

// Configuration de base
$url = 'https://api.sms-gate.app/mobile/v1/messages';
$recipient = '+33600000000';
$message = 'Test authentification ' . date('H:i:s');

// Différentes configurations d'authentification à tester
$auth_configs = [
    // Authentification basique avec nom d'utilisateur/mot de passe
    [
        'name' => 'Basic Auth - Original',
        'auth_type' => 'basic',
        'username' => '-GCB75',
        'password' => 'Mamanmaman06400'
    ],
    // Authentification basique avec nom d'utilisateur/mot de passe sans tiret
    [
        'name' => 'Basic Auth - Sans tiret',
        'auth_type' => 'basic',
        'username' => 'GCB75',
        'password' => 'Mamanmaman06400'
    ],
    // Authentification basique avec mot de passe comme nom d'utilisateur
    [
        'name' => 'Basic Auth - Mot de passe comme utilisateur',
        'auth_type' => 'basic',
        'username' => 'Mamanmaman06400',
        'password' => '-GCB75'
    ],
    // Authentification via header Authorization
    [
        'name' => 'Bearer Token',
        'auth_type' => 'bearer',
        'token' => 'Mamanmaman06400'
    ],
    // Authentification via paramètres dans la requête
    [
        'name' => 'Query Params',
        'auth_type' => 'query',
        'username' => '-GCB75',
        'password' => 'Mamanmaman06400'
    ],
    // Authentification via JSON dans le corps
    [
        'name' => 'Auth in JSON body',
        'auth_type' => 'json',
        'username' => '-GCB75',
        'password' => 'Mamanmaman06400'
    ]
];

// Tester chaque configuration d'authentification
foreach ($auth_configs as $config) {
    echo "=== Test: " . $config['name'] . " ===\n";
    
    // Configuration cURL de base
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Préparation des données en fonction du type d'authentification
    switch ($config['auth_type']) {
        case 'basic':
            // Authentification basique
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $data = json_encode([
                'phone' => $recipient,
                'message' => $message
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            break;
        
        case 'bearer':
            // Authentification par token Bearer
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['token']
            ]);
            $data = json_encode([
                'phone' => $recipient,
                'message' => $message
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            break;
        
        case 'query':
            // Authentification par paramètres dans l'URL
            $query_url = $url . '?username=' . urlencode($config['username']) . '&password=' . urlencode($config['password']);
            curl_setopt($ch, CURLOPT_URL, $query_url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $data = json_encode([
                'phone' => $recipient,
                'message' => $message
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            break;
        
        case 'json':
            // Authentification dans le corps JSON
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $data = json_encode([
                'username' => $config['username'],
                'password' => $config['password'],
                'phone' => $recipient,
                'message' => $message
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            break;
    }
    
    // Capturer les détails pour le débogage
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    // Exécution de la requête
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Affichage des résultats
    echo "Code HTTP: $http_code\n";
    if ($response === false) {
        echo "Erreur: " . curl_error($ch) . "\n";
    } else {
        echo "Réponse: $response\n";
    }
    
    // Afficher les détails verbeux
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    
    if ($http_code >= 200 && $http_code < 300) {
        echo "\nDétails complets (succès) :\n$verbose_log\n";
    }
    
    curl_close($ch);
    fclose($verbose);
    echo "\n";
}

echo "=== RÉSULTAT FINAL ===\n";
echo "Si l'une des méthodes d'authentification a fonctionné, utilisez-la dans votre configuration.\n";
?> 