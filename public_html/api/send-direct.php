<?php
/**
 * API simplifiée pour l'envoi de SMS direct
 * 
 * Cette API est une version simplifiée qui contourne les problèmes potentiels
 * en utilisant uniquement CURL pour effectuer la requête vers l'API SMS Gateway.
 */

// Activer les erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu en JSON
header('Content-Type: application/json');

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée. Utilisez POST.']);
    exit;
}

// Récupérer les données POST
$post_data = file_get_contents('php://input');
$data = json_decode($post_data, true);

// Si le décodage JSON échoue, essayer avec les données POST normales
if (!$data) {
    $data = $_POST;
}

// Vérifier les données requises
$recipient = $data['recipient'] ?? '';
$message = $data['message'] ?? '';

if (empty($recipient) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants. Vous devez fournir recipient et message.']);
    exit;
}

// Formater correctement le numéro de téléphone
$recipient = preg_replace('/[^0-9+]/', '', $recipient);

// Si le numéro commence par un 0, le remplacer par +33 (pour la France)
if (substr($recipient, 0, 1) === '0') {
    $recipient = '+33' . substr($recipient, 1);
} 
// Si le numéro commence par 33 sans +, ajouter le +
elseif (substr($recipient, 0, 2) === '33') {
    $recipient = '+' . $recipient;
}
// Si le numéro ne commence pas par +, l'ajouter
elseif (substr($recipient, 0, 1) !== '+') {
    $recipient = '+' . $recipient;
}

// Vérifier le format du numéro de téléphone
if (!preg_match('/^\+[0-9]{10,15}$/', $recipient)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format de numéro invalide après formatage: ' . $recipient]);
    exit;
}

// Configuration de l'API SMS Gateway
$sms_gateway_url = 'https://api.sms-gate.app/api/v1/messages';
$sms_gateway_username = '-GCB75';
$sms_gateway_password = 'wkbteo4zox0p4q';

// Préparer les données pour l'API
$sms_payload = json_encode([
    'phone' => $recipient,
    'message' => $message
]);

// Log pour débogage
$log_file = '../logs/sms_' . date('Y-m-d') . '.log';
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$log_message = date('[Y-m-d H:i:s]') . " Tentative d'envoi SMS à {$recipient}: " . substr($message, 0, 30) . "...\n";
file_put_contents($log_file, $log_message, FILE_APPEND);

// Initialisation de cURL
$curl = curl_init();

// Configuration de cURL
curl_setopt_array($curl, [
    CURLOPT_URL => $sms_gateway_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $sms_payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($sms_payload)
    ],
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_USERPWD => $sms_gateway_username . ':' . $sms_gateway_password,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => true
]);

// Capturer les informations verboses pour le débogage
$verbose = fopen('php://temp', 'w+');
curl_setopt($curl, CURLOPT_STDERR, $verbose);

// Exécuter la requête
$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Vérifier les erreurs
if ($response === false) {
    $curl_error = curl_error($curl);
    
    // Récupérer les informations détaillées
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    
    // Enregistrer l'erreur dans le journal
    $error_log = date('[Y-m-d H:i:s]') . " ERREUR: {$curl_error}\n{$verbose_log}\n";
    file_put_contents($log_file, $error_log, FILE_APPEND);
    
    // Répondre avec l'erreur
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi du SMS',
        'error' => $curl_error,
        'verbose' => $verbose_log
    ]);
} else {
    // Enregistrer la réponse dans le journal
    $response_log = date('[Y-m-d H:i:s]') . " RÉPONSE (HTTP {$http_code}): {$response}\n";
    file_put_contents($log_file, $response_log, FILE_APPEND);
    
    // Décodage de la réponse JSON
    $json_response = json_decode($response, true);
    
    // Vérifier si le décodage a réussi et si le statut est positif
    if ($http_code >= 200 && $http_code < 300 && $json_response) {
        echo json_encode([
            'success' => true,
            'message' => 'SMS envoyé avec succès',
            'data' => $json_response
        ]);
    } else {
        http_response_code($http_code > 0 ? $http_code : 500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de l\'envoi du SMS',
            'http_code' => $http_code,
            'response' => $response
        ]);
    }
}

// Libérer les ressources
curl_close($curl);
fclose($verbose);
?> 