<?php
/**
 * Script de test pour valider la configuration SMS Gateway
 * Ce script envoie un SMS de test pour vérifier que la configuration fonctionne
 */

// Inclure les fichiers nécessaires
require_once '../includes/functions.php';
require_once '../database.php'; // S'assurer que cette inclusion établit la connexion PDO

// Définir le type de contenu pour afficher correctement les résultats
header('Content-Type: text/html; charset=UTF-8');

// Page de test pour l'API SMS
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/sms/functions.php';

// Variables pour le test
$success = false;
$error_message = "";
$sms_sent = false;
$test_result = null;
$sms_gateway_url = get_sms_config('sms_gateway_url', 'https://api.sms-gate.app/3rdparty/v1/message');
$api_username = get_sms_config('api_username', '-GCB75');
$api_password = get_sms_config('api_password', 'Mamanmaman06400');

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_test'])) {
    $test_phone = isset($_POST['test_phone']) ? trim($_POST['test_phone']) : '';
    $test_message = isset($_POST['test_message']) ? trim($_POST['test_message']) : '';
    $test_url = isset($_POST['test_url']) ? trim($_POST['test_url']) : $sms_gateway_url;
    
    if (empty($test_phone) || empty($test_message)) {
        $error_message = "Veuillez remplir tous les champs du formulaire.";
    } else {
        // Test de la connexion à l'API
        try {
            // Tester la connexion à l'API
            $ch = curl_init($test_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERPWD, "$api_username:$api_password");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POST, true);
            
            $test_data = [
                "phone" => $test_phone,
                "message" => $test_message
            ];
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            
            curl_close($ch);
            
            $success = $http_code >= 200 && $http_code < 300;
            
            $test_result = [
                'url' => $test_url,
                'http_code' => $http_code,
                'curl_error' => $curl_error,
                'response' => $response,
                'success' => $success
            ];
            
            if ($success) {
                $sms_sent = true;
            } else {
                $error_message = "Erreur lors du test de l'API SMS (HTTP $http_code): " . $curl_error;
            }
        } catch (Exception $e) {
            $error_message = "Exception: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de l'API SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .code-block {
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-sms me-2"></i> Test de l'API SMS</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($sms_sent): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i> <strong>SMS envoyé avec succès!</strong>
                                <p class="mb-0 mt-2">La connexion à l'API SMS fonctionne correctement.</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i> <strong>Erreur:</strong> <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <h5 class="mb-4">Configuration actuelle:</h5>
                        <div class="mb-4">
                            <div class="alert alert-info">
                                <strong>URL de l'API:</strong> <?php echo htmlspecialchars($sms_gateway_url); ?><br>
                                <strong>Nom d'utilisateur:</strong> <?php echo htmlspecialchars($api_username); ?><br>
                                <strong>Mot de passe:</strong> ******
                            </div>
                            
                            <?php if (strpos($sms_gateway_url, 'https://api.sms-gate.app') !== false): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i> <strong>Attention:</strong>
                                    L'API distante n'est plus disponible. Veuillez configurer l'application SMS Gateway sur un téléphone Android et utiliser une URL locale.
                                    <a href="../guides/configuration_sms_gateway.md" target="_blank" class="d-block mt-2">Voir le guide de configuration</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h5 class="mb-3">Envoyer un SMS de test</h5>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="test_url" class="form-label">URL à tester</label>
                                <input type="text" class="form-control" id="test_url" name="test_url" value="<?php echo htmlspecialchars($sms_gateway_url); ?>" required>
                                <div class="form-text">Par défaut, utilise l'URL configurée dans les paramètres</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="test_phone" class="form-label">Numéro de téléphone de test</label>
                                <input type="text" class="form-control" id="test_phone" name="test_phone" placeholder="+33612345678" required>
                                <div class="form-text">Entrez un numéro valide pour recevoir le SMS de test</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="test_message" class="form-label">Message de test</label>
                                <textarea class="form-control" id="test_message" name="test_message" rows="3" placeholder="Ceci est un message de test..." required>Test SMS depuis GeekBoard - <?php echo date('H:i:s'); ?></textarea>
                            </div>
                            
                            <button type="submit" name="submit_test" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Envoyer le SMS de test
                            </button>
                        </form>
                        
                        <?php if ($test_result): ?>
                            <div class="mt-4">
                                <h5>Résultats du test:</h5>
                                <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                                    <pre class="mb-0"><?php echo print_r($test_result, true); ?></pre>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="../messagerie/admin_sms_templates.php" class="btn btn-outline-primary">
                                <i class="fas fa-cog me-2"></i> Configuration SMS
                            </a>
                            <a href="../index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Retour à l'accueil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 