<?php
// Inclusion des fichiers nécessaires
require_once '../includes/functions.php';
require_once '../database.php';

// Titre de la page
$page_title = "Envoi de SMS";

// Traitement de l'envoi de SMS direct
$sms_sent = false;
$sms_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_sms') {
    $recipient = isset($_POST['recipient']) ? clean_input($_POST['recipient']) : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';
    
    if (!empty($recipient) && !empty($message)) {
        // Utilisation de la fonction send_sms des includes
        $result = send_sms($recipient, $message);
        
        if ($result['success']) {
            $sms_sent = true;
        } else {
            $sms_error = $result['message'];
            // Ajouter des détails sur l'erreur pour le débogage
            if (isset($result['error'])) {
                $sms_error .= "<br>Détails: " . htmlspecialchars($result['error']);
            }
        }
    } else {
        $sms_error = "Veuillez remplir tous les champs.";
    }
}

// Récupération des modèles de SMS
$sms_templates = [
    [
        'name' => 'Confirmation de rendez-vous',
        'content' => "Bonjour, nous vous confirmons votre rendez-vous à notre magasin le [DATE] à [HEURE]. À bientôt!"
    ],
    [
        'name' => 'Réparation terminée',
        'content' => "Bonjour, votre appareil est réparé et prêt à être récupéré. Notre magasin est ouvert de 9h à 19h du lundi au samedi."
    ],
    [
        'name' => 'Devis disponible',
        'content' => "Bonjour, le devis pour votre réparation est disponible. Merci de nous contacter au 01 23 45 67 89 pour plus d'informations."
    ],
    [
        'name' => 'Rappel de paiement',
        'content' => "Bonjour, nous vous rappelons que votre facture #[NUMERO] d'un montant de [MONTANT]€ est en attente de règlement. Merci."
    ]
];

