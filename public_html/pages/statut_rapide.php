<?php
// Vérifier si l'ID de la réparation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_message("ID réparation non spécifié.", "danger");
    redirect("reparations");
}

// Fonction pour convertir une couleur hexadécimale en RGB
function hexToRgb($hex) {
    // Supprimer le # si présent
    $hex = ltrim($hex, '#');
    
    // Convertir en RGB
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    
    return "$r, $g, $b";
}

$reparation_id = (int)$_GET['id'];

// Récupérer les informations de la réparation
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        set_message("Réparation non trouvée.", "danger");
        redirect("reparations");
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des informations de la réparation: " . $e->getMessage(), "danger");
    redirect("reparations");
}

// Vérifier si l'utilisateur est déjà attribué à cette réparation
$user_id = $_SESSION['user_id'];
$est_attribue = false;
$attribution_id = null;

try {
    // Vérifier dans la table reparation_attributions
    $stmt = $pdo->prepare("
        SELECT ra.id 
        FROM reparation_attributions ra
        WHERE ra.reparation_id = ? AND ra.employe_id = ? AND ra.date_fin IS NULL
    ");
    $stmt->execute([$reparation_id, $user_id]);
    $attribution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attribution) {
        $est_attribue = true;
        $attribution_id = $attribution['id'];
    }
    
    // Vérifier aussi dans la table users (si active_repair_id = reparation_id et techbusy = 1)
    if (!$est_attribue) {
        $stmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE id = ? AND active_repair_id = ? AND techbusy = 1
        ");
        $stmt->execute([$user_id, $reparation_id]);
        
        if ($stmt->rowCount() > 0) {
            $est_attribue = true;
        }
    }
    
    // Afficher le statut d'attribution dans la console pour débogage
    echo "<!-- Statut d'attribution: " . ($est_attribue ? 'Attribué' : 'Non attribué') . " -->";
    
} catch (PDOException $e) {
    // Erreur silencieuse, on considère que l'utilisateur n'est pas attribué
    error_log("Erreur lors de la vérification de l'attribution: " . $e->getMessage());
}

// Traitement de la mise à jour des notes techniques
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_notes') {
    $notes_techniques = clean_input($_POST['notes_techniques'] ?? '');
    
    try {
        // Mettre à jour les notes techniques
        $stmt = $pdo->prepare("UPDATE reparations SET notes_techniques = ? WHERE id = ?");
        $stmt->execute([$notes_techniques, $reparation_id]);
        
        // Enregistrer l'action dans les logs
        $stmt = $pdo->prepare("
            INSERT INTO reparation_logs 
            (reparation_id, employe_id, action_type, details) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $reparation_id,
            $_SESSION['user_id'],
            'mise_a_jour_notes',
            'Mise à jour des notes techniques'
        ]);
        
        set_message("Notes techniques mises à jour avec succès!", "success");
        redirect("index.php?page=statut_rapide&id=" . $reparation_id);
        
    } catch (PDOException $e) {
        set_message("Erreur lors de la mise à jour des notes techniques: " . $e->getMessage(), "danger");
    }
}

// Traitement de la mise à jour du statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nouveau_statut'])) {
    $nouveau_statut = clean_input($_POST['nouveau_statut']);
    $statut_id = isset($_POST['statut_id']) ? (int)$_POST['statut_id'] : 0;
    $statut_categorie = isset($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : 0;
    
    try {
        // Récupérer l'ancien statut pour le log
        $stmt = $pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
        $stmt->execute([$reparation_id]);
        $ancien_statut = $stmt->fetchColumn();
        
        // Mise à jour du statut de la réparation
        $stmt = $pdo->prepare("UPDATE reparations SET statut = ?, statut_categorie = ?, date_modification = NOW() WHERE id = ?");
        $stmt->execute([$nouveau_statut, $statut_categorie, $reparation_id]);
        
        // Enregistrer le changement dans les logs
        $stmt = $pdo->prepare("
            INSERT INTO reparation_logs 
            (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $reparation_id,
            $user_id,
            'changement_statut',
            $ancien_statut,
            $nouveau_statut,
            'Mise à jour via Statut Rapide'
        ]);
        
        // NOUVEAU: Envoi de SMS automatique
        $sms_sent = false;
        $sms_message = '';
        
        // Vérifier s'il existe un modèle de SMS pour ce statut
        if ($statut_id > 0) {
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
                
                // Créer un fichier de log dans un dossier accessible
                $log_dir = dirname(__DIR__) . '/logs';
                if (!is_dir($log_dir)) {
                    mkdir($log_dir, 0755, true);
                }
                $log_file = $log_dir . '/sms_debug_' . date('Y-m-d') . '.log';
                
                // Fonction de log
                $log_debug = function($message) use ($log_file) {
                    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
                };
                
                // Logs avant remplacement
                $log_debug("====== DÉBUT DEBUG SMS ======");
                $log_debug("Template ID: " . $template['id']);
                $log_debug("Template nom: " . $template['nom']);
                $log_debug("Template contenu brut: " . $template['contenu']);
                $log_debug("Réparation ID: " . $reparation_id);
                
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
                
                // Log des variables et leurs valeurs
                $log_debug("Variables à remplacer:");
                foreach ($replacements as $var => $value) {
                    $log_debug("  {$var} => " . (is_null($value) ? "NULL" : "'{$value}'"));
                }
                
                // Message avant remplacement
                $log_debug("Message avant remplacement: " . $message);
                
                // Effectuer les remplacements
                foreach ($replacements as $var => $value) {
                    // Log pour chaque remplacement
                    $old_message = $message;
                    $message = str_replace($var, $value, $message);
                    $log_debug("Remplacement de '{$var}' par '{$value}': " . ($old_message === $message ? "AUCUN CHANGEMENT" : "OK"));
                }
                
                // Correction spécifique pour le problème [CLIENT_NOM][CLIENT_PRENOM] sans espace
                if (strpos($message, '][') !== false) {
                    $log_debug("Détection de variables collées sans espace ']]['");
                    $old_message = $message;
                    $message = str_replace('][', '] [', $message);
                    $log_debug("Correction des variables collées: " . ($old_message === $message ? "AUCUN CHANGEMENT" : "OK - " . $message));
                }
                
                // Log du message final
                $log_debug("Message final après remplacement: " . $message);
                
                // Envoyer le SMS
                if (function_exists('send_sms')) {
                    $log_debug("Tentative d'envoi SMS à " . $reparation['client_telephone']);
                    $sms_result = send_sms($reparation['client_telephone'], $message);
                    $log_debug("Résultat de l'envoi: " . ($sms_result['success'] ? "SUCCÈS" : "ÉCHEC - " . ($sms_result['message'] ?? "Erreur inconnue")));
                    
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
                        set_message("Statut mis à jour avec succès! " . $sms_message, "success");
                    } else {
                        $sms_message = "Erreur lors de l'envoi du SMS: " . $sms_result['message'];
                        set_message("Statut mis à jour, mais " . $sms_message, "warning");
                    }
                } else {
                    $log_debug("ERREUR: La fonction send_sms n'existe pas!");
                    set_message("Statut mis à jour, mais la fonction d'envoi SMS n'est pas disponible.", "warning");
                }
                
                $log_debug("====== FIN DEBUG SMS ======");
            } else {
                if (empty($template)) {
                    set_message("Statut mis à jour. Aucun modèle SMS disponible pour ce statut.", "info");
                } else {
                    set_message("Statut mis à jour. Le client n'a pas de numéro de téléphone pour SMS.", "info");
                }
            }
        } else {
            set_message("Statut mis à jour avec succès.", "success");
        }
        
        // Rediriger pour éviter les soumissions multiples
        redirect("index.php?page=statut_rapide&id=" . $reparation_id);
        
        } catch (PDOException $e) {
        set_message("Erreur lors de la mise à jour du statut: " . $e->getMessage(), "danger");
    }
}

// Traitement du formulaire de finalisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'finaliser') {
    // Récupérer et nettoyer les données
    $statut_final = clean_input($_POST['statut_final'] ?? '');
    $notes_techniques = clean_input($_POST['notes_techniques'] ?? '');
    $envoi_sms = isset($_POST['envoi_sms']) && $_POST['envoi_sms'] === 'on';
    
    // Déterminer le statut_id en fonction du statut_final
    $statut_id = 0;
    switch ($statut_final) {
        case 'reparation_effectue':
            $statut_id = 9;
            break;
        case 'reparation_annule':
            $statut_id = 10;
            break;
        case 'restitue':
            $statut_id = 11;
            break;
        case 'en_attente_responsable':
            $statut_id = 8;
            break;
        default:
            $statut_id = 0;
    }
    
    // Vérifier si le statut est valide
    if (empty($statut_final) || !in_array($statut_final, ['reparation_effectue', 'reparation_annule', 'restitue', 'en_attente_responsable'])) {
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
                SET statut = ?, statut_categorie = ?, notes_techniques = ?, date_modification = NOW()
                WHERE id = ?
            ");
            
            // Déterminer la catégorie de statut en fonction du statut
            $statut_categorie = 4; // Par défaut, terminé
            if ($statut_final === 'en_attente_responsable') {
                $statut_categorie = 3; // En attente
            }
            
            $stmt->execute([$statut_final, $statut_categorie, $notes_techniques, $reparation_id]);
            
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
                $notes_techniques
            ]);
            
            // NOUVEAU: Envoyer un SMS si l'option est activée
            $sms_sent = false;
            $sms_message = '';
            
            if ($envoi_sms) {
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
                    
                    // Créer un fichier de log dans un dossier accessible
                    $log_dir = dirname(__DIR__) . '/logs';
                    if (!is_dir($log_dir)) {
                        mkdir($log_dir, 0755, true);
                    }
                    $log_file = $log_dir . '/sms_debug_' . date('Y-m-d') . '.log';
                    
                    // Fonction de log
                    $log_debug = function($message) use ($log_file) {
                        file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
                    };
                    
                    // Logs avant remplacement
                    $log_debug("====== DÉBUT DEBUG SMS ======");
                    $log_debug("Template ID: " . $template['id']);
                    $log_debug("Template nom: " . $template['nom']);
                    $log_debug("Template contenu brut: " . $template['contenu']);
                    $log_debug("Réparation ID: " . $reparation_id);
                    
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
                    
                    // Log des variables et leurs valeurs
                    $log_debug("Variables à remplacer:");
                    foreach ($replacements as $var => $value) {
                        $log_debug("  {$var} => " . (is_null($value) ? "NULL" : "'{$value}'"));
                    }
                    
                    // Message avant remplacement
                    $log_debug("Message avant remplacement: " . $message);
                    
                    // Effectuer les remplacements
                    foreach ($replacements as $var => $value) {
                        // Log pour chaque remplacement
                        $old_message = $message;
                        $message = str_replace($var, $value, $message);
                        $log_debug("Remplacement de '{$var}' par '{$value}': " . ($old_message === $message ? "AUCUN CHANGEMENT" : "OK"));
                    }
                    
                    // Correction spécifique pour le problème [CLIENT_NOM][CLIENT_PRENOM] sans espace
                    if (strpos($message, '][') !== false) {
                        $log_debug("Détection de variables collées sans espace ']]['");
                        $old_message = $message;
                        $message = str_replace('][', '] [', $message);
                        $log_debug("Correction des variables collées: " . ($old_message === $message ? "AUCUN CHANGEMENT" : "OK - " . $message));
                    }
                    
                    // Log du message final
                    $log_debug("Message final après remplacement: " . $message);
                    
                    // Envoyer le SMS
                    if (function_exists('send_sms')) {
                        $log_debug("Tentative d'envoi SMS à " . $reparation['client_telephone']);
                        $sms_result = send_sms($reparation['client_telephone'], $message);
                        $log_debug("Résultat de l'envoi: " . ($sms_result['success'] ? "SUCCÈS" : "ÉCHEC - " . ($sms_result['message'] ?? "Erreur inconnue")));
                        
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
                        $log_debug("ERREUR: La fonction send_sms n'existe pas!");
                        $sms_message = "La fonction d'envoi SMS n'est pas disponible.";
                    }
                    
                    $log_debug("====== FIN DEBUG SMS ======");
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
            redirect("reparations");
            
        } catch (PDOException $e) {
            set_message("Erreur lors de la finalisation de la réparation: " . $e->getMessage(), "danger");
        }
    }
}

// Récupérer tous les statuts par catégorie
try {
    $statuts_par_categorie = get_all_statuts();
} catch (Exception $e) {
    $statuts_par_categorie = [];
    set_message("Erreur lors de la récupération des statuts: " . $e->getMessage(), "danger");
}

// Récupérer les photos de la réparation
$photos = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM photos_reparation WHERE reparation_id = ? ORDER BY date_upload DESC");
    $stmt->execute([$reparation_id]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Gérer l'erreur silencieusement
    error_log("Erreur lors de la récupération des photos: " . $e->getMessage());
}

// Inclure le modal de finalisation
if ($est_attribue) {
    include_once(BASE_PATH . '/components/terminer_modal.php');
}
?>

<!-- Ajouter les meta tags pour gérer les zones de sécurité -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

