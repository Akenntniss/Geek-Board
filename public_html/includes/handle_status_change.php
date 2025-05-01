<?php
/**
 * Gestionnaire de changement de statut pour les réparations
 * Gère l'envoi de SMS automatisés en fonction du statut sélectionné
 */

// Vérifier si ce fichier est inclus dans un autre script
if (!defined('BASE_PATH')) {
    die("Accès direct non autorisé");
}

/**
 * Traite un changement de statut et envoie un SMS si nécessaire
 * @param int $reparation_id ID de la réparation
 * @param string $nouveau_statut Code du nouveau statut
 * @param string $ancien_statut Code de l'ancien statut (optionnel)
 * @return array Résultat du traitement
 */
function handle_status_change($reparation_id, $nouveau_statut, $ancien_statut = '') {
    global $pdo;
    
    // Initialiser le résultat
    $result = [
        'success' => false,
        'message' => '',
        'sms_sent' => false
    ];
    
    error_log("=== DÉBUT HANDLE_STATUS_CHANGE ===");
    error_log("Réparation ID: $reparation_id, Nouveau statut: $nouveau_statut, Ancien statut: $ancien_statut");
    
    try {
        // 1. Récupérer les informations sur le statut
        $stmt = $pdo->prepare("
            SELECT id 
            FROM statuts 
            WHERE code = ?
        ");
        $stmt->execute([$nouveau_statut]);
        $statut = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$statut) {
            $result['message'] = "Statut inconnu";
            error_log("Erreur: Statut $nouveau_statut inconnu");
            return $result;
        }
        
        $statut_id = $statut['id'];
        error_log("Statut ID trouvé: $statut_id");
        
        // 2. Vérifier s'il existe un modèle de SMS pour ce statut
        $stmt = $pdo->prepare("
            SELECT id, nom, contenu 
            FROM sms_templates 
            WHERE statut_id = ? AND est_actif = 1
        ");
        $stmt->execute([$statut_id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si aucun modèle actif n'est trouvé, on s'arrête là
        if (!$template) {
            $result['success'] = true;
            $result['message'] = "Statut mis à jour, aucun SMS à envoyer";
            error_log("Aucun modèle de SMS trouvé pour le statut ID: $statut_id");
            return $result;
        }
        
        error_log("Modèle de SMS trouvé: ID=" . $template['id'] . ", Nom=" . $template['nom']);
        
        // 3. Récupérer les informations de la réparation et du client
        $stmt = $pdo->prepare("
            SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email 
            FROM reparations r
            JOIN clients c ON r.client_id = c.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reparation_id]);
        $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reparation) {
            $result['message'] = "Réparation introuvable";
            error_log("Erreur: Réparation ID $reparation_id introuvable");
            return $result;
        }
        
        error_log("Réparation trouvée: Client=" . $reparation['client_nom'] . " " . $reparation['client_prenom']);
        
        // Vérifier si le client a un numéro de téléphone
        if (empty($reparation['client_telephone'])) {
            $result['message'] = "Le client n'a pas de numéro de téléphone";
            error_log("Erreur: Client sans numéro de téléphone");
            return $result;
        }
        
        error_log("Numéro de téléphone du client: " . $reparation['client_telephone']);
        
        // 4. Préparer le contenu du SMS en remplaçant les variables
        $message = $template['contenu'];
        
        // Tableau des remplacements
        $replacements = [
            '[CLIENT_NOM]' => $reparation['client_nom'],
            '[CLIENT_PRENOM]' => $reparation['client_prenom'],
            '[CLIENT_TELEPHONE]' => $reparation['client_telephone'],
            '[REPARATION_ID]' => $reparation_id,
            '[APPAREIL_TYPE]' => $reparation['type_appareil'],
            '[APPAREIL_MARQUE]' => $reparation['marque'],
            '[APPAREIL_MODELE]' => $reparation['modele'],
            '[DATE_RECEPTION]' => format_date($reparation['date_reception']),
            '[DATE_FIN_PREVUE]' => !empty($reparation['date_fin_prevue']) ? format_date($reparation['date_fin_prevue']) : '',
            '[PRIX]' => !empty($reparation['prix']) ? number_format($reparation['prix'], 2, ',', ' ') : ''
        ];
        
        // Effectuer les remplacements
        foreach ($replacements as $var => $value) {
            $message = str_replace($var, $value, $message);
        }
        
        error_log("Message préparé: " . substr($message, 0, 50) . "...");
        
        // 5. Formater le numéro de téléphone au format international
        $telephone = $reparation['client_telephone'];
        if (!preg_match('/^\+[0-9]{10,15}$/', $telephone)) {
            // Conversion basique des numéros français
            if (preg_match('/^0[6-7][0-9]{8}$/', $telephone)) {
                $telephone = '+33' . substr($telephone, 1);
            }
        }
        
        error_log("Numéro formaté: $telephone");
        
        // 6. Envoyer le SMS
        if (function_exists('send_sms')) {
            error_log("Fonction send_sms trouvée, tentative d'envoi...");
            $sms_result = send_sms($telephone, $message);
            
            if ($sms_result['success']) {
                error_log("SMS envoyé avec succès, enregistrement dans reparation_sms");
                
                // Enregistrer l'envoi du SMS dans la base de données
                $stmt = $pdo->prepare("
                    INSERT INTO reparation_sms (reparation_id, template_id, telephone, message, date_envoi, statut_id)
                    VALUES (?, ?, ?, ?, NOW(), ?)
                ");
                $stmt->execute([$reparation_id, $template['id'], $telephone, $message, $statut_id]);
                
                $result['success'] = true;
                $result['sms_sent'] = true;
                $result['message'] = "Statut mis à jour et SMS envoyé au client";
            } else {
                error_log("Échec de l'envoi SMS: " . ($sms_result['message'] ?? 'Erreur inconnue'));
                $result['success'] = true;
                $result['message'] = "Statut mis à jour, mais erreur lors de l'envoi du SMS: " . $sms_result['message'];
            }
        } else {
            error_log("Erreur: Fonction send_sms non disponible");
            $result['message'] = "La fonction d'envoi de SMS n'est pas disponible";
        }
        
    } catch (PDOException $e) {
        error_log("Erreur PDO: " . $e->getMessage());
        $result['message'] = "Erreur de base de données: " . $e->getMessage();
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        $result['message'] = "Erreur: " . $e->getMessage();
    }
    
    error_log("=== FIN HANDLE_STATUS_CHANGE ===");
    error_log("Résultat: " . ($result['success'] ? 'Succès' : 'Échec') . ", Message: " . $result['message']);
    
    return $result;
}

/**
 * Crée une table pour stocker l'historique des SMS envoyés liés aux réparations si elle n'existe pas
 */
function create_reparation_sms_table() {
    global $pdo;
    
    try {
        // Table reparation_sms
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `reparation_sms` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `reparation_id` INT NOT NULL,
              `template_id` INT NOT NULL,
              `telephone` VARCHAR(20) NOT NULL,
              `message` TEXT NOT NULL,
              `date_envoi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `statut_id` INT NULL,
              INDEX `idx_reparation_id` (`reparation_id`),
              INDEX `idx_template_id` (`template_id`),
              INDEX `idx_statut_id` (`statut_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Table sms_logs pour le débogage
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `sms_logs` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `recipient` VARCHAR(20) NOT NULL,
              `message` TEXT NOT NULL,
              `status` INT NULL,
              `response` TEXT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              INDEX `idx_recipient` (`recipient`),
              INDEX `idx_status` (`status`),
              INDEX `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur lors de la création des tables SMS: " . $e->getMessage());
        return false;
    }
}

// Créer la table si nécessaire
create_reparation_sms_table(); 