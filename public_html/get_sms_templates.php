<?php
// Script pour lister tous les templates SMS et tester l'envoi direct
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Inclure la configuration de la base de données
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Configurer la session si nécessaire
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['shop_id'] = 1;
}

// Fichier de log spécifique pour ce test
$log_file = __DIR__ . '/logs/test_direct_' . date('Y-m-d') . '.log';

// Fonction pour logger
function log_to_file($message) {
    global $log_file;
    $log_entry = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $message . "<br>";
}

// Fonction pour envoyer un SMS directement via l'API
function send_sms_direct($telephone, $message) {
    log_to_file("Tentative d'envoi d'un SMS à $telephone");
    
    // Configuration de l'API SMS Gateway
    $API_URL = 'https://api.sms-gate.app/3rdparty/v1/message';
    $API_USERNAME = '-GCB75';
    $API_PASSWORD = 'Mamanmaman06400';
    
    // Formater le numéro de téléphone
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
    
    log_to_file("Numéro formaté: $recipient");
    
    // Préparation des données JSON pour l'API
    $sms_data = json_encode([
        'message' => $message,
        'phoneNumbers' => [$recipient]
    ]);
    
    log_to_file("Données JSON: $sms_data");
    
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
    
    log_to_file("Code HTTP: $status");
    log_to_file("Temps: $duration ms");
    
    if ($response === false) {
        log_to_file("Erreur cURL: " . curl_error($curl));
        $result = false;
    } else {
        log_to_file("Réponse API: $response");
        $result = true;
    }
    
    curl_close($curl);
    return $result;
}

