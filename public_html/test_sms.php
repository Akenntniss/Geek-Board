<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Afficher un message d'information sur l'environnement
echo '<!-- Informations environnement: 
PHP Version: ' . phpversion() . '
Document Root: ' . $_SERVER['DOCUMENT_ROOT'] . '
Script: ' . $_SERVER['SCRIPT_FILENAME'] . '
-->';

// Détecter le chemin de base de l'application
$base_path = $_SERVER['DOCUMENT_ROOT'];
// Si le chemin semble incorrect, essayer de le détecter à partir du chemin du script actuel
if (!file_exists($base_path . '/config/database.php')) {
    // Remonter de 2 niveaux depuis le script actuel
    $base_path = dirname(dirname(__FILE__));
    echo '<!-- Correction de chemin: Nouveau base_path=' . $base_path . ' -->';
}

// Inclure les fichiers nécessaires
require_once $base_path . '/config/database.php';
require_once $base_path . '/includes/functions.php';

// Inclure les APIs SMS
if (file_exists($base_path . '/api/sms/send.php')) {
    require_once $base_path . '/api/sms/send.php';
}

// Désactiver la vérification d'authentification pour ce script de test
// Définir un utilisateur factice pour éviter les erreurs
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 999; // ID fictif pour les tests
    $_SESSION['username'] = 'test_user';
    $_SESSION['user_role'] = 'tester';
}

// S'assurer qu'un shop_id est défini
if (!isset($_SESSION['shop_id'])) {
    // Récupérer le premier shop de la base de données
    try {
        $main_pdo = getMainDBConnection();
        if ($main_pdo) {
            $stmt = $main_pdo->query("SELECT id FROM shops LIMIT 1");
            if ($stmt) {
                $shop = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($shop) {
                    $_SESSION['shop_id'] = $shop['id'];
                } else {
                    $_SESSION['shop_id'] = 1; // Valeur par défaut
                }
            } else {
                $_SESSION['shop_id'] = 1; // Valeur par défaut
            }
        } else {
            $_SESSION['shop_id'] = 1; // Valeur par défaut
        }
    } catch (Exception $e) {
        $_SESSION['shop_id'] = 1; // Valeur par défaut en cas d'erreur
    }
}

// Définir un numéro de téléphone de test
$telephone = isset($_POST['telephone']) ? $_POST['telephone'] : '';
$message = isset($_POST['message']) ? $_POST['message'] : '';
$method = isset($_POST['method']) ? $_POST['method'] : '';

$result = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($telephone) && !empty($message)) {
    if ($method === 'direct') {
        // Utiliser notre méthode directe avec cURL
        $data = [
            'telephone' => $telephone,
            'message' => $message,
            'reparation_id' => null,
            'template_id' => null
        ];
        
        // Créer une requête cURL
        $ch = curl_init($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/ajax/direct_send_sms.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        
        // Exécuter la requête
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Analyser la réponse
        $result = [
            'method' => 'direct',
            'http_code' => $http_code,
            'response' => json_decode($response, true) ?: $response
        ];
    } else if ($method === 'function') {
        // Utiliser la fonction send_sms si disponible
        if (function_exists('send_sms')) {
            $result_sms = send_sms($telephone, $message);
            $result = [
                'method' => 'function',
                'response' => $result_sms
            ];
        } else {
            $result = [
                'method' => 'function',
                'error' => "La fonction send_sms() n'existe pas"
            ];
        }
    } else if ($method === 'ajax') {
        // Cette méthode doit être appelée via JavaScript, donc ici on ne fait rien
        // L'envoi réel sera fait côté client
        $result = [
            'method' => 'ajax',
            'info' => "Utiliser le bouton 'Tester via AJAX'"
        ];
    }
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
        body {
            padding-top: 2rem;
        }
        .result-box {
            max-height: 300px;
            overflow-y: auto;
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        pre {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Test d'envoi de SMS</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Formulaire de test</h5>
            </div>
            <div class="card-body">
                <form method="post" action="test_sms.php" id="smsForm">
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Numéro de téléphone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($telephone); ?>" required>
                        <small class="text-muted">Format international recommandé: +33612345678</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required><?php echo htmlspecialchars($message); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Méthode d'envoi</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" name="method" value="direct" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Direct (via direct_send_sms.php)
                            </button>
                            <button type="submit" name="method" value="function" class="btn btn-success">
                                <i class="fas fa-code me-1"></i> Function (via send_sms())
                            </button>
                            <button type="button" id="ajaxTest" class="btn btn-info">
                                <i class="fas fa-exchange-alt me-1"></i> Tester via AJAX (send_sms.php)
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="mt-3 alert alert-info">
                    <strong>Note:</strong> Si vous rencontrez des problèmes avec les chemins de fichiers, essayez d'ajuster manuellement ces chemins:
                    <ul>
                        <li>Chemin actuel: <code><?php echo $base_path; ?></code></li>
                        <li>Document Root: <code><?php echo $_SERVER['DOCUMENT_ROOT']; ?></code></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php if ($result): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Résultat</h5>
            </div>
            <div class="card-body">
                <div class="result-box">
                    <pre><?php echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4 d-none" id="ajaxResultCard">
            <div class="card-header">
                <h5 class="mb-0">Résultat AJAX</h5>
            </div>
            <div class="card-body">
                <div class="result-box">
                    <pre id="ajaxResult">En attente...</pre>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Aide</h5>
            </div>
            <div class="card-body">
                <p><strong>Direct (via direct_send_sms.php)</strong> : Utilise notre nouveau script d'envoi de SMS direct.</p>
                <p><strong>Function (via send_sms())</strong> : Utilise la fonction PHP send_sms() définie dans le système.</p>
                <p><strong>AJAX (send_sms.php)</strong> : Utilise la méthode employée par le modal SMS dans reparations.php.</p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informations de session</h5>
            </div>
            <div class="card-body">
                <div class="result-box">
                    <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Non défini'; ?></p>
                    <p><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'Non défini'; ?></p>
                    <p><strong>Role:</strong> <?php echo $_SESSION['user_role'] ?? 'Non défini'; ?></p>
                    <p><strong>Shop ID:</strong> <?php echo $_SESSION['shop_id'] ?? 'Non défini'; ?></p>
                    <p><strong>SESSION:</strong></p>
                    <pre><?php print_r($_SESSION); ?></pre>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Test AJAX
            document.getElementById('ajaxTest').addEventListener('click', function() {
                const telephone = document.getElementById('telephone').value;
                const message = document.getElementById('message').value;
                
                if (!telephone || !message) {
                    alert('Veuillez remplir tous les champs');
                    return;
                }
                
                // Afficher la carte de résultat AJAX
                document.getElementById('ajaxResultCard').classList.remove('d-none');
                document.getElementById('ajaxResult').textContent = 'Envoi en cours...';
                
                // Préparer les données
                const formData = new FormData();
                formData.append('telephone', telephone);
                formData.append('message', message);
                
                // Envoyer la requête
                fetch('ajax/send_sms.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                })
                .then(response => {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json().then(data => ({ 
                            status: response.status,
                            statusText: response.statusText,
                            data 
                        }));
                    } else {
                        return response.text().then(text => ({
                            status: response.status,
                            statusText: response.statusText,
                            text
                        }));
                    }
                })
                .then(result => {
                    document.getElementById('ajaxResult').textContent = JSON.stringify(result, null, 2);
                })
                .catch(error => {
                    document.getElementById('ajaxResult').textContent = 'Erreur: ' + error.message;
                });
            });
        });
    </script>
</body>
</html> 