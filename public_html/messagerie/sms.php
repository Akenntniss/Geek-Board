<?php
// Inclusion des fichiers nécessaires
require_once '../includes/functions.php';
require_once '../database.php';

// Vérification si l'utilisateur est connecté (à adapter selon votre système)
// if (!isset($_SESSION['user_id'])) {
//     header('Location: ../index.php');
//     exit;
// }

// Titre de la page
$page_title = "Envoi de SMS";

// Inclusion de l'en-tête
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Envoi de SMS</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Nouveau SMS
                </div>
                <div class="card-body">
                    <form id="smsForm">
                        <div class="mb-3">
                            <label for="recipient" class="form-label">Numéro de téléphone</label>
                            <input type="tel" class="form-control" id="recipient" name="recipient" 
                                   placeholder="+33612345678" required>
                            <div class="form-text">Format international (ex: +33612345678)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" 
                                     maxlength="160" required></textarea>
                            <div class="form-text">
                                <span id="charCount">0</span>/160 caractères
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Historique des SMS
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Destinataire</th>
                                    <th>Message</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody id="smsHistory">
                                <?php
                                // Récupération des derniers SMS envoyés
                                try {
                                    $stmt = $conn->query("SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT 10");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $status_class = ($row['status'] >= 200 && $row['status'] < 300) ? 'text-success' : 'text-danger';
                                        $status_text = ($row['status'] >= 200 && $row['status'] < 300) ? 'Envoyé' : 'Échec';
                                        echo '<tr>';
                                        echo '<td>' . date('d/m/Y H:i', strtotime($row['created_at'])) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['recipient']) . '</td>';
                                        echo '<td>' . htmlspecialchars(substr($row['message'], 0, 30)) . '...</td>';
                                        echo '<td class="' . $status_class . '">' . $status_text . '</td>';
                                        echo '</tr>';
                                    }
                                } catch (PDOException $e) {
                                    // La table n'existe peut-être pas encore
                                    echo '<tr><td colspan="4" class="text-center">Aucun historique disponible</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
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
    
    messageTextarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });
    
    // Gestion du formulaire
    const smsForm = document.getElementById('smsForm');
    
    smsForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const recipient = document.getElementById('recipient').value;
        const message = messageTextarea.value;
        
        // Désactivation du bouton pendant l'envoi
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi en cours...';
        
        // Envoi de la requête AJAX
        fetch('../api/sms_gateway.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                api_key: 'VOTRE_CLE_SECRETE', // À remplacer par votre clé API
                recipient: recipient,
                message: message,
                // Configuration de la passerelle SMS déjà intégrée dans l'API
                gateway_url: 'https://api.sms-gate.app:443/api/v1/messages'
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