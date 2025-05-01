<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Récupérer les chemins des fichiers includes
$config_path = realpath(__DIR__ . '/../config/database.php');
$functions_path = realpath(__DIR__ . '/../includes/functions.php');

if (!file_exists($config_path) || !file_exists($functions_path)) {
    echo json_encode([
        'success' => false,
        'message' => 'Fichiers de configuration introuvables.'
    ]);
    exit;
}

// Inclure les fichiers nécessaires
require_once $config_path;
require_once $functions_path;

// Journal de logs pour le débogage
$log_dir = $_SERVER['DOCUMENT_ROOT'] . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/sms_' . date('Y-m-d') . '.log';

function log_message($message) {
    global $log_file;
    $log_entry = date('[Y-m-d H:i:s] ') . $message . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée.'
    ]);
    exit;
}

log_message("=== TRAITEMENT REQUÊTE SMS ===");
log_message("Données reçues: " . json_encode($_POST));

// Récupérer les données du formulaire
$client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
$telephone = isset($_POST['telephone']) ? clean_input($_POST['telephone']) : '';
$message = isset($_POST['message']) ? $_POST['message'] : '';

// Vérifier que les données nécessaires sont présentes
if (empty($telephone) || empty($message)) {
    log_message("Erreur: Données manquantes (téléphone ou message)");
    echo json_encode([
        'success' => false,
        'message' => 'Le numéro de téléphone et le message sont requis.'
    ]);
    exit;
}

// Rechercher les variables dans le message qui nécessitent un remplacement
$variables = [
    '[CLIENT_NOM]',
    '[CLIENT_PRENOM]',
    '[CLIENT_TELEPHONE]',
    '[REPARATION_ID]',
    '[APPAREIL_TYPE]',
    '[APPAREIL_MARQUE]',
    '[APPAREIL_MODELE]',
    '[DATE_RECEPTION]',
    '[DATE_FIN_PREVUE]',
    '[PRIX]'
];

$variables_found = [];
foreach ($variables as $var) {
    if (strpos($message, $var) !== false) {
        $variables_found[] = $var;
    }
}

