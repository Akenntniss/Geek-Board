<?php
// Définition du chemin de base
define('BASE_PATH', dirname(__DIR__));

// Inclure les fichiers nécessaires
require_once(BASE_PATH . '/database.php');
require_once(BASE_PATH . '/includes/functions.php');

// Vérification des droits d'accès
if (!isset($_SESSION['user_id'])) {
    set_message("Vous devez être connecté pour accéder à cette page.", "danger");
    redirect("");
    exit;
}

// Vérifier si un ID de réparation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_message("ID de réparation manquant", "danger");
    redirect("index.php?page=reparations");
    exit;
}

$reparation_id = intval($_GET['id']);

// Récupérer les informations de la réparation
try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email,
               e.full_name as employe_nom
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN users e ON r.employe_id = e.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        set_message("Réparation introuvable", "danger");
        redirect("index.php?page=reparations");
        exit;
    }
    
    // Récupérer l'ancien statut pour l'enregistrement des logs
    $ancien_statut = $reparation['statut'];
    
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des données: " . $e->getMessage(), "danger");
    redirect("index.php?page=reparations");
    exit;
}

// Traitement du formulaire de mise à jour
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
    // Récupérer et nettoyer les données du formulaire
    $statut = clean_input($_POST['statut']);
    $prix = !empty($_POST['prix']) ? (float)$_POST['prix'] : null;
    $date_fin_prevue = !empty($_POST['date_fin_prevue']) ? clean_input($_POST['date_fin_prevue']) : null;
    $notes_techniques = isset($_POST['notes_techniques']) ? clean_input($_POST['notes_techniques']) : '';
        $envoyer_sms = isset($_POST['sms_notification']) && $_POST['sms_notification'] == 'on';
    
    // Validation des données
    $errors = [];
    
    if (empty($statut)) {
        $errors[] = "Le statut est obligatoire.";
    }
    
    // Si pas d'erreurs, mettre à jour la réparation dans la base de données
    if (empty($errors)) {
            // Récupérer l'ancien statut avant la mise à jour
            $ancien_statut = $reparation['statut'];
            
            // Mise à jour dans la base de données
            $stmt = $pdo->prepare("
                UPDATE reparations 
                SET statut = ?, prix = ?, date_fin_prevue = ?, notes_techniques = ?, date_modification = NOW() 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$statut, $prix, $date_fin_prevue, $notes_techniques, $reparation_id])) {
                // Consigner le changement de statut dans les logs si le statut a changé
                if ($ancien_statut != $statut) {
                    $log_stmt = $pdo->prepare("
                        INSERT INTO reparation_logs 
                        (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    $log_stmt->execute([
                        $reparation_id,
                        $_SESSION['user_id'],
                        'changement_statut',
                        $ancien_statut,
                        $statut,
                        'Mise à jour via formulaire de modification'
                    ]);
                    
                    // NOUVEAU: Envoyer SMS si l'option est activée et le statut a changé
                    if ($envoyer_sms) {
                        // Récupérer l'ID du statut pour trouver le modèle SMS correspondant
                        $stmt_status = $pdo->prepare("SELECT id FROM statuts WHERE code = ?");
                        $stmt_status->execute([$statut]);
                        $statut_id = $stmt_status->fetchColumn();
                        
                        if ($statut_id) {
                            // Vérifier s'il existe un modèle SMS pour ce statut
                            $stmt_template = $pdo->prepare("
                                SELECT id, nom, contenu 
                                FROM sms_templates 
                                WHERE statut_id = ? AND est_actif = 1
                            ");
                            $stmt_template->execute([$statut_id]);
                            $template = $stmt_template->fetch(PDO::FETCH_ASSOC);
                            
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
                                    '[DATE_FIN_PREVUE]' => !empty($date_fin_prevue) ? format_date($date_fin_prevue) : '',
                                    '[PRIX]' => !empty($prix) ? number_format($prix, 2, ',', ' ') : ''
                                ];
                                
                                // Effectuer les remplacements
                                foreach ($replacements as $var => $value) {
                                    $message = str_replace($var, $value, $message);
                                }
                                
                                // Envoyer le SMS
                                $sms_result = send_sms($reparation['client_telephone'], $message);
                                
                                if ($sms_result['success']) {
                                    // Enregistrer l'envoi du SMS
                                    $stmt_sms = $pdo->prepare("
                                        INSERT INTO reparation_sms 
                                        (reparation_id, template_id, telephone, message, date_envoi, statut_id) 
                                        VALUES (?, ?, ?, ?, NOW(), ?)
                                    ");
                                    $stmt_sms->execute([
                                        $reparation_id, 
                                        $template['id'], 
                                        $reparation['client_telephone'], 
                                        $message, 
                                        $statut_id
                                    ]);
                                    
                                    set_message("Réparation mise à jour avec succès. Un SMS a été envoyé au client.", "success");
                                } else {
                                    set_message("Réparation mise à jour, mais l'envoi du SMS a échoué: " . $sms_result['message'], "warning");
                                }
                            } else {
                                if (empty($template)) {
                                    set_message("Réparation mise à jour, mais aucun modèle SMS n'est disponible pour ce statut.", "info");
                                } else {
                                    set_message("Réparation mise à jour, mais le client n'a pas de numéro de téléphone pour recevoir un SMS.", "info");
                                }
                            }
                        } else {
                            set_message("Réparation mise à jour, mais le statut n'est pas reconnu pour l'envoi de SMS.", "info");
                        }
                    } else {
                        set_message("Réparation mise à jour avec succès.", "success");
                    }
                } else {
                    set_message("Réparation mise à jour avec succès.", "success");
                }
                
                // Rediriger vers la page des réparations après modification
                redirect("index.php?page=reparations");
                exit;
            } else {
                set_message("Erreur lors de la mise à jour de la réparation.", "danger");
        }
    } else {
            // Afficher les erreurs de validation
        foreach ($errors as $error) {
            set_message($error, "danger");
        }
    }
    } catch (PDOException $e) {
        set_message("Erreur lors de la mise à jour: " . $e->getMessage(), "danger");
    }
}

