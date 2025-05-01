<?php
/**
 * Classe qui gère l'envoi des SMS via l'API SMS Gateway
 */
class SmsService {
    private $apiUrl = 'https://api.sms-gate.app/3rdparty/v1/message';
    private $apiUsername = '-GCB75';
    private $apiPassword = 'Mamanmaman06400';
    
    /**
     * Envoie un SMS à un numéro spécifié
     * 
     * @param string $phoneNumber Le numéro de téléphone du destinataire
     * @param string $message Le message à envoyer
     * @return bool Succès ou échec de l'envoi
     */
    public function sendSms($phoneNumber, $message) {
        // Formater le numéro de téléphone
        $recipient = $this->formatPhoneNumber($phoneNumber);
        
        // Préparation des données JSON pour l'API
        $smsData = json_encode([
            'message' => $message,
            'phoneNumbers' => [$recipient]
        ]);
        
        // Configuration de la requête cURL
        $curl = curl_init($this->apiUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $smsData);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($smsData)
        ]);
        
        // Configuration de l'authentification Basic
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "$this->apiUsername:$this->apiPassword");
        
        // Désactiver la vérification SSL pour le développement (à activer en production)
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        
        // Exécution de la requête
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        // Vérification des erreurs
        if ($response === false) {
            $this->logError("Erreur cURL: " . curl_error($curl));
            curl_close($curl);
            return false;
        }
        
        curl_close($curl);
        
        // Traitement de la réponse
        $responseData = json_decode($response, true);
        
        // Le code 202 indique une acceptation (Accepted) pour traitement asynchrone
        if (($status == 200 || $status == 202) && $responseData) {
            $this->logSuccess("SMS envoyé avec succès au numéro: " . $recipient);
            return true;
        } else {
            $this->logError("Échec de l'envoi SMS - Code: $status, Réponse: " . $response);
            return false;
        }
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