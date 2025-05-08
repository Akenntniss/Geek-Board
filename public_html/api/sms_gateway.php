<?php
/**
 * SMS Gateway API - Documentation et Configuration
 * 
 * Ce fichier sert de référence pour l'utilisation de l'API SMS Gateway pour Android
 * https://docs.sms-gate.app/getting-started/
 */

// Configuration de l'API
$CONFIG = [
    // URL de l'API - utilisez celle qui correspond à votre mode d'utilisation
    'api_url' => 'https://api.sms-gate.app/api/v1/messages', // URL mise à jour pour correspondre à la documentation
    
    // Identifiants d'authentification
    'username' => '-GCB75',
    'password' => 'wkbteo4zox0p4q', // Mot de passe mis à jour
    
    // Paramètres optionnels
    'debug' => true, // Activer les logs détaillés
    'timeout' => 30,  // Délai d'attente en secondes
];

/**
 * Fonction d'exemple pour envoyer un SMS
 * 
 * @param string $to Numéro de téléphone au format international (+33612345678)
 * @param string $message Contenu du message à envoyer
 * @return array Résultat de l'opération
 */
function send_sms_example($to, $message) {
    global $CONFIG;
    
    // Formatage correct du numéro de téléphone
    $to = preg_replace('/[^0-9+]/', '', $to);
    
    // Si le numéro commence par un 0, le remplacer par +33 (pour la France)
    if (substr($to, 0, 1) === '0') {
        $to = '+33' . substr($to, 1);
    } 
    // Si le numéro commence par 33 sans +, ajouter le +
    elseif (substr($to, 0, 2) === '33') {
        $to = '+' . $to;
    }
    // Si le numéro ne commence pas par +, l'ajouter
    elseif (substr($to, 0, 1) !== '+') {
        $to = '+' . $to;
    }
    
    // Vérification du format du numéro
    if (!preg_match('/^\+[0-9]{10,15}$/', $to)) {
        return [
            'success' => false,
            'message' => 'Format de numéro invalide après formatage: ' . $to
        ];
    }
    
    // Préparation des données selon le format correct de l'API
    $data = json_encode([
        'message' => $message,
        'phone' => $to // Format correct pour l'API
    ]);
    
    // Configuration de la requête cURL
    $curl = curl_init($CONFIG['api_url']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // Configuration de l'authentification Basic
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, $CONFIG['username'] . ':' . $CONFIG['password']);
    
    // Paramètres supplémentaires
    curl_setopt($curl, CURLOPT_TIMEOUT, $CONFIG['timeout']);
    
    // Options de debug si activées
    if ($CONFIG['debug']) {
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($curl, CURLOPT_STDERR, $verbose);
    }
    
    // Exécution de la requête
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    // Log détaillé pour le débogage
    if ($CONFIG['debug']) {
        $log_file = '../logs/sms_' . date('Y-m-d') . '.log';
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_message = date('[Y-m-d H:i:s]') . " Requête API SMS:\n";
        $log_message .= "URL: " . $CONFIG['api_url'] . "\n";
        $log_message .= "Données: " . $data . "\n";
        $log_message .= "Code HTTP: " . $http_code . "\n";
        $log_message .= "Réponse: " . $response . "\n\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
    
    // Gestion des erreurs
    if ($response === false) {
        $error = curl_error($curl);
        
        // Log détaillé si debug activé
        if ($CONFIG['debug'] && isset($verbose)) {
            rewind($verbose);
            $verbose_log = stream_get_contents($verbose);
            error_log("SMS Gateway Debug: $verbose_log");
            if (isset($log_file)) {
                file_put_contents($log_file, date('[Y-m-d H:i:s]') . " Erreur détaillée: $verbose_log\n", FILE_APPEND);
            }
            fclose($verbose);
        }
        
        curl_close($curl);
        return [
            'success' => false,
            'message' => "Erreur cURL: $error",
            'code' => $http_code
        ];
    }
    
    // Fermeture de la session cURL
    curl_close($curl);
    
    // Décodage de la réponse
    $result = json_decode($response, true);
    
    // Les codes HTTP 200 et 202 indiquent un succès
    if (($http_code == 200 || $http_code == 202) && $result) {
        return [
            'success' => true,
            'message' => 'SMS envoyé avec succès',
            'response' => $result
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Erreur lors de l\'envoi du SMS - Code: ' . $http_code,
            'response' => $result
        ];
    }
}

/**
 * ======= GUIDE D'UTILISATION DE L'API SMS GATEWAY =======
 * 
 * 1. MODES DE FONCTIONNEMENT
 * --------------------------
 * SMS Gateway pour Android propose trois modes de fonctionnement:
 * 
 * a) Local Server:
 *    - Fonctionne en réseau local uniquement
 *    - URL: http://IP_LOCALE:PORT/api
 *    - Idéal pour les tests ou environnements fermés
 * 
 * b) Cloud Server (Recommandé):
 *    - Utilise les serveurs publics
 *    - URL: https://api.sms-gate.app/3rdparty/v1/message
 *    - Nécessite une connexion Internet mais simplifie la configuration
 * 
 * c) Private Server:
 *    - Déploiement sur votre propre serveur
 *    - Meilleure sécurité et confidentialité
 *    - Nécessite plus de configuration technique
 * 
 * 2. CONFIGURATION REQUISE
 * ------------------------
 * - Application SMS Gateway installée sur un appareil Android
 * - Mode "Cloud Server" activé dans l'application
 * - Appareil Android connecté à Internet
 * - Identifiants d'API (username/password) configurés
 * 
 * 3. FORMAT DE LA REQUÊTE
 * -----------------------
 * - Méthode: POST
 * - En-têtes: Content-Type: application/json
 * - Authentification: Basic Auth (username:password)
 * - Corps: JSON avec deux champs obligatoires:
 *   {
 *     "message": "Contenu du SMS à envoyer",
 *     "phoneNumbers": ["+33612345678", "+33687654321"]  // Tableau de numéros
 *   }
 * 
 * 4. GESTION DES ERREURS
 * ----------------------
 * - Vérifiez toujours le code HTTP de la réponse
 * - 200/202: Succès - Le SMS a été accepté pour envoi
 * - 401: Erreur d'authentification - Vérifiez vos identifiants
 * - 400: Erreur de requête - Format JSON incorrect
 * - 500: Erreur serveur - Problème côté serveur
 * 
 * 5. ASTUCES
 * ----------
 * - Utilisez toujours des numéros au format international (+33...)
 * - Un SMS est limité à 160 caractères (plus = messages multiples)
 * - Pour les tests, utilisez d'abord le mode Local Server
 * - Gardez l'application sur l'appareil Android en cours d'exécution
 * 
 * 6. EXEMPLE D'UTILISATION
 * -----------------------
 * <?php
 * $result = send_sms_example('+33612345678', 'Ceci est un test de SMS via l\'API Gateway');
 * if ($result['success']) {
 *     echo "Message envoyé avec succès!";
 * } else {
 *     echo "Erreur: " . $result['message'];
 * }
 * ?>
 * 
 * 7. DANS NOTRE PROJET
 * --------------------
 * Dans ce projet, la fonction send_sms() du fichier includes/functions.php
 * est déjà configurée pour utiliser l'API SMS Gateway. Vous pouvez l'utiliser
 * directement en fournissant un numéro et un message:
 * 
 * <?php
 * require_once 'includes/functions.php';
 * $result = send_sms('+33612345678', 'Votre message ici');
 * ?>
 */

// Point d'entrée API pour une intégration avec JavaScript/AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accepter les requêtes JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($input['recipient']) && isset($input['message'])) {
        // Formater le numéro de téléphone si nécessaire
        $recipient = $input['recipient'];
        // S'assurer que le numéro commence par un +
        if (!preg_match('/^\+[0-9]{10,15}$/', $recipient)) {
            // Si le numéro commence par un 0, le remplacer par +33 (pour la France)
            if (substr($recipient, 0, 1) === '0') {
                $recipient = '+33' . substr($recipient, 1);
            }
            // Si le numéro commence déjà par 33, ajouter le +
            else if (substr($recipient, 0, 2) === '33') {
                $recipient = '+' . $recipient;
            }
            // Dans tous les autres cas, simplement ajouter le + au début si pas déjà présent
            else if (substr($recipient, 0, 1) !== '+') {
                $recipient = '+' . $recipient;
            }
        }
        
        // Utiliser la fonction d'exemple ou la fonction du système
        if (function_exists('send_sms') && !isset($input['use_example'])) {
            require_once '../includes/functions.php';
            $result = send_sms($recipient, $input['message']);
        } else {
            $result = send_sms_example($recipient, $input['message']);
        }
        
        // Retourner la réponse en JSON
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else {
        // Requête invalide
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Requête invalide. Les paramètres "recipient" et "message" sont requis.'
        ]);
        exit;
    }
}

// Test simple si exécuté directement
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__) && isset($_GET['test'])) {
    header('Content-Type: text/plain');
    echo "=== TEST SMS GATEWAY ===\n\n";
    
    $test_number = $_GET['number'] ?? '+33600000000';
    $test_message = $_GET['message'] ?? 'Test SMS Gateway ' . date('H:i:s');
    
    echo "Envoi d'un SMS de test à $test_number\n";
    echo "Message: $test_message\n\n";
    
    $result = send_sms_example($test_number, $test_message);
    
    echo "Résultat: " . ($result['success'] ? 'SUCCÈS' : 'ÉCHEC') . "\n";
    echo "Message: " . $result['message'] . "\n\n";
    
    if (isset($result['response']) && is_array($result['response'])) {
        echo "Détails de la réponse:\n";
        print_r($result['response']);
    }
    
    exit;
} 