// Inclusion de l'en-tête
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <?php if ($sms_sent): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Succès!</strong> Votre SMS a été envoyé.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($sms_error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erreur!</strong> <?php echo htmlspecialchars($sms_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-sms me-2"></i>Envoi de SMS</h1>
        <a href="sms.php" class="btn btn-outline-secondary">
            <i class="fas fa-history me-1"></i> Voir l'historique
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Nouveau SMS</h5>
                </div>
                <div class="card-body">
                    <form id="smsForm" method="post">
                        <input type="hidden" name="action" value="send_sms">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="recipient" class="form-label">Numéro du destinataire</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="recipient" name="recipient" 
                                        placeholder="+33612345678" required
                                        pattern="^\+[0-9]{10,15}$">
                                </div>
                                <div class="form-text">Format international (ex: +33612345678)</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="template" class="form-label">Modèle de message</label>
                                <select class="form-select" id="template">
                                    <option value="">-- Sélectionner un modèle --</option>
                                    <?php foreach($sms_templates as $index => $template): ?>
                                    <option value="<?php echo $index; ?>"><?php echo htmlspecialchars($template['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" 
                                     maxlength="160" required></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <div class="form-text">
                                    <span id="charCount">0</span>/160 caractères
                                </div>
                                <div class="form-text">
                                    <span id="smsCount">1</span> SMS
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-eraser me-1"></i> Effacer
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Envoyer SMS
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Derniers SMS envoyés</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Destinataire</th>
                                    <th>Message</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Récupération des derniers SMS envoyés
                                if (isset($conn) && $conn instanceof PDO) {
                                    try {
                                        $stmt = $conn->query("SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT 5");
                                        $has_logs = false;
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $has_logs = true;
                                            $status_class = ($row['status'] >= 200 && $row['status'] < 300) ? 'text-success' : 'text-danger';
                                            $status_text = ($row['status'] >= 200 && $row['status'] < 300) ? 'Envoyé' : 'Échec';
                                            echo '<tr>';
                                            echo '<td>' . date('d/m/Y H:i', strtotime($row['created_at'])) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['recipient']) . '</td>';
                                            echo '<td>' . htmlspecialchars(substr($row['message'], 0, 30)) . '...</td>';
                                            echo '<td><span class="badge bg-' . (($row['status'] >= 200 && $row['status'] < 300) ? 'success' : 'danger') . '">' . $status_text . '</span></td>';
                                            echo '</tr>';
                                        }
                                        
                                        if (!$has_logs) {
                                            echo '<tr><td colspan="4" class="text-center py-3">Aucun SMS envoyé récemment</td></tr>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<tr><td colspan="4" class="text-center py-3">Erreur lors de la récupération des données</td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center py-3">Connexion à la base de données non disponible</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="sms.php" class="btn btn-sm btn-outline-secondary">Voir tout l'historique</a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations</h5>
                </div>
                <div class="card-body">
                    <h6>État de la connexion</h6>
                    <?php
                    // Vérifier si l'API est accessible
                    $api_ok = false;
                    $api_status = "Non vérifiée";
                    $api_message = "";
                    
                    try {
                        $ch = curl_init("https://sms-gate.app/");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_NOBODY, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                        curl_exec($ch);
                        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($status > 0) {
                            $api_ok = true;
                            $api_status = "Serveur accessible";
                            $api_message = "Code HTTP: $status";
                        } else {
                            $api_status = "Serveur inaccessible";
                            $api_message = "Aucune réponse du serveur";
                        }
                    } catch (Exception $e) {
                        $api_status = "Erreur";
                        $api_message = $e->getMessage();
                    }
                    ?>
                    <p>
                        <span class="badge bg-<?php echo $api_ok ? 'success' : 'danger'; ?>">
                            <?php echo $api_status; ?>
                        </span>
                        Serveur SMS: <code>api.sms-gate.app</code>
                        <br>
                        <small class="text-muted"><?php echo $api_message; ?></small>
                    </p>
                    
                    <div class="d-grid gap-2 mb-3">
                        <a href="<?php echo get_base_url(); ?>api/api-check.php" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-stethoscope me-1"></i> Diagnostiquer la connexion
                        </a>
                    </div>
                    
                    <hr>
                    
                    <h6>Astuces</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check-circle text-success me-2"></i>Utilisez le format international pour les numéros</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Un SMS standard est limité à 160 caractères</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Les messages plus longs seront divisés en plusieurs SMS</li>
                    </ul>
                    
                    <hr>
                    
                    <h6>Configuration actuelle</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-user me-2"></i>Utilisateur: <code>-GCB75</code></li>
                        <li><i class="fas fa-lock me-2"></i>Mot de passe: <code>●●●●●●●●●●</code></li>
                    </ul>
                    
                    <hr>
                    
                    <h6>Besoin d'aide?</h6>
                    <p>Contactez le support technique ou consultez la documentation.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Compteur de caractères
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    const smsCount = document.getElementById('smsCount');
    
    messageTextarea.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = length;
        
        // Calcul du nombre de SMS
        if (length <= 160) {
            smsCount.textContent = '1';
        } else {
            // 153 caractères par SMS pour les messages concatenés
            smsCount.textContent = Math.ceil(length / 153);
        }
    });
    
    // Gestion des modèles de messages
    const templateSelect = document.getElementById('template');
    const templates = <?php echo json_encode($sms_templates); ?>;
    
    templateSelect.addEventListener('change', function() {
        if (this.value !== '') {
            const selectedTemplate = templates[parseInt(this.value)];
            messageTextarea.value = selectedTemplate.content;
            // Déclencher l'événement input pour mettre à jour le compteur
            messageTextarea.dispatchEvent(new Event('input'));
        }
    });
    
    // Soumission du formulaire avec vérification
    const smsForm = document.getElementById('smsForm');
    
    smsForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const recipient = document.getElementById('recipient').value;
        const message = messageTextarea.value;
        
        // Vérification du format du numéro
        if (!recipient.startsWith('+')) {
            alert('Le numéro doit être au format international et commencer par +');
            return;
        }
        
        // Désactivation du bouton pendant l'envoi
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi en cours...';
        
        // Envoi de la requête AJAX avec le format correct selon la documentation
        fetch('../api/sms_gateway.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                api_key: 'VOTRE_CLE_SECRETE', // À remplacer par votre clé API
                recipient: recipient,
                message: message,
                // URL correcte selon la documentation
                gateway_url: 'http://168.231.85.4:3001/api/messages/send'
            })
        })
        .then(response => response.json())
        .then(data => {
            // Réactivation du bouton
            submitButton.disabled = false;
            submitButton.textContent = 'Envoyer';
            
            // Affichage du résultat
            if (data.success) {
                alert('SMS envoyé avec succès !');
                smsForm.reset();
                charCount.textContent = '0';
                
                // Rechargement de la page pour actualiser l'historique
                location.reload();
            } else {
                alert('Erreur lors de l\'envoi du SMS : ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            
            // Réactivation du bouton
            submitButton.disabled = false;
            submitButton.textContent = 'Envoyer';
            
            alert('Erreur lors de l\'envoi du SMS : ' + error.message);
        });
    });
});
</script>

<?php
// Inclusion du pied de page
include_once '../includes/footer.php';
?> 