<div class="mobile-interface container-fluid p-0">
    <!-- En-tête fixe -->
    <div class="header-fixed">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 safe-area-top">
            <a href="index.php?page=reparations&open_modal=<?php echo $reparation_id; ?>" class="btn-circle safe-touch-area">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h4 class="mb-0 text-truncate mx-2">
                <a href="index.php?page=reparations&open_modal=<?php echo $reparation_id; ?>" class="text-decoration-none text-dark">
                    Réparation #<?php echo $reparation_id; ?>
                </a>
            </h4>
            <div class="status-badge">
                <?php 
                    $statusClass = 'primary';
                    if (strpos(strtolower($reparation['statut']), 'termin') !== false) {
                        $statusClass = 'success';
                    } elseif (strpos(strtolower($reparation['statut']), 'attente') !== false) {
                        $statusClass = 'warning';
                    } elseif (strpos(strtolower($reparation['statut']), 'cours') !== false) {
                        $statusClass = 'info';
                    } elseif (strpos(strtolower($reparation['statut']), 'annul') !== false) {
                        $statusClass = 'danger';
                    }
                ?>
                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($reparation['statut']); ?></span>
            </div>
        </div>
        
        <!-- Info clients/appareil -->
        <div class="info-panel px-3 py-2">
            <div class="client-info mb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="info-item">
                            <i class="fas fa-user text-primary"></i>
                            <span><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone text-primary"></i>
                            <span><?php echo htmlspecialchars($reparation['client_telephone']); ?></span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="info-price" id="prixContainer">
                            <div class="info-label">Prix:</div>
                            <div class="price-value" id="prixValue"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 0, '', ' ') . ' €' : 'Non défini'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="device-info">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="info-item flex-grow-1">
                        <i class="fas fa-laptop text-primary"></i>
                        <span><?php echo htmlspecialchars($reparation['type_appareil']); ?> - <?php echo htmlspecialchars($reparation['modele']); ?></span>
                    </div>
                    <div class="info-status ms-3">
                        <div class="info-label">Statut:</div>
                        <div class="status-value"><?php echo htmlspecialchars($reparation['statut']); ?></div>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-tools text-primary"></i>
                    <span class="text-truncate notes-text"><?php echo html_entity_decode($reparation['notes_techniques'] ?: 'Aucune note technique'); ?></span>
                </div>
                <div class="info-item mt-2">
                    <i class="fas fa-exclamation-circle text-primary"></i>
                    <span class="text-truncate problem-text"><?php echo html_entity_decode($reparation['description_probleme']); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contenu principal avec défilement -->
    <div class="main-content">
        <!-- 3. Boutons d'action rapide en bas -->
        <div class="section-container">
            <div class="action-buttons">
                <div class="action-title">
                    <span class="line"></span>
                    <h6>Actions finales</h6>
                    <span class="line"></span>
                </div>
                
                <div class="final-buttons">
                    <!-- Première ligne -->
                    <div class="button-row mb-3">
                        <!-- Bouton Photos (anciennement Ajouter photo) -->
                        <button type="button" id="btn-photos" class="final-btn photo-btn safe-touch-area" data-bs-toggle="modal" data-bs-target="#photosModal">
                            <i class="fas fa-images"></i>
                            <span>Photos</span>
                        </button>
                        
                        <?php if (!$est_attribue): ?>
                        <!-- Si l'utilisateur n'est pas attribué -->
                        <form id="demarrerForm" method="POST" class="d-inline">
                            <input type="hidden" name="action" value="demarrer">
                            <input type="hidden" name="reparation_id" value="<?php echo $reparation_id; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                            <input type="hidden" name="user_token" value="<?php echo md5(session_id() . $_SESSION['user_id']); ?>">
                            <button type="button" id="btnDemarrer" class="final-btn demarrer-btn safe-touch-area">
                                <i class="fas fa-play-circle"></i>
                                <span>Démarrer</span>
                            </button>
                        </form>
                        <?php else: ?>
                        <!-- Bouton Terminer (si l'utilisateur est attribué) -->
                        <button type="button" id="btnTerminer" class="final-btn terminer-btn safe-touch-area" data-bs-toggle="modal" data-bs-target="#terminerModal">
                            <i class="fas fa-stop-circle"></i>
                            <span>Terminer</span>
                        </button>
                        <?php endif; ?>
                        
                        <!-- Bouton Commander pièce -->
                        <button type="button" id="btn-commander-piece" class="final-btn commande-btn safe-touch-area" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal" data-reparation-id="<?php echo $reparation_id; ?>" data-client-id="<?php echo $reparation['client_id']; ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Commander pièce</span>
                        </button>
                    </div>
                    
                    <!-- Deuxième ligne -->
                    <div class="button-row safe-area-bottom">
                        <form method="POST" action="index.php?page=statut_rapide&id=<?php echo $reparation_id; ?>" class="d-inline">
                            <input type="hidden" name="nouveau_statut" value="restitue">
                            <input type="hidden" name="categorie_id" value="5">
                            <input type="hidden" name="statut_id" value="11">
                            <button type="submit" class="final-btn restitue-btn safe-touch-area">
                                <i class="fas fa-check-circle"></i>
                                <span>Restitué</span>
                            </button>
                        </form>
                        
                        <!-- Bouton Envoyer SMS -->
                        <button type="button" class="final-btn sms-btn safe-touch-area" 
                               onclick="openSmsModal(
                                   '<?php echo htmlspecialchars($reparation['client_id']); ?>', 
                                   '<?php echo htmlspecialchars($reparation['client_nom']); ?>', 
                                   '<?php echo htmlspecialchars($reparation['client_prenom']); ?>', 
                                   '<?php echo htmlspecialchars($reparation['client_telephone']); ?>'
                               ); return false;">
                            <i class="fas fa-sms"></i>
                            <span>Envoyer SMS</span>
                        </button>
                        
                        <!-- Bouton Gardiennage -->
                        <button type="button" id="btn-gardiennage" class="final-btn gardiennage-btn safe-touch-area" data-bs-toggle="modal" data-bs-target="#gardiennageModal">
                            <i class="fas fa-archive"></i>
                            <span>Gardiennage</span>
                        </button>
                        
                        <!-- Bouton Envoyer devis -->
                        <button type="button" id="btn-envoyer-devis" class="final-btn devis-btn safe-touch-area" data-bs-toggle="modal" data-bs-target="#devisModal">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Envoyer devis</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour l'interface mobile */
body, html {
    height: 100%;
    margin: 0;
    padding: 0;
    background: #f5f7fa;
    overflow: hidden;
    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
}

/* Styles spécifiques pour iOS */
.ios-device .header-fixed {
    padding-top: env(safe-area-inset-top, 50px);
}

.ios-device .main-content {
    margin-top: 280px;
}

/* Styles spécifiques pour Android */
.android-device .header-fixed {
    padding-top: env(safe-area-inset-top, 40px);
}

.android-device .main-content {
    margin-top: 260px;
}

.mobile-interface {
    display: flex;
    flex-direction: column;
    height: 100vh;
    width: 100%;
    overflow: hidden;
    background: #f5f7fa;
}

/* Classes pour gérer les zones de sécurité sur les appareils mobiles */
.safe-area-top {
    padding-top: env(safe-area-inset-top, 15px);
}

.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom, 15px);
    margin-bottom: env(safe-area-inset-bottom, 0);
}

.safe-touch-area {
    min-height: 48px;
    min-width: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    touch-action: manipulation;
}

.header-fixed {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000; /* Augmenter la valeur du z-index */
    background-color: #fff;
    box-shadow: 0 1px 10px rgba(0,0,0,0.1);
    padding: 5px 0;
    /* Ajouter un padding en haut pour éviter la barre de statut des téléphones */
    padding-top: env(safe-area-inset-top, 35px);
}

.main-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 1rem 0;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 100px; /* Espace pour les boutons en bas */
    margin-top: 250px; /* Augmenter la marge en haut pour éviter le chevauchement avec l'en-tête fixe et la barre de statut */
    padding-top: env(safe-area-inset-top, 0);
    padding-bottom: env(safe-area-inset-bottom, 100px);
}

/* Styles pour les sections */
.section-container {
    background-color: #fff;
    border-radius: 15px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    margin: 0 10px 20px;
    padding: 15px 0;
    position: relative;
}

.section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #495057;
    margin-bottom: 15px;
    padding-left: 15px;
    position: relative;
    display: flex;
    align-items: center;
}

.section-title::before {
    content: '';
    width: 4px;
    height: 20px;
    background-color: #4361ee;
    border-radius: 2px;
    position: absolute;
    left: 5px;
    top: 50%;
    transform: translateY(-50%);
}

.category-filter-container {
    padding: 0 5px;
}

.status-buttons-container {
    padding: 10px 5px 5px;
    border-top: 1px solid #f0f0f0;
}

.btn-circle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f3f7;
    color: #495057;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    flex-shrink: 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.btn-circle:hover, .btn-circle:focus {
    background: #e9ecef;
    color: #212529;
    transform: scale(1.05);
}

.status-badge {
    margin-left: auto;
}

