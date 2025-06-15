<?php
/**
 * Fonctions SMS unifiées pour GeekBoard
 * Migration vers nouvelle API SMS Gateway (http://168.231.85.4:3001/api)
 * 
 * Ce fichier remplace progressivement les anciens appels à l'API sms-gate.app
 */

// Inclure la nouvelle classe SMS
require_once __DIR__ . '/../classes/NewSmsService.php';

/**
 * Fonction principale d'envoi de SMS
 * Compatible avec l'ancienne fonction mais utilise la nouvelle API
 * 
 * @param string $phoneNumber Numéro de téléphone du destinataire
 * @param string $message Message à envoyer
 * @param string $reference_type Type de référence (optionnel)
 * @param int $reference_id ID de référence (optionnel)
 * @param int $user_id ID utilisateur (optionnel)
 * @return array Résultat avec success (bool) et message (string)
 */
function send_sms($phoneNumber, $message, $reference_type = null, $reference_id = null, $user_id = null) {
    try {
        // Log de l'appel pour débogage
        log_sms_call($phoneNumber, $message, $reference_type, $reference_id);
        
        // Validation des paramètres
        if (empty($phoneNumber) || empty($message)) {
            return [
                'success' => false,
                'message' => 'Numéro de téléphone et message obligatoires'
            ];
        }
        
        // Protection contre les doublons
        require_once __DIR__ . '/../classes/SmsDeduplication.php';
        $deduplication = new SmsDeduplication();
        
        $statusId = ($reference_type === 'repair_status') ? $reference_id : null;
        $repairId = ($reference_type === 'repair') ? $reference_id : null;
        
        if (!$deduplication->canSendSms($phoneNumber, $message, $statusId, $repairId)) {
            return [
                'success' => false,
                'message' => 'SMS identique envoyé récemment - Doublon bloqué',
                'duplicate_blocked' => true
            ];
        }
        
        // Limiter la longueur du message (16000 caractères max pour SMS longs)
        if (strlen($message) > 16000) {
            $message = substr($message, 0, 15997) . '...';
        }
        
        // Créer une instance du service SMS
        $smsService = new NewSmsService();
        
        // Envoyer le SMS
        $result = $smsService->sendSms($phoneNumber, $message);
        
        // Enregistrer le résultat dans la base de données si possible
        if (function_exists('log_sms_to_database')) {
            log_sms_to_database($phoneNumber, $message, $result, $reference_type, $reference_id, $user_id);
        }
        
        // Retourner le résultat dans le format attendu par l'ancien code
        return [
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'http_code' => $result['http_code'] ?? null
        ];
        
    } catch (Exception $e) {
        error_log("Erreur dans send_sms: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur technique lors de l\'envoi du SMS: ' . $e->getMessage()
        ];
    }
}

/**
 * Fonction d'envoi SMS directe (pour compatibilité avec certains fichiers)
 * 
 * @param string $telephone Numéro de téléphone
 * @param string $message Message à envoyer
 * @return array Résultat de l'envoi
 */
function send_sms_direct($telephone, $message) {
    return send_sms($telephone, $message);
}

/**
 * Log des appels SMS pour débogage
 * 
 * @param string $phoneNumber Numéro de téléphone
 * @param string $message Message
 * @param string $reference_type Type de référence
 * @param int $reference_id ID de référence
 */
function log_sms_call($phoneNumber, $message, $reference_type = null, $reference_id = null) {
    $logFile = __DIR__ . '/../logs/sms_calls_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] APPEL SMS - Dest: $phoneNumber, Ref: $reference_type:$reference_id, Message: " . substr($message, 0, 50) . "...\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Enregistre le SMS dans la base de données
 * 
 * @param string $phoneNumber Numéro de téléphone
 * @param string $message Message
 * @param array $result Résultat de l'envoi
 * @param string $reference_type Type de référence
 * @param int $reference_id ID de référence  
 * @param int $user_id ID utilisateur
 */
function log_sms_to_database($phoneNumber, $message, $result, $reference_type = null, $reference_id = null, $user_id = null) {
    try {
        // Utiliser la connexion shop spécifique
        $shop_pdo = getShopDBConnection();
        if (!$shop_pdo) {
            error_log("Impossible d'obtenir la connexion database pour log SMS");
            return;
        }
        
        // Vérifier si la table sms_logs existe
        $checkTable = $shop_pdo->query("SHOW TABLES LIKE 'sms_logs'");
        if ($checkTable->rowCount() == 0) {
            // Créer la table si elle n'existe pas
            create_sms_logs_table($shop_pdo);
        }
        
        $status = $result['success'] ? 'sent' : 'failed';
        $response_data = json_encode($result);
        
        $stmt = $shop_pdo->prepare("
            INSERT INTO sms_logs (
                recipient, message, status, response, 
                reference_type, reference_id, user_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $phoneNumber, $message, $status, $response_data,
            $reference_type, $reference_id, $user_id
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur lors de l'enregistrement SMS en BDD: " . $e->getMessage());
    }
}

/**
 * Crée la table sms_logs si elle n'existe pas
 * 
 * @param PDO $shop_pdo Connexion à la base de données de la boutique
 */
function create_sms_logs_table($shop_pdo) {
    try {
        $sql = "
            CREATE TABLE IF NOT EXISTS sms_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
                response TEXT,
                reference_type VARCHAR(50),
                reference_id INT,
                user_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_recipient (recipient),
                INDEX idx_reference (reference_type, reference_id),
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $shop_pdo->exec($sql);
        error_log("Table sms_logs créée avec succès");
        
    } catch (Exception $e) {
        error_log("Erreur lors de la création de la table sms_logs: " . $e->getMessage());
    }
}

/**
 * Test de connectivité avec la nouvelle API
 * 
 * @return array Résultat du test
 */
function test_new_sms_api() {
    try {
        $smsService = new NewSmsService();
        return $smsService->testConnection();
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erreur lors du test: ' . $e->getMessage()
        ];
    }
}

/**
 * Récupère l'historique des SMS
 * 
 * @param int $limit Nombre de SMS à récupérer
 * @param string $status Filtre par statut (optionnel)
 * @return array Liste des SMS
 */
function get_sms_history($limit = 50, $status = null) {
    try {
        $shop_pdo = getShopDBConnection();
        if (!$shop_pdo) {
            return [];
        }
        
        $sql = "SELECT * FROM sms_logs";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération de l'historique SMS: " . $e->getMessage());
        return [];
    }
}

/**
 * Formatage d'un numéro de téléphone pour affichage
 * 
 * @param string $phoneNumber Numéro à formater
 * @return string Numéro formaté
 */
function format_phone_display($phoneNumber) {
    // Supprimer le +33 et le remplacer par 0 pour l'affichage
    if (strpos($phoneNumber, '+33') === 0) {
        return '0' . substr($phoneNumber, 3);
    }
    return $phoneNumber;
} 