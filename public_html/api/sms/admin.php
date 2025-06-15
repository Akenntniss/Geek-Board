<?php
/**
 * Administration du système SMS
 */

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté et admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// Inclure les fonctions
require_once 'functions.php';

// Traiter le formulaire de configuration
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            // Mise à jour de la configuration
            case 'update_config':
                foreach (['api_key', 'default_sender_name', 'max_retries', 'sms_enabled', 'notification_types'] as $param) {
                    if (isset($_POST[$param])) {
                        update_sms_config($param, $_POST[$param]);
                    }
                }
                $success_message = 'Configuration mise à jour avec succès.';
                break;
                
            // Envoi d'un SMS test
            case 'send_test_sms':
                if (isset($_POST['test_recipient'], $_POST['test_message'])) {
                    $sms_id = queue_sms(
                        $_POST['test_recipient'],
                        $_POST['test_message'],
                        'test',
                        null,
                        $_SESSION['user_id']
                    );
                    
                    if ($sms_id) {
                        $success_message = 'SMS test mis en file d\'attente avec l\'ID ' . $sms_id;
                    } else {
                        $error_message = 'Erreur lors de l\'envoi du SMS test';
                    }
                } else {
                    $error_message = 'Veuillez fournir un destinataire et un message';
                }
                break;
                
            // Réinitialiser la clé API
            case 'reset_api_key':
                $new_key = md5(uniqid(mt_rand(), true));
                if (update_sms_config('api_key', $new_key)) {
                    $success_message = 'Clé API réinitialisée avec succès.';
                } else {
                    $error_message = 'Erreur lors de la réinitialisation de la clé API.';
                }
                break;
                
            // Effacer les journaux
            case 'clear_logs':
                if (file_exists(__DIR__ . '/smssync_requests.log')) {
                    unlink(__DIR__ . '/smssync_requests.log');
                    $success_message = 'Journaux effacés avec succès.';
                }
                break;
        }
    }
}

// Récupérer la configuration actuelle
$config = [
    'api_key' => get_sms_config('api_key', 'Non défini'),
    'default_sender_name' => get_sms_config('default_sender_name', 'VotreEntreprise'),
    'max_retries' => get_sms_config('max_retries', '3'),
    'sms_enabled' => get_sms_config('sms_enabled', '1'),
    'notification_types' => get_sms_config('notification_types', 'reparation_status,appointment_reminder')
];

// Récupérer les statistiques
$shop_pdo = getShopDBConnection();
$stats = [];