.status-badge .badge {
    font-size: 0.9rem;
    font-weight: 500;
    padding: 0.5em 0.85em;
    border-radius: 50px;
    letter-spacing: 0.3px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.info-panel {
    background: #f8f9fa;
    padding: 12px 15px;
    border-bottom-left-radius: 20px;
    border-bottom-right-radius: 20px;
    margin-top: 5px;
}

.client-info, .device-info {
    font-size: 0.9rem;
}

.info-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.info-item i {
    width: 22px;
    margin-right: 10px;
    text-align: center;
    font-size: 1rem;
    margin-top: 3px;
}

.info-price {
    background-color: #f0f7ff;
    padding: 8px 12px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    cursor: pointer;
    transition: all 0.2s;
}

.info-price:hover {
    background-color: #e0f0ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.info-label {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.price-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0d6efd;
}

.problem-text {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: normal;
    line-height: 1.4;
}

.notes-text {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: normal;
    line-height: 1.4;
    color: #666;
    font-style: italic;
    position: relative;
    padding-right: 20px;
    transition: all 0.2s ease;
}

.notes-text::after {
    content: '\f044';
    font-family: 'Font Awesome 5 Free';
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8rem;
    opacity: 0.5;
    transition: opacity 0.3s ease;
}

.notes-text:hover::after {
    opacity: 1;
}

.notes-text:hover {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
    border-radius: 4px;
}

.instructions {
    color: #6c757d;
    padding: 10px 0;
    font-size: 0.95rem;
    font-weight: 500;
}

/* Style pour les onglets */
.status-tabs {
    overflow-x: auto;
    flex-wrap: nowrap;
    border-bottom: none;
    margin-bottom: 1rem;
    padding: 0 15px;
    display: flex;
    justify-content: center;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-x;
}

.status-tabs .nav-link {
    padding: 0.75rem 1.2rem;
    font-weight: 700;
    border: none;
    white-space: nowrap;
    font-size: 0.95rem;
    margin-right: 8px;
    border-radius: 10px;
    background-color: #f0f0f0;
    position: relative;
    touch-action: manipulation;
    user-select: none;
    -webkit-user-select: none;
    cursor: pointer;
}

.status-tabs .nav-link.active {
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status-tabs .nav-link i {
    margin-right: 6px;
    font-size: 0.8rem;
}

/* Grille de boutons Launchpad */
.launchpad-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 15px;
    padding: 0 15px 20px;
    touch-action: pan-y;
    -webkit-overflow-scrolling: touch;
}

@media (min-width: 576px) {
    .launchpad-grid {
        grid-template-columns: 1fr;
        max-width: 400px;
        margin: 0 auto;
        gap: 15px;
    }
}

@media (min-width: 768px) {
    .launchpad-grid {
        grid-template-columns: 1fr;
        max-width: 450px;
        margin: 0 auto;
        gap: 18px;
    }
}

@media (min-width: 992px) {
    .launchpad-grid {
        grid-template-columns: 1fr;
        max-width: 500px;
        margin: 0 auto;
        gap: 20px;
    }
}

@media (min-width: 1200px) {
    .launchpad-grid {
        grid-template-columns: 1fr;
        max-width: 550px;
        margin: 0 auto;
    }
}

.launchpad-btn {
    border: none;
    padding: 10px 20px;
    margin: 5px;
    border-radius: 8px;
    cursor: pointer;
    color: white;
    font-weight: 600;
    touch-action: manipulation;
    user-select: none;
    -webkit-user-select: none;
    cursor: pointer;
    position: relative;
    z-index: 1;
}

.launchpad-btn[data-status="nouveau_diagnostique"] { background-color: #4CAF50; }
.launchpad-btn[data-status="nouvelle_intervention"] { background-color: #2196F3; }
.launchpad-btn[data-status="nouvelle_commande"] { background-color: #9C27B0; }
.launchpad-btn[data-status="en_cours_diagnostique"] { background-color: #FF9800; }
.launchpad-btn[data-status="en_cours_intervention"] { background-color: #FF5722; }
.launchpad-btn[data-status="en_attente_accord_client"] { background-color: #FFC107; }
.launchpad-btn[data-status="en_attente_livraison"] { background-color: #00BCD4; }
.launchpad-btn[data-status="en_attente_responsable"] { background-color: #795548; }
.launchpad-btn[data-status="reparation_effectue"] { background-color: #8BC34A; }
.launchpad-btn[data-status="reparation_annule"] { background-color: #F44336; }

.launchpad-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.launchpad-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.launchpad-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(255,255,255,0.3), rgba(0,0,0,0.1));
    opacity: 0;
    transition: opacity 0.3s;
    border-radius: 15px;
}

.launchpad-btn::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 20%;
    background: linear-gradient(to bottom, rgba(255,255,255,0.4), transparent);
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
}

.launchpad-btn:active {
    transform: translateY(4px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.launchpad-btn:active::before {
    opacity: 1;
}

.btn-text {
    font-size: 1.2rem;
    line-height: 1.3;
    font-weight: 700;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5);
    letter-spacing: 0.02em;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    position: relative;
    z-index: 2;
}

/* Animation pour les boutons */
@keyframes pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 8px 15px rgba(0,0,0,0.2);
    }
    50% {
        transform: scale(1.02);
        box-shadow: 0 12px 20px rgba(0,0,0,0.25);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 8px 15px rgba(0,0,0,0.2);
    }
}

.launchpad-btn:hover {
    animation: pulse 1.5s infinite;
    filter: brightness(1.1);
}

/* Style pour les messages de feedback */
.alert {
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 15px;
}

/* Nouveaux styles pour les boutons d'action en bas */
.action-buttons {
    padding: 20px 15px;
    margin-top: 10px;
    background-color: transparent;
}

.action-title {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 25px;
}

.action-title h6 {
    margin: 0 15px;
    font-weight: 700;
    color: #495057;
    font-size: 1.05rem;
    text-transform: uppercase;
    letter-spacing: 1.5px;
}

.action-title .line {
    flex: 1;
    height: 2px;
    background-color: #dee2e6;
}

.final-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.button-row {
    display: flex;
    gap: 15px;
    justify-content: center;
    width: 100%;
}

.final-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 110px;
    height: 110px;
    border: none;
    border-radius: 18px;
    color: white;
    padding: 15px;
    font-weight: 700;
    margin: 0; /* Supprime les marges pour un espacement égal */
    flex: 1; /* Les boutons prennent une place égale */
    max-width: 110px; /* Limite la largeur maximale */
}

.final-btn::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 25%;
    background: linear-gradient(to bottom, rgba(255,255,255,0.4), transparent);
    border-top-left-radius: 18px;
    border-top-right-radius: 18px;
}

.final-btn i {
    font-size: 2rem;
    margin-bottom: 10px;
    text-shadow: 0 2px 5px rgba(0,0,0,0.2);
    position: relative;
    z-index: 1;
}

.final-btn span {
    font-size: 1.05rem;
    letter-spacing: 0.03em;
    position: relative;
    z-index: 1;
}

.restitue-btn { background-color: #28a745; }
.gardiennage-btn { background-color: #6f42c1; }
.demarrer-btn { background-color: #007bff; }
.terminer-btn { background-color: #dc3545; }
.photo-btn { background-color: #3498db; }
.commande-btn { background-color: #f39c12; }
.sms-btn { background-color: #2ecc71; }
.devis-btn { background-color: #e83e8c; }

.final-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 25px rgba(0,0,0,0.2);
    filter: brightness(1.1);
}

.final-btn:active {
    transform: translateY(2px);
    box-shadow: 0 5px 10px rgba(0,0,0,0.15);
}

@media (max-width: 400px) {
    .final-btn {
        width: 100px;
        height: 100px;
        padding: 12px;
    }
    
    .final-btn i {
        font-size: 1.8rem;
    }
    
    .final-btn span {
        font-size: 0.95rem;
    }
}

/* Styles spécifiques à cette page */
body {
    background-color: #f9f9f9;
}

/* Conteneur principal */
.mobile-interface {
    max-width: 100%;
    padding: 0;
    margin: 0;
    overflow-x: hidden;
}

/* Carte d'information */
.info-card {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 3px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border: none;
}

.info-card .card-header {
    border-bottom: none;
    padding: 1.25rem;
    background-color: #f8f9fa;
}

.info-card .card-body {
    padding: 1.25rem;
}

/* Bouton circulaire */
.btn-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #555;
    font-size: 1.25rem;
    background-color: #f1f1f1;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-circle:hover {
    background-color: #e0e0e0;
    color: #333;
}

/* Badge de statut */
.status-badge {
    border-radius: 30px;
    padding: 0.4rem 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
    display: inline-flex;
    align-items: center;
    min-width: 90px;
    justify-content: center;
}

/* Sections du formulaire */
.form-section {
    margin-bottom: 2rem;
}

.form-section-title {
    margin-bottom: 1rem;
    font-weight: 600;
    color: #333;
    font-size: 1rem;
    display: flex;
    align-items: center;
}

.form-section-title i {
    margin-right: 0.5rem;
    color: #4361ee;
}

/* Catégories de statuts */
.status-categories {
    display: flex;
    overflow-x: auto;
    gap: 0.5rem;
    padding: 0.5rem 0;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    margin-bottom: 1rem;
}

.status-categories::-webkit-scrollbar {
    display: none;
}

.category-btn {
    flex: 0 0 auto;
    padding: 0.75rem 1.25rem;
    border-radius: 30px;
    background-color: #f1f1f1;
    color: #555;
    font-weight: 600;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.2s;
    white-space: nowrap;
}

.category-btn.active {
    background-color: #4361ee;
    color: white;
    box-shadow: 0 3px 8px rgba(67, 97, 238, 0.3);
}

/* Options de statut */
.status-options {
    display: none;
    flex-direction: column;
    gap: 0.75rem;
}

.status-options.active {
    display: flex;
}

.status-option {
    padding: 1rem;
    border-radius: 10px;
    background-color: white;
    border: 1px solid #eaeaea;
    display: flex;
    align-items: center;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
}

.status-option:hover, .status-option:focus {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-color: #4361ee;
}

.status-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.25rem;
}

.status-text {
    flex: 1;
}

.status-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.status-description {
    font-size: 0.875rem;
    color: #777;
    margin: 0;
}

/* Bouton d'action flottant */
.floating-action-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 99;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #4361ee;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.5);
    border: none;
    transition: all 0.2s;
}

.floating-action-btn:hover, .floating-action-btn:focus {
    background-color: #3051e0;
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(67, 97, 238, 0.6);
}

/* Styles pour la table */
.details-table {
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #eaeaea;
}

.details-table th {
    background-color: #f8f9fa;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #eaeaea;
    color: #555;
    font-weight: 600;
}

.details-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #eaeaea;
}

.details-table tr:last-child td {
    border-bottom: none;
}

/* Médias et photos */
.media-section {
    margin-bottom: 2rem;
}

.media-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.75rem;
}

.media-item {
    border-radius: 10px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    aspect-ratio: 1/1;
}

.media-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.2s;
}

.media-item:hover img {
    transform: scale(1.05);
}

.media-item.photo-appareil {
    grid-column: span 2;
    grid-row: span 2;
}

/* Onglets */
.nav-tabs {
    border-bottom: none;
    margin-bottom: 1rem;
    gap: 0.5rem;
}

.nav-tabs .nav-link {
    border: none;
    background-color: #f1f1f1;
    color: #555;
    font-weight: 600;
    border-radius: 30px;
    padding: 0.75rem 1.25rem;
    transition: all 0.2s;
}

.nav-tabs .nav-link.active {
    background-color: #4361ee;
    color: white;
}

/* Formulaire de SMS */
.sms-form {
    background-color: #f8f9fa;
    border-radius: 15px;
    padding: 1.25rem;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

.template-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.template-option {
    padding: 0.75rem;
    border-radius: 10px;
    border: 1px solid #eaeaea;
    background-color: white;
    cursor: pointer;
    transition: all 0.2s;
}

.template-option:hover, .template-option.selected {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-color: #4361ee;
}

.template-option.selected {
    background-color: #f0f4ff;
}

.template-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.template-preview {
    font-size: 0.875rem;
    color: #777;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
    line-height: 1.5;
}

.sms-textarea {
    resize: none;
    height: 120px;
    border-radius: 10px;
    padding: 1rem;
    font-size: 0.95rem;
    line-height: 1.5;
}

.character-count {
    font-size: 0.875rem;
    color: #777;
    margin-top: 0.5rem;
    text-align: right;
}

/* Historique des SMS */
.sms-history {
    margin-top: 1rem;
}

.sms-item {
    padding: 1rem;
    border-radius: 10px;
    border: 1px solid #eaeaea;
    margin-bottom: 0.75rem;
    background-color: white;
}

.sms-item:last-child {
    margin-bottom: 0;
}

.sms-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.sms-date {
    font-size: 0.875rem;
    color: #777;
}

.sms-content {
    font-size: 0.95rem;
    color: #555;
    white-space: pre-line;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.section-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}

/* Responsive */
@media (min-width: 768px) {
    .main-content {
        max-width: 780px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .template-options {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}

/* Mode sombre */
.dark-mode {
    background-color: #111827;
    color: #f8fafc;
}

.dark-mode .mobile-interface {
    background-color: #111827;
}

.dark-mode .header-fixed {
    background-color: #1f2937;
    box-shadow: 0 1px 10px rgba(0,0,0,0.3);
}

.dark-mode .info-panel {
    background-color: #2d3748;
}

.dark-mode .info-item {
    color: #f8fafc;
}

.dark-mode .info-item i {
    color: #60a5fa;
}

.dark-mode .info-price {
    background-color: #3b4c67;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.dark-mode .info-label {
    color: #cfd8e3;
}

.dark-mode .price-value {
    color: #60a5fa;
}

.dark-mode .device-info {
    color: #e2e8f0;
}

.dark-mode .problem-text {
    color: #e2e8f0;
}

.dark-mode .btn-circle {
    background-color: #374151;
    color: #f8fafc;
}

.dark-mode .btn-circle:hover {
    background-color: #4b5563;
    color: #f8fafc;
}

.dark-mode .info-card {
    background-color: #1f2937;
    box-shadow: 0 3px 15px rgba(0,0,0,0.3);
    border-color: #374151;
}

.dark-mode .info-card .card-header {
    background-color: #111827;
    border-bottom-color: #374151;
}

.dark-mode .info-card .card-body {
    background-color: #1f2937;
}

.dark-mode .form-section-title {
    color: #f8fafc;
}

.dark-mode .form-section-title i {
    color: #60a5fa;
}

.dark-mode .section-container {
    background-color: #1f2937;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

.dark-mode .section-title {
    color: #e2e8f0;
}

.dark-mode .section-title::before {
    background-color: #60a5fa;
}

.dark-mode .status-buttons-container {
    border-top-color: #374151;
}

.dark-mode .status-tabs .nav-link {
    background-color: #2d3748;
    color: #e2e8f0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.dark-mode .status-tabs .nav-link::before {
    background: linear-gradient(to bottom, rgba(255,255,255,0.15), transparent);
}

.dark-mode .status-tabs .nav-link.active {
    background-color: #1f2937;
    box-shadow: 0 4px 8px rgba(0,0,0,0.25);
}

.dark-mode .action-title h6 {
    color: #e2e8f0;
}

.dark-mode .action-title .line {
    background-color: #4b5563;
}

.dark-mode .category-btn {
    background-color: #374151;
    color: #f8fafc;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.dark-mode .category-btn.active {
    background-color: #3b82f6;
    box-shadow: 0 3px 8px rgba(59, 130, 246, 0.3);
}

.dark-mode .status-option {
    background-color: #1f2937;
    border-color: #374151;
    color: #f8fafc;
}

.dark-mode .status-option:hover, 
.dark-mode .status-option:focus {
    border-color: #60a5fa;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.dark-mode .status-description {
    color: #94a3b8;
}

.dark-mode .status-icon {
    background-color: #111827;
    color: #f8fafc;
}

.dark-mode .floating-action-btn {
    background-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.5);
}

.dark-mode .floating-action-btn:hover,
.dark-mode .floating-action-btn:focus {
    background-color: #2563eb;
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.6);
}

.dark-mode .details-table {
    border-color: #374151;
}

.dark-mode .details-table th {
    background-color: #111827;
    border-bottom-color: #374151;
    color: #94a3b8;
}

.dark-mode .details-table td {
    border-bottom-color: #374151;
}

.dark-mode .media-item {
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

.dark-mode .nav-tabs .nav-link {
    background-color: #374151;
    color: #f8fafc;
}

.dark-mode .nav-tabs .nav-link.active {
    background-color: #3b82f6;
}

.dark-mode .sms-form {
    background-color: #111827;
    box-shadow: 0 3px 10px rgba(0,0,0,0.3);
}

.dark-mode .template-option {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode .template-option:hover,
.dark-mode .template-option.selected {
    border-color: #60a5fa;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.dark-mode .template-option.selected {
    background-color: #2d3748;
}

.dark-mode .template-name {
    color: #f8fafc;
}

.dark-mode .template-preview {
    color: #94a3b8;
}

.dark-mode .sms-textarea {
    background-color: #111827;
    border-color: #374151;
    color: #f8fafc;
}

.dark-mode .character-count {
    color: #94a3b8;
}

.dark-mode .sms-item {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode .sms-date {
    color: #94a3b8;
}

.dark-mode .sms-content {
    color: #e5e7eb;
}

.dark-mode .form-control,
.dark-mode .form-select {
    background-color: #111827;
    border-color: #374151;
    color: #f8fafc;
}

.dark-mode .input-group-text {
    background-color: #1f2937;
    border-color: #374151;
    color: #94a3b8;
}

.dark-mode .form-check-input {
    background-color: #111827;
    border-color: #374151;
}

.dark-mode .form-check-input:checked {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.dark-mode .modal-content {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode .modal-header {
    border-bottom-color: #374151;
}

.dark-mode .modal-footer {
    border-top-color: #374151;
}

.dark-mode .btn-primary {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.dark-mode .btn-success {
    background-color: #10b981;
    border-color: #10b981;
}

.dark-mode .btn-secondary {
    background-color: #4b5563;
    border-color: #4b5563;
}

.dark-mode .btn-danger {
    background-color: #ef4444;
    border-color: #ef4444;
}

.dark-mode .btn-outline-primary {
    color: #60a5fa;
    border-color: #60a5fa;
}

.dark-mode .btn-outline-success {
    color: #34d399;
    border-color: #34d399;
}

.dark-mode .btn-outline-secondary {
    color: #94a3b8;
    border-color: #4b5563;
}

.dark-mode .text-muted {
    color: #94a3b8 !important;
}

/* Amélioration visuelle pour les onglets et boutons de statut en mode clair */
.status-tabs .nav-link[style*="color"] {
    background-color: rgba(var(--color-rgb), 0.1);
}

.status-tabs .nav-link.active[style*="color"] {
    background-color: rgba(var(--color-rgb), 0.2);
    border-bottom-color: currentColor !important;
}

/* Conversion des couleurs hex en RGB pour utilisation avec rgba */
:root {
    --color-rgb: 67, 97, 238; /* Couleur par défaut si aucune n'est spécifiée */
}

/* Styles pour les statuts spécifiques */
.launchpad-btn[data-status="nouveau_diagnostique"] {
    background-color: #4CAF50;
    background-image: linear-gradient(to bottom, rgba(76, 175, 80, 0.8), rgba(76, 175, 80, 0.6));
}

.launchpad-btn[data-status="nouvelle_intervention"] {
    background-color: #2196F3;
    background-image: linear-gradient(to bottom, rgba(33, 150, 243, 0.8), rgba(33, 150, 243, 0.6));
}

.launchpad-btn[data-status="nouvelle_commande"] {
    background-color: #9C27B0;
    background-image: linear-gradient(to bottom, rgba(156, 39, 176, 0.8), rgba(156, 39, 176, 0.6));
}

.launchpad-btn[data-status="en_cours_diagnostique"] {
    background-color: #FF9800;
    background-image: linear-gradient(to bottom, rgba(255, 152, 0, 0.8), rgba(255, 152, 0, 0.6));
}

.launchpad-btn[data-status="en_cours_intervention"] {
    background-color: #03A9F4;
    background-image: linear-gradient(to bottom, rgba(3, 169, 244, 0.8), rgba(3, 169, 244, 0.6));
}

.launchpad-btn[data-status="en_attente_accord_client"] {
    background-color: #FFC107;
    background-image: linear-gradient(to bottom, rgba(255, 193, 7, 0.8), rgba(255, 193, 7, 0.6));
}

.launchpad-btn[data-status="en_attente_livraison"] {
    background-color: #795548;
    background-image: linear-gradient(to bottom, rgba(121, 85, 72, 0.8), rgba(121, 85, 72, 0.6));
}

.launchpad-btn[data-status="en_attente_responsable"] {
    background-color: #607D8B;
    background-image: linear-gradient(to bottom, rgba(96, 125, 139, 0.8), rgba(96, 125, 139, 0.6));
}

.launchpad-btn[data-status="reparation_effectue"] {
    background-color: #4CAF50;
    background-image: linear-gradient(to bottom, rgba(76, 175, 80, 0.8), rgba(76, 175, 80, 0.6));
}

.launchpad-btn[data-status="reparation_annule"] {
    background-color: #F44336;
    background-image: linear-gradient(to bottom, rgba(244, 67, 54, 0.8), rgba(244, 67, 54, 0.6));
}

/* Styles pour les nouvelles informations */
.info-status, .info-password {
    background-color: #f0f7ff;
    padding: 8px 12px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.2s;
}

.info-status:hover, .info-password:hover {
    background-color: #e0f0ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.status-value, .password-value {
    font-size: 1rem;
    font-weight: 600;
    color: #2563eb;
}

/* Styles pour le mode sombre */
.dark-mode .info-status, 
.dark-mode .info-password {
    background-color: #3b4c67;
    color: #e2e8f0;
}

.dark-mode .status-value, 
.dark-mode .password-value {
    color: #60a5fa;
}

.device-info {
    margin-top: 10px;
}

.info-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.info-item i {
    width: 22px;
    margin-right: 10px;
    text-align: center;
    font-size: 1rem;
    margin-top: 3px;
}

.info-status {
    background-color: #f0f7ff;
    padding: 8px 12px;
    border-radius: 10px;
    min-width: 150px;
    text-align: center;
}

.problem-text {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: normal;
    line-height: 1.4;
}

.custom-numpad {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 10px;
}

.numpad-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.numpad-key {
    padding: 15px;
    font-size: 1.5rem;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    background-color: white;
    color: #333;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60px;
}

.numpad-key:hover {
    background-color: #f0f0f0;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.numpad-key:active {
    transform: translateY(1px);
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.price-display {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
}

#prixDisplay {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2563eb;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* Style pour la modale */
#prixModal .modal-content {
    border-radius: 15px;
    overflow: hidden;
}

#prixModal .modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

#prixModal .modal-body {
    padding: 20px;
}

#prixModal .modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
    padding: 15px;
}

/* Mode sombre */
.dark-mode .custom-numpad {
    background-color: #1f2937;
}

.dark-mode .numpad-key {
    background-color: #374151;
    color: #f8fafc;
}

.dark-mode .numpad-key:hover {
    background-color: #4b5563;
}

.dark-mode .price-display {
    background-color: #1f2937;
}

.dark-mode #prixDisplay {
    color: #60a5fa;
}

/* Styles pour le modal de statut */
.statut-modal-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.category-tabs-container {
    padding: 10px 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    position: sticky;
    top: 0;
    z-index: 10;
}

#categoryTabs .nav-link {
    font-weight: 600;
    border-radius: 8px;
    padding: 10px 15px;
    transition: all 0.2s ease;
    background-color: rgba(255, 255, 255, 0.8);
    margin: 0 3px;
}

#categoryTabs .nav-link.active {
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.statut-buttons-container {
    flex: 1;
    overflow-y: auto;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    padding: 10px;
}

.status-btn {
    border: none;
    border-radius: 10px;
    padding: 15px;
    font-weight: 600;
    color: white;
    text-align: center;
    transition: all 0.2s ease;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.status-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.status-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.current-status {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    margin-top: 15px;
}

.status-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.status-badge {
    border-radius: 8px;
    font-size: 1.1rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

@media (min-width: 768px) {
    .status-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .modal-dialog {
        max-width: 700px;
        margin: 1.75rem auto;
    }
    
    .modal-dialog.modal-fullscreen {
        max-width: 700px;
        height: auto;
    }
    
    .modal-dialog.modal-fullscreen .modal-content {
        height: auto;
        border-radius: 12px;
        overflow: hidden;
    }
}

@media (min-width: 992px) {
    .status-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Styles pour l'interface mobile */
.icon-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* Styles pour la grille de photos */
.photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
    padding: 10px 0;
    max-height: 60vh;
    overflow-y: auto;
}

.photo-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    aspect-ratio: 1;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.photo-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.photo-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

.photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 10px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.photo-item:hover .photo-overlay {
    opacity: 1;
}

.photo-actions {
    display: flex;
    justify-content: flex-end;
}

.photo-description {
    color: white;
    font-size: 0.8rem;
    max-height: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
    background: rgba(0,0,0,0.5);
    padding: 5px;
    border-radius: 5px;
}

/* Styles pour les modaux en plein écran */
.modal-fullscreen {
    width: 100vw;
    max-width: 100%;
    margin: 0;
    padding: 0;
    height: 100vh;
}

.modal-fullscreen .modal-content {
    height: 100%;
    border: 0;
    border-radius: 0;
}

.modal-fullscreen .modal-body {
    overflow-y: auto;
    padding: 15px;
}

/* Styles pour le mode sombre */
.dark-mode .photo-item {
    background-color: #1f2937;
    box-shadow: 0 3px 6px rgba(0,0,0,0.3);
}

.dark-mode .photo-overlay {
    background: rgba(0,0,0,0.7);
}

.dark-mode .photo-description {
    background: rgba(0,0,0,0.7);
}

@media (min-width: 768px) {
    .photo-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    }
}

@media (min-width: 992px) {
    .photo-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }
}

@media (min-width: 1200px) {
    .photo-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    }
}

.photo-view-container {
    display: flex;
    justify-content: center;
    align-items: center;
    max-height: 80vh;
    overflow: hidden;
}

#fullsizePhoto {
    max-height: 80vh;
    object-fit: contain;
}

/* Styles pour le modal SMS */
.bg-gradient-primary {
    background: linear-gradient(135deg, #4361ee, #3a0ca3);
}

.bg-primary-light {
    background-color: rgba(67, 97, 238, 0.15);
}

.bg-secondary-light {
    background-color: rgba(108, 117, 125, 0.15);
}

.client-info-sms {
    border-left: 4px solid #4361ee;
}

.sms-option-card {
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid transparent;
    background-color: #f8f9fa;
    width: 45%;
    position: relative;
    overflow: hidden;
}

.sms-option-card:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(67, 97, 238, 0.05);
    border-radius: 10px;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.3s;
}

.sms-option-card:hover:before {
    opacity: 1;
    transform: scale(1);
}

.sms-option-card.active {
    border-color: #4361ee;
    background-color: rgba(67, 97, 238, 0.05);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
}

.sms-option-card:hover:not(.active) {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.option-title {
    position: relative;
    margin-top: 1.5rem;
}

.templates-container {
    max-height: 200px;
    overflow-y: auto;
    padding-right: 5px;
}

.template-card {
    padding: 12px 15px;
    border-radius: 10px;
    background-color: #f8f9fa;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.template-card.active {
    border-color: #4361ee;
    background-color: rgba(67, 97, 238, 0.05);
}

.template-card:hover:not(.active) {
    background-color: #f1f3f5;
}

.template-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.template-name {
    font-weight: 600;
    color: #495057;
}

.template-check {
    color: #4361ee;
    opacity: 0;
    transition: opacity 0.3s;
}

.template-card.active .template-check {
    opacity: 1;
}

.template-content {
    font-size: 0.85rem;
    color: #6c757d;
    max-height: 3.6em;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.variable-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    background-color: #f1f3f5;
    color: #495057;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 5px;
    cursor: pointer;
    transition: all 0.2s;
}

.variable-badge:hover {
    background-color: #4361ee;
    color: white;
    transform: translateY(-2px);
}

/* Mode sombre pour le modal SMS */
.dark-mode .sms-option-card {
    background-color: #2d3748;
}

.dark-mode .sms-option-card.active {
    background-color: rgba(66, 153, 225, 0.2);
    border-color: #4299e1;
}

.dark-mode .template-card {
    background-color: #2d3748;
}

.dark-mode .template-card.active {
    background-color: rgba(66, 153, 225, 0.2);
    border-color: #4299e1;
}

.dark-mode .template-name {
    color: #e2e8f0;
}

.dark-mode .template-content {
    color: #a0aec0;
}

.dark-mode .variable-badge {
    background-color: #2d3748;
    color: #e2e8f0;
}

.dark-mode .variable-badge:hover {
    background-color: #4299e1;
    color: white;
}

.dark-mode .client-info-sms {
    background-color: #2d3748;
}

.dark-mode .form-control {
    background-color: #2d3748;
    color: #e2e8f0;
    border-color: #4a5568;
}

.dark-mode .form-floating label {
    color: #a0aec0;
}

.template-card.active .template-check {
    opacity: 1;
}

.template-content {
    font-size: 0.85rem;
    color: #6c757d;
    max-height: 3.6em;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

#message-content-container {
    min-height: 200px;
    margin-bottom: 1.5rem;
    transition: all 0.4s ease;
}

.message-option-content {
    transition: all 0.3s ease;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.variable-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    background-color: #f1f3f5;
    color: #495057;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 5px;
    cursor: pointer;
    transition: all 0.2s;
}

#variables-section {
    transition: all 0.3s ease;
    animation: fadeIn 0.3s;
}
</style>

<script>
// Attendre que le DOM soit complètement chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM chargé, initialisation des composants...");
    
    // Variables pour la gestion de la caméra
    let stream = null;
    
    // Initialisation des modals
    const photoModal = new bootstrap.Modal(document.getElementById('cameraModal'));
    const photosModal = new bootstrap.Modal(document.getElementById('photosModal'));
    const viewPhotoModal = new bootstrap.Modal(document.getElementById('viewPhotoModal'));
    
    // Initialiser le modal des statuts manuellement
    const statutModalElement = document.getElementById('statutModal');
    if (statutModalElement) {
        // Vérifier que bootstrap est chargé
        if (typeof bootstrap !== 'undefined') {
            try {
                const statutModal = new bootstrap.Modal(statutModalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                
                // Ajouter un gestionnaire pour les boutons dans le modal
                document.querySelectorAll('.status-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        // Fermer le modal
                        statutModal.hide();
                    });
                });
                
                // Gestionnaire pour les onglets de catégorie
                const categoryTabs = document.querySelectorAll('#categoryTabs .nav-link');
                if (categoryTabs.length > 0) {
                    console.log("Onglets de catégorie initialisés avec succès");
                    categoryTabs.forEach(tab => {
                        tab.addEventListener('click', function(e) {
                            e.preventDefault();
                            
                            // Désactiver tous les onglets
                            categoryTabs.forEach(t => {
                                t.classList.remove('active');
                                t.setAttribute('aria-selected', 'false');
                            });
                            
                            // Activer l'onglet cliqué
                            this.classList.add('active');
                            this.setAttribute('aria-selected', 'true');
                            
                            // Afficher le contenu correspondant
                            const targetId = this.getAttribute('data-bs-target');
                            document.querySelectorAll('.tab-pane').forEach(pane => {
                                pane.classList.remove('show', 'active');
                            });
                            const targetPane = document.querySelector(targetId);
                            if (targetPane) {
                                targetPane.classList.add('show', 'active');
                            }
                        });
                    });
                } else {
                    console.log("Aucun onglet de catégorie trouvé");
                }
            } catch (e) {
                console.error("Erreur d'initialisation du modal:", e);
            }
        } else {
            console.error("Bootstrap n'est pas chargé");
        }
    }
    
    // Initialiser le modal des notes techniques
    const notesText = document.querySelector('.notes-text');
    if (notesText) {
        notesText.addEventListener('click', function() {
            const notesModal = new bootstrap.Modal(document.getElementById('notesModal'));
            notesModal.show();
        });
        
        // Ajouter un style de curseur pour indiquer que c'est cliquable
        notesText.style.cursor = 'pointer';
        
        // Ajouter un effet de survol
        notesText.addEventListener('mouseover', function() {
            this.classList.add('text-primary');
        });
        
        notesText.addEventListener('mouseout', function() {
            this.classList.remove('text-primary');
        });
        
        // Gestionnaire pour le bouton d'historique
        document.getElementById('viewHistoryBtn')?.addEventListener('click', function() {
            // Rediriger vers la page d'historique des modifications avec l'ID de la réparation
            window.location.href = 'index.php?page=historique&reparation_id=<?php echo $reparation_id; ?>&filter=notes';
        });
    }
    
    // Initialiser le modal de finalisation
    const terminerModalElement = document.getElementById('terminerModal');
    if (terminerModalElement) {
        if (typeof bootstrap !== 'undefined') {
            try {
                // Initialiser le modal
                const terminerModal = new bootstrap.Modal(terminerModalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                
                // Trouver et initialiser le bouton Confirmer
                const btnConfirmer = document.getElementById('confirmerTerminer');
                if (btnConfirmer) {
                    console.log("Initialisation de l'événement onclick pour le bouton Confirmer");
                    
                    btnConfirmer.addEventListener('click', function() {
                        const statutSelected = document.querySelector('input[name="nouveau_statut"]:checked');
                        if (!statutSelected) {
                            alert('Veuillez sélectionner un statut.');
                            return;
                        }
                        
                        const nouveau_statut = statutSelected.value;
                        terminerModal.hide();
                        
                        // Envoyer la requête
                        fetch('ajax/manage_repair_attribution.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                action: 'terminer',
                                reparation_id: <?php echo $reparation_id; ?>,
                                nouveau_statut: nouveau_statut
                            }),
                            credentials: 'include'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Réparation terminée avec succès!');
                                window.location.href = 'index.php?page=reparations';
                            } else {
                                alert(data.message || 'Une erreur est survenue.');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            alert('Une erreur de communication est survenue.');
                        });
                    });
                }
            } catch (e) {
                console.error("Erreur d'initialisation du modal terminer:", e);
            }
        }
    }
    
    // Initialiser les boutons d'action finaux
    document.querySelectorAll('.final-btn').forEach(btn => {
        btn.addEventListener('touchstart', function() {
            this.classList.add('pressed');
        });
        
        btn.addEventListener('touchend', function() {
            this.classList.remove('pressed');
        });
    });
    console.log("Feedback tactile des boutons finaux initialisé");
    
    // Gestionnaire pour le bouton Démarrer
    const btnDemarrer = document.getElementById('btnDemarrer');
    if (btnDemarrer) {
        btnDemarrer.addEventListener('click', function() {
            const formData = new FormData(document.getElementById('demarrerForm'));
            const jsonData = {
                action: formData.get('action'),
                reparation_id: formData.get('reparation_id'),
                user_id: formData.get('user_id'),
                user_token: formData.get('user_token')
            };
            
            fetch('ajax/manage_repair_attribution.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(jsonData),
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.active_repairs && data.active_repairs.length > 0) {
                        const activeRepairId = data.active_repairs[0].id;
                        
                        if (confirm('Vous avez déjà des réparations actives. Vous allez être redirigé vers cette réparation pour la clôturer.')) {
                            window.location.href = `index.php?page=statut_rapide&id=${activeRepairId}`;
                        }
                    } else if (data.other_workers && data.other_workers.length > 0) {
                        const workers = data.other_workers.map(w => w.nom).join(', ');
                        if (confirm(`Les techniciens suivants travaillent déjà sur cette réparation: ${workers}. Voulez-vous aussi y travailler?`)) {
                            confirmerDemarrage();
                        }
                    } else {
                        confirmerDemarrage();
                    }
                } else {
                    alert(data.message || 'Une erreur est survenue lors de la vérification.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            });
        });
    }
    
    // Gestionnaire pour le bouton Ajouter photo
    const btnAjouterPhoto = document.getElementById('btn-ajouter-photo');
    if (btnAjouterPhoto) {
        btnAjouterPhoto.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Fermer le modal des photos
            photosModal.hide();
            
            // Laisser un délai pour éviter les conflits de modals
            setTimeout(() => {
                // Initialiser le flux de la caméra
                startCamera();
                
                // Afficher le modal photo
                photoModal.show();
            }, 500);
        });
    }
    
    // Gestionnaire pour les boutons de visualisation de photo
    document.querySelectorAll('.view-photo-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // Empêcher la propagation au parent
            
            // Récupérer l'URL de la photo
            const photoUrl = this.getAttribute('data-url');
            
            // Définir l'URL sur l'image du modal
            document.getElementById('fullsizePhoto').src = photoUrl;
            
            // Afficher le modal
            viewPhotoModal.show();
        });
    });
    
    // Gestionnaire pour les boutons de suppression de photo
    document.querySelectorAll('.delete-photo-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // Empêcher la propagation au parent
            
            // Demander confirmation
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette photo ?')) {
                return;
            }
            
            // Récupérer l'ID de la photo
            const photoId = this.getAttribute('data-id');
            
            // Préparer les données pour la requête
            const formData = new FormData();
            formData.append('photo_id', photoId);
            
            // Envoyer la requête de suppression
            fetch('ajax/delete_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Supprimer l'élément de la grille
                    const photoItem = document.querySelector(`.photo-item[data-id="${photoId}"]`);
                    if (photoItem) {
                        photoItem.remove();
                    }
                    
                    // Si plus de photos, afficher le message vide
                    if (document.querySelectorAll('.photo-item').length === 0) {
                        const photoGrid = document.querySelector('.photo-grid');
                        if (photoGrid) {
                            photoGrid.innerHTML = `
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-camera-retro fa-4x text-muted"></i>
                                </div>
                                <h4 class="text-muted">Aucune photo disponible</h4>
                                <p class="text-muted">Cliquez sur "Ajouter une photo" pour commencer à documenter cette réparation.</p>
                            </div>`;
                        }
                    }
                    
                    // Afficher un message de succès
                    alert('Photo supprimée avec succès !');
                } else {
                    // Afficher un message d'erreur
                    alert(data.error || 'Une erreur est survenue lors de la suppression de la photo.');
                }
            })
            .catch(error => {
                console.error('Erreur :', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            });
        });
    });
    
    // Rendre les items de photo cliquables pour visualiser
    document.querySelectorAll('.photo-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // Ne pas traiter si le clic vient d'un bouton d'action
            if (e.target.closest('.photo-actions')) {
                return;
            }
            
            // Récupérer l'URL de la photo
            const photoUrl = this.getAttribute('data-url');
            
            // Définir l'URL sur l'image du modal
            document.getElementById('fullsizePhoto').src = photoUrl;
            
            // Afficher le modal
            viewPhotoModal.show();
        });
    });
    
    // Fonction pour démarrer la caméra
    function startCamera() {
        const videoElement = document.getElementById('camera');
        const captureBtn = document.getElementById('captureBtn');
        const savePhotoBtn = document.getElementById('savePhotoBtn');
        const cameraContainer = document.getElementById('cameraContainer');
        const photoPreview = document.getElementById('photoPreview');
        const previewImage = document.getElementById('previewImage');
        const retakeBtn = document.getElementById('retakePhoto');
        
        if (!videoElement || !captureBtn) return;
        
        // Contraintes pour la caméra
        const constraints = {
            video: {
                facingMode: 'environment',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false
        };
        
        // Arrêter tout flux actif
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        
        // Accéder à la caméra
        navigator.mediaDevices.getUserMedia(constraints)
            .then(function(mediaStream) {
                stream = mediaStream;
                videoElement.srcObject = mediaStream;
                
                // Afficher la vidéo et masquer la prévisualisation
                cameraContainer.classList.remove('d-none');
                photoPreview.classList.add('d-none');
                captureBtn.classList.remove('d-none');
                savePhotoBtn.classList.add('d-none');
                
                // Gestionnaire pour capturer l'image
                captureBtn.addEventListener('click', function() {
                    const canvas = document.getElementById('cameraCanvas');
                    const context = canvas.getContext('2d');
                    
                    // Définir les dimensions du canvas
                    canvas.width = videoElement.videoWidth;
                    canvas.height = videoElement.videoHeight;
                    
                    // Dessiner l'image
                    context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                    
                    // Convertir en URL de données
                    const dataUrl = canvas.toDataURL('image/jpeg');
                    
                    // Afficher la prévisualisation
                    previewImage.src = dataUrl;
                    cameraContainer.classList.add('d-none');
                    photoPreview.classList.remove('d-none');
                    captureBtn.classList.add('d-none');
                    savePhotoBtn.classList.remove('d-none');
                });
                
                // Gestionnaire pour reprendre une photo
                retakeBtn.addEventListener('click', function() {
                    cameraContainer.classList.remove('d-none');
                    photoPreview.classList.add('d-none');
                    captureBtn.classList.remove('d-none');
                    savePhotoBtn.classList.add('d-none');
                });
                
                // Gestionnaire pour sauvegarder la photo
                savePhotoBtn.addEventListener('click', async function() {
                    const form = document.getElementById('cameraForm');
                    if (!form) return;
                    
                    const formData = new FormData(form);
                    
                    try {
                        this.disabled = true;
                        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sauvegarde en cours...';
                        
                        // Convertir la photo du canvas en blob
                        const canvas = document.getElementById('cameraCanvas');
                        if (!canvas) {
                            throw new Error('Canvas non trouvé');
                        }
                        
                        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg'));
                        formData.set('photo', blob, 'photo.jpg');
                        
                        // Envoyer la photo
                        const response = await fetch('ajax/upload_photo.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        if (!response.ok) {
                            throw new Error(`Erreur HTTP: ${response.status}`);
                        }
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Afficher un message de succès
                            alert('Photo ajoutée avec succès !');
                            
                            // Fermer le modal et réinitialiser
                            photoModal.hide();
                            form.reset();
                            
                            // Recharger la page pour afficher la nouvelle photo
                            window.location.reload();
                        } else {
                            throw new Error(data.error || 'Erreur lors de l\'upload de la photo');
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        alert(`Erreur lors de la sauvegarde de la photo: ${error.message}`);
                    } finally {
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-save me-2"></i> Sauvegarder';
                    }
                });
                
                // Arrêter la caméra à la fermeture du modal
                document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function() {
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }
                });
            })
            .catch(function(err) {
                console.error("Erreur lors de l'accès à la caméra: ", err);
                alert("Impossible d'accéder à la caméra. Vérifiez que vous avez accordé les permissions nécessaires.");
        });
    }
    
    // Gestionnaire pour le bouton Commander pièce
    const btnCommanderPiece = document.getElementById('btn-commander-piece');
    if (btnCommanderPiece) {
        btnCommanderPiece.addEventListener('click', function(e) {
            // Préparation des données pour le modal commande
            const reparationId = this.getAttribute('data-reparation-id');
            const clientId = this.getAttribute('data-client-id');
            
            // On attend que le modal soit chargé avant de définir les valeurs
            $('#ajouterCommandeModal').on('shown.bs.modal', function() {
                // Remplir les champs du formulaire
                if (document.getElementById('reparation_id')) {
                    document.getElementById('reparation_id').value = reparationId;
                }
                
                if (document.getElementById('client_id')) {
                    document.getElementById('client_id').value = clientId;
                }
                
                // Mettre à jour l'affichage du client sélectionné
                if (document.getElementById('client_selectionne')) {
                    document.getElementById('client_selectionne').classList.remove('d-none');
                    const nomClientElement = document.querySelector('#client_selectionne .nom_client');
                    if (nomClientElement) {
                        nomClientElement.textContent = '<?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?>';
                    }
                    
                    const telClientElement = document.querySelector('#client_selectionne .tel_client');
                    if (telClientElement) {
                        telClientElement.textContent = '<?php echo htmlspecialchars($reparation['client_telephone']); ?>';
                    }
                }
                
                // Préremplir le champ nom client selectionné
                if (document.getElementById('nom_client_selectionne')) {
                    document.getElementById('nom_client_selectionne').value = '<?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?>';
                }
            });
            
            // Le modal sera ouvert par l'attribut data-bs-target
        });
    }
    
    // Initialiser le comportement du prix cliquable
    const prixContainer = document.getElementById('prixContainer');
    const prixValue = document.getElementById('prixValue');
    const prixModalEl = document.getElementById('prixModal');
    const prixModal = new bootstrap.Modal(prixModalEl);
    let currentPrice = 0;
    let keyboardListenerActive = false;  // Flag pour suivre si l'écouteur clavier est actif
    
    // Initialiser la valeur actuelle du prix
    if (prixValue) {
        // Extraire la valeur numérique du prix affiché
        const priceText = prixValue.textContent.trim();
        if (priceText !== 'Non défini') {
            const prixMatch = priceText.match(/(\d+(?:\s\d+)*)/);
            if (prixMatch) {
                // Convertir le prix extrait en nombre entier
                currentPrice = parseInt(prixMatch[0].replace(/\s/g, ''));
            }
        }
    }
    
    // Ajouter l'événement de clic sur le prix
    if (prixContainer) {
        prixContainer.style.cursor = 'pointer';
        prixContainer.addEventListener('click', function() {
            // Mettre à jour l'affichage du prix dans le modal
            document.getElementById('prixDisplay').textContent = currentPrice + ' €';
            // Ouvrir le modal
            prixModal.show();
        });
    }
    
    // Gestionnaire pour les événements clavier - fonction séparée pour pouvoir la détacher facilement
    function handleGlobalKeyDown(event) {
        // Vérifier si le modal est ouvert
        if (!prixModalEl.classList.contains('show')) {
            return;
        }
        
        // Bloquer l'événement sur toutes les touches numériques, delete et backspace
        if (/^[0-9]$/.test(event.key) || event.key === 'Backspace' || event.key === 'Delete') {
            console.log('Bloqué:', event.key);
            event.preventDefault();
            event.stopPropagation();
            
            // Pour les touches numériques, faire le traitement
            if (/^[0-9]$/.test(event.key)) {
                handleNumpadInput(event.key);
            } else if (event.key === 'Backspace' || event.key === 'Delete') {
                // Gérer la suppression
                const prixDisplay = document.getElementById('prixDisplay');
                let newPrice = prixDisplay.textContent.replace(/\s|€/g, '');
                
                if (newPrice.length > 1) {
                    newPrice = newPrice.substring(0, newPrice.length - 1);
                } else {
                    newPrice = '0';
                }
                
                prixDisplay.textContent = newPrice + ' €';
                currentPrice = parseInt(newPrice);
            }
        } else if (event.key === 'Enter') {
            event.preventDefault();
            event.stopPropagation();
            savePrice();
        }
    }
    
    // Nous devons ajouter l'écouteur d'événements au niveau du document,
    // car il interceptera les événements clavier avant qu'ils n'atteignent le modal
    prixModalEl.addEventListener('shown.bs.modal', function() {
        if (!keyboardListenerActive) {
            console.log('Ajout de l\'écouteur clavier');
            keyboardListenerActive = true;
            document.addEventListener('keydown', handleGlobalKeyDown, true); // true pour la phase de capture
        }
    });
    
    // Supprimer l'écouteur lors de la fermeture pour éviter les fuites de mémoire
    prixModalEl.addEventListener('hidden.bs.modal', function() {
        if (keyboardListenerActive) {
            console.log('Suppression de l\'écouteur clavier');
            keyboardListenerActive = false;
            document.removeEventListener('keydown', handleGlobalKeyDown, true);
        }
    });
    
    // Gérer le clavier numérique (les boutons virtuels)
    const numpadKeys = document.querySelectorAll('.numpad-key');
    if (numpadKeys.length > 0) {
        numpadKeys.forEach(key => {
            key.addEventListener('click', function(e) {
                e.preventDefault();
                const value = this.getAttribute('data-value');
                
                // Vérifier si nous sommes sur un appareil mobile
                const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
                
                // Sur mobile, désactiver temporairement l'écouteur clavier global pour éviter la double saisie
                if (isMobile && keyboardListenerActive) {
                    document.removeEventListener('keydown', handleGlobalKeyDown, true);
                    
                    // Rétablir l'écouteur après un court délai
                    setTimeout(() => {
                        if (keyboardListenerActive) {
                            document.addEventListener('keydown', handleGlobalKeyDown, true);
                        }
                    }, 300);
                }
                
                handleNumpadInput(value);
            });
        });
    }
    
    // Gérer les touches du clavier numérique
    function handleNumpadInput(value) {
        const prixDisplay = document.getElementById('prixDisplay');
        const currentText = prixDisplay.textContent;
        let newPrice = currentText.replace(/\s|€/g, '');
        
        if (value === 'C') {
            // Effacer tout
            newPrice = '0';
        } else if (value === '.') {
            // Ne rien faire pour le point (pas utilisé pour les prix entiers)
            return;
        } else {
            // Gérer les autres touches numériques
            if (newPrice === '0') {
                newPrice = value; // Remplacer le 0 initial
            } else {
                newPrice += value; // Ajouter le chiffre
            }
        }
        
        // Limiter à 5 chiffres maximum (prix raisonnable)
        if (newPrice.length > 5) {
            newPrice = newPrice.substring(0, 5);
        }
        
        // Mettre à jour l'affichage
        prixDisplay.textContent = newPrice + ' €';
        currentPrice = parseInt(newPrice);
    }
    
    // Gérer le bouton de sauvegarde du prix
    const savePrixBtn = document.getElementById('savePrixBtn');
    if (savePrixBtn) {
        savePrixBtn.addEventListener('click', function() {
            savePrice();
        });
    }
    
    // Fonction pour enregistrer le prix
    function savePrice() {
        // Préparer les données à envoyer
        const formData = new FormData();
        formData.append('repair_id', <?php echo $reparation_id; ?>);
        formData.append('price', currentPrice);
        
        // Envoyer la requête AJAX
        fetch('ajax/update_repair_price.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'affichage du prix
                prixValue.textContent = currentPrice.toLocaleString('fr-FR') + ' €';
                // Fermer le modal
                prixModal.hide();
                
                // Afficher un message de confirmation
                alert('Prix mis à jour avec succès');
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la mise à jour du prix');
        });
    }

    // Ajouter un événement pour charger les réparations lorsque le modal s'ouvre
    document.addEventListener('shown.bs.modal', function(event) {
        // Vérifier si c'est bien le modal de commande qui est ouvert
        if (event.target.id === 'ajouterCommandeModal') {
            const clientId = document.getElementById('client_id').value;
            if (clientId) {
                chargerReparationsClient(clientId);
            }
        }
    });
    
    // Gestionnaire pour le bouton Envoyer devis
    const btnEnvoyerDevis = document.getElementById('btn-envoyer-devis');
    const confirmerDevisBtn = document.getElementById('confirmer-devis-btn');
    
    if (btnEnvoyerDevis && confirmerDevisBtn) {
        confirmerDevisBtn.addEventListener('click', function() {
            const montant = document.getElementById('devis-montant').value;
            const updatePrix = document.getElementById('devis-update-prix').checked;
            
            if (!montant || parseFloat(montant) <= 0) {
                alert('Veuillez saisir un montant valide pour le devis.');
                return;
            }
            
            // Préparer les données à envoyer
            const formData = new FormData();
            formData.append('action', 'envoyer_devis');
            formData.append('reparation_id', <?php echo $reparation_id; ?>);
            formData.append('montant', montant);
            formData.append('update_prix', updatePrix ? '1' : '0');
            // Ajouter l'ID utilisateur pour l'authentification directe (alternative)
            <?php if (isset($_SESSION['user_id'])) : ?>
            formData.append('user_id', <?php echo $_SESSION['user_id']; ?>);
            <?php endif; ?>
            
            // Fermer le modal
            const devisModal = bootstrap.Modal.getInstance(document.getElementById('devisModal'));
            devisModal.hide();
            
            // Afficher un indicateur de chargement
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-50';
            loadingIndicator.style.zIndex = '9999';
            loadingIndicator.innerHTML = `
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            `;
            document.body.appendChild(loadingIndicator);
            
            // Envoyer la requête AJAX
            fetch('ajax/process_devis.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Supprimer l'indicateur de chargement
                document.body.removeChild(loadingIndicator);
                
                if (data.success) {
                    alert('Le devis a été envoyé avec succès au client.');
                    // Rediriger vers la page d'accueil
                    window.location.href = 'index.php';
                } else {
                    alert('Erreur: ' + (data.message || 'Une erreur est survenue lors de l\'envoi du devis.'));
                }
            })
            .catch(error => {
                // Supprimer l'indicateur de chargement
                document.body.removeChild(loadingIndicator);
                
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            });
        });
    }
    
    // Gestionnaire pour le bouton Gardiennage
    const btnGardiennage = document.getElementById('btn-gardiennage');
    const confirmerGardiennageBtn = document.getElementById('confirmer-gardiennage-btn');
    
    if (btnGardiennage && confirmerGardiennageBtn) {
        confirmerGardiennageBtn.addEventListener('click', function() {
            const notes = document.getElementById('gardiennage-notes').value;
            
            // Préparer les données à envoyer
            const formData = new FormData();
            formData.append('action', 'gardiennage');
            formData.append('reparation_id', <?php echo $reparation_id; ?>);
            formData.append('notes', notes);
            
            // Fermer le modal
            const gardiennageModal = bootstrap.Modal.getInstance(document.getElementById('gardiennageModal'));
            gardiennageModal.hide();
            
            // Afficher un indicateur de chargement
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-50';
            loadingIndicator.style.zIndex = '9999';
            loadingIndicator.innerHTML = `
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            `;
            document.body.appendChild(loadingIndicator);
            
            // Envoyer la requête AJAX
            fetch('ajax/process_gardiennage.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Supprimer l'indicateur de chargement
                document.body.removeChild(loadingIndicator);
                
                if (data.success) {
                    alert('L\'appareil a été placé en gardiennage avec succès.');
                    // Rediriger vers la page d'accueil
                    window.location.href = 'index.php';
                } else {
                    alert('Erreur: ' + (data.message || 'Une erreur est survenue lors du placement en gardiennage.'));
                }
            })
            .catch(error => {
                // Supprimer l'indicateur de chargement
                document.body.removeChild(loadingIndicator);
                
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            });
        });
    }
});