// Récupérer les statuts disponibles
try {
    $stmt = $pdo->query("SELECT id, code, nom FROM statuts WHERE est_actif = 1 ORDER BY nom");
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $statuts = [];
    set_message("Erreur lors de la récupération des statuts: " . $e->getMessage(), "danger");
}

// Définir le titre de la page
$page_title = "Modification de la réparation #" . $reparation_id;

// Inclure l'en-tête
include_once(BASE_PATH . '/header.php');
?>

<div class="container mt-4">
<div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h3">
                    <i class="fas fa-edit"></i> <?php echo $page_title; ?>
                </h1>
                <a href="index.php?page=reparations" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour aux réparations
                </a>
            </div>
            
            <?php echo display_message(); ?>
            
            <!-- Informations sur le client et l'appareil -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Informations générales
                    </h5>
            </div>
            <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user"></i> Client</h6>
                            <p>
                                <strong>Nom:</strong> <?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?><br>
                                <strong>Téléphone:</strong> <?php echo htmlspecialchars($reparation['client_telephone']); ?><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($reparation['client_email']); ?>
                            </p>
                        </div>
                    <div class="col-md-6">
                            <h6><i class="fas fa-laptop"></i> Appareil</h6>
                            <p>
                                <strong>Type:</strong> <?php echo htmlspecialchars($reparation['type_appareil']); ?><br>
                                <strong>Marque:</strong> <?php echo htmlspecialchars($reparation['marque']); ?><br>
                                <strong>Modèle:</strong> <?php echo htmlspecialchars($reparation['modele']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6><i class="fas fa-exclamation-triangle"></i> Problème</h6>
                            <p><?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?></p>
                        </div>
                    </div>
                    </div>
                </div>
                
            <!-- Formulaire de modification de la réparation -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tools"></i> Mise à jour de la réparation
                    </h5>
                </div>
                <div class="card-body">
                    <form action="index.php?page=modifier_reparation&id=<?php echo $reparation_id; ?>" method="POST">
                        <!-- Statut de la réparation -->
                        <div class="mb-3">
                            <label for="statut" class="form-label">Statut de la réparation</label>
                            <select name="statut" id="statut" class="form-select" required>
                                <option value="">Sélectionner un statut</option>
                                <?php foreach ($statuts as $s): ?>
                                <option value="<?php echo htmlspecialchars($s['code']); ?>" <?php echo ($reparation['statut'] == $s['code']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Prix de la réparation -->
                        <div class="mb-3">
                            <label for="prix" class="form-label">Prix (€)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" name="prix" id="prix" class="form-control" value="<?php echo htmlspecialchars($reparation['prix'] ?? ''); ?>">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        
                        <!-- Date de fin prévue -->
                        <div class="mb-3">
                            <label for="date_fin_prevue" class="form-label">Date de fin prévue</label>
                            <input type="date" name="date_fin_prevue" id="date_fin_prevue" class="form-control" value="<?php echo htmlspecialchars($reparation['date_fin_prevue'] ?? ''); ?>">
                    </div>
                    
                        <!-- Notes techniques -->
                    <div class="mb-3">
                        <label for="notes_techniques" class="form-label">Notes techniques</label>
                            <textarea name="notes_techniques" id="notes_techniques" class="form-control" rows="4"><?php echo htmlspecialchars($reparation['notes_techniques'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Option pour envoyer un SMS au client -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="sms_notification" name="sms_notification">
                            <label class="form-check-label" for="sms_notification">Envoyer une notification SMS au client</label>
                            <div class="form-text text-muted">Le SMS sera envoyé seulement si un modèle est disponible pour le statut sélectionné.</div>
                    </div>
                    
                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between">
                            <a href="index.php?page=reparations" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Mettre à jour
                            </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once(BASE_PATH . '/footer.php'); ?>