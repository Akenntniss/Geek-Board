<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Désactiver l'affichage des erreurs pour les réponses JSON propres
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Créer un fichier de log pour le débogage
$logFile = __DIR__ . '/status_update.log';
file_put_contents($logFile, "--- Nouvelle tentative de mise à jour du statut ---\n", FILE_APPEND);
file_put_contents($logFile, "Date: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

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
    $stmt = $pdo->prepare("UPDATE reparations SET statut_id = ? WHERE id = ?");
    $result = $stmt->execute([$status_id, $repair_id]);
    
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
            
            // Récupérer les informations du client
            $stmt = $pdo->prepare("
                SELECT c.telephone, c.nom, c.prenom
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                WHERE r.id = ?
            ");
            $stmt->execute([$repair_id]);
            $client_data = $stmt->fetch();
            
            if ($client_data) {
                $telephone = $client_data['telephone'];
                $client_nom = $client_data['nom'] . ' ' . $client_data['prenom'];
                
                // Récupérer le nouveau statut
                $status_name = $status['nom'] ?? 'statut inconnu';
                
                // Construire le message
                $message = "GeekBoard: Votre réparation est maintenant en statut \"$status_name\". Pour plus d'informations, connectez-vous à votre espace client.";
                
                // Vérifier si la fonction send_sms existe
                if (!function_exists('send_sms')) {
                    file_put_contents($logFile, "AVERTISSEMENT: Fonction send_sms non définie\n", FILE_APPEND);
                    $response['data']['sms_message'] = "Fonction d'envoi de SMS non disponible";
                } else {
                    // Envoi du SMS
                    if (!empty($telephone)) {
                        file_put_contents($logFile, "Tentative d'envoi de SMS à $telephone\n", FILE_APPEND);
                        $sms_sent = send_sms($telephone, $message);
                        
                        file_put_contents($logFile, "Résultat de l'envoi du SMS: " . ($sms_sent ? "OK" : "ÉCHEC") . "\n", FILE_APPEND);
                        
                        $response['data']['sms_sent'] = $sms_sent;
                        $response['data']['sms_message'] = $sms_sent 
                            ? "SMS envoyé à $client_nom ($telephone)"
                            : "Échec de l'envoi du SMS à $client_nom ($telephone)";
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