// Fonction pour confirmer le démarrage d'une réparation
function confirmerDemarrage() {
    // Récupérer l'ID de la réparation depuis l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const reparationId = urlParams.get('id');
    
    if (!reparationId) {
        alert('Erreur: ID de réparation non trouvé');
        return;
    }
    
    // Appeler l'API pour confirmer le démarrage
    fetch('ajax/manage_repair_attribution.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'confirmer_demarrage',
            reparation_id: reparationId,
            employe_id: <?php echo $_SESSION['user_id']; ?>,
            est_principal: 1
        }),
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Réparation démarrée avec succès!');
            // Recharger la page pour afficher les changements
            window.location.reload();
        } else {
            alert(data.message || 'Erreur lors du démarrage de la réparation');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur de communication est survenue');
    });
}

// Détection du système d'exploitation mobile
document.addEventListener('DOMContentLoaded', function() {
    // Détecter iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    // Détecter Android
    const isAndroid = /Android/.test(navigator.userAgent);
    
    // Ajouter des classes spécifiques au body
    if (isIOS) {
        document.body.classList.add('ios-device');
    } else if (isAndroid) {
        document.body.classList.add('android-device');
    }
    
    // Ajuster la hauteur de l'en-tête pour les appareils iOS
    if (isIOS) {
        const headerFixed = document.querySelector('.header-fixed');
        if (headerFixed) {
            // Ajouter plus de padding pour iOS (barre de statut plus grande)
            headerFixed.style.paddingTop = '45px';
        }
    }
    
    console.log("Système détecté:", isIOS ? "iOS" : (isAndroid ? "Android" : "Autre"));
});

