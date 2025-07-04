<?php
// Désactiver l'affichage des erreurs PHP pour la production
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Activer la journalisation des erreurs
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Ajouter des logs pour le débogage de session
$logFile = __DIR__ . '/../logs/repair_assignment_debug.log';
file_put_contents($logFile, "--- Nouvelle requête d'attribution de réparation: " . date('Y-m-d H:i:s') . " ---\n", FILE_APPEND);
file_put_contents($logFile, "Session status before: " . session_status() . "\n", FILE_APPEND);
file_put_contents($logFile, "Session ID before: " . session_id() . "\n", FILE_APPEND);
file_put_contents($logFile, "COOKIE data: " . print_r($_COOKIE, true) . "\n", FILE_APPEND);

// Définir l'en-tête JSON
header('Content-Type: application/json');

// Tenter d'inclure la configuration de session d'abord
$session_config_path = realpath(__DIR__ . '/../config/session_config.php');
if (file_exists($session_config_path)) {
    file_put_contents($logFile, "Chargement de la configuration session_config.php\n", FILE_APPEND);
    require_once($session_config_path);
    // session_start() est déjà appelé dans session_config.php
} else {
    file_put_contents($logFile, "Fichier session_config.php non trouvé, utilisation de session_start() directement\n", FILE_APPEND);
    // Démarrer la session si nécessaire
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Inclure la configuration de la base de données et les fonctions
require_once('../config/database.php');
require_once('../includes/functions.php');

// Logs après le démarrage de session
file_put_contents($logFile, "Session status after start: " . session_status() . "\n", FILE_APPEND);
file_put_contents($logFile, "Session ID after start: " . session_id() . "\n", FILE_APPEND);
file_put_contents($logFile, "SESSION data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Récupérer l'ID du magasin, soit de GET, soit de SESSION
$shop_id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : null;
if ($shop_id === null) {
    $shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : null;
}
file_put_contents($logFile, "Shop ID utilisé: " . ($shop_id ?: 'non défini') . "\n", FILE_APPEND);

// Utiliser la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();
if (!$shop_pdo) {
    file_put_contents($logFile, "ERREUR: Impossible d'obtenir une connexion à la base de données du magasin\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => "Erreur de connexion à la base de données"
    ]);
    exit;
}

// Vérifier que nous sommes connectés à la bonne base de données
try {
    $check_db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
    $current_db_info = $check_db_stmt->fetch(PDO::FETCH_ASSOC);
    file_put_contents($logFile, "Base de données connectée: " . ($current_db_info['current_db'] ?? 'inconnue') . "\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($logFile, "Erreur lors de la vérification de la base de données: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    file_put_contents($logFile, "ERROR: Utilisateur non connecté\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action'
    ]);
    exit;
}

file_put_contents($logFile, "User ID trouvé: " . $_SESSION['user_id'] . "\n", FILE_APPEND);

// Récupérer les données de la requête
$data = json_decode(file_get_contents('php://input'), true);
file_put_contents($logFile, "Données reçues: " . print_r($data, true) . "\n", FILE_APPEND);

$reparation_id = isset($data['reparation_id']) ? intval($data['reparation_id']) : 0;
$action = isset($data['action']) ? $data['action'] : '';

// ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Vérifier que l'ID de réparation est valide
if ($reparation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de réparation invalide'
    ]);
    exit;
}

