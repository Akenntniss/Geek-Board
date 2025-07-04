<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Inclure la configuration de la base de données et les fonctions utilitaires
require_once '../config/database.php';
require_once '../config/functions.php';

// Log pour le débogage
error_log("Appel de direct_send_sms.php");
error_log("POST: " . print_r($_POST, true));

// Vérifier que les paramètres nécessaires sont fournis
if (!isset($_POST['telephone']) || empty($_POST['telephone']) || !isset($_POST['message']) || empty($_POST['message'])) {
    error_log("Paramètres manquants dans direct_send_sms.php");
    error_log("POST: " . print_r($_POST, true));
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$telephone = trim($_POST['telephone']);
$message = trim($_POST['message']);
$reparation_id = isset($_POST['reparation_id']) ? (int)$_POST['reparation_id'] : null;
$template_id = isset($_POST['template_id']) ? (int)$_POST['template_id'] : null;
$statut_id = isset($_POST['statut_id']) ? (int)$_POST['statut_id'] : null;

// Normaliser le numéro de téléphone (enlever les espaces, tirets, etc.)
$telephone = preg_replace('/[^0-9+]/', '', $telephone);

// Vérifier que le numéro n'est pas vide après normalisation
if (empty($telephone)) {
    error_log("Numéro de téléphone invalide après normalisation");
    echo json_encode(['success' => false, 'message' => 'Numéro de téléphone invalide']);
    exit;
}

try {
    // Obtenir les informations de configuration SMS depuis la base de données
    $main_pdo = getMainDBConnection();
    $stmt = $main_pdo->prepare("SELECT * FROM settings WHERE category = 'sms'");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Configuration de l'API SMS Gateway - votre API personnalisée
    $API_URL = 'http://168.231.85.4:3001/api/messages/send';
    
    // Formatage du numéro de téléphone
    $recipient = $telephone;
    $recipient = preg_replace('/[^0-9+]/', '', $recipient); // Supprimer tous les caractères non numériques sauf +
    
    // S'assurer que le numéro commence par un +
    if (substr($recipient, 0, 1) !== '+') {
        // Si pas de +, vérifier si c'est un numéro français commençant par 0
        if (substr($recipient, 0, 1) === '0' && strlen($recipient) === 10) {
            $recipient = '+33' . substr($recipient, 1); // Convertir 0X XX XX XX XX en +33X XX XX XX XX
        } else {
            // Sinon, simplement ajouter un + devant (peut ne pas être correct mais mieux que rien)
            $recipient = '+' . $recipient;
        }
    }
    
    error_log("Numéro formaté pour l'envoi: " . $recipient);
    
    // Construire les données pour l'API
    $sms_data = json_encode([
        'recipient' => $recipient,
        'message' => $message,
        'priority' => 'normal'
    ]);
    
    // Initialiser cURL
    $curl = curl_init($API_URL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $sms_data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    // Activer le débogage verbeux
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($curl, CURLOPT_VERBOSE, true);
    curl_setopt($curl, CURLOPT_STDERR, $verbose);
    
    // Exécuter la requête
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    // Log de l'envoi
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    error_log("Détails de la requête cURL: " . $verbose_log);
    error_log("Statut de la réponse: " . $status);
    error_log("Réponse: " . $response);
    
    if (curl_errno($curl)) {
        $curl_error = curl_error($curl);
        error_log("Erreur cURL: " . $curl_error);
        error_log("Détails: " . $verbose_log);
        $result = [
            'success' => false,
            'message' => "Erreur cURL: " . $curl_error,
            'response' => null
        ];
    } else {
        // Traitement de la réponse
        $response_data = json_decode($response, true);
        
        // Vérifier le succès selon le format de votre API
        if (($status == 200 || $status == 202) && $response_data && isset($response_data['success']) && $response_data['success']) {
            error_log("Envoi SMS réussi - ID: " . ($response_data['data']['message_id'] ?? 'N/A'));
            $result = [
                'success' => true, 
                'message' => 'SMS envoyé avec succès',
                'response' => $response_data
            ];
            
            // Si l'envoi a réussi et qu'on a un ID de réparation, enregistrer l'envoi dans la base de données
            if ($reparation_id) {
                try {
                    $shop_pdo = getShopDBConnection();
                    
                    if ($shop_pdo) {
                        $stmt_log = $shop_pdo->prepare("
                            INSERT INTO reparation_sms (reparation_id, template_id, telephone, message, date_envoi, statut_id)
                            VALUES (?, ?, ?, ?, NOW(), ?)
                        ");
                        
                        $stmt_log->execute([
                            $reparation_id,
                            $template_id,
                            $telephone,
                            $message,
                            $statut_id
                        ]);
                        
                        error_log("Enregistrement de l'envoi de SMS réussi pour la réparation #$reparation_id");
                    } else {
                        error_log("Impossible d'obtenir une connexion à la base de données du magasin pour enregistrer l'envoi de SMS");
                    }
                } catch (PDOException $e) {
                    error_log("Erreur lors de l'enregistrement de l'envoi de SMS: " . $e->getMessage());
                }
            }
        } else {
            $error_message = $response_data['message'] ?? 'Erreur inconnue';
            error_log("Échec de l'envoi SMS: Code $status - $error_message");
            $result = [
                'success' => false,
                'message' => "Erreur lors de l'envoi du SMS: $error_message",
                'response' => $response_data
            ];
        }
    }
    
    curl_close($curl);
    if (isset($verbose) && is_resource($verbose)) {
        fclose($verbose);
    }
    
    // Retourner le résultat
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Exception lors de l'envoi du SMS: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du SMS: ' . $e->getMessage()]);
} 