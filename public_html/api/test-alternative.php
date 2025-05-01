<?php
// Teste plusieurs services SMS alternatifs 
// Ce script est utile pour vérifier rapidement si d'autres API SMS fonctionnent

header('Content-Type: text/plain');

// Configuration de base
$phone = '+33600000000'; // Numéro de test
$message = 'Test service alternatif ' . date('H:i:s');

echo "=== TEST DES SERVICES SMS ALTERNATIFS ===\n";
echo "Phone: $phone\n";
echo "Message: $message\n\n";

// Fonction pour tester une requête simple vers un service
function test_service_connectivity($name, $url) {
    echo "--- Test connexion à $name ($url) ---\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Résultat: " . ($http_code > 0 ? "Accessible (HTTP $http_code)" : "Inaccessible") . "\n\n";
    return $http_code;
}

// Liste des services SMS alternatifs à tester
$services = [
    // Services commerciaux établis
    ["Twilio", "https://api.twilio.com"],
    ["Vonage/Nexmo", "https://api.nexmo.com"],
    ["MessageBird", "https://rest.messagebird.com"],
    ["Infobip", "https://api.infobip.com"],
    ["Plivo", "https://api.plivo.com"],
    ["Clickatell", "https://api.clickatell.com"],
    
    // Services européens
    ["SMS77", "https://gateway.sms77.io"],
    ["SMSFactor", "https://api.smsfactor.com"],
    ["OVH SMS", "https://api.ovh.com/1.0/sms"],
    
    // Service original
    ["SMS Gate", "https://api.sms-gate.app"],
    ["SMS Gate Mobile", "https://api.sms-gate.app/mobile"]
];

// Tester la connexion à chaque service
foreach ($services as $service) {
    list($name, $url) = $service;
    test_service_connectivity($name, $url);
}

echo "=== RÉSULTAT ===\n";
echo "Ces tests vérifient uniquement si les services sont accessibles.\n";
echo "Pour utiliser un service alternatif, vous devrez créer un compte et obtenir les clés d'API appropriées.\n";
echo "Alternatives recommandées : Twilio, Vonage, MessageBird ou SMS77.\n";

// Exemples de code pour les services les plus populaires
echo "\n=== EXEMPLES D'INTÉGRATION ===\n";

// Exemple Twilio
echo "--- Exemple Twilio ---\n";
echo "1. Inscrivez-vous sur twilio.com\n";
echo "2. Installez via Composer: composer require twilio/sdk\n";
echo "3. Code PHP:\n";
echo <<<EOT
\$sid = 'VOTRE_TWILIO_SID';
\$token = 'VOTRE_TWILIO_TOKEN';
\$twilio = new Twilio\Rest\Client(\$sid, \$token);
\$message = \$twilio->messages->create(
    '+33600000000', // destinataire
    [
        'from' => 'VOTRE_NUMERO_TWILIO',
        'body' => 'Votre message'
    ]
);
EOT;
echo "\n\n";

// Exemple Vonage/Nexmo
echo "--- Exemple Vonage/Nexmo ---\n";
echo "1. Inscrivez-vous sur vonage.com\n";
echo "2. Installez via Composer: composer require vonage/client\n";
echo "3. Code PHP:\n";
echo <<<EOT
\$client = new Vonage\Client(
    new Vonage\Client\Credentials\Basic('VOTRE_API_KEY', 'VOTRE_API_SECRET')
);
\$response = \$client->sms()->send(
    new Vonage\SMS\Message\SMS('+33600000000', 'VOTRE_NOM', 'Votre message')
);
EOT;
echo "\n\n";

// Exemple de code direct avec cURL (sans dépendances)
echo "--- Exemple avec cURL simple ---\n";
echo "Pour une solution sans dépendances externes, vous pouvez utiliser un service comme SMS77:\n";
echo <<<EOT
\$ch = curl_init('https://gateway.sms77.io/api/sms');
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_POST, true);
curl_setopt(\$ch, CURLOPT_POSTFIELDS, http_build_query([
    'to' => '+33600000000',
    'text' => 'Votre message',
    'from' => 'Votre nom'
]));
curl_setopt(\$ch, CURLOPT_HTTPHEADER, ['X-Api-Key: VOTRE_API_KEY']);
\$response = curl_exec(\$ch);
curl_close(\$ch);
EOT;
echo "\n";
?> 