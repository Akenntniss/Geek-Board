<?php
/**
 * Classe SMS utilisant l'API SMS Gateway selon la documentation officielle
 * API Documentation: http://168.231.85.4/frontend/documentation.html
 * Base URL: http://168.231.85.4:3001/api
 */
class NewSmsService {
    private $apiUrl;
    private $maxRetries = 2;
    private $timeout = 30;
    
    public function __construct() {
        // URL de l'API selon la documentation
        $this->apiUrl = 'http://168.231.85.4:3001/api/messages/send';
    }
    
    /**
     * Envoie un SMS à un numéro spécifié
     * 
     * @param string $phoneNumber Le numéro de téléphone du destinataire
     * @param string $message Le message à envoyer
     * @param string $priority Priorité du message (low, normal, high)
     * @param int $simId ID de la SIM à utiliser (optionnel)
     * @return array Résultat de l'envoi avec succès/échec et détails
     */
    public function sendSms($phoneNumber, $message, $priority = 'normal', $simId = null) {
        // Formater le numéro de téléphone au format international
        $recipient = $this->formatPhoneNumber($phoneNumber);
        
        // Préparer les données selon la documentation API
        $smsData = [
            'recipient' => $recipient,
            'message' => $message,
            'priority' => $priority
        ];
        
        // Ajouter l'ID de SIM si spécifié
        if ($simId !== null) {
            $smsData['sim_id'] = (int)$simId;
        }
        
        $jsonData = json_encode($smsData);
        
        // Tentative d'envoi avec retry et backoff exponentiel
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            $result = $this->attemptSend($jsonData, $attempt);
            
            if ($result['success'] || $attempt == $this->maxRetries) {
                return $result;
            }
            
            // Backoff exponentiel entre les tentatives
            $delay = pow(2, $attempt - 1);
            $this->logError("Tentative $attempt échouée, attente de {$delay}s avant retry");
            sleep($delay);
        }
        