// Si des variables sont trouvées, récupérer les données correspondantes
if (!empty($variables_found) && $client_id > 0) {
    log_message("Variables à remplacer trouvées: " . implode(", ", $variables_found));
    
    try {
        // Récupérer les informations du client
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$client_id]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Récupérer la dernière réparation du client
        $stmt = $pdo->prepare("
            SELECT * FROM reparations 
            WHERE client_id = ? 
            ORDER BY date_reception DESC, id DESC 
            LIMIT 1
        ");
        $stmt->execute([$client_id]);
        $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        log_message("Données récupérées - Client: " . ($client ? "Oui" : "Non") . ", Réparation: " . ($reparation ? "Oui" : "Non"));
        
        // Remplacer les variables par les valeurs réelles
        if ($client) {
            $message = str_replace('[CLIENT_NOM]', $client['nom'], $message);
            $message = str_replace('[CLIENT_PRENOM]', $client['prenom'], $message);
            $message = str_replace('[CLIENT_TELEPHONE]', $client['telephone'], $message);
        }
        
        if ($reparation) {
            $message = str_replace('[REPARATION_ID]', $reparation['id'], $message);
            $message = str_replace('[APPAREIL_TYPE]', $reparation['type_appareil'], $message);
            $message = str_replace('[APPAREIL_MARQUE]', $reparation['marque'], $message);
            $message = str_replace('[APPAREIL_MODELE]', $reparation['modele'], $message);
            
            // Formater les dates
            if (!empty($reparation['date_reception'])) {
                $message = str_replace('[DATE_RECEPTION]', date('d/m/Y', strtotime($reparation['date_reception'])), $message);
            }
            
            if (!empty($reparation['date_fin_prevue'])) {
                $message = str_replace('[DATE_FIN_PREVUE]', date('d/m/Y', strtotime($reparation['date_fin_prevue'])), $message);
            }
            
            // Formater le prix
            if (isset($reparation['prix_reparation']) && $reparation['prix_reparation'] > 0) {
                $message = str_replace('[PRIX]', number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €', $message);
            } elseif (isset($reparation['prix']) && $reparation['prix'] > 0) {
                $message = str_replace('[PRIX]', number_format($reparation['prix'], 2, ',', ' ') . ' €', $message);
            }
        }
        
        log_message("Message après remplacement des variables: " . $message);
        
    } catch (PDOException $e) {
        log_message("Erreur lors de la récupération des données: " . $e->getMessage());
        // Continuer malgré l'erreur, en laissant les variables non remplacées
    }
}

// Envoyer le SMS
try {
    log_message("Envoi du SMS au numéro: " . $telephone);
    
    // Configuration de l'API SMS Gateway
    $API_URL = 'https://api.sms-gate.app/3rdparty/v1/message'; // URL CORRECTE selon la documentation
    $API_USERNAME = '-GCB75';
    $API_PASSWORD = 'Mamanmaman06400';
    
    // Formatage du numéro de téléphone si nécessaire
    $recipient = $telephone;
    $recipient = preg_replace('/[^0-9+]/', '', $recipient); // Supprimer tous les caractères non numériques sauf +
    
    // S'assurer que le numéro commence par un +
    if (substr($recipient, 0, 1) !== '+') {
        if (substr($recipient, 0, 1) === '0') {
            $recipient = '+33' . substr($recipient, 1);
        } else if (substr($recipient, 0, 2) === '33') {
            $recipient = '+' . $recipient;
        } else {
            $recipient = '+' . $recipient;
        }
    }
    
    log_message("Numéro formaté: $recipient");
    
    // Préparation des données JSON pour l'API
    $sms_data = json_encode([
        'message' => $message,
        'phoneNumbers' => [$recipient]
    ]);
    
    log_message("Données JSON: $sms_data");
    
    // Envoi du SMS via l'API SMS Gateway
    $curl = curl_init($API_URL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $sms_data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($sms_data)
    ]);
    
    // Configuration de l'authentification Basic
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "$API_USERNAME:$API_PASSWORD");
    
    // Ajouter des options pour le débogage
    curl_setopt($curl, CURLOPT_VERBOSE, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); 
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    
    // Capturer les messages d'erreur détaillés
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($curl, CURLOPT_STDERR, $verbose);
    
    // Exécution de la requête
    log_message("Exécution de la requête cURL...");
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    log_message("Code HTTP: $status");
    
    // Récupérer les informations d'erreur curl si échec
    $curl_error = '';
    if ($response === false) {
        $curl_error = curl_error($curl);
        rewind($verbose);
        $verbose_log = stream_get_contents($verbose);
        log_message("Erreur cURL: $curl_error");
        log_message("Détails: $verbose_log");
        $result = [
            'success' => false,
            'message' => "Erreur cURL: $curl_error",
            'response' => null
        ];
    } else {
        log_message("Réponse: $response");
        // Traitement de la réponse
        $response_data = json_decode($response, true);
        
        // Le code 202 indique une acceptation (Accepted) pour traitement asynchrone
        if (($status == 200 || $status == 202) && $response_data) {
            log_message("Envoi SMS réussi");
            $result = [
                'success' => true, 
                'message' => 'SMS envoyé avec succès',
                'response' => $response_data
            ];
        } else {
            log_message("Échec de l'envoi SMS: Code $status");
            $result = [
                'success' => false,
                'message' => "Erreur lors de l'envoi du SMS: Code $status",
                'response' => $response_data
            ];
        }
    }
    
    curl_close($curl);
    if (isset($verbose) && is_resource($verbose)) {
        fclose($verbose);
    }
    
    // Enregistrer l'envoi dans la table sms_logs
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $status_code = $status ?: ($result['success'] ? 200 : 400);
            $response_str = isset($result['response']) ? json_encode($result['response']) : '';
            
            $stmt = $pdo->prepare("INSERT INTO sms_logs (recipient, message, status, response, client_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$recipient, $message, $status_code, $response_str, $client_id]);
            
            $result['log_id'] = $pdo->lastInsertId();
            log_message("SMS enregistré dans les logs avec ID: " . $result['log_id']);
        } catch (PDOException $e) {
            log_message("Erreur lors de l'enregistrement du SMS: " . $e->getMessage());
        }
    }
    
    log_message("Résultat final: " . json_encode($result));
    echo json_encode($result);
    
} catch (Exception $e) {
    log_message("Exception lors de l'envoi du SMS: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi du SMS: ' . $e->getMessage()
    ]);
} 