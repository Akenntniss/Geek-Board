<?php
/**
 * API pour envoyer des SMS depuis d'autres parties de l'application
 * 
 * Exemple d'utilisation:
 * 
 * // Envoi direct
 * include_once 'api/sms/send.php';
 * send_sms('+33600000000', 'Votre message ici', 'client', 123);
 * 
 * // Via AJAX
 * $.post('api/sms/send.php', {
 *     recipient: '+33600000000',
 *     message: 'Votre message ici',
 *     reference_type: 'client',
 *     reference_id: 123
 * }, function(response) {
 *     console.log(response);
 * });
 */

// Vérifier si appelé directement ou inclus
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    // Appelé directement, vérifier l'authentification
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
        exit;
    }
    
    // Définir le type de contenu JSON
    header('Content-Type: application/json; charset=UTF-8');
    
    // Récupérer les paramètres
    $recipient = isset($_POST['recipient']) ? trim($_POST['recipient']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $reference_type = isset($_POST['reference_type']) ? trim($_POST['reference_type']) : null;
    $reference_id = isset($_POST['reference_id']) ? (int)$_POST['reference_id'] : null;
    
    // Valider les paramètres
    if (empty($recipient) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Destinataire et message obligatoires']);
        exit;
    }
    
    // Inclure les fonctions
    require_once 'functions.php';
    
    // Envoyer le SMS
    $sms_id = queue_sms(
        $recipient,
        $message,
        $reference_type,
        $reference_id,
        $_SESSION['user_id']
    );
    
    // Répondre
    if ($sms_id) {
        echo json_encode([
            'success' => true, 
            'message' => 'SMS mis en file d\'attente', 
            'sms_id' => $sms_id
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de l\'envoi du SMS'
        ]);
    }
    
    exit;
}

/**
 * Envoie un SMS
 * 
 * @param string $recipient Numéro de téléphone du destinataire
 * @param string $message Contenu du message
 * @param string $reference_type Type de référence (client, reparation, etc.)
 * @param int $reference_id ID de la référence
 * @param int $user_id ID de l'utilisateur qui envoie le SMS
 * @return int|bool ID du SMS ou false en cas d'échec
 */
function send_sms($recipient, $message, $reference_type = null, $reference_id = null, $user_id = null) {
    // Inclure les fonctions
    require_once __DIR__ . '/functions.php';
    
    // Nettoyer le numéro de téléphone
    $recipient = preg_replace('/[^0-9+]/', '', $recipient);
    
    // Obtenir l'ID de l'utilisateur actuel si non fourni
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    // Envoyer le SMS
    return queue_sms($recipient, $message, $reference_type, $reference_id, $user_id);
}

/**
 * Envoie un SMS de notification pour une réparation
 * 
 * @param int $reparation_id ID de la réparation
 * @param string $statut Nouveau statut de la réparation
 * @param int $user_id ID de l'utilisateur qui envoie le SMS
 * @return int|bool ID du SMS ou false en cas d'échec
 */
function send_reparation_notification($reparation_id, $statut, $user_id = null) {
    // Vérifier si les notifications SMS sont activées pour ce type
    require_once __DIR__ . '/functions.php';
    $notification_types = explode(',', get_sms_config('notification_types', ''));
    if (!in_array('reparation_status', $notification_types)) {
        return false;
    }
    
    // Inclure les fonctions principales
    require_once __DIR__ . '/../../includes/functions.php';
    
    // Récupérer les informations de la réparation et du client
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, c.nom, c.prenom, c.telephone 
            FROM reparations r 
            JOIN clients c ON r.client_id = c.id 
            WHERE r.id = :id
        ");
        
        $stmt->execute([':id' => $reparation_id]);
        $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reparation || empty($reparation['telephone'])) {
            return false;
        }
        
        // Récupérer la description du statut
        $stmt = $pdo->prepare("
            SELECT nom FROM statuts
            WHERE code = :code
        ");
        
        $stmt->execute([':code' => $statut]);
        $statut_info = $stmt->fetch(PDO::FETCH_ASSOC);
        $statut_nom = $statut_info ? $statut_info['nom'] : ucfirst($statut);
        
        // Construire le message
        $message = "Bonjour {$reparation['prenom']}, votre réparation #{$reparation_id} est maintenant ";
        $message .= "à l'état: {$statut_nom}. ";
        
        if ($statut == 'terminee') {
            $message .= "Votre appareil est prêt à être récupéré. ";
        } elseif ($statut == 'attente_pieces') {
            $message .= "Nous attendons les pièces nécessaires. ";
        } elseif ($statut == 'en_cours') {
            $message .= "Notre technicien travaille actuellement sur votre appareil. ";
        }
        
        $message .= "Pour plus d'informations, contactez-nous au 0123456789.";
        
        // Envoyer le SMS
        return send_sms(
            $reparation['telephone'],
            $message,
            'reparation',
            $reparation_id,
            $user_id
        );
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de la notification de réparation: " . $e->getMessage());
        return false;
    }
} 