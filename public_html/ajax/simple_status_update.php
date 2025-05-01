<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Désactiver l'affichage des erreurs pour les réponses JSON propres
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Créer un fichier de log pour le débogage
$logFile = __DIR__ . '/simple_status_update.log';
file_put_contents($logFile, "--- Simple status update request ---\n", FILE_APPEND);
file_put_contents($logFile, "Date: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
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
    
    // Mise à jour simplifiée du statut avec PDO
    $stmt = $pdo->prepare("UPDATE reparations SET statut_id = ? WHERE id = ?");
    $result = $stmt->execute([$status_id, $repair_id]);
    
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
            
            // Récupérer les informations du client avec PDO
            $stmt = $pdo->prepare("
                SELECT c.telephone, c.nom, c.prenom
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                WHERE r.id = ?
            ");
            $stmt->execute([$repair_id]);
            $client = $stmt->fetch();
            
            if ($client && !empty($client['telephone'])) {
                $telephone = $client['telephone'];
                $client_nom = $client['nom'] . ' ' . $client['prenom'];
                $status_name = $status['nom'] ?? 'statut inconnu';
                
                // Message simplifié
                $message = "GeekBoard: Votre réparation est maintenant en statut \"$status_name\". Pour plus d'informations, connectez-vous à votre espace client.";
                
                // Envoi du SMS
                file_put_contents($logFile, "Tentative d'envoi de SMS à $telephone\n", FILE_APPEND);
                $sms_sent = send_sms($telephone, $message);
                
                file_put_contents($logFile, "Résultat de l'envoi SMS: " . ($sms_sent ? 'OK' : 'ÉCHEC') . "\n", FILE_APPEND);
                
                $response['data']['sms_sent'] = $sms_sent;
                $response['data']['sms_message'] = $sms_sent 
                    ? "SMS envoyé à $client_nom ($telephone) [mode simplifié]"
                    : "Échec de l'envoi du SMS à $client_nom ($telephone) [mode simplifié]";
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