        return $result;
    }
    
    /**
     * Tentative d'envoi SMS
     * 
     * @param string $jsonData Données JSON à envoyer
     * @param int $attempt Numéro de la tentative
     * @return array Résultat de la tentative
     */
    private function attemptSend($jsonData, $attempt) {
        // Configuration de la requête cURL selon la documentation
        $curl = curl_init($this->apiUrl);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ],
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            // Désactiver la vérification SSL pour le développement local
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'GeekBoard SMS Client v1.0'
        ]);
        
        // Exécution de la requête
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        $requestInfo = curl_getinfo($curl);
        
        curl_close($curl);
        
        // Log de la requête pour debugging
        $this->logDebug("Tentative $attempt - HTTP Code: $httpCode, Time: {$requestInfo['total_time']}s");
        
        // Vérification des erreurs cURL
        if ($response === false) {
            $errorMsg = "Erreur de connexion cURL: $curlError";
            $this->logError("Tentative $attempt - $errorMsg");
            return [
                'success' => false,
                'message' => $errorMsg,
                'attempt' => $attempt,
                'http_code' => 0,
                'curl_error' => $curlError
            ];
        }
        
        // Traitement de la réponse selon la documentation API
        $responseData = json_decode($response, true);
        
        // Log de la réponse brute pour debugging
        $this->logDebug("Réponse brute (tentative $attempt): " . substr($response, 0, 500));
        
        if ($httpCode == 200 || $httpCode == 201) {
            if ($responseData && isset($responseData['success']) && $responseData['success']) {
                // Succès selon la documentation (200 = envoyé, 201 = ajouté à la queue)
                $statusMsg = $httpCode == 200 ? "SMS envoyé avec succès" : "SMS ajouté à la queue d'envoi";
                $this->logSuccess("$statusMsg (tentative $attempt)");
                return [
                    'success' => true,
                    'message' => $responseData['message'] ?? $statusMsg,
                    'data' => $responseData['data'] ?? null,
                    'attempt' => $attempt,
                    'http_code' => $httpCode,
                    'response_time' => $requestInfo['total_time']
                ];
            } else {
                // Réponse 200/201 mais pas de succès
                $errorMsg = $responseData['message'] ?? 'Réponse API invalide';
                $this->logError("Tentative $attempt - Échec: $errorMsg");
                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'response' => $responseData,
                    'attempt' => $attempt,
                    'http_code' => $httpCode
                ];
            }
        } else if ($httpCode == 400) {
            // Erreur de paramètres selon la documentation
            $errorMsg = $responseData['message'] ?? 'Paramètres invalides';
            $details = $responseData['details'] ?? null;
            $this->logError("Tentative $attempt - Erreur 400: $errorMsg");
            return [
                'success' => false,
                'message' => $errorMsg,
                'details' => $details,
                'response' => $responseData,
                'attempt' => $attempt,
                'http_code' => $httpCode
            ];
        } else if ($httpCode == 429) {
            // Limite de taux dépassée
            $errorMsg = "Limite de taux dépassée - Trop de requêtes";
            $this->logError("Tentative $attempt - $errorMsg");
            return [
                'success' => false,
                'message' => $errorMsg,
                'response' => $responseData,
                'attempt' => $attempt,
                'http_code' => $httpCode,
                'retry_after' => $responseData['retry_after'] ?? null
            ];
        } else {
            // Autres erreurs HTTP
            $errorMsg = $responseData['message'] ?? "Erreur HTTP $httpCode";
            $this->logError("Tentative $attempt - $errorMsg");
            return [
                'success' => false,
                'message' => $errorMsg,
                'response' => $responseData,
                'attempt' => $attempt,
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Formate le numéro de téléphone au format international
     * Selon la documentation API, le format requis est +33612345678
     * 
     * @param string $phoneNumber Le numéro à formater
     * @return string Le numéro formaté
     */
    private function formatPhoneNumber($phoneNumber) {
        // Supprimer tous les caractères non numériques sauf +
        $formatted = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Gestion des différents formats français
        if (substr($formatted, 0, 1) !== '+') {
            if (substr($formatted, 0, 1) === '0') {
                // Format français 0612345678 -> +33612345678
                $formatted = '+33' . substr($formatted, 1);
            } else if (substr($formatted, 0, 2) === '33') {
                // Format 33612345678 -> +33612345678
                $formatted = '+' . $formatted;
            } else if (strlen($formatted) === 9) {
                // Format 612345678 -> +33612345678
                $formatted = '+33' . $formatted;
            } else {
                // Autre format, ajouter + par défaut
                $formatted = '+' . $formatted;
            }
        }
        
        return $formatted;
    }
    
    /**
     * Récupère l'historique des messages via l'API
     * 
     * @param int $page Numéro de page
     * @param int $limit Nombre d'éléments par page
     * @param string $status Filtrer par statut
     * @return array Historique des messages
     */
    public function getHistory($page = 1, $limit = 50, $status = null) {
        $url = 'http://168.231.85.4:3001/api/messages/history';
        $params = ['page' => $page, 'limit' => $limit];
        
        if ($status) {
            $params['status'] = $status;
        }
        
        $fullUrl = $url . '?' . http_build_query($params);
        
        $curl = curl_init($fullUrl);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($response !== false && $httpCode == 200) {
            return json_decode($response, true);
        }
        
        return [
            'success' => false,
            'message' => "Erreur lors de la récupération de l'historique (HTTP $httpCode)"
        ];
    }
    
    /**
     * Récupère le statut des SIMs via l'API
     * 
     * @return array Statut des SIMs
     */
    public function getSimsStatus() {
        $url = 'http://168.231.85.4:3001/api/sims';
        
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($response !== false && $httpCode == 200) {
            return json_decode($response, true);
        }
        
        return [
            'success' => false,
            'message' => "Erreur lors de la récupération du statut des SIMs (HTTP $httpCode)"
        ];
    }
    
    /**
     * Teste la connectivité avec l'API
     * 
     * @return array Résultat du test
     */
    public function testConnection() {
        // Test de connectivité simple en récupérant le statut des SIMs
        $simsResult = $this->getSimsStatus();
        
        if ($simsResult['success'] ?? false) {
            return [
                'success' => true,
                'message' => 'API SMS Gateway accessible et fonctionnelle',
                'sims_count' => count($simsResult['data'] ?? [])
            ];
        }
        
        // Test alternatif avec un endpoint de base
        $curl = curl_init('http://168.231.85.4:3001/api/sims');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_NOBODY => true // HEAD request
        ]);
        
        curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        if ($httpCode >= 200 && $httpCode < 400) {
            return [
                'success' => true,
                'message' => 'API SMS Gateway accessible',
                'http_code' => $httpCode
            ];
        }
        
        return [
            'success' => false,
            'message' => $curlError ?: "API non accessible (Code: $httpCode)",
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Journalise un message de succès
     */
    private function logSuccess($message) {
        $this->writeLog('SUCCESS', $message);
    }
    
    /**
     * Journalise un message d'erreur
     */
    private function logError($message) {
        $this->writeLog('ERROR', $message);
        error_log("NewSmsService ERROR: $message");
    }
    
    /**
     * Journalise un message de debug
     */
    private function logDebug($message) {
        $this->writeLog('DEBUG', $message);
    }
    
    /**
     * Écrit dans le fichier de log
     */
    private function writeLog($level, $message) {
        $logFile = __DIR__ . '/../logs/new_sms_' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
} 