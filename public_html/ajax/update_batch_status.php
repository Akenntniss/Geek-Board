<?php
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données et les fonctions
require_once('../config/database.php');
require_once('../includes/functions.php');

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction pour enregistrer les logs de débogage
function debug_log($message) {
    $log_file = __DIR__ . '/sms_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

debug_log("Début de traitement update_batch_status.php");
debug_log("SESSION: " . json_encode($_SESSION));
debug_log("POST: " . json_encode($_POST));

// Vérifier que la requête est bien en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier que les données nécessaires sont présentes
if (!isset($_POST['repair_ids']) || !isset($_POST['new_status'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

// Récupérer les données du formulaire
$repair_ids = $_POST['repair_ids'];
$new_status = cleanInput($_POST['new_status']);
$send_sms = isset($_POST['send_sms']) ? (bool)$_POST['send_sms'] : false;

debug_log("Paramètres reçus: repair_ids=" . json_encode($repair_ids) . ", new_status=$new_status, send_sms=" . ($send_sms ? 'true' : 'false'));

// Vérifier que le nouveau statut est valide
if (!in_array($new_status, ['restitue', 'annule', 'gardiennage'])) {
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit;
}

// Obtenir la connexion à la base de données
$pdo = null;
try {
    debug_log("Tentative de connexion à la base de données");
    // Récupérer la connexion à la base du magasin
    if (function_exists('getShopDBConnection')) {
        $pdo = getShopDBConnection();
        debug_log("Connexion via getShopDBConnection");
    }
    
    // Si pas de connexion via magasin, essayer la connexion principale
    if ($pdo === null && function_exists('getMainDBConnection')) {
        $pdo = getMainDBConnection();
        debug_log("Connexion via getMainDBConnection");
    }
    
    // Si toujours pas de connexion, utiliser $main_pdo global
    if ($pdo === null) {
        global $main_pdo;
        $pdo = $main_pdo;
        debug_log("Connexion via variable globale main_pdo");
    }
    
    if ($pdo === null) {
        throw new Exception("Impossible d'établir une connexion à la base de données");
    }
} catch (Exception $e) {
    debug_log("Erreur de connexion à la base de données: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
    exit;
}

// Initialiser le compteur de réparations mises à jour
$updated_count = 0;
$sms_sent_count = 0;

// Récupérer l'ID de l'utilisateur connecté
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Valeur par défaut 1 (utilisateur système)

// Récupérer le modèle de SMS correspondant au statut sélectionné
$sms_template = null;
if ($send_sms) {
    debug_log("Tentative de récupération du modèle SMS pour le statut: $new_status");
    try {
        // Trouver l'ID du statut à partir de son code
        $status_stmt = $pdo->prepare("SELECT id FROM statuts WHERE code = ?");
        $status_stmt->execute([$new_status]);
        $status_id = $status_stmt->fetchColumn();
        
        debug_log("ID du statut trouvé: " . ($status_id ? $status_id : "non trouvé"));
        
        if ($status_id) {
            // Récupérer le modèle de SMS correspondant au statut
            $template_stmt = $pdo->prepare("SELECT id, contenu FROM sms_templates WHERE statut_id = ? AND est_actif = 1");
            $template_stmt->execute([$status_id]);
            $sms_template = $template_stmt->fetch(PDO::FETCH_ASSOC);
            
            debug_log("Modèle SMS: " . ($sms_template ? "trouvé (ID: {$sms_template['id']})" : "non trouvé"));
            if ($sms_template) {
                debug_log("Contenu du modèle: {$sms_template['contenu']}");
            }
        }
    } catch (PDOException $e) {
        // Journaliser l'erreur et continuer sans envoyer de SMS
        debug_log("Erreur lors de la récupération du modèle de SMS: " . $e->getMessage());
    }
}

// Préparer la requête de mise à jour
try {
    // Démarrer une transaction pour assurer l'intégrité des données
    $pdo->beginTransaction();
    
    // Préparer la requête de mise à jour
    $stmt = $pdo->prepare("UPDATE reparations SET statut = ?, date_modification = NOW() WHERE id = ?");
    
    // Mettre à jour chaque réparation
    foreach ($repair_ids as $repair_id) {
        debug_log("Traitement de la réparation ID: $repair_id");
        
        // Récupérer le statut actuel pour le journal
        $status_stmt = $pdo->prepare("SELECT r.statut, r.type_appareil, r.marque, r.modele, c.id as client_id, c.nom, c.prenom, c.telephone 
                                      FROM reparations r 
                                      LEFT JOIN clients c ON r.client_id = c.id 
                                      WHERE r.id = ?");
        $status_stmt->execute([$repair_id]);
        $repair_data = $status_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$repair_data) {
            debug_log("Réparation ID $repair_id non trouvée, passage à la suivante");
            continue; // Passer à la réparation suivante si celle-ci n'existe pas
        }
        
        $current_status = $repair_data['statut'];
        debug_log("Statut actuel: $current_status, nouveau statut: $new_status");
        
        // Mettre à jour le statut
        $stmt->execute([$new_status, $repair_id]);
        
        // Si la mise à jour a réussi
        if ($stmt->rowCount() > 0) {
            $updated_count++;
            debug_log("Réparation ID $repair_id mise à jour avec succès");
            
            // Ajouter une entrée dans le journal des réparations
            $action_type = 'changement_statut';
            $description = "Mise à jour du statut de '{$current_status}' à '{$new_status}' via mise à jour par lots";
            
            // Utiliser la fonction logReparationAction si elle existe
            if (function_exists('logReparationAction')) {
                logReparationAction($pdo, $repair_id, $user_id, $action_type, $current_status, $new_status, $description);
            } else {
                // Sinon, insérer directement dans la table des logs
                $log_stmt = $pdo->prepare("INSERT INTO reparation_logs (reparation_id, employe_id, action_type, statut_avant, statut_apres, details, date_action) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $log_stmt->execute([$repair_id, $user_id, $action_type, $current_status, $new_status, $description]);
            }
            
            // Si le statut est "gardiennage", mettre à jour la date de gardiennage
            if ($new_status === 'gardiennage') {
                $gardiennage_stmt = $pdo->prepare("UPDATE reparations SET date_gardiennage = NOW() WHERE id = ?");
                $gardiennage_stmt->execute([$repair_id]);
            }
            
            // Envoyer un SMS si l'option est activée et qu'un modèle est disponible
            if ($send_sms) {
                debug_log("Option SMS activée pour réparation ID $repair_id");
                
                if (!$sms_template) {
                    debug_log("Pas de modèle SMS disponible pour le statut $new_status");
                } else if (empty($repair_data['telephone'])) {
                    debug_log("Pas de numéro de téléphone disponible pour le client");
                } else {
                    // Préparer le message en remplaçant les variables
                    $message = $sms_template['contenu'];
                    $message = str_replace('[CLIENT_NOM]', $repair_data['nom'] ?? '', $message);
                    $message = str_replace('[CLIENT_PRENOM]', $repair_data['prenom'] ?? '', $message);
                    $message = str_replace('[APPAREIL_TYPE]', $repair_data['type_appareil'] ?? '', $message);
                    $message = str_replace('[APPAREIL_MARQUE]', $repair_data['marque'] ?? '', $message);
                    $message = str_replace('[APPAREIL_MODELE]', $repair_data['modele'] ?? '', $message);
                    $message = str_replace('[REPARATION_ID]', $repair_id, $message);
                    
                    debug_log("Message préparé: $message");
                    
                    // Envoyer le SMS
                    try {
                        // Formater le numéro de téléphone
                        $telephone = $repair_data['telephone'];
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
                        
                        debug_log("Numéro formaté: $telephone");
                        
                        // Configuration de l'API SMS
                        $API_URL = 'https://api.sms-gate.app/3rdparty/v1/message';
                        $API_USERNAME = '-GCB75';
                        $API_PASSWORD = 'Mamanmaman06400';
                        
                        // Préparation des données JSON pour l'API
                        $sms_data = json_encode([
                            'message' => $message,
                            'phoneNumbers' => [$telephone]
                        ]);
                        
                        debug_log("Données SMS préparées: $sms_data");
                        debug_log("Tentative d'envoi de SMS à l'URL: $API_URL");
                        
                        // Envoi du SMS via l'API
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
                        
                        // Désactiver la vérification SSL pour le développement
                        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
                        
                        // Exécution de la requête
                        $response = curl_exec($curl);
                        $curl_error = curl_error($curl);
                        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                        
                        debug_log("Réponse de l'API SMS: HTTP code $http_code, Response: " . ($response ?: "vide"));
                        if ($curl_error) {
                            debug_log("Erreur cURL: $curl_error");
                        }
                        
                        if ($response !== false) {
                            $sms_sent_count++;
                            debug_log("SMS envoyé avec succès");
                            
                            // Enregistrer l'envoi dans la table des logs SMS si elle existe
                            try {
                                $log_sms_stmt = $pdo->prepare("INSERT INTO sms_logs (recipient, message, status, response, created_at) VALUES (?, ?, 1, ?, NOW())");
                                $log_sms_stmt->execute([$telephone, $message, $response]);
                                debug_log("Log SMS enregistré dans la base de données");
                            } catch (PDOException $e) {
                                // Ignorer si la table n'existe pas
                                debug_log("Erreur lors de l'enregistrement du log SMS: " . $e->getMessage());
                            }
                        } else {
                            debug_log("Erreur lors de l'envoi du SMS pour la réparation $repair_id: " . $curl_error);
                        }
                        
                        curl_close($curl);
                    } catch (Exception $e) {
                        debug_log("Exception lors de l'envoi du SMS pour la réparation $repair_id: " . $e->getMessage());
                    }
                }
            } else {
                debug_log("Option SMS désactivée pour cette mise à jour");
            }
        } else {
            debug_log("Aucune modification pour la réparation ID $repair_id (même statut ou erreur)");
        }
    }
    
    // Valider la transaction
    $pdo->commit();
    debug_log("Transaction validée: $updated_count réparations mises à jour, $sms_sent_count SMS envoyés");
    
    // Retourner une réponse de succès
    $message = "$updated_count réparation(s) mise(s) à jour avec succès.";
    if ($send_sms) {
        $message .= " $sms_sent_count SMS envoyé(s).";
    }
    
    echo json_encode([
        'success' => true, 
        'count' => $updated_count,
        'sms_count' => $sms_sent_count,
        'message' => $message
    ]);
    
} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction
    if ($pdo) {
        $pdo->rollBack();
    }
    
    // Journaliser l'erreur
    $error_message = "Erreur lors de la mise à jour des statuts par lots: " . $e->getMessage();
    debug_log($error_message);
    
    // Retourner une réponse d'erreur
    echo json_encode([
        'success' => false, 
        'message' => "Erreur lors de la mise à jour des statuts: " . $e->getMessage()
    ]);
}