// Fonction pour ouvrir le modal SMS (reprise du code existant)
function openSmsModal(clientId, nom, prenom, telephone) {
    // Remplir les informations du client dans le modal
    document.getElementById('client-name-display').textContent = nom + ' ' + prenom;
    document.getElementById('client-phone-display').textContent = telephone;
    document.getElementById('sms-client-id').value = clientId;
    
    // Réinitialiser l'état du modal
    document.querySelectorAll('.sms-option-card').forEach(card => {
        card.classList.remove('active');
    });
    
    // Réinitialiser la sélection des templates
    document.querySelectorAll('.template-card').forEach(card => {
        card.classList.remove('active');
    });
    document.querySelector('.template-card[data-id="1"]').classList.add('active');
    
    // Vider le champ de texte personnalisé
    const messageTextarea = document.getElementById('sms-message');
    if (messageTextarea) {
        messageTextarea.value = '';
        const charCounter = document.getElementById('sms-char-count');
        if (charCounter) {
            charCounter.textContent = '0';
            charCounter.classList.remove('text-danger', 'fw-bold');
        }
    }
    
    // Masquer les sections de contenu
    document.getElementById('message-content-container').classList.add('d-none');
    document.getElementById('predefined-templates').classList.add('d-none');
    document.getElementById('custom-message').classList.add('d-none');
    
    // Désactiver le bouton d'envoi
    document.getElementById('send-sms-btn').disabled = true;
    
    // Afficher le modal
    const smsModal = new bootstrap.Modal(document.getElementById('smsModal'));
    smsModal.show();
}

