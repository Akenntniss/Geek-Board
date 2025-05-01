<?php
/**
 * Test direct pour SMS Gateway - Version simplifiée sans dépendances
 */

// En-tête pour afficher les résultats en texte
header('Content-Type: text/plain; charset=UTF-8');

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// S'assurer que le répertoire de logs existe
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Fonction pour logger des messages
function log_message($message) {
    global $log_dir;
    $log_file = $log_dir . '/sms_direct_' . date('Y-m-d') . '.log';
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
    echo $message . "\n";
}

// Configuration de l'API
$api_url = 'https://api.sms-gate.app/3rdparty/v1/message';
$username = '-GCB75';
$password = 'Mamanmaman06400';

// Paramètres du SMS
$phone_number = isset($_GET['numero']) ? $_GET['numero'] : '+33782973829'; // Utiliser un numéro par défaut ou celui fourni
$message = isset($_GET['message']) ? $_GET['message'] : 'Test direct SMS Gateway ' . date('H:i:s');

// Formatage du numéro de téléphone
if (substr($phone_number, 0, 1) === '0') {
    // Convertir les numéros français commençant par 0
    $phone_number = '+33' . substr($phone_number, 1);
} elseif (substr($phone_number, 0, 1) !== '+') {
    // Ajouter le + si manquant
    $phone_number = '+' . $phone_number;
}

log_message("=== TEST DIRECT SMS GATEWAY ===");
log_message("URL API: $api_url");
log_message("Identifiants: $username / ********");
log_message("Téléphone: $phone_number");
log_message("Message: $message");

// Préparation des données pour l'API
$data = json_encode([
    'message' => $message,
    'phoneNumbers' => [$phone_number]  // Doit être un tableau
]);

log_message("Données JSON: $data");

// Configuration de cURL pour l'envoi
$curl = curl_init($api_url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);

// Configuration de l'authentification Basic
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");

// Options supplémentaires pour le débogage
curl_setopt($curl, CURLOPT_VERBOSE, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Désactiver la vérification SSL pour les tests
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);     // Désactiver la vérification de l'hôte
curl_setopt($curl, CURLOPT_TIMEOUT, 30);           // Augmenter le délai d'attente

// Capturer les messages d'erreur détaillés
$verbose = fopen('php://temp', 'w+');
curl_setopt($curl, CURLOPT_STDERR, $verbose);

// Exécution de la requête
log_message("Envoi de la requête cURL...");
$start_time = microtime(true);
$response = curl_exec($curl);
$time_taken = microtime(true) - $start_time;
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

log_message("Temps d'exécution: " . round($time_taken * 1000) . " ms");
log_message("Code HTTP: $status");

// Vérifier les erreurs
if ($response === false) {
    $curl_error = curl_error($curl);
    log_message("ERREUR CURL: $curl_error");
    
    // Afficher les détails verbeux
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    log_message("DÉTAILS VERBOSE:");
    log_message($verbose_log);
} else {
    log_message("RÉPONSE BRUTE:");
    log_message($response);
    
    // Décoder la réponse JSON
    $result = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        log_message("RÉPONSE DÉCODÉE:");
        print_r($result);
        
        // Vérifier si le SMS a été accepté
        if ($status == 200 || $status == 202) {
            log_message("SUCCÈS: SMS accepté pour envoi!");
            if (isset($result['id'])) {
                log_message("ID du message: " . $result['id']);
            }
            if (isset($result['state'])) {
                log_message("État: " . $result['state']);
            }
        } else {
            log_message("ÉCHEC: Le SMS n'a pas été accepté. Code: $status");
        }
    } else {
        log_message("ERREUR: Impossible de décoder la réponse JSON. Erreur: " . json_last_error_msg());
    }
}

// Fermeture des ressources
curl_close($curl);
fclose($verbose);

log_message("=== FIN DU TEST ===");
?> 