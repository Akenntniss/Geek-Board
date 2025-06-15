<?php
/**
 * Fichier AJAX pour envoyer des SMS aux clients
 * Supporte les templates prédéfinis et les messages personnalisés
 */

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Initialiser shop_id si non défini
if (!isset($_SESSION['shop_id'])) {
    if (isset($_GET['shop_id'])) {
        $_SESSION['shop_id'] = (int)$_GET['shop_id'];
    } else {
        $_SESSION['shop_id'] = 1;
    }
}

// Inclusion de la configuration de base de données
require_once '../config/database.php';

// Vérification de la méthode et des paramètres
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Données JSON invalides']);
    exit;
}

$client_id = isset($input['client_id']) ? (int)$input['client_id'] : 0;
$telephone = isset($input['telephone']) ? trim($input['telephone']) : '';
$message_type = isset($input['message_type']) ? $input['message_type'] : 'custom'; // 'template' ou 'custom'
$template_id = isset($input['template_id']) ? (int)$input['template_id'] : 0;
$custom_message = isset($input['custom_message']) ? trim($input['custom_message']) : '';

// Validation des paramètres
if ($client_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID client invalide']);
    exit;
}

if (empty($telephone)) {
    echo json_encode(['success' => false, 'error' => 'Numéro de téléphone requis']);
    exit;
}

if ($message_type === 'template' && $template_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Template SMS requis']);
    exit;
}

if ($message_type === 'custom' && empty($custom_message)) {
    echo json_encode(['success' => false, 'error' => 'Message personnalisé requis']);
    exit;
}

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception("Impossible d'obtenir la connexion à la base de données");
    }
    
    // Récupérer les informations du client
    $stmt = $shop_pdo->prepare("SELECT nom, prenom FROM clients WHERE id = :client_id");
    $stmt->execute(['client_id' => $client_id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        echo json_encode(['success' => false, 'error' => 'Client non trouvé']);
        exit;
    }
    
    $message_final = '';
    $template_used = null;
    
    if ($message_type === 'template') {
        // Récupérer le template
        $stmt = $shop_pdo->prepare("SELECT * FROM sms_templates WHERE id = :template_id AND est_actif = 1");
        $stmt->execute(['template_id' => $template_id]);
        $template = $stmt->fetch();
        
        if (!$template) {
            echo json_encode(['success' => false, 'error' => 'Template SMS non trouvé']);
            exit;
        }
        
        $template_used = $template;
        $message_final = $template['contenu'];
        
        // Remplacer les variables dans le template
        $variables = [
            'CLIENT_NOM' => $client['nom'],
            'CLIENT_PRENOM' => $client['prenom'],
            'DATE' => date('d/m/Y')
        ];
        
        foreach ($variables as $var => $value) {
            $message_final = str_replace('{' . $var . '}', $value, $message_final);
        }
        
    } else {
        // Message personnalisé
        $message_final = $custom_message;
    }
    
    // Validation de la longueur du message
    if (strlen($message_final) > 160) {
        echo json_encode([
            'success' => false, 
            'error' => 'Message trop long (' . strlen($message_final) . ' caractères, maximum 160)'
        ]);
        exit;
    }
    
    // Nettoyer le numéro de téléphone
    $telephone_clean = preg_replace('/[^0-9+]/', '', $telephone);
    
    // Validation du numéro de téléphone français
    if (!preg_match('/^(?:\+33|0)[1-9](?:[0-9]{8})$/', $telephone_clean)) {
        echo json_encode(['success' => false, 'error' => 'Numéro de téléphone invalide']);
        exit;
    }
    
    // Simuler l'envoi SMS (remplacer par votre API SMS réelle)
    $sms_sent = simulateSmsSend($telephone_clean, $message_final);
    
    if ($sms_sent) {
        // Enregistrer dans les logs SMS
        $stmt = $shop_pdo->prepare("
            INSERT INTO sms_logs (recipient, message, sent_at, status, client_id, template_id) 
            VALUES (:recipient, :message, NOW(), 'sent', :client_id, :template_id)
        ");
        
        $stmt->execute([
            'recipient' => $telephone_clean,
            'message' => $message_final,
            'client_id' => $client_id,
            'template_id' => $template_used ? $template_used['id'] : null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'SMS envoyé avec succès',
            'details' => [
                'recipient' => $telephone_clean,
                'message_length' => strlen($message_final),
                'template_used' => $template_used ? $template_used['nom'] : 'Message personnalisé'
            ]
        ]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Échec de l\'envoi du SMS']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO dans send_client_sms.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données']);
} catch (Exception $e) {
    error_log("Erreur générale dans send_client_sms.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}

/**
 * Fonction pour simuler l'envoi de SMS
 * À remplacer par votre véritable API SMS
 */
function simulateSmsSend($telephone, $message) {
    // Simulation d'un délai d'envoi
    usleep(500000); // 0.5 seconde
    
    // Simuler un succès dans 95% des cas
    return (rand(1, 100) <= 95);
    
    // Exemple d'intégration avec une vraie API SMS :
    /*
    $api_url = 'https://api.sms-provider.com/send';
    $api_key = 'your-api-key';
    
    $data = [
        'to' => $telephone,
        'message' => $message,
        'from' => 'YourBusiness'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code === 200;
    */
}
?> 