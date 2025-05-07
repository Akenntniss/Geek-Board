<?php
// Page de test pour l'envoi de SMS avec SMS-Gate.app
session_start();

// Définir l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Créer une session de test
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['shop_id'] = 1;
}

// Configuration des logs
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/test_sms_' . date('Y-m-d') . '.log';

function log_message($message) {
    global $log_file;
    $log_entry = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $log_entry . "<br>";
}

// Fonction pour envoyer un SMS directement à l'API SMS-Gate.app
function send_sms_direct($telephone, $message) {
    log_message("Envoi direct d'un SMS à: $telephone");
    log_message("Message: $message");
    
    // Configuration de l'API SMS Gateway
    $API_URL = 'https://api.sms-gate.app/3rdparty/v1/message';
    $API_USERNAME = '-GCB75';
    $API_PASSWORD = 'Mamanmaman06400';
    
    // Formatage du numéro de téléphone
    $recipient = preg_replace('/[^0-9+]/', '', $telephone);
    if (substr($recipient, 0, 1) !== '+') {
        if (substr($recipient, 0, 1) === '0') {
            $recipient = '+33' . substr($recipient, 1);
        } else if (substr($recipient, 0, 2) === '33') {
            $recipient = '+' . $recipient;
        } else {
            $recipient = '+' . $recipient;
        }
    }
    
    log_message("Numéro formaté: $recipient");
    
    // Préparation des données JSON pour l'API
    $sms_data = json_encode([
        'message' => $message,
        'phoneNumbers' => [$recipient]
    ]);
    
    log_message("Données JSON: $sms_data");
    
    // Envoi du SMS via l'API SMS Gateway
    $curl = curl_init($API_URL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $sms_data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($sms_data)
    ]);
    
    // Configuration de l'authentification Basic
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "$API_USERNAME:$API_PASSWORD");
    
    // Ajouter des options pour le débogage
    curl_setopt($curl, CURLOPT_VERBOSE, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    
    // Exécution de la requête
    $start_time = microtime(true);
    $response = curl_exec($curl);
    $end_time = microtime(true);
    $duration = round(($end_time - $start_time) * 1000); // en millisecondes
    
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    log_message("Code HTTP: $status");
    log_message("Temps: $duration ms");
    
    if ($response === false) {
        log_message("Erreur cURL: " . curl_error($curl));
        $result = false;
    } else {
        log_message("Réponse: $response");
        $result = true;
    }
    
    curl_close($curl);
    return $result;
}

// Fonction pour récupérer un template SMS
function get_sms_template($template_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM sms_templates WHERE id = ? AND est_actif = 1");
        $stmt->execute([$template_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_message("Erreur lors de la récupération du template: " . $e->getMessage());
        return false;
    }
}

// Traitement de l'envoi de SMS via le formulaire
$response = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_message("Traitement d'une requête POST");
    
    if (isset($_POST['telephone']) && isset($_POST['message'])) {
        // Envoi direct du SMS
        $result = send_sms_direct($_POST['telephone'], $_POST['message']);
        
        if ($result) {
            $response = ['success' => true, 'message' => 'SMS envoyé avec succès !'];
        } else {
            $response = ['success' => false, 'message' => 'Erreur lors de l\'envoi du SMS.'];
        }
    } else if (isset($_POST['telephone']) && isset($_POST['template_id'])) {
        // Récupérer le template
        $template = get_sms_template($_POST['template_id']);
        
        if ($template) {
            // Remplacements des variables si nécessaire
            $message = $template['contenu'];
            
            // Envoi du SMS avec le template
            $result = send_sms_direct($_POST['telephone'], $message);
            
            if ($result) {
                $response = ['success' => true, 'message' => 'SMS envoyé avec succès depuis le template !'];
            } else {
                $response = ['success' => false, 'message' => 'Erreur lors de l\'envoi du SMS avec le template.'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Template non trouvé ou inactif.'];
        }
    }
}

// Récupérer tous les templates SMS disponibles
$templates = [];
try {
    $stmt = $pdo->prepare("SELECT id, nom FROM sms_templates WHERE est_actif = 1 ORDER BY id");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    log_message("Erreur lors de la récupération des templates: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test d'envoi de SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 20px; }
        .response { margin-top: 20px; }
        .card { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Test d'envoi de SMS</h1>
        
        <?php if (!empty($response)): ?>
            <div class="alert alert-<?php echo $response['success'] ? 'success' : 'danger'; ?> response">
                <?php echo $response['message']; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Envoi direct</div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="telephone" class="form-label">Numéro de téléphone</label>
                                <input type="text" class="form-control" id="telephone" name="telephone" required>
                                <div class="form-text">Format: 06XXXXXXXX ou +33XXXXXXXXX</div>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Envoyer le SMS</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Envoi avec template</div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="telephone_template" class="form-label">Numéro de téléphone</label>
                                <input type="text" class="form-control" id="telephone_template" name="telephone" required>
                                <div class="form-text">Format: 06XXXXXXXX ou +33XXXXXXXXX</div>
                            </div>
                            <div class="mb-3">
                                <label for="template_id" class="form-label">Template SMS</label>
                                <select class="form-select" id="template_id" name="template_id" required>
                                    <option value="">Sélectionner un template</option>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?php echo $template['id']; ?>" <?php echo ($template['id'] == 5) ? 'selected' : ''; ?>>
                                            <?php echo $template['id'] . ' - ' . $template['nom']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">Envoyer avec template</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">Logs d'envoi</div>
            <div class="card-body">
                <pre id="logs" style="max-height: 300px; overflow-y: auto;"><?php
                    if (file_exists($log_file)) {
                        echo htmlspecialchars(file_get_contents($log_file));
                    } else {
                        echo "Aucun log disponible.";
                    }
                ?></pre>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 