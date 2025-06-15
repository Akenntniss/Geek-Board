<?php
/**
 * Fonctions pour la gestion des SMS avec SMSSync
 */

// Inclure les fonctions principales
require_once __DIR__ . '/../../includes/functions.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

/**
 * Récupère une configuration SMS
 * 
 * @param string $param_name Nom du paramètre
 * @param mixed $default Valeur par défaut si non trouvée
 * @return mixed Valeur du paramètre
 */
function get_sms_config($param_name, $default = null) {
    global $shop_pdo;
    
    try {
        $stmt = $shop_pdo->prepare("SELECT param_value FROM sms_config WHERE param_name = :param_name");
        $stmt->execute([':param_name' => $param_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['param_value'] : $default;
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération de la configuration SMS: " . $e->getMessage());
        return $default;
    }
}

/**
 * Met à jour une configuration SMS
 * 
 * @param string $param_name Nom du paramètre
 * @param mixed $param_value Valeur du paramètre
 * @return bool Succès ou échec
 */
function update_sms_config($param_name, $param_value) {
    global $shop_pdo;
    
    try {
        $stmt = $shop_pdo->prepare("
            INSERT INTO sms_config (param_name, param_value) 
            VALUES (:param_name, :param_value)
            ON DUPLICATE KEY UPDATE param_value = :param_value
        ");
        
        return $stmt->execute([
            ':param_name' => $param_name,
            ':param_value' => $param_value
        ]);
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour de la configuration SMS: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si l'API key est valide
 * 
 * @param string $api_key Clé API fournie
 * @return bool Validité de la clé
 */
function verify_sms_api_key($api_key) {
    $stored_key = get_sms_config('api_key');
    return $stored_key && $api_key === $stored_key;
}

/**
 * Enregistre un SMS sortant dans la base de données
 * 
 * @param string $recipient Destinataire
 * @param string $message Contenu du message
 * @param string $reference_type Type de référence
 * @param int $reference_id ID de référence
 * @param int $created_by ID de l'utilisateur créateur
 * @return int|bool ID du SMS ou false en cas d'échec
 */
function queue_sms($recipient, $message, $reference_type = null, $reference_id = null, $created_by = null) {
    global $shop_pdo;
    
    // Vérifier si le service SMS est activé
    if (get_sms_config('sms_enabled', '1') !== '1') {
        error_log("Service SMS désactivé");
        return false;
    }
    
    // Nettoyer le numéro de téléphone
    $recipient = preg_replace('/[^0-9+]/', '', $recipient);
    
    try {
        $stmt = $shop_pdo->prepare("
            INSERT INTO sms_outgoing (
                recipient, message, status, reference_type, reference_id, created_by
            ) VALUES (
                :recipient, :message, 'pending', :reference_type, :reference_id, :created_by
            )
        ");
        
        $stmt->execute([
            ':recipient' => $recipient,
            ':message' => $message,
            ':reference_type' => $reference_type,
            ':reference_id' => $reference_id,
            ':created_by' => $created_by
        ]);
        
        return $shop_pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement du SMS: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les SMS en attente d'envoi
 * 
 * @param int $limit Nombre maximal de SMS à récupérer
 * @return array Liste des SMS
 */
function get_pending_sms($limit = 10) {
    global $shop_pdo;
    
    try {
        $stmt = $shop_pdo->prepare("
            SELECT * FROM sms_outgoing 
            WHERE status = 'pending' AND retry_count < :max_retries
            ORDER BY created_at ASC
            LIMIT :limit
        ");
        
        $max_retries = (int)get_sms_config('max_retries', 3);
        $stmt->bindValue(':max_retries', $max_retries, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des SMS en attente: " . $e->getMessage());
        return [];
    }
}

/**
 * Met à jour le statut d'un SMS
 * 
 * @param int $sms_id ID du SMS
 * @param string $status Nouveau statut
 * @param string $timestamp_field Champ de timestamp à mettre à jour
 * @return bool Succès ou échec
 */
function update_sms_status($sms_id, $status, $timestamp_field = null) {
    global $shop_pdo;
    
    try {
        $sql = "UPDATE sms_outgoing SET status = :status";
        
        if ($timestamp_field) {
            $sql .= ", $timestamp_field = NOW()";
        }
        
        if ($status === 'failed') {
            $sql .= ", retry_count = retry_count + 1";
        }
        
        $sql .= " WHERE id = :sms_id";
        
        $stmt = $shop_pdo->prepare($sql);
        
        return $stmt->execute([
            ':status' => $status,
            ':sms_id' => $sms_id
        ]);
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour du statut du SMS: " . $e->getMessage());
        return false;
    }
}

/**
 * Enregistre un SMS entrant dans la base de données
 * 
 * @param string $sender Expéditeur
 * @param string $message Contenu du message
 * @param string $received_timestamp Horodatage de réception
 * @return int|bool ID du SMS ou false en cas d'échec
 */
function record_incoming_sms($sender, $message, $received_timestamp = null) {
    global $shop_pdo;
    
    // Nettoyer le numéro de téléphone
    $sender = preg_replace('/[^0-9+]/', '', $sender);
    
    // Utiliser l'horodatage actuel si non fourni
    if (!$received_timestamp) {
        $received_timestamp = date('Y-m-d H:i:s');
    }
    
    try {
        $stmt = $shop_pdo->prepare("
            INSERT INTO sms_incoming (
                sender, message, received_timestamp, status
            ) VALUES (
                :sender, :message, :received_timestamp, 'new'
            )
        ");
        
        $stmt->execute([
            ':sender' => $sender,
            ':message' => $message,
            ':received_timestamp' => $received_timestamp
        ]);
        
        $sms_id = $shop_pdo->lastInsertId();
        
        // Vérifier si ce numéro correspond à un client
        try {
            $stmt = $shop_pdo->prepare("
                SELECT id FROM clients 
                WHERE telephone = :telephone
                LIMIT 1
            ");
            
            $stmt->execute([':telephone' => $sender]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($client) {
                // Mettre à jour la référence
                $stmt = $shop_pdo->prepare("
                    UPDATE sms_incoming 
                    SET reference_type = 'client', reference_id = :client_id
                    WHERE id = :sms_id
                ");
                
                $stmt->execute([
                    ':client_id' => $client['id'],
                    ':sms_id' => $sms_id
                ]);
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la recherche du client pour le SMS entrant: " . $e->getMessage());
        }
        
        return $sms_id;
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement du SMS entrant: " . $e->getMessage());
        return false;
    }
}

/**
 * Envoie une notification à l'interface utilisateur concernant un nouveau SMS
 * 
 * @param int $sms_id ID du SMS entrant
 * @return bool Succès ou échec
 */
function notify_new_sms($sms_id) {
    global $shop_pdo;
    
    try {
        // Récupérer les informations du SMS
        $stmt = $shop_pdo->prepare("
            SELECT s.*, c.id AS client_id, c.nom, c.prenom  
            FROM sms_incoming s
            LEFT JOIN clients c ON s.reference_type = 'client' AND s.reference_id = c.id
            WHERE s.id = :sms_id
        ");
        
        $stmt->execute([':sms_id' => $sms_id]);
        $sms = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sms) {
            return false;
        }
        
        // Créer le message de notification
        $message = "Nouveau SMS de ";
        if ($sms['client_id']) {
            $message .= "{$sms['prenom']} {$sms['nom']}";
        } else {
            $message .= $sms['sender'];
        }
        
        // Récupérer les administrateurs
        $stmt = $shop_pdo->prepare("
            SELECT id FROM utilisateurs 
            WHERE role = 'admin'
        ");
        
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Insérer une notification pour chaque admin
        foreach ($admins as $admin) {
            $stmt = $shop_pdo->prepare("
                INSERT INTO notifications (
                    user_id, notification_type, message, related_id, related_type, 
                    is_important, status, created_at
                ) VALUES (
                    :user_id, 'sms_notification', :message, :related_id, 'sms_incoming',
                    1, 'new', NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $admin['id'],
                ':message' => $message,
                ':related_id' => $sms_id
            ]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de la création de la notification SMS: " . $e->getMessage());
        return false;
    }
} 