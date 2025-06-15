<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction pour enregistrer les logs de débogage
function debug_log($message) {
    $log_file = __DIR__ . '/relance_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

debug_log("Début de traitement client_relance.php");
debug_log("GET: " . json_encode($_GET));
debug_log("POST: " . json_encode($_POST));

// Inclure la configuration de la base de données et les fonctions
require_once('../config/database.php');
require_once('../includes/functions.php');

// Initialiser la connexion à la base de données boutique
$shop_pdo = getShopDBConnection();

// S'assurer que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

debug_log("SESSION: " . json_encode($_SESSION));

// Vérifier que la requête est bien en POST et au format JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

debug_log("Données JSON reçues: " . $json_data);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Données JSON invalides']);
    exit;
}

// Récupérer l'ID du magasin depuis différentes sources
$shop_id = null;

// 1. Vérifier dans l'URL (méthode GET)
if (isset($_GET['shop_id'])) {
    $shop_id = (int)$_GET['shop_id'];
    debug_log("ID du magasin trouvé dans l'URL: $shop_id");
}

// 2. Vérifier dans le corps de la requête JSON
if (!$shop_id && isset($data['shop_id'])) {
    $shop_id = (int)$data['shop_id'];
    debug_log("ID du magasin trouvé dans le corps JSON: $shop_id");
}

// 3. Vérifier dans la session
if (!$shop_id && isset($_SESSION['shop_id'])) {
    $shop_id = (int)$_SESSION['shop_id'];
    debug_log("ID du magasin trouvé dans la session: $shop_id");
}

// Si un ID de magasin a été trouvé, le mettre dans la session
if ($shop_id) {
    $_SESSION['shop_id'] = $shop_id;
    debug_log("ID du magasin $shop_id stocké en session");
}

