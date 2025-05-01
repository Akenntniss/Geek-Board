<?php
/**
 * Script de vérification complète de l'API SMS
 * Ce script effectue une série de tests pour identifier pourquoi l'API SMS ne fonctionne pas
 */

// En-tête pour affichage en texte brut
header('Content-Type: text/plain');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=========================================\n";
echo "DIAGNOSTIC DE L'API SMS - " . date('d/m/Y H:i:s') . "\n";
echo "=========================================\n\n";

// 1. Vérifier les extensions PHP
echo "=== EXTENSIONS PHP ===\n";
$required_extensions = ['curl', 'json', 'openssl'];
$all_extensions_ok = true;

foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "$ext: " . ($loaded ? "OK" : "MANQUANT") . "\n";
    if (!$loaded) $all_extensions_ok = false;
}

if (!$all_extensions_ok) {
    echo "\nATTENTION: Certaines extensions requises ne sont pas chargées!\n";
}

echo "\n";

// 2. Vérifier la connectivité réseau - Test DNS
echo "=== TEST DNS ===\n";
$host = 'api.sms-gate.app';
$ip = gethostbyname($host);
if ($ip != $host) {
    echo "Résolution DNS pour $host: $ip (OK)\n";
} else {
    echo "Résolution DNS pour $host: ÉCHEC (Impossible de résoudre le nom d'hôte)\n";
}

// Test DNS alternatif
$host2 = 'sms-gate.app';
$ip2 = gethostbyname($host2);
if ($ip2 != $host2) {
    echo "Résolution DNS pour $host2: $ip2 (OK)\n";
} else {
    echo "Résolution DNS pour $host2: ÉCHEC (Impossible de résoudre le nom d'hôte)\n";
}

echo "\n";

// 3. Test de connectivité HTTP simple
echo "=== TEST DE CONNECTIVITÉ HTTP ===\n";
$urls_to_test = [
    'https://api.sms-gate.app/',
    'https://sms-gate.app/',
    'https://docs.sms-gate.app/',
    'https://www.google.com/' // Pour vérifier si d'autres sites sont accessibles
];

foreach ($urls_to_test as $test_url) {
    $ch = curl_init($test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "$test_url: " . ($http_code > 0 ? "Accessible (HTTP $http_code)" : "Inaccessible") . "\n";
}

echo "\n";

// 4. Vérifier les informations d'authentification
echo "=== INFORMATIONS D'AUTHENTIFICATION ===\n";
$username = '-GCB75';
$password = 'Mamanmaman06400';

echo "Nom d'utilisateur: $username\n";
echo "Mot de passe: " . (empty($password) ? "VIDE!" : "Défini (" . strlen($password) . " caractères)") . "\n";

if (strpos($username, ' ') !== false || strpos($password, ' ') !== false) {
    echo "ATTENTION: Le nom d'utilisateur ou le mot de passe contient des espaces!\n";
}

echo "\n";

// 5. Test d'envoi SMS avec configuration de base
echo "=== TEST D'ENVOI SMS BASIQUE ===\n";
$url = 'https://api.sms-gate.app/api/v1/messages';
$recipient = '+33600000000'; // Numéro de test
$message = 'Test diagnostic ' . date('H:i:s');

echo "URL: $url\n";
echo "Destinataire: $recipient\n";
echo "Message: $message\n\n";

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
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

// Récupérer les informations détaillées
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);

echo "Code HTTP: " . $info['http_code'] . "\n";
echo "Temps total: " . round($info['total_time'] * 1000) . " ms\n";
echo "Content type: " . $info['content_type'] . "\n";

if ($error) {
    echo "Erreur cURL: $error\n\n";
    
    // Afficher les détails verbeux en cas d'erreur
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    echo "Détails de la communication:\n";
    echo "==========================\n";
    echo $verbose_log . "\n";
} else {
    echo "Réponse: $response\n\n";
    
    // Afficher les détails verbeux même en cas de succès
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    echo "Détails de la communication:\n";
    echo "==========================\n";
    echo $verbose_log . "\n";
}

curl_close($ch);
fclose($verbose);

