<?php
/**
 * Endpoint principal pour SMSSync
 * 
 * Cet endpoint gère:
 * 1. La réception des SMS envoyés par le téléphone (method=sent)
 * 2. La récupération des SMS à envoyer par le téléphone (method=task)
 * 3. La mise à jour du statut des SMS envoyés (method=result)
 */

// Activer l'affichage des erreurs pour le débogage (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu JSON
header('Content-Type: application/json; charset=UTF-8');

// Inclure les fonctions
require_once 'functions.php';

// Journaliser la requête pour le débogage
$input = file_get_contents('php://input');
$request_log = "Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . "\n";
$request_log .= "Headers: " . json_encode(getallheaders()) . "\n";
$request_log .= "GET: " . json_encode($_GET) . "\n";
$request_log .= "POST: " . json_encode($_POST) . "\n";
$request_log .= "Body: " . $input . "\n";
file_put_contents(__DIR__ . '/smssync_requests.log', date('Y-m-d H:i:s') . " " . $request_log . "\n\n", FILE_APPEND);

// Vérifier l'authentification
$api_key = isset($_POST['secret']) ? $_POST['secret'] : (isset($_GET['secret']) ? $_GET['secret'] : null);

if (!$api_key || !verify_sms_api_key($api_key)) {
    echo json_encode([
        'payload' => [
            'success' => false,
            'error' => 'Clé API invalide ou manquante'
        ]
    ]);
    exit;
}

// Déterminer l'action à effectuer
$method = isset($_POST['task']) ? $_POST['task'] : (isset($_GET['task']) ? $_GET['task'] : 'task');

// Traiter les différentes méthodes
switch ($method) {
    // Recevoir les SMS envoyés à partir du téléphone
    case 'sent':
        handleIncomingSms();
        break;
    
    // Envoyer les tâches au téléphone (SMS à envoyer)
    case 'task':
    default:
        handleOutgoingSms();
        break;
    
    // Recevoir les résultats des envois de SMS
    case 'result':
        handleSmsResults();
        break;
}

/**
 * Gère les SMS entrants envoyés par SMSSync
 */
function handleIncomingSms() {
    // Format attendu de SMSSync pour les messages entrants
    $messages = isset($_POST['message_list']) ? json_decode($_POST['message_list'], true) : null;
    
    if (!$messages || !is_array($messages)) {
        echo json_encode([
            'payload' => [
                'success' => false,
                'error' => 'Format de données invalide'
            ]
        ]);
        return;
    }
    
    $success = true;
    $processed = 0;
    
    foreach ($messages as $message) {
        // Vérifier les champs obligatoires
        if (isset($message['from'], $message['message'], $message['timestamp'])) {
            // Enregistrer le SMS
            $sms_id = record_incoming_sms(
                $message['from'],
                $message['message'],
                date('Y-m-d H:i:s', $message['timestamp'] / 1000) // Convertir en format MySQL
            );
            
            if ($sms_id) {
                $processed++;
                // Créer une notification pour les administrateurs
                notify_new_sms($sms_id);
            } else {
                $success = false;
            }
        }
    }
    
    // Répondre à SMSSync
    echo json_encode([
        'payload' => [
            'success' => $success,
            'message' => "Traité $processed SMS"
        ],
        // Activer l'auto-synchronisation
        'payload' => [
            'success' => $success,
            'message' => "Traité $processed SMS"
        ]
    ]);
}

/**
 * Envoie les SMS en attente à SMSSync
 */
function handleOutgoingSms() {
    // Récupérer les SMS en attente
    $pending_sms = get_pending_sms(5); // Limiter à 5 SMS par requête
    
    $messages = [];
    foreach ($pending_sms as $sms) {
        $messages[] = [
            'to' => $sms['recipient'],
            'message' => $sms['message'],
            'uuid' => $sms['id'] // Utiliser l'ID comme référence unique
        ];
    }
    
    // Répondre à SMSSync avec la liste des messages à envoyer
    echo json_encode([
        'payload' => [
            'task' => 'send',
            'messages' => $messages
        ]
    ]);
}

/**
 * Traite les résultats d'envoi des SMS
 */
function handleSmsResults() {
    $results = isset($_POST['results']) ? json_decode($_POST['results'], true) : null;
    
    if (!$results || !is_array($results)) {
        echo json_encode([
            'payload' => [
                'success' => false,
                'error' => 'Format de données invalide'
            ]
        ]);
        return;
    }
    
    $processed = 0;
    
    foreach ($results as $result) {
        if (isset($result['uuid'], $result['status'])) {
            $sms_id = $result['uuid'];
            $status = strtolower($result['status']) === 'sent' ? 'sent' : 'failed';
            $timestamp_field = $status === 'sent' ? 'sent_timestamp' : null;
            
            if (update_sms_status($sms_id, $status, $timestamp_field)) {
                $processed++;
            }
        }
    }
    
    // Répondre à SMSSync
    echo json_encode([
        'payload' => [
            'success' => true,
            'message' => "Mis à jour $processed statuts de SMS"
        ]
    ]);
} 