try {
    // Nombre total de SMS envoyés
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM sms_outgoing");
    $stats['total_sent'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Nombre de SMS en attente
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM sms_outgoing WHERE status = 'pending'");
    $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Nombre de SMS envoyés avec succès
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM sms_outgoing WHERE status = 'sent'");
    $stats['sent_success'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Nombre de SMS reçus
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM sms_incoming");
    $stats['total_received'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Derniers SMS envoyés
    $stmt = $shop_pdo->query("
        SELECT s.*, u.nom, u.prenom 
        FROM sms_outgoing s 
        LEFT JOIN utilisateurs u ON s.created_by = u.id 
        ORDER BY s.created_at DESC 
        LIMIT 10
    ");
    $recent_sent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Derniers SMS reçus
    $stmt = $shop_pdo->query("
        SELECT s.*, c.nom, c.prenom 
        FROM sms_incoming s 
        LEFT JOIN clients c ON s.reference_type = 'client' AND s.reference_id = c.id 
        ORDER BY s.received_timestamp DESC 
        LIMIT 10
    ");
    $recent_received = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = 'Erreur lors de la récupération des statistiques: ' . $e->getMessage();
}

// Lire les journaux (si disponibles)
$logs = '';
if (file_exists(__DIR__ . '/smssync_requests.log')) {
    $logs = file_get_contents(__DIR__ . '/smssync_requests.log');
    // Limiter à 100 lignes maximum
    $logs_array = explode("\n", $logs);
    if (count($logs_array) > 100) {
        $logs_array = array_slice($logs_array, -100);
        $logs = implode("\n", $logs_array);
    }
}

// Inclure l'en-tête
$title = "Administration SMS";
include '../../includes/header.php';
?>

<div class="container mt-4">
    <h1>Administration du système SMS</h1>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Configuration SMSSync</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="update_config">
                        
                        <div class="form-group">
                            <label for="api_key">Clé API</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="api_key" name="api_key" value="<?php echo htmlspecialchars($config['api_key']); ?>" readonly>
                                <div class="input-group-append">
                                    <button type="submit" name="action" value="reset_api_key" class="btn btn-warning">Réinitialiser</button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Cette clé est utilisée pour authentifier votre téléphone Android.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="default_sender_name">Nom d'expéditeur par défaut</label>
                            <input type="text" class="form-control" id="default_sender_name" name="default_sender_name" value="<?php echo htmlspecialchars($config['default_sender_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="max_retries">Nombre maximum de tentatives</label>
                            <input type="number" class="form-control" id="max_retries" name="max_retries" value="<?php echo htmlspecialchars($config['max_retries']); ?>" min="1" max="10">
                        </div>
                        
                        <div class="form-group">
                            <label for="sms_enabled">Service SMS</label>
                            <select class="form-control" id="sms_enabled" name="sms_enabled">
                                <option value="1" <?php echo $config['sms_enabled'] === '1' ? 'selected' : ''; ?>>Activé</option>
                                <option value="0" <?php echo $config['sms_enabled'] === '0' ? 'selected' : ''; ?>>Désactivé</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="notification_types">Types de notifications</label>
                            <input type="text" class="form-control" id="notification_types" name="notification_types" value="<?php echo htmlspecialchars($config['notification_types']); ?>">
                            <small class="form-text text-muted">Séparés par des virgules</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>SMS Test</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="send_test_sms">
                        
                        <div class="form-group">
                            <label for="test_recipient">Numéro de téléphone</label>
                            <input type="text" class="form-control" id="test_recipient" name="test_recipient" placeholder="+33600000000" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="test_message">Message</label>
                            <textarea class="form-control" id="test_message" name="test_message" rows="3" required>Ceci est un message de test depuis votre système SMS.</textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success">Envoyer SMS test</button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Instructions de configuration SMSSync</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Installez l'application <a href="https://play.google.com/store/apps/details?id=org.ushahidi.android.app.smssync" target="_blank">SMSSync</a> sur votre smartphone Android</li>
                        <li>Ouvrez l'application et allez dans "Settings"</li>
                        <li>Dans "SYNC URL", configurez l'URL suivante: <code><?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/api/sms/smssync_endpoint.php'; ?></code></li>
                        <li>Dans "Secret key", entrez la clé API: <code><?php echo htmlspecialchars($config['api_key']); ?></code></li>
                        <li>Activez "Auto sync" et définissez un intervalle (par exemple 5 minutes)</li>
                        <li>Activez également "Task checking" et "Get task from server"</li>
                        <li>Retournez à l'écran principal et activez le service en cliquant sur "Start SMSSync service"</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Statistiques</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Total envoyés</h5>
                                    <p class="card-text display-4"><?php echo isset($stats['total_sent']) ? $stats['total_sent'] : 0; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">En attente</h5>
                                    <p class="card-text display-4"><?php echo isset($stats['pending']) ? $stats['pending'] : 0; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Envoyés avec succès</h5>
                                    <p class="card-text display-4"><?php echo isset($stats['sent_success']) ? $stats['sent_success'] : 0; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Total reçus</h5>
                                    <p class="card-text display-4"><?php echo isset($stats['total_received']) ? $stats['total_received'] : 0; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Derniers SMS envoyés</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Destinataire</th>
                                    <th>Message</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($recent_sent) && count($recent_sent) > 0): ?>
                                    <?php foreach ($recent_sent as $sms): ?>
                                        <tr>
                                            <td><?php echo $sms['id']; ?></td>
                                            <td><?php echo htmlspecialchars($sms['recipient']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($sms['message'], 0, 30)) . (strlen($sms['message']) > 30 ? '...' : ''); ?></td>
                                            <td>
                                                <?php
                                                $status_class = 'secondary';
                                                switch ($sms['status']) {
                                                    case 'sent': $status_class = 'success'; break;
                                                    case 'failed': $status_class = 'danger'; break;
                                                    case 'pending': $status_class = 'warning'; break;
                                                    case 'delivered': $status_class = 'info'; break;
                                                }
                                                ?>
                                                <span class="badge badge-<?php echo $status_class; ?>"><?php echo ucfirst($sms['status']); ?></span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($sms['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Aucun SMS envoyé</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Derniers SMS reçus</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Expéditeur</th>
                                    <th>Message</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($recent_received) && count($recent_received) > 0): ?>
                                    <?php foreach ($recent_received as $sms): ?>
                                        <tr>
                                            <td><?php echo $sms['id']; ?></td>
                                            <td>
                                                <?php
                                                if (!empty($sms['nom']) && !empty($sms['prenom'])) {
                                                    echo htmlspecialchars($sms['prenom'] . ' ' . $sms['nom']);
                                                } else {
                                                    echo htmlspecialchars($sms['sender']);
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($sms['message'], 0, 30)) . (strlen($sms['message']) > 30 ? '...' : ''); ?></td>
                                            <td>
                                                <?php
                                                $status_class = 'secondary';
                                                switch ($sms['status']) {
                                                    case 'new': $status_class = 'info'; break;
                                                    case 'processed': $status_class = 'success'; break;
                                                    case 'responded': $status_class = 'primary'; break;
                                                }
                                                ?>
                                                <span class="badge badge-<?php echo $status_class; ?>"><?php echo ucfirst($sms['status']); ?></span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($sms['received_timestamp'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Aucun SMS reçu</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Journaux (dernières requêtes)</h5>
            <form method="post">
                <button type="submit" name="action" value="clear_logs" class="btn btn-sm btn-danger">Effacer les journaux</button>
            </form>
        </div>
        <div class="card-body">
            <pre class="bg-dark text-light p-3" style="max-height: 400px; overflow: auto;"><?php echo htmlspecialchars($logs); ?></pre>
        </div>
    </div>
    
</div>

<?php
// Inclure le pied de page
include '../../includes/footer.php';
?> 