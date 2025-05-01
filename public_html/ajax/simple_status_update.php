<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Définir le chemin de base pour les inclusions
$root_path = realpath(__DIR__ . '/..');
define('BASE_PATH', $root_path);

// Désactiver l'affichage des erreurs pour les réponses JSON propres
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Créer un fichier de log pour le débogage
$logFile = __DIR__ . '/specific_status_update.log';
file_put_contents($logFile, "--- Nouvelle tentative de mise à jour simplifiée du statut ---\n", FILE_APPEND);
file_put_contents($logFile, "Date: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents($logFile, "BASE_PATH: " . BASE_PATH . "\n", FILE_APPEND);
file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

try {
    // Récupérer les paramètres simplifiés
    $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
    $status_id = isset($_POST['status_id']) ? intval($_POST['status_id']) : 0;
    $send_sms = isset($_POST['send_sms']) ? (intval($_POST['send_sms']) === 1) : false;
    
    file_put_contents($logFile, "Parsed data: repair_id=$repair_id, status_id=$status_id, send_sms=" . ($send_sms ? 'true' : 'false') . "\n", FILE_APPEND);
    
    // Vérifier les paramètres requis
    if ($repair_id <= 0 || $status_id <= 0) {
        throw new Exception('Paramètres invalides');
    }
    
    // Charger la configuration de la base de données
    require_once __DIR__ . '/../config/database.php';
    
    // Si besoin de la fonction d'envoi de SMS
    if ($send_sms) {
        require_once __DIR__ . '/../includes/functions.php';
    }
    
    // Vérifier que la connexion PDO est disponible
    if (!isset($pdo) || $pdo === null) {
        file_put_contents($logFile, "ERREUR: Connexion PDO non disponible après inclusion de database.php\n", FILE_APPEND);
        throw new Exception('Connexion à la base de données non disponible');
    }
    
    // Récupérer le code du statut
    $stmt = $pdo->prepare("SELECT code FROM statuts WHERE id = ?");
    $stmt->execute([$status_id]);
    $status_code = $stmt->fetchColumn();
    
    if (!$status_code) {
        file_put_contents($logFile, "ERREUR: Code de statut non trouvé pour l'ID $status_id\n", FILE_APPEND);
        throw new Exception("Code de statut non trouvé pour l'ID $status_id");
    }
    
    // Mise à jour des deux colonnes: statut_id et statut
    $stmt = $pdo->prepare("UPDATE reparations SET statut_id = ?, statut = ?, date_modification = NOW() WHERE id = ?");
    $result = $stmt->execute([$status_id, $status_code, $repair_id]);
    
    if (!$result) {
        file_put_contents($logFile, "ERREUR de mise à jour: " . implode(", ", $stmt->errorInfo()) . "\n", FILE_APPEND);
        throw new Exception('Erreur lors de la mise à jour: ' . implode(", ", $stmt->errorInfo()));
    }
    
    // Vérifier si des lignes ont été affectées
    if ($stmt->rowCount() === 0) {
        file_put_contents($logFile, "AVERTISSEMENT: Aucune ligne affectée - la réparation n'existe peut-être pas ou le statut est inchangé\n", FILE_APPEND);
        // Ne pas lancer d'exception, juste un avertissement dans le log
    } else {
        file_put_contents($logFile, "Mise à jour réussie: " . $stmt->rowCount() . " ligne(s) affectée(s)\n", FILE_APPEND);
        
        // Récupérer le statut précédent pour le journal
        $stmt_prev = $pdo->prepare("SELECT statut_apres FROM reparation_logs WHERE reparation_id = ? AND action_type = 'changement_statut' ORDER BY date_action DESC LIMIT 1");
        $stmt_prev->execute([$repair_id]);
        $previous_status = $stmt_prev->fetchColumn();
        
        if (!$previous_status) {
            // Si aucun statut précédent dans les logs, essayer de trouver une valeur par défaut
            $previous_status = 'inconnu';
        }
        
        // Récupérer l'ID de l'utilisateur qui fait la modification
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 1; // Utiliser admin par défaut si non spécifié
        
        // Insérer un enregistrement dans reparation_logs
        try {
            $stmt_log = $pdo->prepare("
                INSERT INTO reparation_logs (
                    reparation_id, employe_id, action_type, statut_avant, statut_apres, details
                ) VALUES (?, ?, 'changement_statut', ?, ?, ?)
            ");
            
            $details = "Mise à jour simplifiée du statut";
            $stmt_log->execute([
                $repair_id, 
                $user_id, 
                $previous_status, 
                $status_code,
                $details
            ]);
            
            file_put_contents($logFile, "Log enregistré dans la table reparation_logs\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents($logFile, "ERREUR lors de l'enregistrement du log: " . $e->getMessage() . "\n", FILE_APPEND);
            // Ne pas bloquer le processus en cas d'erreur d'enregistrement du log
        }
    }
    
    // Récupérer les informations sur le statut pour l'affichage du badge
    $stmt = $pdo->prepare("SELECT nom, code FROM statuts WHERE id = ?");
    $stmt->execute([$status_id]);
    $status = $stmt->fetch();
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'message' => 'Statut mis à jour avec succès (simple)',
        'data' => [
            'badge' => [
                'text' => $status['nom'] ?? 'Statut inconnu',
                'color' => $status['code'] ?? '#999999'
            ],
            'sms_sent' => false,
            'sms_message' => 'SMS non envoyé (mode simplifié)'
        ]
    ];
    
    // Envoi de SMS si demandé et si la fonction existe
    if ($send_sms && function_exists('send_sms')) {
        try {
            file_put_contents($logFile, "Tentative d'envoi de SMS\n", FILE_APPEND);
            
            // Récupérer les informations du client et de la réparation
            $stmt = $pdo->prepare("
                SELECT r.*, c.telephone, c.nom as client_nom, c.prenom as client_prenom
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                WHERE r.id = ?
            ");
            $stmt->execute([$repair_id]);
            $repair_data = $stmt->fetch();
            
            if ($repair_data && !empty($repair_data['telephone'])) {
                $telephone = $repair_data['telephone'];
                $client_nom = $repair_data['client_nom'];
                $client_prenom = $repair_data['client_prenom'];
                
                // Récupérer le template correspondant au statut_id
                $stmt = $pdo->prepare("
                    SELECT id, contenu 
                    FROM sms_templates 
                    WHERE statut_id = ? AND est_actif = 1
                    LIMIT 1
                ");
                $stmt->execute([$status_id]);
                $template = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($template && !empty($template['contenu'])) {
                    // Créer le message à partir du template et des informations de la réparation
                    $message = $template['contenu'];
                    
                    // Remplacer les variables dans le template
                    $replacements = [
                        '[CLIENT_NOM]' => $client_nom,
                        '[CLIENT_PRENOM]' => $client_prenom,
                        '[CLIENT_TELEPHONE]' => $telephone,
                        '[REPARATION_ID]' => $repair_id,
                        '[APPAREIL_TYPE]' => $repair_data['type_appareil'] ?? '',
                        '[APPAREIL_MARQUE]' => $repair_data['marque'] ?? '',
                        '[APPAREIL_MODELE]' => $repair_data['modele'] ?? '',
                        '[DATE_RECEPTION]' => !empty($repair_data['date_reception']) ? date('d/m/Y', strtotime($repair_data['date_reception'])) : '',
                        '[DATE_FIN_PREVUE]' => !empty($repair_data['date_fin_prevue']) ? date('d/m/Y', strtotime($repair_data['date_fin_prevue'])) : '',
                        '[PRIX]' => !empty($repair_data['prix_reparation']) ? number_format($repair_data['prix_reparation'], 2, ',', ' ') . ' €' : ''
                    ];
                    
                    foreach ($replacements as $placeholder => $value) {
                        $message = str_replace($placeholder, $value, $message);
                    }
                    
                    file_put_contents($logFile, "Template de SMS trouvé pour le statut_id $status_id\n", FILE_APPEND);
                    file_put_contents($logFile, "Message après remplacement des variables : " . substr($message, 0, 100) . "...\n", FILE_APPEND);
                } else {
                    // Fallback si aucun template trouvé pour ce statut
                    file_put_contents($logFile, "Aucun template trouvé pour le statut_id $status_id, utilisation du message par défaut\n", FILE_APPEND);
                    $status_name = $status['nom'] ?? 'statut inconnu';
                    $message = "GeekBoard: Votre réparation est maintenant en statut \"$status_name\". Pour plus d'informations, connectez-vous à votre espace client.";
                }
                
                // Envoi du SMS
                file_put_contents($logFile, "Tentative d'envoi de SMS à $telephone\n", FILE_APPEND);
                $sms_result = send_sms($telephone, $message);
                
                file_put_contents($logFile, "Résultat de l'envoi SMS: " . print_r($sms_result, true) . "\n", FILE_APPEND);
                
                // Déterminer si l'envoi a réussi
                $sms_sent = isset($sms_result['success']) && $sms_result['success'] === true;
                
                // Enregistrer dans la table reparation_sms
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO reparation_sms (
                            reparation_id, template_id, statut_id, telephone, message
                        ) VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $template_id = isset($template['id']) ? $template['id'] : 0;
                    
                    $stmt->execute([$repair_id, $template_id, $status_id, $telephone, $message]);
                    
                    file_put_contents($logFile, "SMS enregistré dans reparation_sms: ID=" . $pdo->lastInsertId() . "\n", FILE_APPEND);
                } catch (Exception $e) {
                    file_put_contents($logFile, "Erreur lors de l'enregistrement dans reparation_sms: " . $e->getMessage() . "\n", FILE_APPEND);
                }
                
                $response['data']['sms_sent'] = $sms_sent;
                $response['data']['sms_message'] = $sms_sent 
                    ? "SMS envoyé à $client_nom ($telephone) [mode simplifié]"
                    : "Échec de l'envoi du SMS à $client_nom ($telephone): " . ($sms_result['message'] ?? 'Erreur inconnue');
            } else {
                file_put_contents($logFile, "Client non trouvé ou sans téléphone\n", FILE_APPEND);
                $response['data']['sms_message'] = "SMS non envoyé: client non trouvé ou sans téléphone";
            }
        } catch (Exception $e) {
            file_put_contents($logFile, "Erreur lors de l'envoi du SMS: " . $e->getMessage() . "\n", FILE_APPEND);
            $response['data']['sms_message'] = "Erreur lors de l'envoi du SMS: " . $e->getMessage();
        }
    }
    
    // Renvoyer la réponse
    file_put_contents($logFile, "Réponse finale: " . json_encode($response) . "\n", FILE_APPEND);
    echo json_encode($response);
    
} catch (Exception $e) {
    file_put_contents($logFile, "ERREUR FATALE: " . $e->getMessage() . "\n", FILE_APPEND);
    
    $error_response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    file_put_contents($logFile, "Réponse d'erreur: " . json_encode($error_response) . "\n", FILE_APPEND);
    echo json_encode($error_response);
}

// Ajouter un séparateur de fin dans le log
file_put_contents($logFile, "--- Fin de la requête ---\n\n", FILE_APPEND); 