// 6. Test d'envoi avec bibliothèque différente (si disponible)
if (function_exists('stream_context_create') && function_exists('file_get_contents')) {
    echo "\n=== TEST D'ENVOI ALTERNATIF (file_get_contents) ===\n";
    
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                        "Authorization: Basic " . base64_encode("$username:$password") . "\r\n",
            'content' => $data,
            'timeout' => 15,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ];
    
    $context = stream_context_create($opts);
    
    try {
        $alt_response = @file_get_contents($url, false, $context);
        $status_line = $http_response_header[0] ?? '';
        
        echo "Statut: $status_line\n";
        echo "Réponse: " . ($alt_response !== false ? $alt_response : "ÉCHEC") . "\n";
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}

// 7. Vérifications supplémentaires
echo "\n=== VÉRIFICATIONS SUPPLÉMENTAIRES ===\n";

// Vérifier si on est derrière un proxy
$proxy_env_vars = ['HTTP_PROXY', 'HTTPS_PROXY', 'http_proxy', 'https_proxy', 'ALL_PROXY', 'all_proxy'];
$proxy_detected = false;

foreach ($proxy_env_vars as $var) {
    if (!empty(getenv($var))) {
        echo "Proxy détecté: " . getenv($var) . " (variable $var)\n";
        $proxy_detected = true;
    }
}

if (!$proxy_detected) {
    echo "Aucun proxy détecté dans les variables d'environnement.\n";
}

// Vérifier si l'hébergement ou serveur bloque les connexions sortantes
echo "\nTest de connexion sortante vers un service externe...\n";
$external_url = 'https://api.ipify.org?format=json';
$ch = curl_init($external_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$external_response = curl_exec($ch);
$external_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($external_response !== false) {
    echo "Connexion externe réussie: $external_response\n";
} else {
    echo "Échec de la connexion externe! L'hébergeur pourrait bloquer les connexions sortantes.\n";
}

// 8. Recommandations
echo "\n=== CONCLUSION ET RECOMMANDATIONS ===\n";

if ($info['http_code'] == 404) {
    echo "PROBLÈME PRINCIPAL: L'URL de l'API semble incorrecte (erreur 404).\n";
    echo "Recommandation: Vérifiez la documentation pour la bonne URL d'API.\n";
} elseif ($info['http_code'] == 401) {
    echo "PROBLÈME PRINCIPAL: Authentification refusée (erreur 401).\n";
    echo "Recommandation: Vérifiez vos identifiants (nom d'utilisateur et mot de passe).\n";
} elseif ($info['http_code'] == 0) {
    echo "PROBLÈME PRINCIPAL: Impossible d'établir une connexion avec le serveur.\n";
    if ($proxy_detected) {
        echo "Recommandation: Votre serveur utilise un proxy, vérifiez si le proxy autorise les connexions à ce domaine.\n";
    } else {
        echo "Recommandation: Vérifiez si votre hébergeur n'a pas de restrictions sur les connexions sortantes.\n";
    }
} elseif ($info['http_code'] >= 500) {
    echo "PROBLÈME PRINCIPAL: Erreur serveur (HTTP " . $info['http_code'] . ").\n";
    echo "Recommandation: Le service SMS peut être temporairement hors service. Réessayez plus tard ou contactez leur support.\n";
} elseif ($info['http_code'] >= 200 && $info['http_code'] < 300) {
    echo "L'appel API a réussi avec le code " . $info['http_code'] . ".\n";
    
    $json_response = json_decode($response, true);
    if (is_array($json_response) && isset($json_response['id'])) {
        echo "La réponse semble valide et contient un ID de message: " . $json_response['id'] . "\n";
        echo "L'intégration SMS semble fonctionner correctement!\n";
    } else {
        echo "ATTENTION: Le format de la réponse n'est pas celui attendu.\n";
        echo "Recommandation: Vérifiez la documentation pour le format de réponse attendu.\n";
    }
} else {
    echo "PROBLÈME PRINCIPAL: Code HTTP inattendu (" . $info['http_code'] . ").\n";
    echo "Recommandation: Examinez la réponse et les logs pour plus d'informations.\n";
}

echo "\nRapport de diagnostic terminé. Gardez ce rapport pour le support technique si nécessaire.\n";
?> 