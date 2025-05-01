<?php
// Test de l'API SMS MightBell
header('Content-Type: text/plain');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuration 
$url = 'https://sms.mightbell.com/public/api/send';
$api_key = '55AFEEB7A51DFC';
$recipient = '+33600000000'; // Remplacer par un numéro de test valide
$message = 'Test SMS MightBell ' . date('d/m/Y H:i:s');

echo "Test de connexion à l'API MightBell SMS\n";
echo "URL: $url\n";
echo "API Key: $api_key\n";
echo "Destinataire: $recipient\n";
echo "Message: $message\n\n";

// Préparation des données
$data = [
    'api_key' => $api_key,
    'phone' => $recipient,
    'message' => $message,
    'sender_id' => 'SMS'
];

// Test simple avec curl
if (!function_exists('curl_init')) {
    die("Extension CURL non disponible");
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Activation du mode verbose pour déboguer
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Exécution de la requête
echo "Envoi de la requête...\n";
$response = curl_exec($ch);
$info = curl_getinfo($ch);

// Affichage des détails du résultat
echo "Statut HTTP: " . $info['http_code'] . "\n";
echo "Temps total: " . $info['total_time'] . " secondes\n\n";

if ($response === false) {
    echo "ERREUR: " . curl_error($ch) . "\n\n";
    
    // Affichage des informations détaillées
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    
    echo "Informations détaillées:\n";
    echo "======================\n";
    echo $verboseLog . "\n";
} else {
    echo "RÉPONSE:\n";
    echo "========\n";
    echo $response . "\n";
    
    // Tentative de décodage de la réponse JSON
    $json = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "\nDonnées décodées:\n";
        echo "===============\n";
        print_r($json);
    }
}

curl_close($ch);
fclose($verbose);
?> 