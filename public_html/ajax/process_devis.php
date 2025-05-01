<?php
// Définir le chemin de base
define('BASE_PATH', dirname(__DIR__));

// Inclure les fichiers nécessaires
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/config/session_config.php'; // Utiliser la config de session du projet

// Vérifier si l'utilisateur est connecté
// Méthode alternative d'authentification via l'ID utilisateur fourni par AJAX
$user_id = null;
$is_authenticated = false;

// Vérifier d'abord la session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $is_authenticated = true;
    error_log("Utilisateur authentifié via SESSION: " . $user_id);
} 
// Vérifier ensuite le paramètre user_id fourni par AJAX
elseif (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    // Valider l'utilisateur dans la base de données
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->rowCount() > 0) {
            $is_authenticated = true;
            // Définir l'utilisateur dans la session pour les futurs appels
            $_SESSION['user_id'] = $user_id;
            error_log("Utilisateur authentifié via AJAX parameter: " . $user_id);
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification de l'utilisateur: " . $e->getMessage());
    }
}

// Si l'authentification échoue, renvoyer une erreur
if (!$is_authenticated) {
    error_log("Non authentifié - session: " . json_encode($_SESSION) . " - POST: " . json_encode($_POST));
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// Loguer l'état de la session pour le débogage
$log_dir = BASE_PATH . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/devis_debug_' . date('Y-m-d') . '.log';

// Fonction de log
$log_debug = function($message) use ($log_file) {
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
};

$log_debug("=== DÉBUT TRAITEMENT DEVIS ===");
$log_debug("Session ID: " . session_id());
$log_debug("Session name: " . session_name());
$log_debug("User ID in session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Non défini'));
$log_debug("Remember token cookie: " . (isset($_COOKIE['remember_token']) ? 'Présent' : 'Absent'));
$log_debug("MDGEEK_SESSION cookie: " . (isset($_COOKIE['MDGEEK_SESSION']) ? 'Présent' : 'Absent'));

// Loguer tous les cookies
$log_debug("--- COOKIES ---");
foreach ($_COOKIE as $key => $value) {
    $log_debug("Cookie $key: " . (is_array($value) ? json_encode($value) : $value));
}

// Loguer les headers pertinents
$log_debug("--- HEADERS ---");
// Fonction de secours si getallheaders() n'existe pas
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
$headers = getallheaders();
foreach ($headers as $key => $value) {
    if (strpos(strtolower($key), 'cookie') !== false || 
        strpos(strtolower($key), 'content') !== false || 
        strpos(strtolower($key), 'origin') !== false || 
        strpos(strtolower($key), 'referer') !== false || 
        strpos(strtolower($key), 'host') !== false) {
        $log_debug("Header $key: $value");
    }
}

// Récupérer les données
$reparation_id = isset($_POST['reparation_id']) ? (int)$_POST['reparation_id'] : 0;
$montant = isset($_POST['montant']) ? (float)$_POST['montant'] : 0;
$update_prix = isset($_POST['update_prix']) && $_POST['update_prix'] === '1';

$log_debug("Paramètres reçus:");
$log_debug("- reparation_id: $reparation_id");
$log_debug("- montant: $montant");
$log_debug("- update_prix: " . ($update_prix ? 'Oui' : 'Non'));

// Vérifier les données
if ($reparation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de réparation invalide.']);
    exit;
}

if ($montant <= 0) {
    echo json_encode(['success' => false, 'message' => 'Montant invalide.']);
    exit;
}

try {
    // Récupérer les informations de la réparation
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.id as client_id
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        echo json_encode(['success' => false, 'message' => 'Réparation non trouvée.']);
        exit;
    }
    
    // Récupérer l'ancien statut pour le log
    $ancien_statut = $reparation['statut'];
    
    // Mettre à jour le prix si demandé
    if ($update_prix) {
        $stmt = $pdo->prepare("UPDATE reparations SET prix_reparation = ? WHERE id = ?");
        $stmt->execute([$montant, $reparation_id]);
    }
    
    // Mettre à jour le statut de la réparation
    $nouveau_statut = 'en_attente_accord_client';
    $categorie_id = 3; // Catégorie "En attente"
    $statut_id = 6;    // ID du statut "En attente de l'accord client"
    
    $stmt = $pdo->prepare("UPDATE reparations SET statut = ?, statut_categorie = ?, date_modification = NOW() WHERE id = ?");
    $stmt->execute([$nouveau_statut, $categorie_id, $reparation_id]);
    
    // Enregistrer le changement dans les logs
    $stmt = $pdo->prepare("
        INSERT INTO reparation_logs 
        (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $reparation_id,
        $_SESSION['user_id'],
        'changement_statut',
        $ancien_statut,
        $nouveau_statut,
        'Envoi de devis au client: ' . $montant . ' €'
    ]);
    
    // Récupérer le template SMS
    $stmt = $pdo->prepare("
        SELECT id, nom, contenu 
        FROM sms_templates 
        WHERE statut_id = ? AND est_actif = 1
    ");
    $stmt->execute([4]); // Template ID 4 pour "En attente de validation"
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sms_sent = false;
    $sms_message = '';
    
    if ($template && !empty($reparation['client_telephone'])) {
        // Préparer le contenu du SMS en remplaçant les variables
        $message = $template['contenu'];
        
        // Créer un fichier de log dans un dossier accessible
        $log_dir = BASE_PATH . '/logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $log_file = $log_dir . '/sms_debug_' . date('Y-m-d') . '.log';
        
        // Fonction de log
        $log_debug = function($message) use ($log_file) {
            file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
        };
        
        // Logs avant remplacement
        $log_debug("====== DÉBUT DEBUG SMS DEVIS ======");
        $log_debug("Template ID: " . $template['id']);
        $log_debug("Template nom: " . $template['nom']);
        $log_debug("Template contenu brut: " . $template['contenu']);
        $log_debug("Réparation ID: " . $reparation_id);
        
        // Tableau des remplacements
        $replacements = [
            '[CLIENT_NOM]' => $reparation['client_nom'],
            '[CLIENT_PRENOM]' => $reparation['client_prenom'],
            '[CLIENT_TELEPHONE]' => $reparation['client_telephone'],
            '[REPARATION_ID]' => $reparation_id,
            '[APPAREIL_TYPE]' => $reparation['type_appareil'],
            '[APPAREIL_MARQUE]' => $reparation['marque'],
            '[APPAREIL_MODELE]' => $reparation['modele'],
            '[DATE_RECEPTION]' => format_date($reparation['date_reception']),
            '[DATE_FIN_PREVUE]' => format_date($reparation['date_fin_prevue'] ?? ''),
            '[PRIX]' => number_format($montant, 2, ',', ' ')
        ];
        
        // Log des variables et leurs valeurs
        $log_debug("Variables à remplacer:");
        foreach ($replacements as $var => $value) {
            $log_debug("  {$var} => " . (is_null($value) ? "NULL" : "'{$value}'"));
        }
        
        // Message avant remplacement
        $log_debug("Message avant remplacement: " . $message);
        
        // Effectuer les remplacements
        foreach ($replacements as $var => $value) {
            // Log pour chaque remplacement
            $old_message = $message;
            $message = str_replace($var, $value, $message);
            $log_debug("Remplacement de '{$var}' par '{$value}': " . ($old_message === $message ? "AUCUN CHANGEMENT" : "OK"));
        }
        
        // Correction spécifique pour le problème [CLIENT_NOM][CLIENT_PRENOM] sans espace
        if (strpos($message, '][') !== false) {
            $log_debug("Détection de variables collées sans espace ']]['");
            $old_message = $message;
            $message = str_replace('][', '] [', $message);
            $log_debug("Correction des variables collées: " . ($old_message === $message ? "AUCUN CHANGEMENT" : "OK - " . $message));
        }
        
        // Log du message final
        $log_debug("Message final après remplacement: " . $message);
        
        // Envoyer le SMS
        if (function_exists('send_sms')) {
            $log_debug("Tentative d'envoi SMS à " . $reparation['client_telephone']);
            $sms_result = send_sms($reparation['client_telephone'], $message);
            $log_debug("Résultat de l'envoi: " . ($sms_result['success'] ? "SUCCÈS" : "ÉCHEC - " . ($sms_result['message'] ?? "Erreur inconnue")));
            
            if ($sms_result['success']) {
                $sms_sent = true;
                
                // Enregistrer l'envoi du SMS dans la base de données
                $stmt = $pdo->prepare("
                    INSERT INTO reparation_sms (reparation_id, template_id, telephone, message, date_envoi, statut_id)
                    VALUES (?, ?, ?, ?, NOW(), ?)
                ");
                $stmt->execute([
                    $reparation_id, 
                    $template['id'], 
                    $reparation['client_telephone'], 
                    $message, 
                    $statut_id
                ]);
                
                $sms_message = 'Un SMS a été envoyé au client.';
            } else {
                $sms_message = "Erreur lors de l'envoi du SMS: " . $sms_result['message'];
            }
        } else {
            $log_debug("ERREUR: La fonction send_sms n'existe pas!");
            $sms_message = "La fonction d'envoi SMS n'est pas disponible.";
        }
        
        $log_debug("====== FIN DEBUG SMS DEVIS ======");
    } else {
        if (empty($template)) {
            $sms_message = "Aucun modèle SMS disponible pour ce statut.";
        } else {
            $sms_message = "Le client n'a pas de numéro de téléphone pour SMS.";
        }
    }
    
    // Renvoyer le résultat
    echo json_encode([
        'success' => true, 
        'message' => 'Le devis a été envoyé avec succès. ' . $sms_message,
        'sms_sent' => $sms_sent
    ]);
    
} catch (PDOException $e) {
    // Log l'erreur
    error_log("Erreur lors de l'envoi du devis: " . $e->getMessage());
    
    // Renvoyer l'erreur
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} 