// Fonction pour vérifier si l'utilisateur a déjà une réparation active
function getUserActiveRepair($shop_pdo, $user_id) {
    // Vérifier si l'utilisateur a une réparation active dans la table users
    $stmt = $shop_pdo->prepare("SELECT active_repair_id FROM users WHERE id = ? AND active_repair_id IS NOT NULL");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['active_repair_id']) {
        // Récupérer les détails de la réparation active
        $stmt = $shop_pdo->prepare("
            SELECT r.*, s.nom as statut_nom
            FROM reparations r
            LEFT JOIN statuts s ON r.statut = s.code
            WHERE r.id = ?
        ");
        $stmt->execute([$result['active_repair_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return null;
}

// Fonction pour attribuer une réparation à un utilisateur
function assignRepairToUser($shop_pdo, $user_id, $reparation_id, $statut_code = null) {
    try {
        // Démarrer une transaction
        $shop_pdo->beginTransaction();
        
        // Mettre à jour l'employe_id dans la table réparations
        $stmt = $shop_pdo->prepare("
            UPDATE reparations 
            SET employe_id = ?, 
                date_modification = NOW()
                " . ($statut_code ? ", statut = ?" : "") . "
            WHERE id = ?
        ");
        
        if ($statut_code) {
            $stmt->execute([$user_id, $statut_code, $reparation_id]);
        } else {
            $stmt->execute([$user_id, $reparation_id]);
        }
        
        // Mettre à jour le champ active_repair_id dans la table users
        $stmt = $shop_pdo->prepare("
            UPDATE users 
            SET active_repair_id = ?, 
                techbusy = 1
            WHERE id = ?
        ");
        $stmt->execute([$reparation_id, $user_id]);
        
        // Enregistrer dans les logs
        $action_message = "Réparation assignée à l'employé";
        $stmt = $shop_pdo->prepare("
            INSERT INTO reparation_logs 
            (reparation_id, employe_id, action_type, details, date_action)
            VALUES (?, ?, 'demarrage', ?, NOW())
        ");
        $stmt->execute([$reparation_id, $user_id, $action_message]);
        
        // Valider la transaction
        $shop_pdo->commit();
        
        return true;
    } catch (PDOException $e) {
        // Annuler la transaction en cas d'erreur
        $shop_pdo->rollBack();
        error_log("Erreur lors de l'attribution de la réparation: " . $e->getMessage());
        return false;
    }
}

// Fonction pour compléter la réparation active et libérer l'utilisateur
function completeActiveRepair($shop_pdo, $user_id, $repair_id, $new_status = 'reparation_effectue') {
    try {
        // Démarrer une transaction
        $shop_pdo->beginTransaction();
        
        // Récupérer l'ancien statut pour les logs
        $stmt = $shop_pdo->prepare("SELECT statut, client_id FROM reparations WHERE id = ?");
        $stmt->execute([$repair_id]);
        $repair_info = $stmt->fetch(PDO::FETCH_ASSOC);
        $old_status = $repair_info['statut'];
        $client_id = $repair_info['client_id'];
        
        // Mettre à jour le statut de la réparation
        $stmt = $shop_pdo->prepare("
            UPDATE reparations 
            SET statut = ?, 
                date_modification = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $repair_id]);
        
        // Libérer l'utilisateur
        $stmt = $shop_pdo->prepare("
            UPDATE users 
            SET active_repair_id = NULL, 
                techbusy = 0
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        
        // Enregistrer dans les logs
        $action_message = "Réparation marquée comme " . $new_status;
        $stmt = $shop_pdo->prepare("
            INSERT INTO reparation_logs 
            (reparation_id, employe_id, action_type, statut_avant, statut_apres, details, date_action)
            VALUES (?, ?, 'changement_statut', ?, ?, ?, NOW())
        ");
        $stmt->execute([$repair_id, $user_id, $old_status, $new_status, $action_message]);
        
        // NOUVEAU: Envoyer un SMS automatique au client
        $sms_sent = false;
        $sms_message = '';
        
        // Récupérer directement le statut_id depuis la table statuts pour le nouveau statut
        $stmt = $shop_pdo->prepare("SELECT id FROM statuts WHERE code = ?");
        $stmt->execute([$new_status]);
        $status_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $statut_id = $status_result ? $status_result['id'] : 0;
        
        // Log pour débogage
        error_log("SMS: Statut code = $new_status, Statut ID trouvé = " . ($statut_id ?: 'non trouvé'));
        
        if ($statut_id > 0) {
            // Vérifier s'il existe un modèle SMS pour ce statut
            $stmt = $shop_pdo->prepare("
                SELECT id, nom, contenu 
                FROM sms_templates 
                WHERE statut_id = ? AND est_actif = 1
            ");
            $stmt->execute([$statut_id]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($template) {
                // Log pour débogage
                error_log("SMS: Modèle trouvé - " . $template['nom']);
                
                // Récupérer les infos du client
                $stmt = $shop_pdo->prepare("
                    SELECT c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone,
                           r.type_appareil, r.marque, r.modele, r.date_reception, r.date_fin_prevue, r.prix as prix_reparation
                    FROM clients c
                    JOIN reparations r ON c.id = r.client_id
                    WHERE r.id = ?
                ");
                $stmt->execute([$repair_id]);
                $client_info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($client_info && !empty($client_info['client_telephone'])) {
                    // Préparer le contenu du SMS en remplaçant les variables
                    $message = $template['contenu'];
                    
                    // Tableau des remplacements
                    $replacements = [
                        '[CLIENT_NOM]' => $client_info['client_nom'],
                        '[CLIENT_PRENOM]' => $client_info['client_prenom'],
                        '[CLIENT_TELEPHONE]' => $client_info['client_telephone'],
                        '[REPARATION_ID]' => $repair_id,
                        '[APPAREIL_TYPE]' => $client_info['type_appareil'],
                        '[APPAREIL_MARQUE]' => $client_info['marque'],
                        '[APPAREIL_MODELE]' => $client_info['modele'],
                        '[DATE_RECEPTION]' => format_date($client_info['date_reception']),
                        '[DATE_FIN_PREVUE]' => !empty($client_info['date_fin_prevue']) ? format_date($client_info['date_fin_prevue']) : '',
                        '[PRIX]' => !empty($client_info['prix_reparation']) ? number_format($client_info['prix_reparation'], 2, ',', ' ') : ''
                    ];
                    
                    // Remplacer toutes les variables du tableau dans le message
                    foreach ($replacements as $placeholder => $value) {
                        $message = str_replace($placeholder, $value, $message);
                    }
                    
                    // Configuration de l'API SMS Gateway - votre API personnalisée
                    $API_URL = 'http://168.231.85.4:3001/api/messages/send';
                    
                    // Formatage du numéro de téléphone si nécessaire
                    $recipient = $client_info['client_telephone'];
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
                    
                    // Log pour débogage
                    error_log("SMS: Tentative d'envoi à $recipient pour la réparation #$repair_id");
                    
                    // Préparation des données JSON pour l'API
                    $sms_data = json_encode([
                        'recipient' => $recipient,
                        'message' => $message,
                        'priority' => 'normal'
                    ]);
                    
                    // Envoi du SMS via l'API SMS Gateway
                    $curl = curl_init($API_URL);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $sms_data);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json'
                    ]);
                    
                    // Ajouter des options pour le débogage
                    curl_setopt($curl, CURLOPT_VERBOSE, true);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); 
                    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
                    
                    // Exécution de la requête
                    $response = curl_exec($curl);
                    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    
                    // Récupérer les informations d'erreur curl si échec
                    $curl_error = '';
                    $sms_result = [];
                    
                    if ($response === false) {
                        $curl_error = curl_error($curl);
                        $sms_result = [
                            'success' => false,
                            'message' => "Erreur cURL: $curl_error",
                            'response' => null
                        ];
                        error_log("SMS: Erreur cURL - $curl_error");
                    } else {
                        // Traitement de la réponse
                        $response_data = json_decode($response, true);
                        
                        // Vérifier le succès selon le format de votre API
                        if (($status == 200 || $status == 202) && $response_data && isset($response_data['success']) && $response_data['success']) {
                            $sms_result = [
                                'success' => true, 
                                'message' => 'SMS envoyé avec succès',
                                'response' => $response_data
                            ];
                            $sms_sent = true;
                            error_log("SMS: Envoyé avec succès - Code $status - ID: " . ($response_data['data']['message_id'] ?? 'N/A'));
                        } else {
                            $error_message = $response_data['message'] ?? 'Erreur inconnue';
                            $sms_result = [
                                'success' => false,
                                'message' => "Erreur lors de l'envoi du SMS: $error_message",
                                'response' => $response_data
                            ];
                            error_log("SMS: Échec - Code $status - $error_message");
                        }
                    }
                    
                    curl_close($curl);
                    
                    // Enregistrer l'envoi du SMS dans la base de données
                    if ($sms_sent) {
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO reparation_sms (reparation_id, template_id, telephone, message, date_envoi, statut_id)
                            VALUES (?, ?, ?, ?, NOW(), ?)
                        ");
                        $stmt->execute([
                            $repair_id, 
                            $template['id'], 
                            $recipient, 
                            $message, 
                            $statut_id
                        ]);
                        error_log("SMS: Enregistré dans la base de données - template_id: " . $template['id']);
                    }
                } else {
                    $sms_message = "Le client n'a pas de numéro de téléphone pour SMS.";
                    error_log("SMS: Client sans téléphone pour la réparation #$repair_id");
                }
            } else {
                $sms_message = "Aucun modèle SMS disponible pour ce statut.";
                error_log("SMS: Pas de modèle SMS pour statut_id=$statut_id dans repair_assignment.php");
            }
        } else {
            $sms_message = "Statut non reconnu pour envoi de SMS.";
            error_log("SMS: Statut non reconnu: $new_status (aucun statut_id trouvé)");
        }
        
        // Valider la transaction
        $shop_pdo->commit();
        
        return [
            'success' => true,
            'sms_sent' => $sms_sent,
            'sms_message' => $sms_message
        ];
    } catch (PDOException $e) {
        // Annuler la transaction en cas d'erreur
        $shop_pdo->rollBack();
        error_log("Erreur lors de la complétion de la réparation: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Traitement des actions
if ($action === 'check_active_repair') {
    // Vérifier si l'utilisateur a déjà une réparation active
    $active_repair = getUserActiveRepair($shop_pdo, $user_id);
    
    if ($active_repair) {
        echo json_encode([
            'success' => true,
            'has_active_repair' => true,
            'active_repair' => $active_repair
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'has_active_repair' => false
        ]);
    }
    exit;
}
else if ($action === 'assign_repair') {
    // Vérifier si l'utilisateur a déjà une réparation active
    $active_repair = getUserActiveRepair($shop_pdo, $user_id);
    
    if ($active_repair) {
        echo json_encode([
            'success' => false,
            'message' => 'Vous avez déjà une réparation active (#' . $active_repair['id'] . '). Veuillez d\'abord la terminer.',
            'active_repair' => $active_repair
        ]);
        exit;
    }
    
    // Récupérer les informations sur la réparation
    $stmt = $shop_pdo->prepare("SELECT * FROM reparations WHERE id = ?");
    $stmt->execute([$reparation_id]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        echo json_encode([
            'success' => false,
            'message' => 'Réparation non trouvée'
        ]);
        exit;
    }
    
    // Définir le statut comme en_cours_intervention (peu importe le statut actuel)
    $new_status = 'en_cours_intervention';
    
    // Attribuer la réparation à l'utilisateur
    if (assignRepairToUser($shop_pdo, $user_id, $reparation_id, $new_status)) {
        echo json_encode([
            'success' => true,
            'message' => 'Réparation #' . $reparation_id . ' attribuée avec succès',
            'new_status' => $new_status
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de l\'attribution de la réparation'
        ]);
    }
    exit;
}
else if ($action === 'complete_active_repair') {
    // Vérifier que l'ID de réparation correspond à la réparation active de l'utilisateur
    $active_repair = getUserActiveRepair($shop_pdo, $user_id);
    
    if (!$active_repair || $active_repair['id'] != $reparation_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Cette réparation n\'est pas votre réparation active actuelle'
        ]);
        exit;
    }
    
    // Récupérer le statut final demandé
    $final_status = isset($data['final_status']) ? $data['final_status'] : 'reparation_effectue';
    
    // Compléter la réparation active
    $result = completeActiveRepair($shop_pdo, $user_id, $reparation_id, $final_status);

    if (is_array($result) && isset($result['success']) && $result['success']) {
        $response = [
            'success' => true,
            'message' => 'Réparation #' . $reparation_id . ' terminée avec succès'
        ];
        
        // Ajouter les informations SMS si disponibles
        if (isset($result['sms_sent'])) {
            $response['sms_sent'] = $result['sms_sent'];
            $response['sms_message'] = $result['sms_message'] ?? '';
        }
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false,
            'message' => is_array($result) && isset($result['message']) ? $result['message'] : 'Erreur lors de la complétion de la réparation'
        ]);
    }
    exit;
}
else {
    echo json_encode([
        'success' => false,
        'message' => 'Action non reconnue'
    ]);
    exit;
} 