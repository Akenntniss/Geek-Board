<?php
/**
 * Test de l'API SMS Gateway
 * Utilisez ce fichier pour vérifier que le système SMS fonctionne
 * Accès : /ajax/test_sms_api.php
 */

// Démarrer la session
session_start();

// Afficher les erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test de l'API SMS Gateway</h2>";
echo "<p><strong>URL de l'API :</strong> http://168.231.85.4:3001/api</p>";

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sms_functions.php';

echo "<h3>1. Test de connectivité API</h3>";

try {
    // Test de connectivité directe
    $smsService = new NewSmsService();
    $connectivity = $smsService->testConnection();
    
    if ($connectivity['success']) {
        echo "<p style='color: green;'>✓ API accessible (Code HTTP: {$connectivity['http_code']})</p>";
    } else {
        echo "<p style='color: red;'>✗ API non accessible: {$connectivity['message']}</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erreur de connectivité: " . $e->getMessage() . "</p>";
}

echo "<h3>2. Test des fonctions SMS</h3>";

// Test avec un numéro fictif pour ne pas envoyer de vrai SMS
$testPhone = '+33600000000';
$testMessage = 'Test automatique depuis GeekBoard - ' . date('Y-m-d H:i:s');

echo "<p><strong>Numéro de test :</strong> $testPhone</p>";
echo "<p><strong>Message de test :</strong> $testMessage</p>";

try {
    $result = send_sms($testPhone, $testMessage, 'test', 1);
    
    echo "<h4>Résultat de l'envoi :</h4>";
    echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 4px;'>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<p style='color: green;'>✓ Fonction send_sms() fonctionne correctement</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Fonction send_sms() a retourné : " . $result['message'] . "</p>";
        echo "<p><em>Note : C'est normal avec un numéro fictif</em></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erreur lors du test d'envoi : " . $e->getMessage() . "</p>";
}

echo "<h3>3. Vérification de la configuration</h3>";

// Vérifier les tables SMS
try {
    $shop_pdo = getShopDBConnection();
    
    if ($shop_pdo) {
        echo "<p style='color: green;'>✓ Connexion à la base de données OK</p>";
        
        // Vérifier la table sms_logs
        $stmt = $shop_pdo->query("SHOW TABLES LIKE 'sms_logs'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table sms_logs existe</p>";
            
            // Compter les SMS récents
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM sms_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $count = $stmt->fetch()['count'];
            echo "<p>📊 SMS envoyés dans les dernières 24h : $count</p>";
            
        } else {
            echo "<p style='color: orange;'>⚠ Table sms_logs n'existe pas encore (sera créée automatiquement)</p>";
        }
        
        // Vérifier la table sms_templates
        $stmt = $shop_pdo->query("SHOW TABLES LIKE 'sms_templates'");
        if ($stmt->rowCount() > 0) {
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM sms_templates WHERE est_actif = 1");
            $count = $stmt->fetch()['count'];
            echo "<p style='color: green;'>✓ Templates SMS actifs : $count</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Table sms_templates non trouvée</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Impossible de se connecter à la base de données</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erreur de vérification BDD : " . $e->getMessage() . "</p>";
}

echo "<h3>4. Test en temps réel</h3>";
echo "<p>Utilisez le formulaire ci-dessous pour tester avec un vrai numéro :</p>";

?>

<form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <div style="margin-bottom: 15px;">
        <label for="phone" style="display: block; margin-bottom: 5px; font-weight: bold;">Numéro de téléphone :</label>
        <input type="text" id="phone" name="phone" placeholder="+33612345678" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="message" style="display: block; margin-bottom: 5px; font-weight: bold;">Message :</label>
        <textarea id="message" name="message" rows="3" placeholder="Votre message de test..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>Test SMS depuis GeekBoard</textarea>
    </div>
    
    <button type="submit" name="send_test_sms" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
        Envoyer SMS de test
    </button>
</form>

<?php

// Traitement du formulaire
if (isset($_POST['send_test_sms'])) {
    $phone = $_POST['phone'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (!empty($phone) && !empty($message)) {
        echo "<h4>Résultat du test en temps réel :</h4>";
        
        try {
            $result = send_sms($phone, $message, 'manual_test', 1);
            
            echo "<div style='background: " . ($result['success'] ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            
            if ($result['success']) {
                echo "<h5 style='color: #155724; margin: 0 0 10px 0;'>✓ SMS envoyé avec succès !</h5>";
                echo "<p style='margin: 0;'><strong>Destinataire :</strong> $phone</p>";
                echo "<p style='margin: 0;'><strong>Message :</strong> $message</p>";
            } else {
                echo "<h5 style='color: #721c24; margin: 0 0 10px 0;'>✗ Échec de l'envoi</h5>";
                echo "<p style='margin: 0;'><strong>Erreur :</strong> " . $result['message'] . "</p>";
            }
            
            echo "</div>";
            
            // Afficher les détails techniques
            echo "<details style='margin: 10px 0;'>";
            echo "<summary style='cursor: pointer; font-weight: bold;'>Détails techniques</summary>";
            echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 4px; margin-top: 10px;'>";
            print_r($result);
            echo "</pre>";
            echo "</details>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h5>✗ Erreur lors du test</h5>";
            echo "<p>" . $e->getMessage() . "</p>";
            echo "</div>";
        }
    }
}

?>

<hr>
<h3>5. Informations sur l'API</h3>
<p>Cette API SMS utilise :</p>
<ul>
    <li><strong>URL :</strong> <a href="http://168.231.85.4/frontend/documentation.html" target="_blank">http://168.231.85.4:3001/api</a></li>
    <li><strong>Méthode :</strong> POST /messages/send</li>
    <li><strong>Format :</strong> JSON</li>
    <li><strong>Authentification :</strong> Non requise en développement</li>
    <li><strong>Documentation :</strong> <a href="http://168.231.85.4/frontend/documentation.html" target="_blank">Voir la documentation complète</a></li>
</ul>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    pre { overflow-x: auto; }
    details { background: #f8f9fa; padding: 10px; border-radius: 4px; }
    summary { padding: 10px; background: #e9ecef; margin: -10px -10px 10px -10px; border-radius: 4px 4px 0 0; }
</style> 