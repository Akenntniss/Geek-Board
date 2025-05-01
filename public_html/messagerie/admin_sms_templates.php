<?php
// Inclusion des fichiers nécessaires
require_once '../includes/functions.php';
require_once '../database.php';

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Titre de la page
$page_title = "Administration des modèles SMS";

// Traitement des actions (activer/désactiver un modèle, modifier un modèle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Activer ou désactiver un modèle
        if ($action === 'toggle_status') {
            $template_id = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
            $new_status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
            
            try {
                if ($conn instanceof PDO) {
                    $stmt = $conn->prepare("UPDATE sms_notification_templates SET actif = ? WHERE id = ?");
                    $stmt->execute([$new_status, $template_id]);
                    
                    $message = $new_status ? "Modèle activé avec succès." : "Modèle désactivé avec succès.";
                    set_message($message, "success");
                } else {
                    set_message("Erreur de connexion à la base de données.", "danger");
                }
            } catch (PDOException $e) {
                set_message("Erreur lors de la mise à jour du statut: " . $e->getMessage(), "danger");
            }
        }
        
        // Modifier un modèle
        elseif ($action === 'update_template') {
            $template_id = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
            $message = isset($_POST['message']) ? $_POST['message'] : '';
            
            try {
                if ($conn instanceof PDO) {
                    $stmt = $conn->prepare("UPDATE sms_notification_templates SET message = ? WHERE id = ?");
                    $stmt->execute([$message, $template_id]);
                    
                    set_message("Modèle mis à jour avec succès.", "success");
                } else {
                    set_message("Erreur de connexion à la base de données.", "danger");
                }
            } catch (PDOException $e) {
                set_message("Erreur lors de la mise à jour du modèle: " . $e->getMessage(), "danger");
            }
        }
        
        // Mettre à jour la configuration SMS
        elseif ($action === 'update_config') {
            $sms_enabled = isset($_POST['sms_notifications_enabled']) ? 'true' : 'false';
            $sms_signature = isset($_POST['sms_signature']) ? $_POST['sms_signature'] : '';
            $sms_gateway_url = isset($_POST['sms_gateway_url']) ? $_POST['sms_gateway_url'] : '';
            $sms_gateway_username = isset($_POST['sms_gateway_username']) ? $_POST['sms_gateway_username'] : '';
            $sms_gateway_password = isset($_POST['sms_gateway_password']) ? $_POST['sms_gateway_password'] : '';
            $sms_gateway_private_token = isset($_POST['sms_gateway_private_token']) ? $_POST['sms_gateway_private_token'] : '';
            
            try {
                update_sms_config('sms_notifications_enabled', $sms_enabled);
                update_sms_config('sms_signature', $sms_signature);
                update_sms_config('sms_gateway_url', $sms_gateway_url);
                update_sms_config('sms_gateway_username', $sms_gateway_username);
                update_sms_config('sms_gateway_private_token', $sms_gateway_private_token);
                
                // Ne mettre à jour le mot de passe que s'il est fourni
                if (!empty($sms_gateway_password)) {
                    update_sms_config('sms_gateway_password', $sms_gateway_password);
                }
                
                set_message("Configuration SMS mise à jour avec succès.", "success");
            } catch (Exception $e) {
                set_message("Erreur lors de la mise à jour de la configuration: " . $e->getMessage(), "danger");
            }
        }
    }
    
    // Rediriger pour éviter les soumissions multiples
    header('Location: admin_sms_templates.php');
    exit;
}

// Récupérer tous les modèles de notification SMS
$templates = get_all_sms_templates();

// Récupérer la configuration SMS
$sms_enabled = get_sms_config('sms_notifications_enabled', 'false');
$sms_signature = get_sms_config('sms_signature', '');
$sms_gateway_url = get_sms_config('sms_gateway_url', 'https://api.sms-gate.app/3rdparty/v1/message');
$sms_gateway_username = get_sms_config('sms_gateway_username', '');

// Récupérer l'historique des SMS envoyés
$sms_history = get_sms_notifications_history(50);

