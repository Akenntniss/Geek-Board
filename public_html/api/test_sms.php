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

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de la configuration SMS Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <div class="container mt-5">
        <h1>Test de la configuration SMS Gateway</h1>
        <hr>

        <?php
        // Vérifier si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_sms') {
            $numero = isset($_POST['numero']) ? $_POST['numero'] : '';
            $message = isset($_POST['message']) ? $_POST['message'] : '';

            echo '<div class="card mb-4">';
            echo '<div class="card-header bg-primary text-white">Résultat du test</div>';
            echo '<div class="card-body">';
            
            // Valider les données
            $errors = [];
if (empty($numero)) {
                $errors[] = "Le numéro de téléphone est obligatoire.";
            }
            if (empty($message)) {
                $errors[] = "Le message est obligatoire.";
}

            if (empty($errors)) {
                try {
                    echo "<h5>Envoi d'un SMS de test</h5>";
                    echo "<p><strong>Numéro :</strong> $numero</p>";
                    echo "<p><strong>Message :</strong> $message</p>";
                    
                    // Envoi du SMS
                    $result = send_sms($numero, $message);
                    
                    if ($result['success']) {
                        echo '<div class="alert alert-success">Le SMS a été envoyé avec succès!</div>';
                    } else {
                        echo '<div class="alert alert-danger">Échec de l\'envoi du SMS. Erreur : ' . $result['message'] . '</div>';
                    }
                    
                    // Afficher les détails de la réponse
                    echo '<h5 class="mt-4">Détails de la réponse :</h5>';
                    echo '<div class="code-block">';
                    print_r($result);
                    echo '</div>';
                    
                    // Vérifier les logs
                    echo '<h5 class="mt-4">Vérifiez les logs de l\'application pour plus de détails.</h5>';
                    echo '<p>Les logs peuvent être trouvés dans le répertoire de logs de votre serveur.</p>';
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">Une erreur est survenue : ' . $e->getMessage() . '</div>';
                }
            } else {
                echo '<div class="alert alert-danger"><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . $error . '</li>';
        }
                echo '</ul></div>';
            }
            
            echo '</div></div>';
        }
        ?>
        
        <div class="card">
            <div class="card-header bg-info text-white">Envoyer un SMS de test</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="test_sms">
                    
                    <div class="mb-3">
                        <label for="numero" class="form-label">Numéro de téléphone</label>
                        <input type="text" class="form-control" id="numero" name="numero" placeholder="+33612345678 ou 0612345678">
                        <div class="form-text">Format international recommandé (ex: +33612345678)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="3" placeholder="Votre message de test ici...">Ceci est un message de test envoyé le <?php echo date('d/m/Y à H:i:s'); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Envoyer le SMS de test</button>
                </form>
            </div>
        </div>
        
        <h2 class="mt-5">Informations système</h2>
        <table class="table">
            <tbody>
                <tr>
                    <th>PHP Version</th>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <th>cURL Disponible</th>
                    <td><?php echo function_exists('curl_version') ? '<span class="text-success">Oui</span>' : '<span class="text-danger">Non</span>'; ?></td>
                </tr>
                <tr>
                    <th>Version cURL</th>
                    <td><?php 
                        if (function_exists('curl_version')) {
                            $curl_info = curl_version();
                            echo $curl_info['version'];
                        } else {
                            echo 'Non disponible';
                        }
                    ?></td>
                </tr>
                <tr>
                    <th>SSL Support</th>
                    <td><?php 
                        if (function_exists('curl_version')) {
                            $curl_info = curl_version();
                            echo ($curl_info['features'] & CURL_VERSION_SSL) ? '<span class="text-success">Oui</span>' : '<span class="text-danger">Non</span>';
                        } else {
                            echo 'Non disponible';
                        }
                    ?></td>
                </tr>
                <tr>
                    <th>Fonction send_sms</th>
                    <td><?php echo function_exists('send_sms') ? '<span class="text-success">Disponible</span>' : '<span class="text-danger">Non disponible</span>'; ?></td>
                </tr>
                <tr>
                    <th>Connexion Base de données</th>
                    <td><?php 
                        echo (isset($pdo) && $pdo instanceof PDO) ? '<span class="text-success">OK</span>' : '<span class="text-danger">Erreur</span>';
                    ?></td>
                </tr>
                <tr>
                    <th>Table sms_logs</th>
                    <td><?php 
                        if (isset($pdo) && $pdo instanceof PDO) {
                            try {
                                $stmt = $pdo->query("SHOW TABLES LIKE 'sms_logs'");
                                echo $stmt->rowCount() > 0 ? '<span class="text-success">Existe</span>' : '<span class="text-danger">Manquante</span>';
                            } catch (Exception $e) {
                                echo '<span class="text-danger">Erreur: ' . $e->getMessage() . '</span>';
                            }
                        } else {
                            echo 'Non vérifiable - Connexion BDD indisponible';
                        }
                    ?></td>
                </tr>
                <tr>
                    <th>Permissions d'écriture log</th>
                    <td><?php 
                        $log_dir = $_SERVER['DOCUMENT_ROOT'] . '/logs';
                        if (!is_dir($log_dir)) {
                            echo '<span class="text-warning">Répertoire de logs inexistant</span>';
                        } else {
                            echo is_writable($log_dir) ? '<span class="text-success">OK</span>' : '<span class="text-danger">Pas de permission d\'écriture</span>';
                        }
                    ?></td>
                </tr>
            </tbody>
        </table>

        <h2 class="mt-5">Configuration SMS Gateway</h2>
        <p>Assurez-vous que l'application SMS Gateway est installée et configurée sur votre appareil Android.</p>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Vérifications à effectuer</div>
            <div class="card-body">
                <ul>
                    <li>L'application SMS Gateway est installée sur un téléphone Android</li>
                    <li>Le téléphone est connecté à Internet (WiFi ou données mobiles)</li>
                    <li>L'application est configurée en mode "Cloud Server"</li>
                    <li>Les identifiants d'API sont correctement configurés</li>
                    <li>L'application est en cours d'exécution sur le téléphone</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 