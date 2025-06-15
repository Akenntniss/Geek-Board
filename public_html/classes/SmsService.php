<?php
/**
 * Classe qui gère l'envoi des SMS via l'API SMS Gateway
 */
class SmsService {
    // MIGRATION EN COURS - Cette classe sera remplacée par NewSmsService
    // URL de l'ancienne API (désactivée)
    private $apiUrl = 'DEPRECATED_API';
    private $apiUsername = 'DEPRECATED';
    private $apiPassword = 'DEPRECATED';
    
    /**
     * Envoie un SMS à un numéro spécifié
     * 
     * @param string $phoneNumber Le numéro de téléphone du destinataire
     * @param string $message Le message à envoyer
     * @return bool Succès ou échec de l'envoi
     */
    public function sendSms($phoneNumber, $message) {
        // MIGRATION - Utiliser la nouvelle fonction send_sms globale
        $this->logError("ATTENTION: SmsService::sendSms() est obsolète - Redirection vers nouvelle API");
        
        // Inclure les nouvelles fonctions SMS si pas déjà fait
        if (!function_exists('send_sms')) {
            require_once(__DIR__ . '/../includes/sms_functions.php');
        }
        
        // Appeler la nouvelle fonction unifiée
        $result = send_sms($phoneNumber, $message);
        
        // Retourner un booléen pour compatibilité avec l'ancien code
        return $result['success'];
    }
    
    /**
     * Formate le numéro de téléphone pour qu'il soit compatible avec l'API
     * 
     * @param string $phoneNumber Le numéro à formater
     * @return string Le numéro formaté
     */
    private function formatPhoneNumber($phoneNumber) {
        // Supprimer tous les caractères non numériques sauf +
        $formatted = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // S'assurer que le numéro commence par un +
        if (substr($formatted, 0, 1) !== '+') {
            if (substr($formatted, 0, 1) === '0') {
                // Numéro français commençant par 0
                $formatted = '+33' . substr($formatted, 1);
            } else if (substr($formatted, 0, 2) === '33') {
                // Numéro français sans +
                $formatted = '+' . $formatted;
            } else {
                // Autre numéro, ajouter + par défaut
                $formatted = '+' . $formatted;
            }
        }
        
        return $formatted;
    }
    
    /**
     * Journalise un message de succès
     * 
     * @param string $message Le message à journaliser
     */
    private function logSuccess($message) {
        if (function_exists('log_message')) {
            log_message($message);
        }
    }
    
    /**
     * Journalise un message d'erreur
     * 
     * @param string $message Le message d'erreur à journaliser
     */
    private function logError($message) {
        if (function_exists('log_message')) {
            log_message("ERREUR SMS: " . $message);
        }
    }
}
?> 