// Fonction pour remplir la liste des réparations du client
function chargerReparationsClient(clientId) {
    if (!clientId) return;
    
    // Récupérer l'élément select des réparations
    const reparationSelect = document.getElementById('reparation_id');
    if (!reparationSelect) return;
    
    // Nettoyer les options existantes sauf la première
    while (reparationSelect.options.length > 1) {
        reparationSelect.remove(1);
    }
    
    // Charger les réparations du client via AJAX
    fetch(`ajax/get_client_reparations.php?client_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.reparations) {
                // Ajouter chaque réparation comme option
                data.reparations.forEach(reparation => {
                    const option = document.createElement('option');
                    option.value = reparation.id;
                    option.textContent = `#${reparation.id} - ${reparation.type_appareil} ${reparation.marque} ${reparation.modele}`;
                    
                    // Sélectionner automatiquement la réparation courante
                    if (reparation.id == <?php echo $reparation_id; ?>) {
                        option.selected = true;
                    }
                    
                    reparationSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des réparations:', error);
        });
}

// Ajouter un événement pour charger les réparations lorsque le modal s'ouvre
document.addEventListener('shown.bs.modal', function(event) {
    // Vérifier si c'est bien le modal de commande qui est ouvert
    if (event.target.id === 'ajouterCommandeModal') {
        const clientId = document.getElementById('client_id').value;
        if (clientId) {
            chargerReparationsClient(clientId);
        }
    }
});

// Gestionnaire pour le bouton Envoyer devis
const btnEnvoyerDevis = document.getElementById('btn-envoyer-devis');
const confirmerDevisBtn = document.getElementById('confirmer-devis-btn');

if (btnEnvoyerDevis && confirmerDevisBtn) {
    confirmerDevisBtn.addEventListener('click', function() {
        const montant = document.getElementById('devis-montant').value;
        const updatePrix = document.getElementById('devis-update-prix').checked;
        
        if (!montant || parseFloat(montant) <= 0) {
            alert('Veuillez saisir un montant valide pour le devis.');
            return;
        }
        
        // Préparer les données à envoyer
        const formData = new FormData();
        formData.append('action', 'envoyer_devis');
        formData.append('reparation_id', <?php echo $reparation_id; ?>);
        formData.append('montant', montant);
        formData.append('update_prix', updatePrix ? '1' : '0');
        // Ajouter l'ID utilisateur pour l'authentification directe (alternative)
        <?php if (isset($_SESSION['user_id'])) : ?>
        formData.append('user_id', <?php echo $_SESSION['user_id']; ?>);
        <?php endif; ?>
        
        // Fermer le modal
        const devisModal = bootstrap.Modal.getInstance(document.getElementById('devisModal'));
        devisModal.hide();
        
        // Afficher un indicateur de chargement
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-50';
        loadingIndicator.style.zIndex = '9999';
        loadingIndicator.innerHTML = `
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        `;
        document.body.appendChild(loadingIndicator);
        
        // Envoyer la requête AJAX
        fetch('ajax/process_devis.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Supprimer l'indicateur de chargement
            document.body.removeChild(loadingIndicator);
            
            if (data.success) {
                alert('Le devis a été envoyé avec succès au client.');
                // Rediriger vers la page d'accueil
                window.location.href = 'index.php';
            } else {
                alert('Erreur: ' + (data.message || 'Une erreur est survenue lors de l\'envoi du devis.'));
            }
        })
        .catch(error => {
            // Supprimer l'indicateur de chargement
            document.body.removeChild(loadingIndicator);
            
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
        });
    });
}

// Gestionnaire pour le bouton Gardiennage
const btnGardiennage = document.getElementById('btn-gardiennage');
const confirmerGardiennageBtn = document.getElementById('confirmer-gardiennage-btn');

if (btnGardiennage && confirmerGardiennageBtn) {
    confirmerGardiennageBtn.addEventListener('click', function() {
        const notes = document.getElementById('gardiennage-notes').value;
        
        // Préparer les données à envoyer
        const formData = new FormData();
        formData.append('action', 'gardiennage');
        formData.append('reparation_id', <?php echo $reparation_id; ?>);
        formData.append('notes', notes);
        
        // Fermer le modal
        const gardiennageModal = bootstrap.Modal.getInstance(document.getElementById('gardiennageModal'));
        gardiennageModal.hide();
        
        // Afficher un indicateur de chargement
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-50';
        loadingIndicator.style.zIndex = '9999';
        loadingIndicator.innerHTML = `
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        `;
        document.body.appendChild(loadingIndicator);
        
        // Envoyer la requête AJAX
        fetch('ajax/process_gardiennage.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Supprimer l'indicateur de chargement
            document.body.removeChild(loadingIndicator);
            
            if (data.success) {
                alert('L\'appareil a été placé en gardiennage avec succès.');
                // Rediriger vers la page d'accueil
                window.location.href = 'index.php';
            } else {
                alert('Erreur: ' + (data.message || 'Une erreur est survenue lors du placement en gardiennage.'));
            }
        })
        .catch(error => {
            // Supprimer l'indicateur de chargement
            document.body.removeChild(loadingIndicator);
            
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
        });
    });
}