// Test de connexion à la base de données
try {
    // Essayer la connexion à la base de données du magasin
    log_to_file("Test de connexion à la base de données du magasin...");
    $shop_pdo = getShopDBConnection();
    
    if ($shop_pdo === null) {
        log_to_file("ERREUR: Connexion à la base de données du magasin impossible");
    } else {
        $db_name_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
        $db_result = $db_name_stmt->fetch(PDO::FETCH_ASSOC);
        log_to_file("Connecté à la base du magasin: " . ($db_result['current_db'] ?? 'Inconnue'));
        
        // Vérifier si la table sms_templates existe dans cette base
        try {
            $tables_stmt = $shop_pdo->query("SHOW TABLES LIKE 'sms_templates'");
            $table_exists = $tables_stmt->rowCount() > 0;
            log_to_file("Table sms_templates dans la base du magasin: " . ($table_exists ? "Existe" : "N'existe pas"));
            
            if ($table_exists) {
                $templates_stmt = $shop_pdo->query("SELECT * FROM sms_templates");
                $templates = $templates_stmt->fetchAll(PDO::FETCH_ASSOC);
                log_to_file("Nombre de templates trouvés dans la base du magasin: " . count($templates));
                
                echo "<h3>Templates SMS dans la base du magasin:</h3>";
                echo "<table border='1'>";
                echo "<tr><th>ID</th><th>Nom</th><th>Contenu</th><th>Actif</th></tr>";
                foreach ($templates as $t) {
                    echo "<tr>";
                    echo "<td>" . $t['id'] . "</td>";
                    echo "<td>" . $t['nom'] . "</td>";
                    echo "<td>" . $t['contenu'] . "</td>";
                    echo "<td>" . ($t['est_actif'] ? 'Oui' : 'Non') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (PDOException $e) {
            log_to_file("ERREUR lors de la vérification de la table sms_templates dans la base du magasin: " . $e->getMessage());
        }
    }
    
    // Essayer la connexion à la base de données principale
    log_to_file("Test de connexion à la base de données principale...");
    $main_pdo = getMainDBConnection();
    
    if ($main_pdo === null) {
        log_to_file("ERREUR: Connexion à la base de données principale impossible");
    } else {
        $db_name_stmt = $main_pdo->query("SELECT DATABASE() as current_db");
        $db_result = $db_name_stmt->fetch(PDO::FETCH_ASSOC);
        log_to_file("Connecté à la base principale: " . ($db_result['current_db'] ?? 'Inconnue'));
        
        // Vérifier si la table sms_templates existe dans cette base
        try {
            $tables_stmt = $main_pdo->query("SHOW TABLES LIKE 'sms_templates'");
            $table_exists = $tables_stmt->rowCount() > 0;
            log_to_file("Table sms_templates dans la base principale: " . ($table_exists ? "Existe" : "N'existe pas"));
            
            if ($table_exists) {
                $templates_stmt = $main_pdo->query("SELECT * FROM sms_templates");
                $templates = $templates_stmt->fetchAll(PDO::FETCH_ASSOC);
                log_to_file("Nombre de templates trouvés dans la base principale: " . count($templates));
                
                echo "<h3>Templates SMS dans la base principale:</h3>";
                echo "<table border='1'>";
                echo "<tr><th>ID</th><th>Nom</th><th>Contenu</th><th>Actif</th></tr>";
                foreach ($templates as $t) {
                    echo "<tr>";
                    echo "<td>" . $t['id'] . "</td>";
                    echo "<td>" . $t['nom'] . "</td>";
                    echo "<td>" . $t['contenu'] . "</td>";
                    echo "<td>" . ($t['est_actif'] ? 'Oui' : 'Non') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Chercher spécifiquement le template ID 5
                $template_5_stmt = $main_pdo->prepare("SELECT * FROM sms_templates WHERE id = ?");
                $template_5_stmt->execute([5]);
                $template_5 = $template_5_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($template_5) {
                    log_to_file("Template ID 5 trouvé dans la base principale: " . $template_5['nom']);
                    log_to_file("Contenu: " . $template_5['contenu']);
                    log_to_file("Actif: " . ($template_5['est_actif'] ? 'Oui' : 'Non'));
                    
                    echo "<h3>Template ID 5 trouvé:</h3>";
                    echo "<pre>";
                    print_r($template_5);
                    echo "</pre>";
                } else {
                    log_to_file("Template ID 5 NON TROUVÉ dans la base principale!");
                }
            }
        } catch (PDOException $e) {
            log_to_file("ERREUR lors de la vérification de la table sms_templates dans la base principale: " . $e->getMessage());
        }
    }
    
    // Formulaire pour envoyer un SMS de test
    echo '<h3>Envoyer un SMS de test:</h3>';
    echo '<form method="post" action="">';
    echo '<div>';
    echo '<label for="telephone">Numéro de téléphone:</label><br>';
    echo '<input type="text" id="telephone" name="telephone" value="0659406676" required>';
    echo '</div><br>';
    echo '<div>';
    echo '<label for="message">Message:</label><br>';
    echo '<textarea id="message" name="message" rows="4" required>Ceci est un test d\'envoi de SMS direct depuis get_sms_templates.php</textarea>';
    echo '</div><br>';
    echo '<button type="submit" name="send_test_sms">Envoyer le SMS de test</button>';
    echo '</form>';
    
    // Traitement du formulaire
    if (isset($_POST['send_test_sms'])) {
        $telephone = $_POST['telephone'];
        $message = $_POST['message'];
        
        log_to_file("Envoi d'un SMS de test à $telephone");
        $result = send_sms_direct($telephone, $message);
        
        if ($result) {
            echo '<div style="color:green;margin-top:20px;"><strong>SMS envoyé avec succès!</strong></div>';
        } else {
            echo '<div style="color:red;margin-top:20px;"><strong>Échec de l\'envoi du SMS.</strong></div>';
        }
    }
    
} catch (Exception $e) {
    log_to_file("ERREUR CRITIQUE: " . $e->getMessage());
    echo '<div style="color:red"><strong>Erreur: </strong>' . $e->getMessage() . '</div>';
}
?>

<h3>Logs:</h3>
<pre style="max-height: 400px; overflow-y: auto; background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd;">
<?php
if (file_exists($log_file)) {
    echo htmlspecialchars(file_get_contents($log_file));
} else {
    echo "Aucun log généré pour le moment.";
}
?>
</pre> 