<?php
// Test avec l'endpoint correct selon la documentation officielle
header('Content-Type: text/plain');

// Configuration
$username = '-GCB75';
$password = 'Mamanmaman06400';

// NOTE: La documentation indique que le format d'envoi est différent!
// - L'URL correcte est https://api.sms-gate.app/3rdparty/v1/message
// - Le format JSON utilise "phoneNumbers" (tableau) et non "phone"

echo "=== TEST SELON LA DOCUMENTATION OFFICIELLE ===\n";
echo "URL: https://api.sms-gate.app/3rdparty/v1/message\n";
echo "Authentification: Basic ($username:$password)\n\n";

// Formatage correct des données selon la documentation
$recipient = '+33600000000'; // Remplacer par un numéro valide pour les tests
$message = 'Test selon doc officielle ' . date('H:i:s');

// Le bon format selon la documentation
$data = json_encode([
    'message' => $message,
    'phoneNumbers' => [$recipient] // Tableau de numéros
]);

echo "Données JSON envoyées:\n$data\n\n";

// Configuration de la requête selon la documentation
$ch = curl_init('https://api.sms-gate.app/3rdparty/v1/message');
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

// Capturer les détails pour le débogage
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Exécution
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Code HTTP: $http_code\n";

if ($response === false) {
    echo "Erreur: " . curl_error($ch) . "\n";
} else {
    echo "Réponse: $response\n";
}

// En cas d'erreur ou de succès, afficher les détails verbeux
rewind($verbose);
$verbose_log = stream_get_contents($verbose);
echo "\nDétails de la communication:\n";
echo "==========================\n";
echo $verbose_log . "\n";

curl_close($ch);
fclose($verbose);

echo "\n=== CONCLUSION ===\n";
if ($http_code >= 200 && $http_code < 300) {
    echo "SUCCÈS! L'envoi a fonctionné avec l'URL et le format corrects.\n";
    echo "Utiliser l'URL: https://api.sms-gate.app/3rdparty/v1/message\n";
    echo "Format JSON: { \"message\": \"...\", \"phoneNumbers\": [\"+1234...\", \"+5678...\"] }\n";
} else {
    echo "L'envoi a échoué. Vérifiez les informations suivantes:\n";
    echo "1. L'application SMS Gateway est-elle en cours d'exécution sur votre téléphone?\n";
    echo "2. Le téléphone est-il connecté à Internet?\n";
    echo "3. Avez-vous bien activé le mode 'Cloud Server' dans l'application?\n";
    echo "4. Les identifiants (nom d'utilisateur et mot de passe) sont-ils corrects?\n";
}
?> 