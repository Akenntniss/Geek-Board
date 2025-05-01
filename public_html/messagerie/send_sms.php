<?php
// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?page=login');
    exit;
}

// Inclure les fichiers nécessaires
include_once('../includes/header.php');
include_once('../database.php');

// Initialiser les variables
$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $recipient = $_POST['recipient'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Validation des données
    if (empty($recipient)) {
        $error = 'Le numéro de téléphone est obligatoire';
    } elseif (empty($message)) {
        $error = 'Le message est obligatoire';
    } else {
        // Configuration de la requête vers l'API Android
        $android_api_url = 'https://VOTRE_URL_NGROK.ngrok.io/send_sms';
        $api_key = 'votre_cle_secrete'; // À définir avec la même valeur que dans Tasker
        
        // Préparer les données
        $data = [
            'recipient' => $recipient,
            'message' => $message,
            'api_key' => $api_key
        ];
        
        // Envoyer la requête à l'API
        $ch = curl_init($android_api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Traiter la réponse
        if ($http_code == 200) {
            $response_data = json_decode($response, true);
            if ($response_data && isset($response_data['success']) && $response_data['success']) {
                $success = 'SMS envoyé avec succès!';
                
                // Enregistrer l'envoi dans la base de données pour le suivi
                $user_id = $_SESSION['user_id'];
                $sql = "INSERT INTO sms_logs (user_id, recipient, message, sent_at) 
                        VALUES (?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iss', $user_id, $recipient, $message);
                $stmt->execute();
                
                // Réinitialiser les champs du formulaire
                $recipient = '';
                $message = '';
            } else {
                $error = 'Erreur: ' . ($response_data['message'] ?? 'Réponse invalide du serveur');
            }
        } else {
            $error = 'Erreur de connexion au serveur SMS (code: ' . $http_code . ')';
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Envoi de SMS</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="form-group mb-3">
                            <label for="recipient">Numéro de téléphone</label>
                            <input type="text" class="form-control" id="recipient" name="recipient" 
                                   value="<?php echo htmlspecialchars($recipient ?? ''); ?>" 
                                   placeholder="+33600000000" required>
                            <small class="form-text text-muted">Format international (+33...)</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="message">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" 
                                      required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            <small class="form-text text-muted">
                                <span id="character-count">0</span>/160 caractères
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Envoyer le SMS</button>
                    </form>
                </div>
            </div>
            
            <!-- Historique des SMS -->
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5>Historique des envois récents</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
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
                            // Récupérer l'historique des SMS
                            $user_id = $_SESSION['user_id'];
                            $sql = "SELECT * FROM sms_logs WHERE user_id = ? ORDER BY sent_at DESC LIMIT 10";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param('i', $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars(date('d/m/Y H:i', strtotime($row['sent_at']))) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['recipient']) . '</td>';
                                    echo '<td>' . htmlspecialchars(substr($row['message'], 0, 30)) . (strlen($row['message']) > 30 ? '...' : '') . '</td>';
                                    echo '<td>' . ($row['status'] ?? 'Envoyé') . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="4" class="text-center">Aucun SMS envoyé</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script pour compter les caractères
document.addEventListener('DOMContentLoaded', function() {
    const messageField = document.getElementById('message');
    const characterCount = document.getElementById('character-count');
    
    function updateCharacterCount() {
        const count = messageField.value.length;
        characterCount.textContent = count;
        
        if (count > 160) {
            characterCount.classList.add('text-danger');
        } else {
            characterCount.classList.remove('text-danger');
        }
    }
    
    messageField.addEventListener('input', updateCharacterCount);
    updateCharacterCount(); // Initial count
});
</script>

<?php include_once('../includes/footer.php'); ?> 