// === GESTION DU MODAL SMS ===
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaires pour les boutons d'option du type de message
    const optionsPredefined = document.querySelector('.sms-option-card.predefini');
    const optionsPersonnalise = document.querySelector('.sms-option-card.personnalise');
    
    // Gestionnaire pour le changement d'option de message
    if (optionsPredefined && optionsPersonnalise) {
        optionsPredefined.addEventListener('click', function() {
            // Mettre à jour les classes actives
            this.classList.add('active');
            optionsPersonnalise.classList.remove('active');
            
            // Afficher le conteneur de message
            document.getElementById('message-content-container').classList.remove('d-none');
            
            // Gérer l'affichage des contenus avec animation
            document.getElementById('predefined-templates').classList.remove('d-none');
            document.getElementById('custom-message').classList.add('d-none');
            
            // Afficher la section des variables
            document.getElementById('variables-section').classList.remove('d-none');
            
            // Activer le bouton si un template est déjà sélectionné
            const activeTemplate = document.querySelector('.template-card.active');
            if (activeTemplate) {
                document.getElementById('send-sms-btn').disabled = false;
            }
        });
        
        optionsPersonnalise.addEventListener('click', function() {
            // Mettre à jour les classes actives
            this.classList.add('active');
            optionsPredefined.classList.remove('active');
            
            // Afficher le conteneur de message
            document.getElementById('message-content-container').classList.remove('d-none');
            
            // Gérer l'affichage des contenus avec animation
            document.getElementById('predefined-templates').classList.add('d-none');
            document.getElementById('custom-message').classList.remove('d-none');
            
            // Afficher la section des variables
            document.getElementById('variables-section').classList.remove('d-none');
            
            // Vérifier si le champ texte contient du texte et ajouter la signature si nécessaire
            const messageTextarea = document.getElementById('sms-message');
            const signature = "\n\nMAISON DU GEEK\n78 BD PAUL DOUMER\n06110 LE CANNET\n08 95 79 59 33";
            
            // Ne pas ajouter la signature ici, elle sera gérée par l'événement input
            
            document.getElementById('send-sms-btn').disabled = messageTextarea.value.trim().length === 0;
            
            // Mettre le focus sur la zone de texte
            setTimeout(() => {
                messageTextarea.focus();
            }, 100);
        });
    }
    
    // Gestionnaire pour les templates SMS
    const templateCards = document.querySelectorAll('.template-card');
    if (templateCards.length > 0) {
        templateCards.forEach(card => {
            card.addEventListener('click', function() {
                // Retirer la classe active de tous les modèles
                document.querySelectorAll('.template-card').forEach(c => c.classList.remove('active'));
                // Ajouter la classe active au modèle sélectionné
                this.classList.add('active');
                
                // Récupérer le contenu du modèle
                const templateContent = this.querySelector('.template-content').textContent;
                
                // Mettre à jour le message personnalisé
                const customMessage = document.getElementById('customMessage');
                customMessage.value = templateContent;
                
                // Activer le bouton d'envoi
                document.getElementById('sendSmsBtn').disabled = false;
                
                // Mettre à jour le compteur de caractères
                updateCharCount();
            });
        });
    }
    
    // Gestionnaire pour l'insertion des variables
    const variableBadges = document.querySelectorAll('.variable-badge');
    if (variableBadges.length > 0) {
        variableBadges.forEach(badge => {
            badge.addEventListener('click', function() {
                const variable = this.textContent;
                const messageTextarea = document.getElementById('sms-message');
                
                if (messageTextarea) {
                    // Insérer la variable à la position du curseur
                    const startPos = messageTextarea.selectionStart;
                    const endPos = messageTextarea.selectionEnd;
                    const textBefore = messageTextarea.value.substring(0, startPos);
                    const textAfter = messageTextarea.value.substring(endPos, messageTextarea.value.length);
                    
                    messageTextarea.value = textBefore + variable + textAfter;
                    
                    // Remettre le focus sur le textarea et repositionner le curseur
                    messageTextarea.focus();
                    messageTextarea.selectionStart = startPos + variable.length;
                    messageTextarea.selectionEnd = startPos + variable.length;
                    
                    // Mettre à jour le compteur de caractères
                    updateCharCount();
                }
            });
        });
    }
    
    // Gestionnaire pour le compteur de caractères
    const messageTextarea = document.getElementById('sms-message');
    const charCounter = document.getElementById('sms-char-count');
    
    if (messageTextarea && charCounter) {
        // Signature obligatoire
        const signature = "\n\nMAISON DU GEEK\n78 BD PAUL DOUMER\n06110 LE CANNET\n08 95 79 59 33";
        
        messageTextarea.addEventListener('input', function() {
            // S'assurer que la signature est toujours présente
            if (!this.value.includes(signature)) {
                const cursorPos = this.selectionStart;
                this.value = this.value + signature;
                // Restaurer la position du curseur
                this.selectionStart = cursorPos;
                this.selectionEnd = cursorPos;
            }
            
            updateCharCount();
            // Activer/désactiver le bouton d'envoi en fonction du contenu
            const contentWithoutSignature = this.value.replace(signature, "").trim();
            document.getElementById('send-sms-btn').disabled = contentWithoutSignature.length === 0;
        });
        
        // S'assurer que la signature est présente dès le chargement
        messageTextarea.addEventListener('focus', function() {
            if (!this.value.includes(signature)) {
                this.value = this.value + signature;
                // Positionner le curseur avant la signature
                this.selectionStart = this.value.indexOf(signature);
                this.selectionEnd = this.selectionStart;
            }
        });
        
        // Empêcher la suppression de la signature
        messageTextarea.addEventListener('keydown', function(e) {
            const sigPos = this.value.indexOf(signature);
            if (sigPos !== -1) {
                const selStart = this.selectionStart;
                const selEnd = this.selectionEnd;
                
                // Vérifier si l'utilisateur tente de supprimer la signature
                if ((e.key === 'Backspace' && selStart > sigPos && selStart <= this.value.length) ||
                    (e.key === 'Delete' && selEnd >= sigPos)) {
                    // Empêcher la suppression de la signature
                    e.preventDefault();
                    
                    // Si une sélection inclut du texte avant la signature, ne supprimer que cette partie
                    if (selStart < sigPos && e.key === 'Delete') {
                        const beforeSig = this.value.substring(0, selStart);
                        this.value = beforeSig + signature;
                        this.selectionStart = beforeSig.length;
                        this.selectionEnd = beforeSig.length;
                        updateCharCount();
                    }
                }
            }
        });
    }
    
    function updateCharCount() {
        if (messageTextarea && charCounter) {
            charCounter.textContent = messageTextarea.value.length;
            
            // Changer la couleur si on dépasse la limite
            if (messageTextarea.value.length > 160) {
                charCounter.classList.add('text-danger');
                charCounter.classList.add('fw-bold');
            } else {
                charCounter.classList.remove('text-danger');
                charCounter.classList.remove('fw-bold');
            }
        }
    }
    
    // Gestionnaire pour l'envoi du SMS
    const sendSmsBtn = document.getElementById('send-sms-btn');
    if (sendSmsBtn) {
        sendSmsBtn.addEventListener('click', function() {
            // Variables pour le message et le client
            let message = '';
            const clientId = document.getElementById('sms-client-id').value;
            const signature = "\n\nMAISON DU GEEK\n78 BD PAUL DOUMER\n06110 LE CANNET\n08 95 79 59 33";
            
            // Déterminer le type de message (prédéfini ou personnalisé)
            const isPredefined = document.querySelector('.sms-option-card.predefini').classList.contains('active');
            
            if (isPredefined) {
                // Récupérer le template sélectionné
                const selectedTemplate = document.querySelector('.template-card.active');
                if (!selectedTemplate) {
                    alert('Veuillez sélectionner un modèle de message.');
                    return;
                }
                
                message = selectedTemplate.querySelector('.template-content').textContent;
                
                // Ajouter la signature si elle n'est pas déjà incluse
                if (!message.includes(signature)) {
                    message += signature;
                }
            } else {
                // Récupérer le message personnalisé
                message = document.getElementById('sms-message').value;
                
                if (!message.trim()) {
                    alert('Veuillez saisir un message.');
                    return;
                }
                
                // S'assurer que la signature est présente
                if (!message.includes(signature)) {
                    message += signature;
                }
            }
            
            // Vérifier que le message n'est pas trop long
            if (message.length > 160) {
                if (!confirm('Le message dépasse la limite de 160 caractères et pourrait être envoyé en plusieurs parties. Continuer ?')) {
                    return;
                }
            }
            
            // Afficher l'icône de chargement
            const spinner = document.getElementById('sms-spinner');
            spinner.classList.remove('d-none');
            sendSmsBtn.disabled = true;
            
            // Préparer les données pour l'envoi
            const formData = new FormData();
            formData.append('action', 'send_sms');
            formData.append('client_id', clientId);
            formData.append('reparation_id', <?php echo $reparation_id; ?>);
            formData.append('message', message);
            
            // Récupérer les informations du client depuis le modal
            const clientName = document.getElementById('client-name-display').textContent;
            let clientPhone = document.getElementById('client-phone-display').textContent;
            
            // Nettoyer le numéro de téléphone (supprimer espaces, tirets, etc.)
            clientPhone = clientPhone.replace(/[^0-9+]/g, '');
            
            // Ajouter le numéro de téléphone au formData
            formData.append('telephone', clientPhone);
            
            // Créer les données de l'API
            fetch('ajax/send_sms.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Masquer l'icône de chargement
                spinner.classList.add('d-none');
                sendSmsBtn.disabled = false;
                
                // Fermer le modal
                const smsModal = bootstrap.Modal.getInstance(document.getElementById('smsModal'));
                smsModal.hide();
                
                if (data.success) {
                    // Afficher un message de succès
                    alert('SMS envoyé avec succès à ' + clientName + ' au ' + clientPhone);
                } else {
                    // Afficher un message d'erreur
                    alert('Erreur lors de l\'envoi du SMS: ' + (data.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Masquer l'icône de chargement
                spinner.classList.add('d-none');
                sendSmsBtn.disabled = false;
                
                // Afficher un message d'erreur
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            });
        });
    }
});

// Gestionnaire pour les touches du clavier physique
function handleKeyboardInput(e) {
    // Vérifier si le modal de prix est visible
    const modalElement = document.getElementById('prixModal');
    if (!modalElement.classList.contains('show')) {
        // Si le modal n'est plus visible, supprimer l'écouteur d'événement
        document.removeEventListener('keydown', handleKeyboardInput);
        return;
    }
    
    // Ne traiter que les touches numériques, Backspace, Delete et Escape
    if (/^[0-9]$/.test(e.key)) {
        handleNumpadInput(e.key);
        e.preventDefault(); // Empêcher le comportement par défaut
    } else if (e.key === 'Backspace' || e.key === 'Delete') {
        // Gérer la suppression
        const prixDisplay = document.getElementById('prixDisplay');
        const currentText = prixDisplay.textContent;
        let newPrice = currentText.replace(/\s|€/g, '');
        
        if (newPrice.length > 1) {
            newPrice = newPrice.substring(0, newPrice.length - 1);
        } else {
            newPrice = '0';
        }
        
        prixDisplay.textContent = newPrice + ' €';
        currentPrice = parseInt(newPrice);
        e.preventDefault();
    } else if (e.key === 'Escape') {
        // Fermer le modal avec Escape
        prixModal.hide();
        e.preventDefault();
    } else if (e.key === 'Enter') {
        // Simuler un clic sur le bouton Sauvegarder avec Enter
        document.getElementById('savePrixBtn').click();
        e.preventDefault();
    }
}
</script>

<!-- Modale pour la modification du prix -->
<div class="modal fade" id="prixModal" tabindex="-1" aria-labelledby="prixModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="prixModalLabel">Modifier le prix</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="price-display mb-4">
                    <div id="prixDisplay" class="text-center display-4 fw-bold">0 €</div>
                </div>
                
                <div id="custom-keyboard" class="custom-numpad">
                    <div class="numpad-row">
                        <button type="button" class="numpad-key" data-value="1">1</button>
                        <button type="button" class="numpad-key" data-value="2">2</button>
                        <button type="button" class="numpad-key" data-value="3">3</button>
                    </div>
                    <div class="numpad-row">
                        <button type="button" class="numpad-key" data-value="4">4</button>
                        <button type="button" class="numpad-key" data-value="5">5</button>
                        <button type="button" class="numpad-key" data-value="6">6</button>
                    </div>
                    <div class="numpad-row">
                        <button type="button" class="numpad-key" data-value="7">7</button>
                        <button type="button" class="numpad-key" data-value="8">8</button>
                        <button type="button" class="numpad-key" data-value="9">9</button>
                    </div>
                    <div class="numpad-row">
                        <button type="button" class="numpad-key" data-value="C">
                            <i class="fas fa-times"></i>
                        </button>
                        <button type="button" class="numpad-key" data-value="0">0</button>
                        <button type="button" class="numpad-key" data-value=".">
                            <i class="fas fa-dot-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="savePrixBtn">Sauvegarder</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter une photo -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-bottom-0">
                <h5 class="modal-title" id="cameraModalLabel">
                    <i class="fas fa-camera me-2"></i>Prendre une photo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <form id="cameraForm">
                    <input type="hidden" name="reparation_id" value="<?php echo $reparation_id; ?>">
                    
                    <!-- Zone caméra -->
                    <div id="cameraContainer" class="text-center mb-3">
                        <video id="camera" autoplay playsinline class="img-fluid rounded" style="max-height: 400px;"></video>
                        <canvas id="cameraCanvas" class="d-none"></canvas>
                    </div>

                    <!-- Zone de prévisualisation -->
                    <div id="photoPreview" class="text-center mb-3 d-none">
                        <div class="position-relative d-inline-block">
                            <img id="previewImage" src="" alt="Aperçu" class="img-fluid rounded" style="max-height: 400px;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" id="retakePhoto">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="photoDescription" class="form-label">Description (optionnelle)</label>
                        <textarea class="form-control" id="photoDescription" name="description" rows="2" placeholder="Décrivez ce que montre la photo..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="button" class="btn btn-primary" id="captureBtn">
                    <i class="fas fa-camera me-2"></i> Capturer
                </button>
                <button type="button" class="btn btn-success d-none" id="savePhotoBtn">
                    <i class="fas fa-save me-2"></i> Sauvegarder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour afficher toutes les photos -->
