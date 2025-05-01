<?php
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données et les fonctions
require_once('../config/database.php');
require_once('../includes/functions.php');

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

// Vérifier que le nouveau statut est valide
if (!in_array($new_status, ['restitue', 'annule', 'gardiennage'])) {
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
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
    try {
        // Trouver l'ID du statut à partir de son code
        $status_stmt = $pdo->prepare("SELECT id FROM statuts WHERE code = ?");
        $status_stmt->execute([$new_status]);
        $status_id = $status_stmt->fetchColumn();
        
        if ($status_id) {
            // Récupérer le modèle de SMS correspondant au statut
            $template_stmt = $pdo->prepare("SELECT id, contenu FROM sms_templates WHERE statut_id = ? AND est_actif = 1");
            $template_stmt->execute([$status_id]);
            $sms_template = $template_stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // Journaliser l'erreur et continuer sans envoyer de SMS
        error_log("Erreur lors de la récupération du modèle de SMS: " . $e->getMessage());
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
        // Récupérer le statut actuel pour le journal
        $status_stmt = $pdo->prepare("SELECT r.statut, r.type_appareil, r.marque, r.modele, c.id as client_id, c.nom, c.prenom, c.telephone 
                                      FROM reparations r 
                                      LEFT JOIN clients c ON r.client_id = c.id 
                                      WHERE r.id = ?");
        $status_stmt->execute([$repair_id]);
        $repair_data = $status_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$repair_data) {
            continue; // Passer à la réparation suivante si celle-ci n'existe pas
        }
        
        $current_status = $repair_data['statut'];
        
        // Mettre à jour le statut
        $stmt->execute([$new_status, $repair_id]);
        
        // Si la mise à jour a réussi
        if ($stmt->rowCount() > 0) {
            $updated_count++;
            
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
            if ($send_sms && $sms_template && !empty($repair_data['telephone'])) {
                // Préparer le message en remplaçant les variables
                $message = $sms_template['contenu'];
                $message = str_replace('[CLIENT_NOM]', $repair_data['nom'], $message);
                $message = str_replace('[CLIENT_PRENOM]', $repair_data['prenom'], $message);
                $message = str_replace('[APPAREIL_TYPE]', $repair_data['type_appareil'], $message);
                $message = str_replace('[APPAREIL_MARQUE]', $repair_data['marque'], $message);
                $message = str_replace('[APPAREIL_MODELE]', $repair_data['modele'], $message);
                $message = str_replace('[REPARATION_ID]', $repair_id, $message);
                
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
                    
                    // Configuration de l'API SMS
                    $API_URL = 'https://api.sms-gate.app/3rdparty/v1/message';
                    $API_USERNAME = '-GCB75';
                    $API_PASSWORD = 'Mamanmaman06400';
                    
                    // Préparation des données JSON pour l'API
                    $sms_data = json_encode([
                        'message' => $message,
                        'phoneNumbers' => [$telephone]
                    ]);
                    
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
                    
                    if ($response !== false) {
                        $sms_sent_count++;
                        
                        // Enregistrer l'envoi dans la table des logs SMS si elle existe
                        try {
                            $log_sms_stmt = $pdo->prepare("INSERT INTO sms_logs (recipient, message, status, response, created_at) VALUES (?, ?, 1, ?, NOW())");
                            $log_sms_stmt->execute([$telephone, $message, $response]);
                        } catch (PDOException $e) {
                            // Ignorer si la table n'existe pas
                            error_log("Erreur lors de l'enregistrement du log SMS: " . $e->getMessage());
                        }
                    } else {
                        error_log("Erreur lors de l'envoi du SMS pour la réparation $repair_id: " . curl_error($curl));
                    }
                    
                    curl_close($curl);
                } catch (Exception $e) {
                    error_log("Exception lors de l'envoi du SMS pour la réparation $repair_id: " . $e->getMessage());
                }
            }
        }
    }
    
    // Valider la transaction
    $pdo->commit();
    
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
    $pdo->rollBack();
    
    // Journaliser l'erreur
    error_log("Erreur lors de la mise à jour des statuts par lots: " . $e->getMessage());
    
    // Retourner une réponse d'erreur
    echo json_encode([
        'success' => false, 
        'message' => "Erreur lors de la mise à jour des statuts: " . $e->getMessage()
    ]);
}