// Vérifier que les données nécessaires sont présentes
if (!isset($data['action']) || !isset($data['days'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

// Nettoyer les données
$action = cleanInput($data['action']);
$days = (int)$data['days'];
$filterType = isset($data['filterType']) ? cleanInput($data['filterType']) : 'default';

debug_log("Action: $action, Days: $days, FilterType: $filterType");

// Vérifier que l'action est valide
if (!in_array($action, ['preview', 'send'])) {
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit;
}

// Récupérer les IDs des clients sélectionnés (pour l'action 'send')
$selectedClientIds = isset($data['clientIds']) ? $data['clientIds'] : [];
debug_log("Client IDs sélectionnés: " . json_encode($selectedClientIds));

// Vérifier que la connexion à la base de données du magasin est disponible
try {
    debug_log("Vérification de la connexion à la base de données");
    
    if ($shop_pdo === null) {
        throw new Exception("Impossible d'établir une connexion à la base de données");
    }
    debug_log("Connexion shop_pdo disponible");
} catch (Exception $e) {
    debug_log("Erreur de connexion à la base de données: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
    exit;
}

// Récupérer l'ID du modèle de SMS pour la relance client
try {
    debug_log("Recherche du modèle SMS de relance client");
    $template_stmt = $shop_pdo->prepare("SELECT id, contenu FROM sms_templates WHERE nom = 'Relance client' AND est_actif = 1 LIMIT 1");
    $template_stmt->execute();
    $sms_template = $template_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sms_template) {
        debug_log("Modèle SMS de relance non trouvé");
        echo json_encode(['success' => false, 'message' => 'Modèle de SMS de relance non trouvé']);
        exit;
    }
    
    debug_log("Modèle SMS trouvé: ID " . $sms_template['id']);
} catch (PDOException $e) {
    debug_log("Erreur lors de la récupération du modèle de SMS: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du modèle de SMS']);
    exit;
}

// Récupérer les réparations terminées qui n'ont pas été récupérées depuis X jours
try {
    debug_log("Construction de la requête SQL pour le type de filtre: $filterType");
    
    // Construire la requête SQL en fonction du type de filtre
    if ($filterType === 'commande') {
        // Pour les commandes, chercher dans la table commandes_pieces
        $sql = "
            SELECT cp.id, cp.client_id, cp.statut, cp.nom_piece AS type_appareil, 
                   f.nom as marque, cp.reference as modele, 
                   cp.date_modification as date_modification, c.nom as client_nom, c.prenom as client_prenom, 
                   c.telephone, c.email,
                   DATEDIFF(NOW(), cp.date_modification) as days_since
            FROM commandes_pieces cp
            JOIN clients c ON cp.client_id = c.id
            LEFT JOIN fournisseurs f ON cp.fournisseur_id = f.id
            WHERE cp.statut = 'recue'
            AND cp.id NOT IN (
                SELECT SUBSTRING(reparation_id, 5) 
                FROM sms_logs 
                WHERE reparation_id LIKE 'CMD-%' 
                AND date_envoi > DATE_SUB(NOW(), INTERVAL 7 DAY)
            )
        ";
    } else {
        // Pour les réparations, utiliser la requête d'origine
        $sql = "
            SELECT r.id, r.client_id, r.statut, r.statut_id, r.type_appareil, r.marque, r.modele, 
                   r.date_modification, c.nom as client_nom, c.prenom as client_prenom, 
                   c.telephone, c.email,
                   DATEDIFF(NOW(), r.date_modification) as days_since
            FROM reparations r
            JOIN clients c ON r.client_id = c.id
            WHERE ";
        
        // Appliquer le filtre en fonction du type sélectionné
        if ($filterType === 'reparation') {
            // Statuts "Réparation effectuée" et "Réparation annulée"
            $sql .= "r.statut IN ('reparation_effectue', 'reparation_annule')";
        } else {
            // Filtre par défaut (statuts 9, 10, 11 comme avant)
            $sql .= "r.statut_id IN (9, 10, 11)";
        }
        
        $sql .= " AND r.date_modification IS NOT NULL ";
        
        // N'appliquer la condition de jours que pour le filtre par défaut
        if ($filterType === 'default') {
            $sql .= " AND DATEDIFF(NOW(), r.date_modification) >= ? ";
        }
        
        $sql .= " AND r.id NOT IN (
                SELECT reparation_id 
                FROM sms_logs 
                WHERE message LIKE '%est réparé et attend votre visite%' 
                AND date_envoi > DATE_SUB(NOW(), INTERVAL 7 DAY)
            )
        ";
    }
    
    // Si c'est une action d'envoi et que des IDs de clients sont spécifiés, les utiliser
    if ($action === 'send' && !empty($selectedClientIds)) {
        if ($filterType === 'commande') {
            $sql .= " AND cp.id IN (" . implode(',', array_fill(0, count($selectedClientIds), '?')) . ")";
        } else {
            $sql .= " AND r.id IN (" . implode(',', array_fill(0, count($selectedClientIds), '?')) . ")";
        }
    }
    
    $sql .= " ORDER BY days_since DESC";
    
    debug_log("Requête SQL: $sql");
    
    $stmt = $shop_pdo->prepare($sql);
    
    // Exécuter avec les paramètres appropriés
    if ($action === 'send' && !empty($selectedClientIds)) {
        if ($filterType === 'default') {
            $params = array_merge([$days], $selectedClientIds);
            debug_log("Exécution avec paramètres: " . json_encode($params));
            $stmt->execute($params);
        } else {
            debug_log("Exécution avec IDs clients: " . json_encode($selectedClientIds));
            $stmt->execute($selectedClientIds);
        }
    } else {
        if ($filterType === 'default') {
            debug_log("Exécution avec paramètre days: $days");
            $stmt->execute([$days]);
        } else {
            debug_log("Exécution sans paramètres");
            $stmt->execute();
        }
    }
    
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug_log("Nombre de clients trouvés: " . count($clients));
    
    if (empty($clients)) {
        debug_log("Aucun client trouvé");
        echo json_encode(['success' => true, 'clients' => [], 'count' => 0]);
        exit;
    }
} catch (PDOException $e) {
    debug_log("Erreur lors de la récupération des clients: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des clients: ' . $e->getMessage()]);
    exit;
}

// Si c'est juste un aperçu, retourner la liste des clients
if ($action === 'preview') {
    debug_log("Envoi de la réponse d'aperçu avec " . count($clients) . " clients");
    echo json_encode(['success' => true, 'clients' => $clients, 'count' => count($clients)]);
    exit;
}

// Si on est ici, c'est qu'on doit envoyer les SMS
$sms_sent = 0;
$errors = [];

// Inclure la fonction SMS unifiée
if (!function_exists('send_sms')) {
    require_once __DIR__ . '/../includes/sms_functions.php';
}

debug_log("Début de l'envoi des SMS pour " . count($clients) . " clients via API Gateway");

foreach ($clients as $client) {
    debug_log("Traitement du client ID: {$client['client_id']}, Nom: {$client['client_nom']} {$client['client_prenom']}");
    
    // Vérifier que le client a un numéro de téléphone
    if (empty($client['telephone'])) {
        $error_msg = "Client #{$client['client_id']} ({$client['client_nom']} {$client['client_prenom']}) n'a pas de numéro de téléphone";
        debug_log($error_msg);
        $errors[] = $error_msg;
        continue;
    }
    
    // Préparer le message en remplaçant les variables
    $message = $sms_template['contenu'];
    
    // Adapter le message en fonction du type de notification (commande ou réparation)
    if ($filterType === 'commande') {
        // Message pour les commandes reçues
        $message = "Bonjour [CLIENT_PRENOM] [CLIENT_NOM], votre commande de pièce \"[APPAREIL_TYPE]\" ([APPAREIL_MODELE]) est disponible dans notre boutique. A bientôt !";
    } elseif ($filterType === 'reparation') {
        // Message pour les réparations terminées
        $message = "Bonjour [CLIENT_PRENOM] [CLIENT_NOM], votre [APPAREIL_TYPE] [APPAREIL_MARQUE] [APPAREIL_MODELE] est réparé et disponible dans notre boutique. A bientôt !";
    }
    
    $message = str_replace('[CLIENT_NOM]', $client['client_nom'], $message);
    $message = str_replace('[CLIENT_PRENOM]', $client['client_prenom'], $message);
    $message = str_replace('[APPAREIL_TYPE]', $client['type_appareil'], $message);
    $message = str_replace('[APPAREIL_MARQUE]', $client['marque'], $message);
    $message = str_replace('[APPAREIL_MODELE]', $client['modele'], $message);
    $message = str_replace('[REPARATION_ID]', $client['id'], $message);
    
    debug_log("Message préparé: $message");
    
    $telephone = $client['telephone'];
    debug_log("Numéro de téléphone: $telephone");
    
    // Envoyer le SMS via la nouvelle API Gateway
    try {
        $reference_type = ($filterType === 'commande') ? 'relance_commande' : 'relance_reparation';
        $reference_id = $client['id'];
        
        $sms_result = send_sms($telephone, $message, $reference_type, $reference_id, $_SESSION['user_id'] ?? 1);
        
        debug_log("Résultat envoi SMS: " . json_encode($sms_result));
        
        if (isset($sms_result['success']) && $sms_result['success']) {
            debug_log("SMS envoyé avec succès via API Gateway pour le client ID {$client['client_id']}");
            $sms_sent++;
        } else {
            $error_msg = "Erreur lors de l'envoi du SMS: " . ($sms_result['message'] ?? 'Erreur inconnue');
            debug_log($error_msg);
            $errors[] = "Erreur lors de l'envoi du SMS à {$client['client_nom']} {$client['client_prenom']}: " . $error_msg;
        }
    } catch (Exception $e) {
        $error_msg = "Exception lors de l'envoi du SMS: " . $e->getMessage();
        debug_log($error_msg);
        $errors[] = "Erreur lors de l'envoi du SMS à {$client['client_nom']} {$client['client_prenom']}: " . $error_msg;
    }
}

debug_log("Fin de traitement: $sms_sent SMS envoyés, " . count($errors) . " erreurs");

// Retourner le résultat
echo json_encode([
    'success' => true,
    'count' => $sms_sent,
    'errors' => $errors,
    'message' => "$sms_sent SMS de relance envoyés avec succès." . (count($errors) > 0 ? " " . count($errors) . " erreurs rencontrées." : "")
]);
exit; 