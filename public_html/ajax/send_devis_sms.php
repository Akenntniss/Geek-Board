<?php
// Désactiver l'affichage des erreurs PHP pour la production
// mais les logger pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Ajouter un fichier de log personnalisé pour cet endpoint
ini_set('error_log', __DIR__ . '/../logs/errors/app_errors.log');
error_log("Démarrage de send_devis_sms.php");

// Démarrer la session pour récupérer l'ID du magasin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer l'ID du magasin depuis les paramètres POST ou GET
$shop_id_from_request = $_POST['shop_id'] ?? $_GET['shop_id'] ?? null;
if ($shop_id_from_request) {
    $_SESSION['shop_id'] = $shop_id_from_request;
    error_log("ID du magasin récupéré depuis la requête: $shop_id_from_request");
}

// S'assurer que nous envoyons du JSON
header('Content-Type: application/json');

// Vérifier le type de requête HTTP
$method = $_SERVER['REQUEST_METHOD'];
error_log("Méthode HTTP reçue: " . $method);

require_once('../config/database.php');

// Utiliser la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Vérifier si la connexion à la base de données est établie
if (!isset($shop_pdo) || $shop_pdo === null) {
    error_log("Erreur: Connexion à la base de données non établie dans send_devis_sms.php");
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

// Vérifier quelle base de données nous utilisons réellement
try {
    $db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
    $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Base de données connectée dans send_devis_sms.php: " . ($db_info['current_db'] ?? 'Inconnue'));
} catch (Exception $e) {
    error_log("Erreur lors de la vérification de la base: " . $e->getMessage());
}

// Journaliser les données reçues pour le débogage
error_log("Données POST reçues: " . print_r($_POST, true));

// Vérifier si les données nécessaires sont fournies
if (!isset($_POST['repair_id']) || !isset($_POST['sms_type'])) {
    error_log("Erreur: Données requises non fournies dans send_devis_sms.php");
    echo json_encode([
        'success' => false,
        'error' => 'Données requises non fournies'
    ]);
    exit;
}

$repair_id = (int)$_POST['repair_id'];
$sms_type = (int)$_POST['sms_type'];

// Récupérer les nouveaux paramètres
$prix_update = isset($_POST['prix']) ? (float)$_POST['prix'] : null;
$type_message = isset($_POST['type_message']) ? $_POST['type_message'] : 'simple';
$notes_techniques = isset($_POST['notes_techniques']) ? $_POST['notes_techniques'] : '';

error_log("Traitement de l'envoi de devis pour réparation ID: $repair_id, Type SMS: $sms_type, Type Message: $type_message");
if ($prix_update !== null) {
    error_log("Prix mis à jour: $prix_update €");
}

// Vérifier que le SMS type est bien 4 (devis)
if ($sms_type !== 4) {
    error_log("Erreur: Type de SMS invalide: {$sms_type}");
    echo json_encode([
        'success' => false,
        'error' => 'Type de SMS invalide'
    ]);
    exit;
}

try {
    // 1. Récupérer les informations de la réparation et du client
    error_log("Étape 1: Récupération des informations de la réparation et du client");
    $stmt = $shop_pdo->prepare("
        SELECT r.*, c.telephone, c.nom, c.prenom, c.id as client_id, c.email
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    
    $stmt->execute([$repair_id]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        error_log("Réparation non trouvée avec ID: $repair_id dans la base " . ($db_info['current_db'] ?? 'Inconnue'));
        echo json_encode([
            'success' => false,
            'error' => 'Réparation non trouvée'
        ]);
        exit;
    }
    
    error_log("Réparation trouvée: " . print_r([
        'id' => $repair['id'],
        'client_id' => $repair['client_id'],
        'telephone' => $repair['telephone'],
        'statut' => $repair['statut']
    ], true));
    
    // 2. Changer le statut de la réparation en "en attente d'accord client"
    error_log("Étape 2: Changement du statut en 'en attente d'accord client'");
    $statut_code = "en_attente_accord_client";
    
    // Récupérer l'ID du statut pour la journalisation
    $statusIdStmt = $shop_pdo->prepare("SELECT id FROM statuts WHERE code = ?");
    $statusIdStmt->execute([$statut_code]);
    $statusRow = $statusIdStmt->fetch(PDO::FETCH_ASSOC);
    $statut_id = $statusRow ? $statusRow['id'] : null;
    
    error_log("ID du statut trouvé: " . ($statut_id ? $statut_id : "null"));
    
    // Mettre à jour le statut
    $updateStmt = $shop_pdo->prepare("
        UPDATE reparations 
        SET statut = ?, statut_id = ?, date_modification = NOW() 
        WHERE id = ?
    ");
    
    $updateSuccess = $updateStmt->execute([$statut_code, $statut_id, $repair_id]);
    
    if (!$updateSuccess) {
        $error = $updateStmt->errorInfo();
        error_log("Erreur lors de la mise à jour du statut: " . json_encode($error));
        throw new PDOException("Erreur lors de la mise à jour du statut: " . $error[2]);
    }
    
    error_log("Statut de la réparation mis à jour avec succès");
    
    // 3. Enregistrer le changement dans l'historique
    error_log("Étape 3: Enregistrement du changement dans l'historique");
    $logStmt = $shop_pdo->prepare("
        INSERT INTO reparation_logs (reparation_id, employe_id, action_type, date_action, statut_avant, statut_apres, details) 
        VALUES (?, ?, 'changement_statut', NOW(), ?, ?, ?)
    ");
    
    $description = "Statut changé en 'En attente d'accord client' lors de l'envoi d'un devis";
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Valeur par défaut si non connecté
    $statut_avant = $repair['statut']; // Status actuel avant changement
    $statut_apres = $statut_code; // Nouveau statut (en_attente_accord_client)
    
    $logResult = $logStmt->execute([$repair_id, $user_id, $statut_avant, $statut_apres, $description]);
    if (!$logResult) {
        error_log("Avertissement: Impossible d'enregistrer l'action dans l'historique: " . json_encode($logStmt->errorInfo()));
    } else {
        error_log("Changement enregistré dans l'historique avec succès");
    }
    
    // 4. Envoyer le SMS de devis au client
    error_log("Étape 4: Préparation du SMS de devis");
    // Récupérer le modèle de SMS type 4 (Devis)
    $templateStmt = $shop_pdo->prepare("
        SELECT * FROM sms_templates WHERE id = ?
    ");
    
    $templateStmt->execute([4]); // SMS ID 4 pour le devis
    $template = $templateStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        error_log("Modèle de SMS ID 4 non trouvé");
        throw new Exception("Modèle de SMS pour devis non trouvé");
    }
    
    error_log("Modèle de SMS trouvé: " . $template['nom']);
    
    // Préparer le message SMS
    $message = $template['contenu'];
    
    // Remplacer les variables dans le message
    // Tableau des remplacements (format utilisé dans le modèle)
    $replacements = [
        '[CLIENT_NOM]' => $repair['nom'],
        '[CLIENT_PRENOM]' => $repair['prenom'],
        '[REPARATION_ID]' => $repair_id,
        '[APPAREIL_TYPE]' => $repair['type_appareil'],
        '[APPAREIL_MARQUE]' => $repair['marque'],
        '[APPAREIL_MODELE]' => $repair['modele'],
        '[REF]' => $repair_id,
        '[PRIX]' => $repair['prix_reparation'] . '€',
        '[LIEN]' => "https://" . $_SERVER['HTTP_HOST'] . "/pages/accepter_devis.php?id=" . $repair_id,
        '[APPAREIL]' => $repair['type_appareil'] . ' ' . $repair['marque'] . ' ' . $repair['modele']
    ];
    
    // Remplacer toutes les variables du tableau dans le message
    foreach ($replacements as $placeholder => $value) {
        $message = str_replace($placeholder, $value, $message);
    }
    
    // Si le type de message est détaillé, ajouter les notes techniques
    if ($type_message === 'detaille' && !empty($notes_techniques)) {
        error_log("Ajout des notes techniques au message");
        $message .= "\n\nDétails techniques:\n" . $notes_techniques;
    }
    
    // Vérifier si des variables n'ont pas été remplacées
    $pattern = '/\[([A-Z_]+)\]/';
    if (preg_match_all($pattern, $message, $matches)) {
        error_log("ATTENTION: Variables non remplacées détectées: " . implode(', ', $matches[0]));
        
        // Remplacer les variables restantes par des valeurs par défaut
        $message = preg_replace('/\[CLIENT_NOM\]/', '[Nom client]', $message);
        $message = preg_replace('/\[CLIENT_PRENOM\]/', '[Prénom client]', $message);
        $message = preg_replace('/\[REPARATION_ID\]/', $repair_id, $message);
        $message = preg_replace('/\[APPAREIL_TYPE\]/', $repair['type_appareil'] ?: '[Type appareil]', $message);
        $message = preg_replace('/\[APPAREIL_MARQUE\]/', $repair['marque'] ?: '[Marque]', $message);
        $message = preg_replace('/\[APPAREIL_MODELE\]/', $repair['modele'] ?: '[Modèle]', $message);
        $message = preg_replace('/\[REF\]/', $repair_id, $message);
        $message = preg_replace('/\[PRIX\]/', $repair['prix_reparation'] . '€', $message);
        $message = preg_replace('/\[APPAREIL\]/', ($repair['type_appareil'] ?: '') . ' ' . 
                                              ($repair['marque'] ?: '') . ' ' . 
                                              ($repair['modele'] ?: ''), $message);
        
        // URL d'acceptation du devis
        $message = preg_replace('/\[LIEN\]/', "https://" . $_SERVER['HTTP_HOST'] . "/pages/accepter_devis.php?id=" . $repair_id, $message);
    }
    
    // Créer un lien pour accepter le devis (si nécessaire)
    $lien_acceptation = "https://" . $_SERVER['HTTP_HOST'] . "/pages/accepter_devis.php?id=" . $repair_id;
    
    error_log("Message SMS après remplacement des variables: " . $message);
    error_log("Longueur du message: " . strlen($message) . " caractères");
    error_log("Lien d'acceptation: " . $lien_acceptation);
    
    // Enregistrer l'envoi du SMS dans la base de données
    error_log("Étape 5: Enregistrement de l'envoi du SMS dans la base de données");
    $smsLogStmt = $shop_pdo->prepare("
        INSERT INTO reparation_sms (reparation_id, template_id, telephone, message, date_envoi, statut_id)
        VALUES (?, ?, ?, ?, NOW(), ?)
    ");
    
    $smsLogResult = $smsLogStmt->execute([
        $repair_id,
        4, // ID du modèle de SMS
        $repair['telephone'],
        $message,
        $statut_id // Utiliser l'ID du statut récupéré précédemment
    ]);
    
    if (!$smsLogResult) {
        error_log("Avertissement: Impossible d'enregistrer le SMS dans l'historique: " . json_encode($smsLogStmt->errorInfo()));
    }
    
    $sms_id = $shop_pdo->lastInsertId();
    error_log("SMS enregistré dans l'historique avec ID: " . $sms_id);
    
    // Ici, nous allons implémenter directement l'envoi de SMS sans passer par la fonction send_sms()
    error_log("Étape 6: Envoi direct du SMS via API SMS Gateway");
    
    // Configuration de l'API SMS Gateway
    $API_URL = 'https://api.sms-gate.app/3rdparty/v1/message'; // URL CORRECTE selon la documentation
    $API_USERNAME = '-GCB75';
    $API_PASSWORD = 'Mamanmaman06400';
    
    // Formatage du numéro de téléphone si nécessaire
    $recipient = $repair['telephone'];
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
    
    error_log("Numéro formaté: $recipient");
    
    // Préparation des données JSON pour l'API
    $sms_data = json_encode([
        'message' => $message,
        'phoneNumbers' => [$recipient]
    ]);
    
    error_log("Données JSON: $sms_data");
    
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
    error_log("Exécution de la requête cURL...");
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    error_log("Code HTTP: $status");
    
    // Récupérer les informations d'erreur curl si échec
    $curl_error = '';
    if ($response === false) {
        $curl_error = curl_error($curl);
        rewind($verbose);
        $verbose_log = stream_get_contents($verbose);
        error_log("Erreur cURL: $curl_error");
        error_log("Détails: $verbose_log");
        $smsResult = [
            'success' => false,
            'message' => "Erreur cURL: $curl_error",
            'response' => null
        ];
    } else {
        error_log("Réponse: $response");
        // Traitement de la réponse
        $response_data = json_decode($response, true);
        
        // Le code 202 indique une acceptation (Accepted) pour traitement asynchrone
        if (($status == 200 || $status == 202) && $response_data) {
            error_log("Envoi SMS réussi");
            $smsResult = [
                'success' => true, 
                'message' => 'SMS envoyé avec succès',
                'response' => $response_data
            ];
        } else {
            error_log("Échec de l'envoi SMS: Code $status");
            $smsResult = [
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
    
    // Après l'envoi, journaliser le statut du SMS dans sms_logs
    $smsLogsStmt = $shop_pdo->prepare("
        INSERT INTO sms_logs (recipient, message, status, response)
        VALUES (?, ?, ?, ?)
    ");
    
    $smsResponse = isset($smsResult['response']) ? json_encode($smsResult['response']) : 'Aucune réponse';
    $smsStatus = $smsResult['success'] ? 200 : ($status ?: 400);
    
    $smsLogResult = $smsLogsStmt->execute([
        $recipient,
        $message,
        $smsStatus,
        $smsResponse
    ]);
    
    if (!$smsLogResult) {
        error_log("Avertissement: Impossible d'enregistrer le log SMS: " . json_encode($smsLogsStmt->errorInfo()));
    } else {
        error_log("Log SMS enregistré avec succès");
    }
    
    // 5. Envoyer également un email si l'adresse email du client est disponible
    if (!empty($repair['email'])) {
        error_log("Étape 7: Envoi d'un email au client à l'adresse " . $repair['email']);
        // Code pour envoyer un email (si nécessaire)
        // ...
    }
    
    // Mettre à jour le prix de la réparation si fourni
    if ($prix_update !== null) {
        try {
            $updatePrixStmt = $shop_pdo->prepare("
                UPDATE reparations 
                SET prix_reparation = ? 
                WHERE id = ?
            ");
            $updatePrixSuccess = $updatePrixStmt->execute([$prix_update, $repair_id]);
            
            if ($updatePrixSuccess) {
                error_log("Prix de la réparation mis à jour avec succès: $prix_update €");
                // Mettre à jour le prix dans notre variable $repair
                $repair['prix_reparation'] = $prix_update;
            } else {
                error_log("Erreur lors de la mise à jour du prix: " . json_encode($updatePrixStmt->errorInfo()));
            }
        } catch (PDOException $e) {
            error_log("Erreur PDO lors de la mise à jour du prix: " . $e->getMessage());
        }
    }
    
    // Préparer la réponse
    error_log("Étape 8: Envoi de la réponse JSON de succès");
    echo json_encode([
        'success' => true,
        'message' => 'Devis envoyé avec succès et statut mis à jour',
        'sms_id' => $sms_id,
        'repair_id' => $repair_id
    ]);
    
    error_log("Devis envoyé avec succès pour la réparation ID: $repair_id");
    
} catch (PDOException $e) {
    error_log("Erreur PDO dans send_devis_sms.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de l\'envoi du devis: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur inattendue dans send_devis_sms.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur inattendue est survenue: ' . $e->getMessage()
    ]);
} 