// Inclusion de l'en-tête
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">
        <i class="fas fa-sms me-2 text-primary"></i>
        Administration des SMS
    </h1>
    
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="list-group shadow-sm sticky-top" style="top: 15px;">
                <a href="#config" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                    <i class="fas fa-cogs me-2"></i>Configuration générale
                </a>
                <a href="#templates" class="list-group-item list-group-item-action" data-bs-toggle="list">
                    <i class="fas fa-file-alt me-2"></i>Modèles de SMS
                </a>
                <a href="#history" class="list-group-item list-group-item-action" data-bs-toggle="list">
                    <i class="fas fa-history me-2"></i>Historique d'envoi
                </a>
                <a href="#test" class="list-group-item list-group-item-action" data-bs-toggle="list">
                    <i class="fas fa-vial me-2"></i>Test d'envoi
                </a>
                <div class="list-group-item bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-toggle-on me-2"></i>Statut du système</span>
                        <?php if ($sms_enabled === 'true'): ?>
                            <span class="badge bg-success">Activé</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Désactivé</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4 border-primary">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle me-2 text-primary"></i>Aide</h5>
                    <p class="card-text small">
                        Cette section vous permet de configurer les modèles de SMS envoyés automatiquement lors des changements 
                        de statut des réparations.
                    </p>
                    <p class="card-text small">
                        <strong>Variables disponibles:</strong><br>
                        [CLIENT_NOM] - Nom du client<br>
                        [CLIENT_PRENOM] - Prénom du client<br>
                        [MARQUE] - Marque de l'appareil<br>
                        [MODELE] - Modèle de l'appareil<br>
                        [NUMERO] - Numéro de réparation
                    </p>
                </div>
            </div>
            
            <div class="card mt-4 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-server me-2"></i>Configuration SMS Gate</h5>
                </div>
                <div class="card-body">
                    <p class="card-text small">
                        <strong>Configuration en mode serveur privé:</strong><br>
                        1. Installez l'application SMS Gate sur l'appareil Android<br>
                        2. Configurez un serveur privé en suivant <a href="https://docs.sms-gate.app/getting-started/private-server/" target="_blank">la documentation officielle</a><br>
                        3. Définissez l'URL de l'API et le token privé dans les paramètres ci-contre<br>
                        4. Les identifiants seront générés automatiquement après configuration
                    </p>
                    <p class="card-text small">
                        <strong>Format des données:</strong><br>
                        - URL API: https://private.example.com/api/mobile/v1<br>
                        - Token privé: chaîne sécurisée définie dans votre configuration<br>
                        - Les numéros doivent être au format international (+33...)
                    </p>
                    
                    <hr class="my-3">
                    
                    <div class="small">
                        <strong><i class="fas fa-lightbulb me-2 text-warning"></i>Exemples de configuration:</strong>
                        
                        <div class="mt-2 p-2 bg-light rounded">
                            <div class="fw-bold mb-1">Exemple 1 : Serveur local</div>
                            <div class="mb-1">URL de l'API: <code>http://192.168.1.10:3000/api/mobile/v1</code></div>
                            <div>Token privé: <code>votre-token-securise-ici</code></div>
                        </div>
                        
                        <div class="mt-2 p-2 bg-light rounded">
                            <div class="fw-bold mb-1">Exemple 2 : Serveur distant avec nom de domaine</div>
                            <div class="mb-1">URL de l'API: <code>https://sms.votreentreprise.com/api/mobile/v1</code></div>
                            <div>Token privé: <code>votre-token-securise-ici</code></div>
                        </div>
                        
                        <div class="mt-2 p-2 bg-light rounded">
                            <div class="fw-bold mb-1">Exemple 3 : Configuration via Docker</div>
                            <pre class="mb-0" style="font-size: 85%">docker run -d --name sms-gateway \
    -p 3000:3000 \
    -v $(pwd)/config.yml:/app/config.yml \
    ghcr.io/android-sms-gateway/server:latest</pre>
                        </div>
                        
                        <div class="mt-2">
                            <div class="fw-bold">Conseils de sécurité:</div>
                            <ul class="ps-3 mb-0">
                                <li>Utilisez toujours HTTPS pour les serveurs accessibles depuis Internet</li>
                                <li>Choisissez un token privé fort et unique (min. 20 caractères)</li>
                                <li>Limitez l'accès au serveur par IP si possible</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="tab-content">
                <!-- Configuration générale -->
                <div class="tab-pane fade show active" id="config">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Configuration générale des SMS</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <input type="hidden" name="action" value="update_config">
                                
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="sms_notifications_enabled" name="sms_notifications_enabled" <?php echo $sms_enabled === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="sms_notifications_enabled">
                                        <strong>Activer l'envoi automatique de SMS</strong>
                                    </label>
                                    <div class="form-text">Active ou désactive globalement l'envoi de SMS automatiques lors des changements de statut.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sms_signature" class="form-label">Signature SMS</label>
                                    <input type="text" class="form-control" id="sms_signature" name="sms_signature" value="<?php echo htmlspecialchars($sms_signature); ?>" placeholder="Ex: L'équipe de Mon Entreprise">
                                    <div class="form-text">Cette signature sera ajoutée à la fin de chaque SMS envoyé.</div>
                                </div>
                                
                                <hr class="my-4">
                                <h5 class="mb-3">Configuration de la passerelle SMS Gate</h5>
                                
                                <div class="mb-3">
                                    <label for="sms_gateway_url" class="form-label">URL de l'API</label>
                                    <input type="text" class="form-control" id="sms_gateway_url" name="sms_gateway_url" value="<?php echo htmlspecialchars($sms_gateway_url); ?>" required>
                                    <div class="form-text">URL de l'API (ex: https://private.example.com/api/mobile/v1)</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sms_gateway_private_token" class="form-label">Token privé</label>
                                    <input type="text" class="form-control" id="sms_gateway_private_token" name="sms_gateway_private_token" value="<?php echo htmlspecialchars(get_sms_config('sms_gateway_private_token', '')); ?>" placeholder="Votre token privé sécurisé">
                                    <div class="form-text">Token privé utilisé pour la connexion en mode serveur privé</div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="sms_gateway_username" class="form-label">Nom d'utilisateur</label>
                                        <input type="text" class="form-control" id="sms_gateway_username" name="sms_gateway_username" value="<?php echo htmlspecialchars($sms_gateway_username); ?>" required>
                                        <div class="form-text">Identifiant généré automatiquement par SMS Gate</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="sms_gateway_password" class="form-label">Mot de passe</label>
                                        <input type="password" class="form-control" id="sms_gateway_password" name="sms_gateway_password" placeholder="Laisser vide pour conserver l'actuel">
                                        <div class="form-text">Mot de passe généré automatiquement par SMS Gate</div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Information:</strong> Cette configuration utilise SMS Gate en mode serveur privé. 
                                    Le nom d'utilisateur et le mot de passe seront générés automatiquement après avoir configuré l'application Android.
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Enregistrer la configuration
                                    </button>
                                    
                                    <button type="button" id="testConnectionBtn" class="btn btn-outline-secondary">
                                        <i class="fas fa-wifi me-2"></i>Tester la connexion
                                    </button>
                                </div>
                                
                                <div id="connectionTestResult" class="mt-3" style="display: none;"></div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Modèles de SMS -->
                <div class="tab-pane fade" id="templates">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Modèles de SMS</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30%;">Statut</th>
                                            <th>Message</th>
                                            <th style="width: 120px;">Actif</th>
                                            <th style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($templates)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4">
                                                    <div class="alert alert-info mb-0">
                                                        <i class="fas fa-info-circle me-2"></i>Aucun modèle de SMS disponible
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($templates as $template): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge me-2" style="background-color: #<?php echo $template['categorie_couleur']; ?>">&nbsp;</span>
                                                            <div>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($template['statut_nom']); ?></div>
                                                                <div class="small text-muted"><?php echo htmlspecialchars($template['categorie_nom']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="message-preview"><?php echo nl2br(htmlspecialchars($template['message'])); ?></div>
                                                    </td>
                                                    <td>
                                                        <form method="post" action="" class="toggle-form">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                                            <input type="hidden" name="status" value="<?php echo $template['actif'] ? '0' : '1'; ?>">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input toggle-submit" type="checkbox" <?php echo $template['actif'] ? 'checked' : ''; ?>>
                                                            </div>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-primary edit-button" data-bs-toggle="modal" data-bs-target="#editTemplateModal" data-id="<?php echo $template['id']; ?>" data-message="<?php echo htmlspecialchars($template['message']); ?>" data-name="<?php echo htmlspecialchars($template['statut_nom']); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Historique d'envoi -->
                <div class="tab-pane fade" id="history">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historique des SMS envoyés</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Client</th>
                                            <th>Statut</th>
                                            <th>Message</th>
                                            <th>Résultat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($sms_history)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <div class="alert alert-info mb-0">
                                                        <i class="fas fa-info-circle me-2"></i>Aucun SMS envoyé
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($sms_history as $sms): ?>
                                                <tr>
                                                    <td class="text-nowrap"><?php echo date('d/m/Y H:i', strtotime($sms['sent_at'])); ?></td>
                                                    <td>
                                                        <div><?php echo htmlspecialchars($sms['client_nom'] . ' ' . $sms['client_prenom']); ?></div>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($sms['telephone']); ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($sms['statut_nom']); ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($sms['message']); ?></div>
                                                    </td>
                                                    <td>
                                                        <?php if ($sms['status'] === 'success'): ?>
                                                            <span class="badge bg-success">Succès</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($sms['error_message']); ?>">Échec</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Test d'envoi -->
                <div class="tab-pane fade" id="test">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-vial me-2"></i>Test d'envoi de SMS</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Utilisez ce formulaire pour tester l'envoi de SMS avec la configuration SMS Gate actuelle.
                                Assurez-vous que l'application mobile est correctement configurée et connectée.
                            </div>
                            
                            <form id="testSmsForm" action="../api/sms_gateway.php" method="post">
                                <div class="mb-3">
                                    <label for="test_phone" class="form-label">Numéro de téléphone</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="tel" class="form-control" id="test_phone" name="recipient" placeholder="+33612345678" required pattern="^\+[0-9]{10,15}$">
                                    </div>
                                    <div class="form-text">Format international (ex: +33612345678)</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="test_message" class="form-label">Message</label>
                                    <textarea class="form-control" id="test_message" name="message" rows="4" required>Ceci est un message de test de la passerelle SMS Gate.</textarea>
                                    <div class="d-flex justify-content-between mt-1">
                                        <div class="form-text">
                                            <span id="charCount">0</span> caractères
                                        </div>
                                        <div class="form-text">
                                            <span id="smsCount">1</span> SMS
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Envoyer le SMS de test
                                </button>
                            </form>
                            
                            <div id="testResult" class="mt-3" style="display: none;"></div>
                            
                            <div class="mt-4">
                                <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>Résolution des problèmes</h6>
                                <ul class="small text-muted">
                                    <li>Vérifiez que l'appareil Android avec l'application SMS Gate est allumé et connecté à Internet</li>
                                    <li>Assurez-vous que le serveur privé est correctement configuré et accessible</li>
                                    <li>Confirmez que le token privé correspond à celui défini dans la configuration du serveur</li>
                                    <li>Vérifiez que l'application a les autorisations nécessaires pour envoyer des SMS</li>
                                    <li>Si aucun message n'est envoyé, consultez les journaux du serveur pour plus de détails</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'édition de modèle -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editTemplateModalLabel">Modifier le modèle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="action" value="update_template">
                <input type="hidden" name="template_id" id="edit_template_id">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Statut</label>
                        <input type="text" class="form-control" id="edit_template_name" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_template_message" class="form-label">Message</label>
                        <textarea class="form-control" id="edit_template_message" name="message" rows="6" required></textarea>
                        <div class="form-text">
                            Variables disponibles: [CLIENT_NOM], [CLIENT_PRENOM], [MARQUE], [MODELE], [NUMERO]
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activation/désactivation des modèles
    document.querySelectorAll('.toggle-submit').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });
    
    // Édition des modèles
    document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const message = this.getAttribute('data-message');
            const name = this.getAttribute('data-name');
            
            document.getElementById('edit_template_id').value = id;
            document.getElementById('edit_template_message').value = message;
            document.getElementById('edit_template_name').value = name;
        });
    });
    
    // Test de connexion à SMS Gate
    const testConnectionBtn = document.getElementById('testConnectionBtn');
    if (testConnectionBtn) {
        testConnectionBtn.addEventListener('click', function() {
            const resultDiv = document.getElementById('connectionTestResult');
            
            // Récupérer les valeurs actuelles du formulaire
            const url = document.getElementById('sms_gateway_url').value;
            const username = document.getElementById('sms_gateway_username').value;
            const password = document.getElementById('sms_gateway_password').value;
            const privateToken = document.getElementById('sms_gateway_private_token').value;
            
            // Vérifier les données minimales
            if (!url) {
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Veuillez spécifier l\'URL de l\'API</div>';
                resultDiv.style.display = 'block';
                return;
            }
            
            if (!privateToken && (!username || !password)) {
                resultDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Vous devez configurer soit un token privé, soit des identifiants</div>';
                resultDiv.style.display = 'block';
                return;
            }
            
            // Afficher l'indicateur de chargement
            resultDiv.innerHTML = '<div class="alert alert-info"><div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Test de connexion en cours...</div></div>';
            resultDiv.style.display = 'block';
            
            // Préparer les données pour le test
            const testData = {
                action: 'test_connection',
                url: url,
                username: username,
                password: password,
                private_token: privateToken
            };
            
            // Envoyer la requête AJAX
            fetch('../api/test_sms_connection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(testData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Connexion réussie!</strong> La configuration est valide.
                            ${data.details ? '<div class="mt-2 small">' + data.details + '</div>' : ''}
                        </div>`;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>Échec de la connexion:</strong> ${data.message}
                            ${data.details ? '<div class="mt-2 small">' + data.details + '</div>' : ''}
                        </div>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Erreur:</strong> ${error.message}
                    </div>`;
            });
        });
    }
    
    // Compteur de caractères pour le test SMS
    const testMessage = document.getElementById('test_message');
    const charCount = document.getElementById('charCount');
    const smsCount = document.getElementById('smsCount');
    
    if (testMessage && charCount && smsCount) {
        testMessage.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;
            
            // Calcul du nombre de SMS selon les standards GSM
            if (length <= 160) {
                smsCount.textContent = '1';
            } else {
                // 153 caractères par SMS pour les messages concaténés
                smsCount.textContent = Math.ceil(length / 153);
            }
        });
        
        // Déclencher l'événement pour initialiser les compteurs
        testMessage.dispatchEvent(new Event('input'));
    }
    
    // Test d'envoi SMS
    const testForm = document.getElementById('testSmsForm');
    if (testForm) {
        testForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('testResult');
            
            // Afficher l'indicateur de chargement
            resultDiv.innerHTML = '<div class="alert alert-info"><div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Envoi en cours...</div></div>';
            resultDiv.style.display = 'block';
            
            // Convertir les données en JSON
            const jsonData = {
                recipient: formData.get('recipient'),
                message: formData.get('message')
            };
            
            // Envoyer la requête AJAX
            fetch('../api/sms_gateway.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(jsonData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>SMS envoyé avec succès!</div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Erreur: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Erreur: ' + error.message + '</div>';
            });
        });
    }
    
    // Initialiser les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>

<style>
.message-preview {
    max-height: 100px;
    overflow-y: auto;
    font-size: 0.9rem;
}

.badge[data-bs-toggle="tooltip"] {
    cursor: help;
}
</style>

<?php
// Inclusion du pied de page
include_once '../includes/footer.php';
?> 