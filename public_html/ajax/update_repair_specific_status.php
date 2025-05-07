<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Désactiver l'affichage des erreurs pour les réponses JSON propres
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Définir le chemin de base pour les inclusions
$root_path = realpath(__DIR__ . '/..');
define('BASE_PATH', $root_path);

// Créer un fichier de log pour le débogage
$logFile = __DIR__ . '/status_update.log';
file_put_contents($logFile, "--- Nouvelle tentative de mise à jour du statut ---\n", FILE_APPEND);
file_put_contents($logFile, "Date: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents($logFile, "BASE_PATH: " . BASE_PATH . "\n", FILE_APPEND);

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    $functions_path = realpath(__DIR__ . '/../includes/functions.php');
    
    file_put_contents($logFile, "Config path: " . $config_path . "\n", FILE_APPEND);
    file_put_contents($logFile, "Functions path: " . $functions_path . "\n", FILE_APPEND);

    // Inclure les fichiers requis
    if (!$config_path || !$functions_path) {
        throw new Exception('Impossible de localiser les fichiers requis');
    }

    // Inclure le fichier de configuration
    require_once $config_path;
    file_put_contents($logFile, "Paramètres de connexion: host=" . DB_HOST . ", user=" . DB_USER . "\n", FILE_APPEND);
    
    // Inclure les fonctions
    require_once $functions_path;

    // Vérifier que la connexion PDO existe
    if (!isset($pdo) || $pdo === null) {
        file_put_contents($logFile, "ERREUR: Connexion PDO non disponible après inclusion de database.php\n", FILE_APPEND);
        throw new Exception('Erreur de connexion à la base de données: connexion PDO non disponible');
    }

    file_put_contents($logFile, "Connexion PDO établie avec succès\n", FILE_APPEND);
    
    // Déterminer comment les données sont envoyées
    $input = file_get_contents('php://input');
    file_put_contents($logFile, "PHP INPUT: " . $input . "\n", FILE_APPEND);
    
    // Récupérer les données en analysant diverses sources
    if (!empty($input)) {
        // Tenter de décoder le JSON directement
        $data = json_decode($input, true);
        file_put_contents($logFile, "Données JSON reçues directement\n", FILE_APPEND);
    } else if (isset($_POST['json_data'])) {
        // Récupérer les données JSON depuis FormData
        $input = $_POST['json_data'];
        $data = json_decode($input, true);
        file_put_contents($logFile, "Données JSON reçues via FormData\n", FILE_APPEND);
    } else if (!empty($_POST)) {
        // Récupérer les données depuis les paramètres POST standards
        $data = [
            'repair_id' => isset($_POST['repair_id']) ? $_POST['repair_id'] : null,
            'status_id' => isset($_POST['status_id']) ? $_POST['status_id'] : null,
            'send_sms' => isset($_POST['send_sms']) ? $_POST['send_sms'] : false,
            'user_id' => isset($_POST['user_id']) ? $_POST['user_id'] : 1 // Utiliser admin par défaut
        ];
        file_put_contents($logFile, "Données reçues via POST standard\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "ERREUR: Aucune donnée n'a été reçue\n", FILE_APPEND);
        throw new Exception('Aucune donnée reçue');
    }

    // Vérifier si nous avons des données valides
    if (!isset($data) || !is_array($data)) {
        file_put_contents($logFile, "ERREUR: Données reçues invalides\n", FILE_APPEND);
        throw new Exception('Données reçues invalides');
    }
    
    file_put_contents($logFile, "Données décodées: " . print_r($data, true) . "\n", FILE_APPEND);

    // Valider les données requises
    if (!isset($data['repair_id']) || !isset($data['status_id'])) {
        file_put_contents($logFile, "ERREUR: Données requises manquantes\n", FILE_APPEND);
        throw new Exception('Données requises manquantes');
    }
    
    $repair_id = $data['repair_id'];
    $status_id = $data['status_id'];
    $send_sms = isset($data['send_sms']) ? (bool)$data['send_sms'] : false;
    $user_id = isset($data['user_id']) ? $data['user_id'] : 1; // Utiliser l'ID de l'admin par défaut
    
    file_put_contents($logFile, "Paramètres pour la mise à jour: 
        repair_id: $repair_id
        status_id: $status_id
        send_sms: " . ($send_sms ? 'true' : 'false') . "
        user_id: $user_id
    \n", FILE_APPEND);
    
    // Mise à jour du statut de la réparation
    file_put_contents($logFile, "Tentative de mise à jour...\n", FILE_APPEND);
    
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
        file_put_contents($logFile, "ERREUR lors de la mise à jour: " . implode(", ", $stmt->errorInfo()) . "\n", FILE_APPEND);
        throw new Exception('Erreur lors de la mise à jour du statut: ' . implode(", ", $stmt->errorInfo()));
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
        
        // Insérer un enregistrement dans reparation_logs
        try {
            $stmt_log = $pdo->prepare("
                INSERT INTO reparation_logs (
                    reparation_id, employe_id, action_type, statut_avant, statut_apres, details
                ) VALUES (?, ?, 'changement_statut', ?, ?, ?)
            ");
            
            $details = "Mise à jour du statut avec SMS " . ($send_sms ? "activé" : "désactivé");
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
    
    // Générer une réponse
    $response = [
        'success' => true,
        'message' => 'Statut mis à jour avec succès',
        'data' => [
            'badge' => [
                'text' => $status['nom'] ?? 'Statut inconnu',
                'color' => $status['code'] ?? '#999999'
            ],
            'sms_sent' => false,
            'sms_message' => 'SMS non traité'
        ]
    ];
    
    // Si l'envoi de SMS est demandé
    if ($send_sms) {
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
            
            if ($repair_data) {
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
                    
                    // Récupérer le nouveau statut pour le message de fallback
                    $status_name = $status['nom'] ?? 'statut inconnu';
                    $message = "GeekBoard: Votre réparation est maintenant en statut \"$status_name\". Pour plus d'informations, connectez-vous à votre espace client.";
                }
                
                // Vérifier si la fonction send_sms existe
                if (!function_exists('send_sms')) {
                    file_put_contents($logFile, "AVERTISSEMENT: Fonction send_sms non définie\n", FILE_APPEND);
                    $response['data']['sms_message'] = "Fonction d'envoi de SMS non disponible";
                } else {
                    // Envoi du SMS
                    if (!empty($telephone)) {
                        file_put_contents($logFile, "Tentative d'envoi de SMS à $telephone\n", FILE_APPEND);
                        
                        // Appeler la fonction send_sms et récupérer le résultat complet
                        $sms_result = send_sms($telephone, $message);
                    
                        // Journaliser le résultat complet
                        file_put_contents($logFile, "Résultat de l'envoi du SMS: " . print_r($sms_result, true) . "\n", FILE_APPEND);
                        
                        // Déterminer si l'envoi a réussi
                        $sms_sent = isset($sms_result['success']) && $sms_result['success'] === true;
                        
                        // Enregistrer dans la table reparation_sms
                        try {
                        $stmt = $pdo->prepare("
                                INSERT INTO reparation_sms (
                                    reparation_id, template_id, statut_id, telephone, message
                                ) VALUES (?, ?, ?, ?, ?)
                            ");
                            
                            // Récupérer l'ID du template utilisé
                            $template_id = 0;
                            if (!empty($template) && isset($template['id'])) {
                                $template_id = $template['id'];
                            } else {
                                // Si on n'a pas l'ID du template, essayer de le récupérer
                                $stmt_template = $pdo->prepare("
                                    SELECT id FROM sms_templates WHERE statut_id = ? AND est_actif = 1 LIMIT 1
                        ");
                                $stmt_template->execute([$status_id]);
                                $template_id_result = $stmt_template->fetch(PDO::FETCH_COLUMN);
                                if ($template_id_result) {
                                    $template_id = $template_id_result;
                                }
                            }
                            
                            $stmt->execute([$repair_id, $template_id, $status_id, $telephone, $message]);
                            
                            file_put_contents($logFile, "SMS enregistré dans reparation_sms: ID=" . $pdo->lastInsertId() . "\n", FILE_APPEND);
                        } catch (Exception $e) {
                            file_put_contents($logFile, "Erreur lors de l'enregistrement dans reparation_sms: " . $e->getMessage() . "\n", FILE_APPEND);
                        }
                        
                        $response['data']['sms_sent'] = $sms_sent;
                        $response['data']['sms_message'] = $sms_sent 
                            ? "SMS envoyé à $client_nom ($telephone)"
                            : "Échec de l'envoi du SMS à $client_nom ($telephone): " . ($sms_result['message'] ?? 'Erreur inconnue');
                            
                        // Pour le débogage, ajouter des informations supplémentaires
                        $response['data']['sms_details'] = $sms_result;
                    } else {
                        file_put_contents($logFile, "Pas de numéro de téléphone pour le client\n", FILE_APPEND);
                        $response['data']['sms_message'] = "Impossible d'envoyer le SMS : numéro de téléphone manquant";
                    }
                }
            } else {
                file_put_contents($logFile, "Client non trouvé pour cette réparation\n", FILE_APPEND);
                $response['data']['sms_message'] = "Impossible d'envoyer le SMS : client non trouvé";
            }
        } catch (Exception $e) {
            file_put_contents($logFile, "Erreur lors de l'envoi du SMS: " . $e->getMessage() . "\n", FILE_APPEND);
            $response['data']['sms_message'] = "Erreur lors de l'envoi du SMS : " . $e->getMessage();
        }
    }
    
    // Logger l'action
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs (user_id, action, details) 
            VALUES (?, 'update_repair_status', ?)
        ");
        $details = json_encode([
            'repair_id' => $repair_id,
            'new_status_id' => $status_id,
            'sms_sent' => $response['data']['sms_sent']
        ]);
        $stmt->execute([$user_id, $details]);
        file_put_contents($logFile, "Log enregistré dans la table logs\n", FILE_APPEND);
    } catch (Exception $e) {
        // Ignorer les erreurs de logging pour ne pas bloquer la fonctionnalité principale
        file_put_contents($logFile, "Erreur lors du logging (ignorée): " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    // Envoyer la réponse
    file_put_contents($logFile, "Réponse finale: " . json_encode($response) . "\n", FILE_APPEND);
        echo json_encode($response);
    
} catch (Exception $e) {
    // Logger l'erreur
    file_put_contents($logFile, "ERREUR FATALE: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Envoyer une réponse d'erreur
    $error_response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    file_put_contents($logFile, "Réponse d'erreur: " . json_encode($error_response) . "\n", FILE_APPEND);
    echo json_encode($error_response);
}

// Ajouter un séparateur de fin dans le log
file_put_contents($logFile, "--- Fin de la requête ---\n\n", FILE_APPEND);

/**
 * Convertit un nom de catégorie en valeur ENUM pour la colonne statut
 * 
 * @param string $categorie_nom Le nom de la catégorie
 * @return string La valeur ENUM correspondante
 */
function map_status_to_enum($categorie_nom) {
    $map = [
        'Nouvelle' => 'En attente',
        'En cours' => 'En cours',
        'En attente' => 'En attente',
        'Terminé' => 'Terminé',
        'Annulé' => 'Terminé' // Il n'y a pas d'équivalent direct, nous utilisons 'Terminé'
    ];
    
    return isset($map[$categorie_nom]) ? $map[$categorie_nom] : 'En attente';
} 