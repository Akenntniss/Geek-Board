<?php
// Inclure les fichiers nécessaires
require_once '../includes/header.php';
require_once '../database.php';

// Traitement du formulaire d'envoi
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient = $_POST['recipient'] ?? '';
    $sms_message = $_POST['sms_message'] ?? '';
    $secret = '12345678'; // Doit correspondre à celle dans sms_sync_endpoint.php
    
    if (empty($recipient) || empty($sms_message)) {
        $message = '<div class="alert alert-danger">Veuillez remplir tous les champs</div>';
    } else {
        // Envoyer la requête à notre API
        $api_url = 'https://' . $_SERVER['HTTP_HOST'] . '/api/sms_sync_endpoint.php';
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'task' => 'queue',
                'secret' => $secret,
                'to' => $recipient,
                'message' => $sms_message
            ]
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($http_code == 200) {
            $result = json_decode($response, true);
            
            if ($result && isset($result['success']) && $result['success']) {
                $message = '<div class="alert alert-success">SMS ajouté à la file d\'attente. ID: ' . $result['id'] . '</div>';
            } else {
                $message = '<div class="alert alert-danger">Erreur lors de l\'envoi du SMS: ' . ($result['error'] ?? 'Erreur inconnue') . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Erreur de communication avec l\'API: Code HTTP ' . $http_code . '</div>';
        }
    }
}

// Liste des SMS envoyés récemment
$sms_list = [];
try {
    $query = "SELECT * FROM sms_messages ORDER BY created_at DESC LIMIT 20";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $sms_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = '<div class="alert alert-warning">La table des SMS n\'existe peut-être pas encore. Elle sera créée lors du premier envoi de SMS.</div>';
}
?>

<div class="container mt-4">
    <h1>Envoi de SMS</h1>
    
    <?php echo $message; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Envoyer un SMS</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label for="recipient" class="form-label">Numéro de téléphone</label>
                    <input type="tel" class="form-control" id="recipient" name="recipient" 
                           placeholder="+33612345678" required>
                    <small class="text-muted">Format international recommandé: +33612345678</small>
                </div>
                
                <div class="mb-3">
                    <label for="sms_message" class="form-label">Message</label>
                    <textarea class="form-control" id="sms_message" name="sms_message" 
                              rows="3" maxlength="160" required></textarea>
                    <small class="text-muted">
                        <span id="char-count">0</span>/160 caractères
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary">Envoyer</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Historique des SMS</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Numéro</th>
                        <th>Message</th>
                        <th>Statut</th>
                        <th>Date de création</th>
                        <th>Date d'envoi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sms_list)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Aucun SMS envoyé</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sms_list as $sms): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sms['id']); ?></td>
                                <td><?php echo htmlspecialchars($sms['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($sms['message']); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch ($sms['status']) {
                                        case 'pending': 
                                            $status_class = 'badge bg-warning';
                                            $status_text = 'En attente';
                                            break;
                                        case 'sending': 
                                            $status_class = 'badge bg-info';
                                            $status_text = 'En cours d\'envoi';
                                            break;
                                        case 'sent': 
                                            $status_class = 'badge bg-success';
                                            $status_text = 'Envoyé';
                                            break;
                                        case 'failed': 
                                            $status_class = 'badge bg-danger';
                                            $status_text = 'Échec';
                                            break;
                                        default: 
                                            $status_class = 'badge bg-secondary';
                                            $status_text = $sms['status'];
                                    }
                                    echo '<span class="'.$status_class.'">'.$status_text.'</span>';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($sms['created_at']); ?></td>
                                <td><?php echo $sms['sent_at'] ? htmlspecialchars($sms['sent_at']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Compteur de caractères pour le SMS
    const messageTextarea = document.getElementById('sms_message');
    const charCount = document.getElementById('char-count');
    
    messageTextarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });
});
</script>

<?php
require_once '../includes/footer.php';
?> 