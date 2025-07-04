<?php
// Script pour tester l'envoi de SMS dans le contexte d'une réparation
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

// Récupérer l'ID de réparation depuis l'URL ou utiliser une valeur par défaut
$reparation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fichier de log spécifique pour ce test
$log_file = __DIR__ . '/logs/test_reparation_sms_' . date('Y-m-d') . '.log';

// Fonction pour logger
function log_message($message) {
    global $log_file;
    $log_entry = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $message . "<br>";
}

// Initialisation
log_message("====== DÉBUT TEST ENVOI SMS RÉPARATION ======");
log_message("Réparation ID: " . ($reparation_id > 0 ? $reparation_id : "Non spécifié (test uniquement)"));

// Si aucun ID n'est spécifié, proposer un formulaire pour le définir
if ($reparation_id == 0) {
    echo '<h2>Test d\'envoi de SMS pour une réparation</h2>';
    echo '<form method="get" action="">';
    echo '<div>';
    echo '<label for="id">ID de la réparation:</label><br>';
    echo '<input type="number" id="id" name="id" required min="1">';
    echo '</div><br>';
    echo '<button type="submit">Tester avec cette réparation</button>';
    echo '</form>';
    
    // Lister les dernières réparations pour faciliter le choix
    try {
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo) {
            $stmt = $shop_pdo->query("
                SELECT r.id, r.type_appareil, r.modele, c.nom, c.prenom, c.telephone, r.date_reception
                FROM reparations r
                JOIN clients c ON r.client_id = c.id
                ORDER BY r.id DESC
                LIMIT 10
            ");
            $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($reparations) > 0) {
                echo '<h3>Dernières réparations:</h3>';
                echo '<table border="1" style="border-collapse: collapse; width: 100%;">';
                echo '<tr><th>ID</th><th>Client</th><th>Téléphone</th><th>Appareil</th><th>Date</th><th>Action</th></tr>';
                foreach ($reparations as $r) {
                    echo '<tr>';
                    echo '<td>' . $r['id'] . '</td>';
                    echo '<td>' . $r['nom'] . ' ' . $r['prenom'] . '</td>';
                    echo '<td>' . $r['telephone'] . '</td>';
                    echo '<td>' . $r['type_appareil'] . ' ' . $r['modele'] . '</td>';
                    echo '<td>' . $r['date_reception'] . '</td>';
                    echo '<td><a href="?id=' . $r['id'] . '">Tester</a></td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
    } catch (Exception $e) {
        log_message("Erreur lors de la récupération des réparations: " . $e->getMessage());
    }
    
    // Arrêter l'exécution si aucun ID n'est spécifié
    log_message("====== FIN TEST ENVOI SMS RÉPARATION (AUCUN ID SPÉCIFIÉ) ======");
    echo '<hr>';
    echo '<h3>Logs:</h3>';
    echo '<pre style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd;">';
    if (file_exists($log_file)) {
        echo htmlspecialchars(file_get_contents($log_file));
    } else {
        echo "Aucun log généré pour le moment.";
    }
    echo '</pre>';
    exit;
}

// Si un ID est spécifié, simuler le processus d'envoi de SMS
log_message("Simulation du processus d'envoi de SMS pour la réparation ID: $reparation_id");

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception("Impossible d'obtenir la connexion à la base de données du magasin");
    }
    
    // Récupérer les informations du client et de la réparation
    $stmt_client = $shop_pdo->prepare("
        SELECT c.telephone, c.nom, c.prenom, r.type_appareil, r.modele, r.prix_reparation, r.date_reception
        FROM clients c
        JOIN reparations r ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt_client->execute([$reparation_id]);
    $info = $stmt_client->fetch(PDO::FETCH_ASSOC);
    
    if (!$info) {
        throw new Exception("Aucune information trouvée pour la réparation ID: $reparation_id");
    }
    
    log_message("Informations récupérées:");
    log_message(json_encode($info, JSON_UNESCAPED_UNICODE));
    
    // Vérifier si le client a un numéro de téléphone
    if (empty($info['telephone'])) {
        throw new Exception("Le client n'a pas de numéro de téléphone");
    }
    
    log_message("Téléphone du client: " . $info['telephone']);
    
    // Essayer de récupérer le template SMS ID 5 depuis la base du magasin
    $stmt_template = $shop_pdo->prepare("SELECT * FROM sms_templates WHERE id = ? AND est_actif = 1");
    $stmt_template->execute([5]);
    $template = $stmt_template->fetch(PDO::FETCH_ASSOC);
    
    // Si le template n'est pas trouvé dans la base du magasin, essayer la base principale
    if (!$template) {
        log_message("Template non trouvé dans la base du magasin, tentative avec la base principale");
        $main_pdo = getMainDBConnection();
        if (!$main_pdo) {
            throw new Exception("Impossible d'obtenir la connexion à la base de données principale");
        }
        
        $stmt_template = $main_pdo->prepare("SELECT * FROM sms_templates WHERE id = ? AND est_actif = 1");
        $stmt_template->execute([5]);
        $template = $stmt_template->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$template) {
        throw new Exception("Le template SMS ID 5 n'a pas été trouvé ou n'est pas actif");
    }
    
    log_message("Template trouvé: ID " . $template['id'] . " - " . $template['nom']);
    log_message("Contenu du template: " . $template['contenu']);
    
    // Formatter le numéro de téléphone
    $telephone = preg_replace('/[^0-9+]/', '', $info['telephone']);
    if (substr($telephone, 0, 1) !== '+') {
        if (substr($telephone, 0, 1) === '0') {
            $telephone = '+33' . substr($telephone, 1);
        } else if (substr($telephone, 0, 2) === '33') {
            $telephone = '+' . $telephone;
        } else {
            $telephone = '+' . $telephone;
        }
    }
    
    log_message("Numéro formaté: " . $telephone);
    
    // Préparer le message avec le template
    $message = $template['contenu'];
    
    // Préparer les remplacements
    $replacements = [
        '[CLIENT_NOM]' => isset($info['nom']) ? $info['nom'] : '',
        '[CLIENT_PRENOM]' => isset($info['prenom']) ? $info['prenom'] : '',
        '[CLIENT_TELEPHONE]' => $telephone,
        '[REPARATION_ID]' => $reparation_id,
        '[APPAREIL_TYPE]' => isset($info['type_appareil']) ? $info['type_appareil'] : '',
        '[APPAREIL_MARQUE]' => isset($info['marque']) ? $info['marque'] : '',
        '[APPAREIL_MODELE]' => isset($info['modele']) ? $info['modele'] : '',
        '[DATE_RECEPTION]' => isset($info['date_reception']) ? date('d/m/Y', strtotime($info['date_reception'])) : date('d/m/Y'),
        '[DATE_FIN_PREVUE]' => 'Non définie',
        '[PRIX]' => isset($info['prix_reparation']) ? number_format((float)$info['prix_reparation'], 2, ',', '') : '0,00'
    ];
    
    log_message("Variables de remplacement: " . json_encode($replacements, JSON_UNESCAPED_UNICODE));
    
    // Effectuer les remplacements
    foreach ($replacements as $var => $value) {
        $message = str_replace($var, $value, $message);
    }
    
    log_message("Message final: " . $message);
    
    // Afficher les données avant envoi
    echo '<h2>Test d\'envoi de SMS pour la réparation ID: ' . $reparation_id . '</h2>';
    echo '<div style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 5px;">';
    echo '<h3>Informations:</h3>';
    echo '<p><strong>Client:</strong> ' . $info['nom'] . ' ' . $info['prenom'] . '</p>';
    echo '<p><strong>Téléphone:</strong> ' . $info['telephone'] . ' (formaté: ' . $telephone . ')</p>';
    echo '<p><strong>Appareil:</strong> ' . $info['type_appareil'] . ' ' . $info['modele'] . '</p>';
    echo '<p><strong>Date de réception:</strong> ' . $info['date_reception'] . '</p>';
    echo '</div>';
    
    echo '<div style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 5px;">';
    echo '<h3>Template SMS:</h3>';
    echo '<p><strong>ID:</strong> ' . $template['id'] . '</p>';
    echo '<p><strong>Nom:</strong> ' . $template['nom'] . '</p>';
    echo '<p><strong>Contenu brut:</strong></p>';
    echo '<pre style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($template['contenu']) . '</pre>';
    echo '</div>';
    
    echo '<div style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 5px;">';
    echo '<h3>Message à envoyer:</h3>';
    echo '<pre style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($message) . '</pre>';
    echo '</div>';
    
    // Formulaire pour envoyer le SMS
    echo '<form method="post" action="">';
    echo '<input type="hidden" name="telephone" value="' . htmlspecialchars($telephone) . '">';
    echo '<input type="hidden" name="message" value="' . htmlspecialchars($message) . '">';
    echo '<button type="submit" name="send_sms" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">Envoyer le SMS maintenant</button>';
    echo '</form>';
    
    // Traitement de l'envoi du SMS
    if (isset($_POST['send_sms'])) {
        log_message("Tentative d'envoi du SMS...");
        
        // Configuration de l'API SMS Gateway - votre API personnalisée
        $API_URL = 'http://168.231.85.4:3001/api/messages/send';
        
        // Préparation des données JSON pour l'API
        $sms_data = json_encode([
            'recipient' => $_POST['telephone'],
            'message' => $_POST['message'],
            'priority' => 'normal'
        ]);
        
        log_message("Données JSON pour l'API: " . $sms_data);
        
        // Envoi du SMS via l'API SMS Gateway
        $curl = curl_init($API_URL);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $sms_data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
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
        log_message("Temps d'exécution: $duration ms");
        
        if ($response === false) {
            $error = curl_error($curl);
            log_message("Erreur cURL: " . $error);
            echo '<div style="margin-top: 20px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">';
            echo '<h3>Erreur lors de l\'envoi du SMS:</h3>';
            echo '<p>' . $error . '</p>';
            echo '</div>';
        } else {
            log_message("Réponse: $response");
            echo '<div style="margin-top: 20px; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;">';
            echo '<h3>SMS envoyé avec succès!</h3>';
            echo '<p><strong>Code de statut:</strong> ' . $status . '</p>';
            echo '<p><strong>Réponse:</strong> ' . htmlspecialchars($response) . '</p>';
            echo '</div>';
        }
        
        curl_close($curl);
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    log_message("ERREUR: " . $error_message);
    echo '<div style="margin-top: 20px; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">';
    echo '<h3>Erreur:</h3>';
    echo '<p>' . $error_message . '</p>';
    echo '</div>';
}

log_message("====== FIN TEST ENVOI SMS RÉPARATION ======");

// Afficher les logs
echo '<hr>';
echo '<h3>Logs:</h3>';
echo '<pre style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;">';
if (file_exists($log_file)) {
    echo htmlspecialchars(file_get_contents($log_file));
} else {
    echo "Aucun log généré pour le moment.";
}
echo '</pre>';
?> 