<div class="modal fade" id="photosModal" tabindex="-1" aria-labelledby="photosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="photosModalLabel">
                    <i class="fas fa-images me-2"></i>Photos de la réparation #<?php echo $reparation_id; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Bouton pour ajouter une nouvelle photo -->
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-primary" id="btn-ajouter-photo">
                        <i class="fas fa-camera me-2"></i>Ajouter une photo
                    </button>
                </div>

                <?php if (empty($photos)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-camera-retro fa-4x text-muted"></i>
                    </div>
                    <h4 class="text-muted">Aucune photo disponible</h4>
                    <p class="text-muted">Cliquez sur "Ajouter une photo" pour commencer à documenter cette réparation.</p>
                </div>
                <?php else: ?>
                <!-- Grille des photos -->
                <div class="photo-grid">
                    <?php foreach ($photos as $photo): ?>
                    <div class="photo-item" data-id="<?php echo $photo['id']; ?>" data-url="<?php echo $photo['url']; ?>">
                        <div class="photo-wrapper">
                            <img src="<?php echo $photo['url']; ?>" alt="Photo de réparation" class="img-fluid">
                            <div class="photo-overlay">
                                <div class="photo-actions">
                                    <button type="button" class="btn btn-sm btn-light me-1 view-photo-btn" data-url="<?php echo $photo['url']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-photo-btn" data-id="<?php echo $photo['id']; ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                                <?php if (!empty($photo['description'])): ?>
                                <div class="photo-description">
                                    <?php echo htmlspecialchars($photo['description']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour voir une photo en grand -->
<div class="modal fade" id="viewPhotoModal" tabindex="-1" aria-labelledby="viewPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="viewPhotoModalLabel">Photo en détail</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <div class="photo-view-container">
                    <img id="fullsizePhoto" src="" alt="Photo agrandie" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modale pour terminer la réparation -->
<div class="modal fade" id="terminerModal" tabindex="-1" aria-labelledby="terminerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="terminerModalLabel">Terminer la réparation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3 fw-bold">Veuillez choisir le nouveau statut de la réparation :</p>
                
                <div class="statut-options">
                    <?php
                    // Récupérer les statuts possibles pour la fin d'une réparation
                    $statuts_fin = [];
                    try {
                        $stmt = $pdo->query("
                            SELECT s.id, s.code, s.nom, sc.couleur 
                            FROM statuts s 
                            JOIN statut_categories sc ON s.categorie_id = sc.id 
                            WHERE sc.id IN (2, 4) AND s.est_actif = 1
                            ORDER BY s.ordre ASC
                        ");
                        $statuts_fin = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        // En cas d'erreur, utiliser des statuts par défaut
                        $statuts_fin = [
                            ['id' => 9, 'code' => 'reparation_effectue', 'nom' => 'Réparation effectuée', 'couleur' => '28a745'],
                            ['id' => 10, 'code' => 'reparation_annule', 'nom' => 'Réparation annulée', 'couleur' => 'dc3545'],
                            ['id' => 6, 'code' => 'en_attente_accord_client', 'nom' => "En attente de l'accord client", 'couleur' => 'ffc107']
                        ];
                    }
                    
                    foreach ($statuts_fin as $statut):
                    ?>
                    <div class="statut-option mb-3">
                        <input class="form-check-input visually-hidden" type="radio" name="nouveau_statut" 
                               id="statut_<?php echo $statut['code']; ?>" 
                               value="<?php echo $statut['code']; ?>"
                               data-statut-id="<?php echo $statut['id']; ?>">
                        <label class="status-option-label d-block w-100" for="statut_<?php echo $statut['code']; ?>">
                            <div class="d-flex align-items-center w-100 p-3 rounded status-option-content" 
                                 style="border: 2px solid #<?php echo $statut['couleur']; ?>; 
                                        background-color: rgba(<?php echo hexToRgb($statut['couleur']); ?>, 0.1);">
                                <div class="status-icon me-3" style="background-color: #<?php echo $statut['couleur']; ?>;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span class="fw-bold status-name">
                                    <?php echo htmlspecialchars($statut['nom']); ?>
                                </span>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <!-- Ajouter l'attribut onclick directement sur le bouton -->
                <button type="button" class="btn btn-primary" id="confirmerTerminer" onclick="confirmerTerminerClick()">Confirmer</button>
                <!-- Bouton alternatif de formulaire -->
                <form id="terminerForm" method="post" action="ajax/manual_terminer.php">
                    <input type="hidden" name="reparation_id" value="<?php echo $reparation_id; ?>">
                    <input type="hidden" name="nouveau_statut" id="terminerFormStatut" value="">
                    <button type="button" class="btn btn-warning" onclick="submitTerminerForm()">Confirmer (Alt)</button>
                </form>
                <!-- Bouton de test pour déboguer -->
                <button type="button" class="btn btn-info" id="testButton" onclick="testButtonClick()">Test</button>
            </div>
        </div>
    </div>
</div> 

<!-- Modale pour modifier les notes techniques -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notesModalLabel">Notes techniques</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" action="index.php?page=statut_rapide&id=<?php echo $reparation_id; ?>">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_notes">
                    <div class="mb-3">
                        <label for="notes_techniques" class="form-label">Notes internes (visibles uniquement par les techniciens) :</label>
                        <textarea class="form-control" id="notes_techniques" name="notes_techniques" rows="5"><?php echo html_entity_decode($reparation['notes_techniques'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-info" id="viewHistoryBtn">Voir historique</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ajouter Commande -->
<div class="modal fade" id="ajouterCommandeModal" tabindex="-1" aria-labelledby="ajouterCommandeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title" id="ajouterCommandeModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Nouvelle commande de pièces
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="ajouterCommandeForm" method="post" action="ajax/add_commande.php">
                    <div class="row g-4">
                        <!-- Sélection du client -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Client</label>
                                <div class="input-group">
                                    <button class="btn btn-outline-primary" type="button" id="searchClientBtn" data-bs-toggle="modal" data-bs-target="#rechercheClientModal">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <input type="text" id="nom_client_selectionne" class="form-control border-0 shadow-sm" value="" placeholder="Saisir ou rechercher un client...">
                                    <input type="hidden" name="client_id" id="client_id" value="">
                                </div>
                                <div id="client_selectionne" class="selected-item-info d-none mt-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-icon me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <span class="fw-medium nom_client"></span>
                                            <span class="d-block small text-muted tel_client"></span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Résultats de recherche client inline -->
                                <div id="resultats_recherche_client_inline" class="mt-2 d-none">
                                    <div class="card border-0 shadow-sm">
                                        <div class="list-group list-group-flush" id="liste_clients_recherche_inline">
                                            <!-- Les résultats seront ajoutés ici -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sélection de la réparation liée -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Réparation liée (optionnel)</label>
                                <select class="form-select form-select-lg border-0 shadow-sm rounded-3" name="reparation_id" id="reparation_id" onchange="getClientFromReparation(this.value)">
                                    <option value="">Sélectionner une réparation...</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Informations de la première pièce -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Fournisseur</label>
                                <select class="form-select form-select-lg border-0 shadow-sm rounded-3" name="fournisseur_id" id="fournisseur_id_ajout" required>
                                    <option value="">Sélectionner un fournisseur</option>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
                                        while ($fournisseur = $stmt->fetch()) {
                                            echo "<option value='{$fournisseur['id']}'>" . 
                                                 htmlspecialchars($fournisseur['nom']) . "</option>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<option value=''>Erreur de chargement des fournisseurs</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Pièce commandée</label>
                                <input type="text" class="form-control form-control-lg border-0 shadow-sm rounded-3" name="nom_piece" id="nom_piece" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Code barre</label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-lg border-0 shadow-sm rounded-start-3" name="code_barre" id="code_barre" placeholder="Saisir le code barre">
                                    <button type="button" class="btn btn-outline-primary btn-lg rounded-end-3 scan-code-btn">
                                        <i class="fas fa-barcode"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Quantité</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-primary btn-lg rounded-start-3 decrement-btn">-</button>
                                    <input type="number" class="form-control form-control-lg text-center border-0 shadow-sm quantite-input" name="quantite" id="quantite" min="1" value="1" required>
                                    <button type="button" class="btn btn-outline-primary btn-lg rounded-end-3 increment-btn">+</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Prix estimé (€)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control form-control-lg border-0 shadow-sm rounded-start-3" name="prix_estime" id="prix_estime" step="0.01" required>
                                    <span class="input-group-text bg-light rounded-end-3 border-0 shadow-sm">€</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-medium">Statut</label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-warning flex-grow-1 status-btn active rounded-3" data-status="en_attente">
                                        <i class="fas fa-clock me-1"></i> En attente
                                    </button>
                                    <button type="button" class="btn btn-outline-primary flex-grow-1 status-btn rounded-3" data-status="commande">
                                        <i class="fas fa-shopping-cart fa-lg"></i> Commandé
                                    </button>
                                    <button type="button" class="btn btn-outline-success flex-grow-1 status-btn rounded-3" data-status="recue">
                                        <i class="fas fa-box fa-lg"></i> Reçu
                                    </button>
                                </div>
                                <input type="hidden" name="statut" id="statut_input" value="en_attente">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Container pour les pièces additionnelles -->
                    <div id="pieces-additionnelles"></div>
                    
                    <!-- Bouton pour ajouter une pièce supplémentaire -->
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-outline-primary btn-lg rounded-pill" id="ajouter-piece-btn">
                            <i class="fas fa-plus-circle me-2"></i>Ajouter une autre pièce
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="ajouterCommandeForm" class="btn btn-primary" id="saveCommandeBtn">
                    <i class="fas fa-save me-2"></i>Enregistrer la commande
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Script supplémentaire pour le modal de commande
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire d'événements pour les boutons de statut du modal
    document.querySelectorAll('#ajouterCommandeModal .status-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Désélectionner tous les boutons
            document.querySelectorAll('#ajouterCommandeModal .status-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Sélectionner le bouton cliqué
            this.classList.add('active');
            
            // Mettre à jour la valeur cachée
            const status = this.getAttribute('data-status');
            document.querySelector('#statut_input').value = status;
        });
    });
    
    // Gestion des boutons +/- pour la quantité
    const setupQuantityButtons = function() {
        // Boutons de diminution
        document.querySelectorAll('.decrement-btn').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.nextElementSibling;
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                }
            });
        });
        
        // Boutons d'augmentation
        document.querySelectorAll('.increment-btn').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                let value = parseInt(input.value);
                input.value = value + 1;
            });
        });
    };
    
    // Initialiser les boutons de quantité
    setupQuantityButtons();
    
    // Fonction pour charger la réparation en cours
    function chargerReparationEnCours() {
        const reparationId = <?php echo $reparation_id; ?>;
        const clientId = <?php echo $reparation['client_id']; ?>;
        
        // Préremplir le champ client et réparation
        document.getElementById('client_id').value = clientId;
        document.getElementById('nom_client_selectionne').value = '<?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?>';
        
        // Afficher le client sélectionné
        const clientSelectionne = document.getElementById('client_selectionne');
        if (clientSelectionne) {
            clientSelectionne.classList.remove('d-none');
            const nomClientElement = clientSelectionne.querySelector('.nom_client');
            if (nomClientElement) {
                nomClientElement.textContent = '<?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?>';
            }
            
            const telClientElement = clientSelectionne.querySelector('.tel_client');
            if (telClientElement) {
                telClientElement.textContent = '<?php echo htmlspecialchars($reparation['client_telephone']); ?>';
            }
        }
        
        // Charger les réparations du client pour permettre de choisir
        chargerReparationsClient(clientId);
    }
    
    // Lorsque le modal s'ouvre, charger la réparation en cours
    document.querySelector('#ajouterCommandeModal').addEventListener('shown.bs.modal', function() {
        chargerReparationEnCours();
    });
    
    // Fonction pour récupérer le client à partir de la réparation sélectionnée
    window.getClientFromReparation = function(reparationId) {
        if (!reparationId) return;
        
        fetch(`ajax/get_reparation_client.php?reparation_id=${reparationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.client) {
                    // Mettre à jour le client sélectionné
                    document.getElementById('client_id').value = data.client.id;
                    document.getElementById('nom_client_selectionne').value = `${data.client.nom} ${data.client.prenom}`;
                    
                    // Afficher le client sélectionné
                    const clientSelectionne = document.getElementById('client_selectionne');
                    if (clientSelectionne) {
                        clientSelectionne.classList.remove('d-none');
                        const nomClientElement = clientSelectionne.querySelector('.nom_client');
                        if (nomClientElement) {
                            nomClientElement.textContent = `${data.client.nom} ${data.client.prenom}`;
                        }
                        
                        const telClientElement = clientSelectionne.querySelector('.tel_client');
                        if (telClientElement) {
                            telClientElement.textContent = data.client.telephone;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération du client:', error);
            });
    };
});
</script>

<!-- Modale pour la confirmation de l'envoi du devis -->
<div class="modal fade" id="devisModal" tabindex="-1" aria-labelledby="devisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="devisModalLabel">Confirmation du devis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Vous allez envoyer un devis au client pour la réparation de son appareil.</p>
                <div class="mb-3">
                    <label for="devis-montant" class="form-label">Montant du devis :</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="devis-montant" name="devis-montant" step="0.01" 
                               value="<?php echo !empty($reparation['prix_reparation']) ? $reparation['prix_reparation'] : ''; ?>" required>
                        <span class="input-group-text">€</span>
                    </div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="devis-update-prix" name="devis-update-prix" checked>
                    <label class="form-check-label" for="devis-update-prix">
                        Mettre à jour le prix de la réparation
                    </label>
                </div>
                <div class="alert alert-info">
                    <small>Un SMS sera envoyé au client (<?php echo htmlspecialchars($reparation['client_telephone']); ?>) et le statut de la réparation passera à "En attente de l'accord client".</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="confirmer-devis-btn" class="btn btn-primary">Envoyer le devis</button>
            </div>
        </div>
    </div>
</div>

<!-- Modale pour la confirmation du gardiennage -->
<div class="modal fade" id="gardiennageModal" tabindex="-1" aria-labelledby="gardiennageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gardiennageModalLabel">Confirmation du gardiennage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Vous allez placer l'appareil en gardiennage. Un SMS sera envoyé au client pour l'informer des frais de gardiennage.</p>
                <div class="mb-3">
                    <label for="gardiennage-notes" class="form-label">Notes (optionnel) :</label>
                    <textarea class="form-control" id="gardiennage-notes" name="gardiennage-notes" rows="3"></textarea>
                </div>
                <div class="alert alert-info">
                    <small>Un SMS sera envoyé au client (<?php echo htmlspecialchars($reparation['client_telephone']); ?>) et le statut de la réparation passera à "Gardiennage".</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="confirmer-gardiennage-btn" class="btn btn-primary">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour envoyer un SMS -->
<div class="modal fade" id="smsModal" tabindex="-1" aria-labelledby="smsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary text-white border-0">
                <h5 class="modal-title" id="smsModalLabel">
                    <i class="fas fa-sms me-2"></i>Envoyer un SMS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="client-info-sms mb-3 p-3 rounded bg-light">
                    <div class="d-flex align-items-center">
                        <div class="client-avatar bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h6 class="mb-0" id="client-name-display">Nom du client</h6>
                            <p class="mb-0 text-muted" id="client-phone-display">Téléphone</p>
                        </div>
                    </div>
                </div>

                <form id="smsForm">
                    <input type="hidden" id="sms-client-id" name="client_id" value="">
                    <input type="hidden" id="sms-reparation-id" name="reparation_id" value="<?php echo $reparation_id; ?>">
                    
                    <div class="sms-options mb-4">
                        <div class="option-title mb-3">
                            <h6 class="text-uppercase small fw-bold text-muted">Type de message</h6>
                            <div class="progress" style="height: 1px">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-center gap-3">
                            <div class="sms-option-card predefini" data-type="predefini">
                                <div class="icon-circle bg-primary-light mb-2">
                                    <i class="fas fa-comment-dots text-primary"></i>
                                </div>
                                <h6 class="option-text">Message prédéfini</h6>
                            </div>
                            
                            <div class="sms-option-card personnalise" data-type="personnalise">
                                <div class="icon-circle bg-secondary-light mb-2">
                                    <i class="fas fa-edit text-secondary"></i>
                                </div>
                                <h6 class="option-text">Message personnalisé</h6>
                            </div>
                        </div>
                    </div>
                    
                    <div id="message-content-container" class="d-none">
                        <!-- Templates prédéfinis -->
                        <div id="predefined-templates" class="message-option-content d-none">
                            <div class="option-title mb-3">
                                <h6 class="text-uppercase small fw-bold text-muted">Modèles disponibles</h6>
                                <div class="progress" style="height: 1px">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>
                            
                            <div class="templates-container">
                                <?php
                                // Récupérer les modèles de SMS depuis la base de données
                                try {
                                    $stmt = $pdo->query("
                                        SELECT id, nom, contenu
                                        FROM sms_templates 
                                        WHERE est_actif = 1
                                        ORDER BY nom ASC
                                    ");
                                    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (empty($templates)) {
                                        echo '<div class="alert alert-info">Aucun modèle de SMS disponible.</div>';
                                    } else {
                                        foreach ($templates as $index => $template) {
                                            $activeClass = $index === 0 ? 'active' : '';
                                            echo '<div class="template-card ' . $activeClass . '" data-id="' . $template['id'] . '">';
                                            echo '<div class="template-header">';
                                            echo '<div class="template-name">' . htmlspecialchars($template['nom']) . '</div>';
                                            echo '<div class="template-check"><i class="fas fa-check-circle"></i></div>';
                                            echo '</div>';
                                            echo '<div class="template-content">' . htmlspecialchars($template['contenu']) . '</div>';
                                            echo '</div>';
                                        }
                                    }
                                } catch (PDOException $e) {
                                    echo '<div class="alert alert-danger">Erreur lors du chargement des modèles de SMS.</div>';
                                    error_log("Erreur lors du chargement des modèles de SMS: " . $e->getMessage());
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Message personnalisé -->
                        <div id="custom-message" class="message-option-content d-none">
                            <div class="option-title mb-3">
                                <h6 class="text-uppercase small fw-bold text-muted">Votre message</h6>
                                <div class="progress" style="height: 1px">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>
                            
                            <div class="form-floating">
                                <textarea class="form-control" id="sms-message" name="message" style="height: 120px"></textarea>
                                <label for="sms-message">Saisissez votre message</label>
                            </div>
                            
                            <div class="text-end mt-2">
                                <small class="text-muted"><span id="sms-char-count">0</span>/160 caractères</small>
                            </div>
                        </div>
                        
                        <!-- Variables disponibles -->
                        <div id="variables-section">
                            <div class="option-title mb-3">
                                <h6 class="text-uppercase small fw-bold text-muted">Variables disponibles</h6>
                                <div class="progress" style="height: 1px">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>
                            
                            <div class="variables-container mb-4">
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="variable-badge">[CLIENT_NOM]</span>
                                    <span class="variable-badge">[CLIENT_PRENOM]</span>
                                    <span class="variable-badge">[REPARATION_ID]</span>
                                    <span class="variable-badge">[APPAREIL_TYPE]</span>
                                    <span class="variable-badge">[APPAREIL_MODELE]</span>
                                    <span class="variable-badge">[PRIX]</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary d-flex align-items-center" id="send-sms-btn" disabled>
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="sms-spinner"></span>
                    <i class="fas fa-paper-plane me-2"></i>Envoyer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modale pour terminer la réparation -->
</div>
