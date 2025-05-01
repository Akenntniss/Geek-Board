<?php
// Configuration et sécurité
header('Content-Type: application/json');
define('SECRET_KEY', '12345678'); // À changer pour votre sécurité

// Connexion à la base de données
require_once '../database.php';

// Log des requêtes
function logRequest($type, $data) {
    $log_file = '../logs/sms_sync_' . date('Y-m-d') . '.log';
    $log_data = date('Y-m-d H:i:s') . " - $type: " . json_encode($data) . "\n";
    file_put_contents($log_file, $log_data, FILE_APPEND);
}

// Vérification de la clé d'API
function checkApiKey() {
    $provided_key = isset($_POST['secret']) ? $_POST['secret'] : 
                   (isset($_GET['secret']) ? $_GET['secret'] : '');
    
    if ($provided_key !== SECRET_KEY) {
        echo json_encode([
            'payload' => [
                'success' => false,
                'error' => 'Clé API invalide'
            ]
        ]);
        exit;
    }
    return true;
}

// Table pour stocker les SMS
function createSmsTableIfNotExist($db) {
    $query = "CREATE TABLE IF NOT EXISTS sms_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone_number VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL
    )";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
}

// Initialisation
// Utilisation directe de $pdo défini dans database.php
$db = $pdo;
createSmsTableIfNotExist($db);
logRequest('REQUEST', $_REQUEST);

// Réponse par défaut pour le test d'intégration
if (empty($_POST) && empty($_GET)) {
    // SMSSync teste l'endpoint sans paramètres, donc on retourne une réponse valide
    echo json_encode([
        'payload' => [
            'success' => true,
            'error' => null
        ]
    ]);
    exit;
}

// Traitement par type de requête
$task = isset($_POST['task']) ? $_POST['task'] : 
       (isset($_GET['task']) ? $_GET['task'] : '');

switch ($task) {
    // Réception de SMS depuis le téléphone
    case 'sent':
        checkApiKey();
        
        $from = $_POST['from'] ?? '';
        $message = $_POST['message'] ?? '';
        $timestamp = $_POST['sent_timestamp'] ?? date('Y-m-d H:i:s');
        $uuid = $_POST['message_id'] ?? '';
        
        // Log et traitement des SMS reçus
        logRequest('SMS_RECEIVED', [
            'from' => $from,
            'message' => $message,
            'timestamp' => $timestamp,
            'uuid' => $uuid
        ]);
        
        // Traitement spécifique des SMS reçus (à personnaliser)
        
        echo json_encode([
            'payload' => [
                'success' => true,
                'error' => null
            ]
        ]);
        break;
    
    // Envoi de SMS au téléphone
    case 'send':
        checkApiKey();
        
        $response = [
            'payload' => [
                'success' => true,
                'error' => null,
                'task' => 'send',
                'messages' => []
            ]
        ];
        
        // Récupérer les SMS en attente
        $query = "SELECT id, phone_number, message FROM sms_messages WHERE status = 'pending' LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $pending_sms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($pending_sms as $sms) {
            $response['payload']['messages'][] = [
                'to' => $sms['phone_number'],
                'message' => $sms['message'],
                'uuid' => (string)$sms['id'] // Conversion en string pour compatibilité
            ];
            
            // Marquer comme "en cours d'envoi"
            $update = "UPDATE sms_messages SET status = 'sending' WHERE id = :id";
            $stmt = $db->prepare($update);
            $stmt->bindParam(':id', $sms['id']);
            $stmt->execute();
        }
        
        echo json_encode($response);
        break;
    
    // Mise à jour du statut d'envoi
    case 'result':
        checkApiKey();
        
        $uuid = $_POST['uuid'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if ($uuid && $status) {
            // Mettre à jour le statut dans la base de données
            $query = "UPDATE sms_messages SET status = :status, sent_at = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $uuid);
            $stmt->execute();
            
            logRequest('SMS_STATUS_UPDATE', [
                'uuid' => $uuid,
                'status' => $status
            ]);
        }
        
        echo json_encode([
            'payload' => [
                'success' => true,
                'error' => null
            ]
        ]);
        break;
    
    // Requête pour ajouter un SMS à envoyer (API interne)
    case 'queue':
        checkApiKey();
        
        $to = $_POST['to'] ?? '';
        $message = $_POST['message'] ?? '';
        
        if (empty($to) || empty($message)) {
            echo json_encode([
                'success' => false,
                'error' => 'Numéro de téléphone ou message manquant'
            ]);
            exit;
        }
        
        // Ajouter le SMS à la file d'attente
        $query = "INSERT INTO sms_messages (phone_number, message, status) VALUES (:phone, :message, 'pending')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':phone', $to);
        $stmt->bindParam(':message', $message);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'SMS ajouté à la file d\'attente',
                'id' => $db->lastInsertId()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de l\'ajout du SMS'
            ]);
        }
        break;
    
    default:
        // Réponse par défaut pour le ping initial de SMSSync
        echo json_encode([
            'payload' => [
                'success' => true,
                'error' => null,
                'task' => 'send',
                'messages' => []
            ]
        ]);
        break;
} 