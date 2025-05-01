<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données et les fonctions
require_once('../config/database.php');
require_once('../includes/functions.php');

// Vérifier que la requête est bien en POST et au format JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Données JSON invalides']);
    exit;
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

// Vérifier que l'action est valide
if (!in_array($action, ['preview', 'send'])) {
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit;
}

// Récupérer les IDs des clients sélectionnés (pour l'action 'send')
$selectedClientIds = isset($data['clientIds']) ? $data['clientIds'] : [];

// Récupérer l'ID du modèle de SMS pour la relance client
try {
    $template_stmt = $pdo->prepare("SELECT id, contenu FROM sms_templates WHERE nom = 'Relance client' AND est_actif = 1 LIMIT 1");
    $template_stmt->execute();
    $sms_template = $template_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sms_template) {
        echo json_encode(['success' => false, 'message' => 'Modèle de SMS de relance non trouvé']);
        exit;
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération du modèle de SMS: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du modèle de SMS']);
    exit;
}

// Récupérer les réparations terminées qui n'ont pas été récupérées depuis X jours
try {
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
    
    $stmt = $pdo->prepare($sql);
    
    // Exécuter avec les paramètres appropriés
    if ($action === 'send' && !empty($selectedClientIds)) {
        if ($filterType === 'default') {
            $params = array_merge([$days], $selectedClientIds);
            $stmt->execute($params);
        } else {
            $stmt->execute($selectedClientIds);
        }
    } else {
        if ($filterType === 'default') {
            $stmt->execute([$days]);
        } else {
            $stmt->execute();
        }
    }
    
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($clients)) {
        echo json_encode(['success' => true, 'clients' => [], 'count' => 0]);
        exit;
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des clients: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des clients: ' . $e->getMessage()]);
    exit;
}

// Si c'est juste un aperçu, retourner la liste des clients
if ($action === 'preview') {
    echo json_encode(['success' => true, 'clients' => $clients, 'count' => count($clients)]);
    exit;
}

// Si on est ici, c'est qu'on doit envoyer les SMS
$sms_sent = 0;
$errors = [];

// Configuration de l'API SMS
$API_URL = 'https://api.sms-gate.app/3rdparty/v1/message';
$API_USERNAME = '-GCB75';
$API_PASSWORD = 'Mamanmaman06400';

foreach ($clients as $client) {
    // Vérifier que le client a un numéro de téléphone
    if (empty($client['telephone'])) {
        $errors[] = "Client #{$client['client_id']} ({$client['client_nom']} {$client['client_prenom']}) n'a pas de numéro de téléphone";
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
    
    // Formater le numéro de téléphone
    $telephone = $client['telephone'];
    $telephone = preg_replace('/[^0-9+]/', '', $telephone);
    
    if (substr($telephone, 0, 1) !== '+') {
        if (substr($telephone, 0, 1) === '0') {
            $telephone = '+33' . substr($telephone, 1);
        } else if (substr($telephone, 0, 2) === '33') {
            $telephone = '+' . $telephone;
        } else {
            $telephone = '+' . $telephone;
        }
    }
    
    // Envoyer le SMS via l'API
    try {
        // Préparation des données JSON pour l'API
        $sms_data = json_encode([
            'message' => $message,
            'phoneNumbers' => [$telephone]
        ]);
        
        // Initialiser CURL
        $curl = curl_init($API_URL);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $sms_data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($sms_data)
        ]);
        
        // Configuration de l'authentification
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "$API_USERNAME:$API_PASSWORD");
        
        // Options supplémentaires
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        
        // Exécution de la requête
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $errors[] = "Erreur lors de l'envoi du SMS à {$client['client_prenom']} {$client['client_nom']}: " . curl_error($curl);
        } else {
            // Enregistrer le SMS dans les logs
            try {
                // Pour les commandes, utiliser un préfixe différent pour distinguer dans les logs
                $entity_id = $client['id'];
                $entity_type = ($filterType === 'commande') ? 'CMD-' . $entity_id : $entity_id;
                
                $log_stmt = $pdo->prepare("
                    INSERT INTO sms_logs (recipient, message, status, response, reparation_id, date_envoi) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $log_stmt->execute([$telephone, $message, ($http_code == 200 ? 1 : 0), $response, $entity_type]);
                
                // Incrémenter le compteur de SMS envoyés
                $sms_sent++;
                
                // Log supplémentaire pour le suivi
                error_log("SMS envoyé avec succès à {$client['client_nom']} {$client['client_prenom']} pour " . 
                    ($filterType === 'commande' ? "la commande" : "la réparation") . " #{$client['id']}");
            } catch (PDOException $e) {
                error_log("Erreur lors de l'enregistrement du SMS dans les logs: " . $e->getMessage());
                // Continuer malgré l'erreur
            }
        }
        
        curl_close($curl);
    } catch (Exception $e) {
        $errors[] = "Exception lors de l'envoi du SMS à {$client['client_prenom']} {$client['client_nom']}: " . $e->getMessage();
    }
}

// Retourner le résultat
echo json_encode([
    'success' => true,
    'count' => $sms_sent,
    'total' => count($clients),
    'errors' => $errors
]); 