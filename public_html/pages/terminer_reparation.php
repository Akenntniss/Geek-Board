// Traitement du formulaire de finalisation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $statut_final = clean_input($_POST['statut_final'] ?? '');
    $statut_id = isset($_POST['statut_id']) ? (int)$_POST['statut_id'] : 0;
    $commentaire = clean_input($_POST['commentaire'] ?? '');
    $envoi_sms = isset($_POST['envoi_sms']) && $_POST['envoi_sms'] === 'on';
    
    // Vérifier si le statut est valide
    if (empty($statut_final) || !in_array($statut_final, ['reparation_effectue', 'reparation_annule', 'restitue'])) {
        set_message("Vous devez sélectionner un statut valide pour finaliser la réparation.", "danger");
    } else {
        try {
            // Récupérer l'ancien statut
            $stmt = $pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
            $stmt->execute([$reparation_id]);
            $ancien_statut = $stmt->fetchColumn();
            
            // Mettre à jour la réparation
            $stmt = $pdo->prepare("
                UPDATE reparations 
                SET statut = ?, statut_categorie = 4, date_fin = NOW(), notes_finales = ?, date_modification = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$statut_final, $commentaire, $reparation_id]);
            
            // Terminer toutes les attributions actives pour cette réparation
            $stmt = $pdo->prepare("
                UPDATE reparation_attributions 
                SET date_fin = NOW() 
                WHERE reparation_id = ? AND date_fin IS NULL
            ");
            $stmt->execute([$reparation_id]);
            
            // Libérer les techniciens associés
            $stmt = $pdo->prepare("
                UPDATE users 
                SET techbusy = 0, active_repair_id = NULL 
                WHERE active_repair_id = ?
            ");
            $stmt->execute([$reparation_id]);
            
            // Enregistrer dans les logs
            $stmt = $pdo->prepare("
                INSERT INTO reparation_logs 
                (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $reparation_id,
                $_SESSION['user_id'],
                'terminer',
                $ancien_statut,
                $statut_final,
                $commentaire
            ]);
            
            // NOUVEAU: Envoyer un SMS si l'option est activée
            $sms_sent = false;
            $sms_message = '';
            
            if ($envoi_sms && $statut_id > 0) {
                // Vérifier s'il existe un modèle de SMS pour ce statut
                $stmt = $pdo->prepare("
                    SELECT id, nom, contenu 
                    FROM sms_templates 
                    WHERE statut_id = ? AND est_actif = 1
                ");
                $stmt->execute([$statut_id]);
                $template = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($template && !empty($reparation['client_telephone'])) {
                    // Préparer le contenu du SMS en remplaçant les variables
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
                        '[DATE_FIN_PREVUE]' => format_date($reparation['date_fin_prevue'] ?? ''),
                        '[PRIX]' => !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') : ''
                    ];
                    
                    // Effectuer les remplacements
                    foreach ($replacements as $var => $value) {
                        $message = str_replace($var, $value, $message);
                    }
                    
                    // Envoyer le SMS
                    if (function_exists('send_sms')) {
                        $sms_result = send_sms($reparation['client_telephone'], $message);
                        
                        if ($sms_result['success']) {
                            $sms_sent = true;
                            
                            // Enregistrer l'envoi du SMS dans la base de données
                            $stmt = $pdo->prepare("
                                INSERT INTO reparation_sms (reparation_id, template_id, telephone, message, date_envoi, statut_id)
                                VALUES (?, ?, ?, ?, NOW(), ?)
                            ");
                            $stmt->execute([
                                $reparation_id, 
                                $template['id'], 
                                $reparation['client_telephone'], 
                                $message, 
                                $statut_id
                            ]);
                            
                            $sms_message = 'Un SMS a été envoyé au client.';
                        } else {
                            $sms_message = "Erreur lors de l'envoi du SMS: " . $sms_result['message'];
                        }
                    } else {
                        $sms_message = "La fonction d'envoi SMS n'est pas disponible.";
                    }
                } else {
                    if (empty($template)) {
                        $sms_message = "Aucun modèle SMS disponible pour ce statut.";
                    } else {
                        $sms_message = "Le client n'a pas de numéro de téléphone pour SMS.";
                    }
                }
            }
            
            // Message de confirmation
            if ($sms_sent) {
                set_message("La réparation a été finalisée avec succès! " . $sms_message, "success");
            } else if (!empty($sms_message) && $envoi_sms) {
                set_message("La réparation a été finalisée. " . $sms_message, "warning");
            } else {
                set_message("La réparation a été finalisée avec succès!", "success");
            }
            
            // Rediriger vers la liste des réparations
            redirect("index.php?page=reparations");
            
        } catch (PDOException $e) {
            set_message("Erreur lors de la finalisation de la réparation: " . $e->getMessage(), "danger");
        }
    }
} 