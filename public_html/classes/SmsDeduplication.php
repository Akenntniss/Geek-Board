<?php
/**
 * Classe pour éviter l'envoi de SMS en double
 * Protection basée sur le temps et le contenu
 */
class SmsDeduplication {
    private $shop_pdo;
    private $dedupTimeWindow = 60; // 60 secondes de protection contre les doublons
    
    public function __construct() {
        $this->shop_pdo = getShopDBConnection();
        $this->createDeduplicationTable();
    }
    
    /**
     * Créer la table de déduplication si elle n'existe pas
     */
    private function createDeduplicationTable() {
        try {
            $this->shop_pdo->exec("
                CREATE TABLE IF NOT EXISTS sms_deduplication (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    phone_hash VARCHAR(64) NOT NULL,
                    message_hash VARCHAR(64) NOT NULL,
                    status_id INT,
                    repair_id INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_phone_message (phone_hash, message_hash),
                    INDEX idx_created_at (created_at)
                )
            ");
        } catch (PDOException $e) {
            error_log("Erreur création table sms_deduplication: " . $e->getMessage());
        }
    }
    
    /**
     * Vérifier si ce SMS a déjà été envoyé récemment
     * 
     * @param string $phone Numéro de téléphone
     * @param string $message Contenu du message
     * @param int $statusId ID du statut
     * @param int $repairId ID de la réparation
     * @return bool True si le SMS peut être envoyé, False s'il est en doublon
     */
    public function canSendSms($phone, $message, $statusId = null, $repairId = null) {
        try {
            $phoneHash = hash('sha256', $phone);
            $messageHash = hash('sha256', trim($message));
            
            // Nettoyer les anciens enregistrements (plus vieux que 24h)
            $this->cleanOldRecords();
            
            // Vérifier s'il existe un SMS similaire dans la fenêtre de temps
            $stmt = $this->shop_pdo->prepare("
                SELECT COUNT(*) as count, MAX(created_at) as last_sent
                FROM sms_deduplication 
                WHERE phone_hash = ? 
                AND message_hash = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$phoneHash, $messageHash, $this->dedupTimeWindow]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                error_log("SMS DUPLIQUÉ BLOQUÉ: Téléphone: " . substr($phone, -4) . 
                         ", Message: " . substr($message, 0, 50) . "..., " .
                         "Dernier envoi: " . $result['last_sent']);
                return false;
            }
            
            // Enregistrer cet envoi pour la déduplication future
            $stmt = $this->shop_pdo->prepare("
                INSERT INTO sms_deduplication 
                (phone_hash, message_hash, status_id, repair_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$phoneHash, $messageHash, $statusId, $repairId]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Erreur déduplication SMS: " . $e->getMessage());
            // En cas d'erreur, autoriser l'envoi pour ne pas bloquer le service
            return true;
        }
    }
    
    /**
     * Nettoyer les enregistrements anciens (plus de 24h)
     */
    private function cleanOldRecords() {
        try {
            $this->shop_pdo->exec("
                DELETE FROM sms_deduplication 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
        } catch (PDOException $e) {
            error_log("Erreur nettoyage sms_deduplication: " . $e->getMessage());
        }
    }
    
    /**
     * Obtenir les statistiques de déduplication
     * 
     * @return array Statistiques des dernières 24h
     */
    public function getStats() {
        try {
            $stmt = $this->shop_pdo->query("
                SELECT 
                    COUNT(*) as total_attempts,
                    COUNT(DISTINCT phone_hash) as unique_phones,
                    COUNT(DISTINCT message_hash) as unique_messages,
                    MIN(created_at) as first_attempt,
                    MAX(created_at) as last_attempt
                FROM sms_deduplication 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur stats déduplication: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Modifier la fenêtre de temps de déduplication
     * 
     * @param int $seconds Nombre de secondes
     */
    public function setDeduplicationWindow($seconds) {
        $this->dedupTimeWindow = max(10, min(3600, $seconds)); // Entre 10s et 1h
    }
} 