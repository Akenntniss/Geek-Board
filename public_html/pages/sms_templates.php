<?php
// Vérification des droits de base
if (!isset($_SESSION['user_id'])) {
    set_message("Vous devez être connecté pour accéder à cette page.", "danger");
    redirect("");
    exit;
}

// Variable pour déterminer le niveau d'accès
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Traitement des actions (réservé aux administrateurs)
if (isset($_POST['action'])) {
    // Vérifier que l'utilisateur est admin pour les actions de modification
    if (!$is_admin) {
        set_message("Vous n'avez pas les droits nécessaires pour modifier les modèles de SMS.", "danger");
        redirect("sms_templates");
        exit;
    }
    
    $action = $_POST['action'];
    
    // Traitement de l'ajout ou modification de template
    if ($action === 'save_template') {
        $template_id = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
        $nom = clean_input($_POST['nom']);
        $contenu = $_POST['contenu']; // Pas de nettoyage pour préserver les variables
        $statut_id = !empty($_POST['statut_id']) ? (int)$_POST['statut_id'] : null;
        $est_actif = isset($_POST['est_actif']) ? 1 : 0;
        
        // Validation
        if (empty($nom) || empty($contenu)) {
            set_message("Tous les champs obligatoires doivent être remplis.", "danger");
        } else {
            try {
                // Vérifier si un autre template est associé au même statut (sauf celui en cours d'édition)
                if ($statut_id) {
                    $shop_pdo = getShopDBConnection();
$check_stmt = $shop_pdo->prepare("SELECT id FROM sms_templates WHERE statut_id = ? AND id != ?");
                    $check_stmt->execute([$statut_id, $template_id]);
                    if ($check_stmt->rowCount() > 0) {
                        set_message("Un autre modèle est déjà associé à ce statut. Veuillez choisir un statut différent.", "danger");
                        redirect("sms_templates");
                        exit;
                    }
                }
                
                // Ajout ou modification
                if ($template_id > 0) {
                    // Modification
                    $stmt = $shop_pdo->prepare("UPDATE sms_templates SET nom = ?, contenu = ?, statut_id = ?, est_actif = ? WHERE id = ?");
                    $stmt->execute([$nom, $contenu, $statut_id, $est_actif, $template_id]);
                    set_message("Modèle de SMS mis à jour avec succès.", "success");
                } else {
                    // Ajout
                    $stmt = $shop_pdo->prepare("INSERT INTO sms_templates (nom, contenu, statut_id, est_actif) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nom, $contenu, $statut_id, $est_actif]);
                    set_message("Modèle de SMS ajouté avec succès.", "success");
                }
            } catch (PDOException $e) {
                set_message("Erreur lors de l'enregistrement du modèle : " . $e->getMessage(), "danger");
            }
        }
        redirect("sms_templates");
        exit;
    }
    
    // Traitement de la suppression
    if ($action === 'delete_template' && isset($_POST['template_id'])) {
        $template_id = (int)$_POST['template_id'];
        try {
            $stmt = $shop_pdo->prepare("DELETE FROM sms_templates WHERE id = ?");
            $stmt->execute([$template_id]);
            set_message("Modèle de SMS supprimé avec succès.", "success");
        } catch (PDOException $e) {
            set_message("Erreur lors de la suppression du modèle : " . $e->getMessage(), "danger");
        }
        redirect("sms_templates");
        exit;
    }
    
    // Traitement de l'activation/désactivation
    if ($action === 'toggle_active' && isset($_POST['template_id'])) {
        $template_id = (int)$_POST['template_id'];
        $est_actif = isset($_POST['est_actif']) ? (int)$_POST['est_actif'] : 0;
        
        // Ajout de logs détaillés pour débogage
        error_log("Toggle SMS template - Request data: " . print_r($_POST, true));
        error_log("Template ID: " . $template_id . ", État actuel dans la BDD avant mise à jour: " . getTemplateCurrentState($shop_pdo, $template_id));
        error_log("Nouvel état demandé: " . $est_actif);
        
        try {
            $stmt = $shop_pdo->prepare("UPDATE sms_templates SET est_actif = ? WHERE id = ?");
            $stmt->execute([$est_actif, $template_id]);
            $rowCount = $stmt->rowCount();
            error_log("Nombre de lignes affectées par la mise à jour: " . $rowCount);
            
            // Vérifier l'état après la mise à jour
            error_log("État après mise à jour: " . getTemplateCurrentState($shop_pdo, $template_id));
            
            set_message("Statut du modèle mis à jour avec succès.", "success");
        } catch (PDOException $e) {
            error_log("Erreur SQL lors de la mise à jour du statut: " . $e->getMessage());
            set_message("Erreur lors de la mise à jour du statut : " . $e->getMessage(), "danger");
        }
        redirect("sms_templates");
        exit;
    }
}

// Récupération des modèles de SMS
try {
    $stmt = $shop_pdo->query("
        SELECT t.*, s.nom as statut_nom 
        FROM sms_templates t
        LEFT JOIN statuts s ON t.statut_id = s.id
        ORDER BY t.est_actif DESC, t.nom ASC
    ");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $templates = [];
    set_message("Erreur lors de la récupération des modèles : " . $e->getMessage(), "danger");
}

// Récupération des statuts disponibles
try {
    $stmt = $shop_pdo->query("
        SELECT s.id, s.nom, s.code, c.nom as categorie_nom
        FROM statuts s
        JOIN statut_categories c ON s.categorie_id = c.id
        WHERE s.est_actif = 1
        ORDER BY c.ordre, s.ordre
    ");
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $statuts = [];
    set_message("Erreur lors de la récupération des statuts : " . $e->getMessage(), "danger");
}

// Récupération des variables disponibles
try {
    $stmt = $shop_pdo->query("SELECT * FROM sms_template_variables ORDER BY nom");
    $variables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $variables = [];
    set_message("Erreur lors de la récupération des variables : " . $e->getMessage(), "danger");
}

// Template à éditer
$template_to_edit = null;
if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $template_id = (int)$_GET['edit'];
    try {
        $stmt = $shop_pdo->prepare("SELECT * FROM sms_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $template_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        set_message("Erreur lors de la récupération du modèle : " . $e->getMessage(), "danger");
    }
}

// Fonction d'aide pour obtenir l'état actuel d'un template
function getTemplateCurrentState($shop_pdo, $template_id) {
    try {
        $stmt = $shop_pdo->prepare("SELECT est_actif FROM sms_templates WHERE id = ?");
        $stmt->execute([$template_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['est_actif'] : 'non trouvé';
    } catch (PDOException $e) {
        return 'erreur: ' . $e->getMessage();
    }
}
?>

<div class="p-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Modèles de SMS disponibles</h5>
                <div>
                    <a href="index.php?page=campagne_sms" class="btn btn-success me-2">
                        <i class="fas fa-paper-plane me-2"></i>Campagnes SMS
                    </a>
                    <?php if ($is_admin): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal">
                        <i class="fas fa-plus me-2"></i>Nouveau modèle
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Contenu</th>
                            <th>Statut associé</th>
                            <th>État</th>
                            <?php if ($is_admin): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="<?php echo $is_admin ? '5' : '4'; ?>" class="text-center py-3">Aucun modèle de SMS trouvé</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($template['nom']); ?></td>
                                <td>
                                    <?php 
                                    // Afficher une version tronquée du contenu
                                    $contenu = htmlspecialchars($template['contenu']);
                                    echo strlen($contenu) > 50 ? substr($contenu, 0, 50) . '...' : $contenu;
                                    ?>
                                </td>
                                <td>
                                    <?php if ($template['statut_nom']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($template['statut_nom']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Non associé</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_admin): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                        <input type="hidden" name="est_actif" value="<?php echo $template['est_actif'] ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $template['est_actif'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo $template['est_actif'] ? 'Actif' : 'Inactif'; ?>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="badge bg-<?php echo $template['est_actif'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $template['est_actif'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($is_admin): ?>
                                <td>
                                    <a href="index.php?page=sms_templates&edit=<?php echo $template['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal"
                                        data-id="<?php echo $template['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($template['nom']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter/éditer un modèle -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="templateModalLabel">
                    <?php echo $template_to_edit ? 'Modifier le modèle' : 'Nouveau modèle de SMS'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_template">
                    <input type="hidden" name="template_id" value="<?php echo $template_to_edit ? $template_to_edit['id'] : 0; ?>">
                    
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom du modèle *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required
                            value="<?php echo $template_to_edit ? htmlspecialchars($template_to_edit['nom']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="contenu" class="form-label">Contenu du SMS *</label>
                        <textarea class="form-control" id="contenu" name="contenu" rows="5" required
                            maxlength="320"><?php echo $template_to_edit ? htmlspecialchars($template_to_edit['contenu']) : ''; ?></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <div id="charCount" class="form-text">0/320 caractères</div>
                            <div id="smsCount" class="form-text">1 SMS</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="statut_id" class="form-label">Associer à un statut de réparation</label>
                        <select class="form-select" id="statut_id" name="statut_id">
                            <option value="">-- Aucun statut --</option>
                            <?php foreach ($statuts as $statut): ?>
                            <option value="<?php echo $statut['id']; ?>" 
                                <?php echo ($template_to_edit && $template_to_edit['statut_id'] == $statut['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($statut['categorie_nom'] . ' - ' . $statut['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Si sélectionné, ce modèle sera envoyé automatiquement lorsqu'une réparation passe à ce statut.</div>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="est_actif" name="est_actif"
                            <?php echo (!$template_to_edit || $template_to_edit['est_actif']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="est_actif">Activer ce modèle</label>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Variables disponibles</h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($variables as $variable): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary mb-1 me-1 variable-btn" 
                                data-variable="[<?php echo $variable['nom']; ?>]"
                                title="<?php echo htmlspecialchars($variable['description']); ?>">
                                [<?php echo $variable['nom']; ?>]
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Ajout: Bouton de test et aperçu -->
                    <div class="mt-3">
                        <button type="button" class="btn btn-info text-white" id="btnPreviewSMS">
                            <i class="fas fa-eye me-2"></i>Tester le remplacement des variables
                        </button>
                        <div id="testResultContainer" class="mt-3 d-none">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Résultat du test</h6>
                                </div>
                                <div class="card-body">
                                    <h6>Message avec variables:</h6>
                                    <pre id="preTemplateContent" class="border p-2 mb-3 bg-light" style="white-space: pre-wrap;"></pre>
                                    
                                    <h6>Message après remplacement:</h6>
                                    <pre id="preReplacedContent" class="border p-2 mb-3" style="white-space: pre-wrap;"></pre>
                                    
                                    <h6>Détails du remplacement:</h6>
                                    <div id="replacementDetails" class="border p-2 small" style="max-height: 200px; overflow-y: auto;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer le modèle "<span id="templateName"></span>" ?
            </div>
            <div class="modal-footer">
                <form method="post">
                    <input type="hidden" name="action" value="delete_template">
                    <input type="hidden" name="template_id" id="deleteTemplateId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ouvrir automatiquement le modal d'édition si édition demandée
    <?php if ($template_to_edit): ?>
    var templateModal = new bootstrap.Modal(document.getElementById('templateModal'));
    templateModal.show();
    <?php endif; ?>
    
    // Compteur de caractères pour le SMS
    const contenuTextarea = document.getElementById('contenu');
    const charCount = document.getElementById('charCount');
    const smsCount = document.getElementById('smsCount');
    
    function updateCounter() {
        const length = contenuTextarea.value.length;
        charCount.textContent = length + "/320 caractères";
        
        // Calcul du nombre de SMS
        if (length <= 160) {
            smsCount.textContent = "1 SMS";
        } else {
            // 153 caractères par SMS pour les messages concaténés
            const count = Math.ceil(length / 153);
            smsCount.textContent = count + " SMS";
        }
    }
    
    contenuTextarea.addEventListener('input', updateCounter);
    
    // Initialiser le compteur au chargement
    updateCounter();
    
    // Insérer les variables dans le texte
    const variableBtns = document.querySelectorAll('.variable-btn');
    variableBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const variable = this.getAttribute('data-variable');
            const cursorPos = contenuTextarea.selectionStart;
            const textBefore = contenuTextarea.value.substring(0, cursorPos);
            const textAfter = contenuTextarea.value.substring(cursorPos);
            
            contenuTextarea.value = textBefore + variable + textAfter;
            
            // Replacer le curseur après la variable insérée
            const newCursorPos = cursorPos + variable.length;
            contenuTextarea.focus();
            contenuTextarea.setSelectionRange(newCursorPos, newCursorPos);
            
            // Mettre à jour le compteur
            updateCounter();
        });
    });
    
    // Configuration du modal de suppression
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const name = button.getAttribute('data-name');
        
        document.getElementById('deleteTemplateId').value = id;
        document.getElementById('templateName').textContent = name;
    });
    
    // Ajout: Fonctionnalité de test des variables
    const btnPreviewSMS = document.getElementById('btnPreviewSMS');
    if (btnPreviewSMS) {
        btnPreviewSMS.addEventListener('click', function() {
            const testResultContainer = document.getElementById('testResultContainer');
            const templateContent = contenuTextarea.value;
            const preTemplateContent = document.getElementById('preTemplateContent');
            const preReplacedContent = document.getElementById('preReplacedContent');
            const replacementDetails = document.getElementById('replacementDetails');
            
            // Afficher le contenu du template
            preTemplateContent.textContent = templateContent;
            
            // Valeurs d'exemple pour le test
            const testValues = {
                '[CLIENT_NOM]': 'Dupont',
                '[CLIENT_PRENOM]': 'Jean',
                '[CLIENT_TELEPHONE]': '+33612345678',
                '[REPARATION_ID]': '12345',
                '[APPAREIL_TYPE]': 'Smartphone',
                '[APPAREIL_MARQUE]': 'Samsung',
                '[APPAREIL_MODELE]': 'Galaxy S21',
                '[DATE_RECEPTION]': '01/01/2023',
                '[DATE_FIN_PREVUE]': '15/01/2023',
                '[PRIX]': '89,90 €'
            };
            
            // Effectuer les remplacements
            let replacedContent = templateContent;
            let detailsHTML = '<ul class="list-group">';
            
            for (const [variable, value] of Object.entries(testValues)) {
                const oldContent = replacedContent;
                replacedContent = replacedContent.replace(new RegExp(escapeRegExp(variable), 'g'), value);
                
                if (oldContent !== replacedContent) {
                    detailsHTML += `<li class="list-group-item list-group-item-success">${variable} → <strong>${value}</strong> (Remplacé avec succès)</li>`;
                } else {
                    if (templateContent.includes(variable)) {
                        detailsHTML += `<li class="list-group-item list-group-item-warning">${variable} → <strong>${value}</strong> (Variable présente mais non remplacée)</li>`;
                    } else {
                        detailsHTML += `<li class="list-group-item list-group-item-secondary">${variable} → <strong>${value}</strong> (Variable non trouvée dans le template)</li>`;
                    }
                }
            }
            
            detailsHTML += '</ul>';
            
            // Afficher le résultat
            preReplacedContent.textContent = replacedContent;
            replacementDetails.innerHTML = detailsHTML;
            testResultContainer.classList.remove('d-none');
        });
    }
    
    // Fonction pour échapper les caractères spéciaux dans les expressions régulières
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
});
</script> 