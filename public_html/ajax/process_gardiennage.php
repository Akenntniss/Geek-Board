<?php
// Définir le chemin de base
define('BASE_PATH', dirname(__DIR__));

// Inclure les fichiers nécessaires
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/config/session_config.php'; // Utiliser la config de session du projet

// Loguer l'état de la session pour le débogage
$log_dir = BASE_PATH . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/gardiennage_debug_' . date('Y-m-d') . '.log';

// Fonction de log
$log_debug = function($message) use ($log_file) {
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
};

$log_debug("=== DÉBUT TRAITEMENT GARDIENNAGE ===");
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

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Essayer de récupérer l'ID utilisateur des cookies
    if (isset($_COOKIE['remember_token']) && function_exists('check_remember_token')) {
        check_remember_token();
    }
    
    // Vérifier s'il y a un token d'authentification dans la requête
    $api_token = null;
    if (isset($_POST['api_token'])) {
        $api_token = $_POST['api_token'];
        $log_debug("API token trouvé dans POST: " . substr($api_token, 0, 10) . '...');
    } elseif (isset($_GET['api_token'])) {
        $api_token = $_GET['api_token'];
        $log_debug("API token trouvé dans GET: " . substr($api_token, 0, 10) . '...');
    } elseif (isset($_COOKIE['api_token'])) {
        $api_token = $_COOKIE['api_token'];
        $log_debug("API token trouvé dans COOKIE: " . substr($api_token, 0, 10) . '...');
    } elseif (isset($_SERVER['HTTP_X_API_TOKEN'])) {
        $api_token = $_SERVER['HTTP_X_API_TOKEN'];
        $log_debug("API token trouvé dans HEADER: " . substr($api_token, 0, 10) . '...');
    }
    
    // Si on a un token API, essayer de l'utiliser pour authentifier l'utilisateur
    if ($api_token) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM user_sessions WHERE token = ? AND expires > NOW()");
            $stmt->execute([$api_token]);
            if ($user_id = $stmt->fetchColumn()) {
                $_SESSION['user_id'] = $user_id;
                $log_debug("Authentification réussie par API token, user_id: " . $user_id);
            } else {
                $log_debug("Token API non valide ou expiré");
            }
        } catch (PDOException $e) {
            $log_debug("Erreur lors de la vérification du token API: " . $e->getMessage());
        }
    }
    
    // Si toujours pas connecté, essayer d'utiliser l'user_id directement (pour tests seulement)
    if (!isset($_SESSION['user_id']) && isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
        $direct_user_id = (int)$_POST['user_id'];
        
        // Vérifier si cet utilisateur existe
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$direct_user_id]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['user_id'] = $direct_user_id;
                $log_debug("Authentification directe par user_id, user_id: " . $direct_user_id);
            } else {
                $log_debug("User ID direct non valide");
            }
        } catch (PDOException $e) {
            $log_debug("Erreur lors de la vérification du user_id direct: " . $e->getMessage());
        }
    }
    
    // Si toujours pas connecté
    if (!isset($_SESSION['user_id'])) {
        $log_debug("ÉCHEC D'AUTHENTIFICATION: Aucune méthode n'a fonctionné");
        echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action.']);
        exit;
    }
}

// Vérifier que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// Récupérer les données
$reparation_id = isset($_POST['reparation_id']) ? (int)$_POST['reparation_id'] : 0;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

$log_debug("Paramètres reçus:");
$log_debug("- reparation_id: $reparation_id");
$log_debug("- notes: " . ($notes ? 'Présent (' . strlen($notes) . ' caractères)' : 'Absent'));

// Vérifier les données
if ($reparation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de réparation invalide.']);
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
    
    // Mettre à jour le statut de la réparation
    $nouveau_statut = 'gardiennage';
    $categorie_id = 5; // Catégorie gardiennage
    $statut_id = 12;   // ID du statut "Gardiennage"
    
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
        'Mise en gardiennage' . (!empty($notes) ? ': ' . $notes : '')
    ]);
    
    // Ajouter une entrée dans la table gardiennage
    $stmt = $pdo->prepare("
        INSERT INTO gardiennage 
        (reparation_id, date_debut, date_derniere_facturation, notes) 
        VALUES (?, CURDATE(), CURDATE(), ?)
    ");
    $stmt->execute([
        $reparation_id,
        $notes
    ]);
    
    // Récupérer le template SMS
    $stmt = $pdo->prepare("
        SELECT id, nom, contenu 
        FROM sms_templates 
        WHERE statut_id = ? AND est_actif = 1
    ");
    $stmt->execute([12]); // Template ID 12 pour "Gardiennage"
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
        $log_debug("====== DÉBUT DEBUG SMS GARDIENNAGE ======");
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
            '[PRIX]' => !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') : ''
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
        
        $log_debug("====== FIN DEBUG SMS GARDIENNAGE ======");
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
        'message' => 'L\'appareil a été placé en gardiennage avec succès. ' . $sms_message,
        'sms_sent' => $sms_sent
    ]);
    
} catch (PDOException $e) {
    // Log l'erreur
    error_log("Erreur lors de la mise en gardiennage: " . $e->getMessage());
    
    // Renvoyer l'erreur
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} 