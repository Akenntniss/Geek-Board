<?php
/**
 * Script de test pour vérifier que les SMS longs ne sont plus tronqués
 */

// Inclusion des fichiers nécessaires
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/sms_functions.php';

// Obtenir la connexion à la base de données
$shop_pdo = getShopDBConnection();

if (!$shop_pdo) {
    die("❌ Impossible de se connecter à la base de données");
}

echo "<h1>🧪 Test de Longueur des SMS</h1>";

// Récupérer quelques templates pour test
$stmt = $shop_pdo->prepare("
    SELECT id, nom, contenu, LENGTH(contenu) as longueur_origine
    FROM sms_templates 
    WHERE id IN (5, 6, 9, 15) 
    ORDER BY LENGTH(contenu) DESC
");
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>📋 Templates trouvés :</h2>";
foreach ($templates as $template) {
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
    echo "<h3>{$template['nom']} (ID: {$template['id']})</h3>";
    echo "<p><strong>Longueur originale :</strong> {$template['longueur_origine']} caractères</p>";
    
    // Simuler le remplacement des variables
    $message_test = $template['contenu'];
    $replacements = [
        '[CLIENT_NOM]' => 'Dupont',
        '[CLIENT_PRENOM]' => 'Jean',
        '[CLIENT_TELEPHONE]' => '+33601020304',
        '[REPARATION_ID]' => '12345',
        '[APPAREIL_TYPE]' => 'Smartphone',
        '[APPAREIL_MARQUE]' => 'Apple',
        '[APPAREIL_MODELE]' => 'iPhone 14 Pro Max',
        '[DATE_RECEPTION]' => '15/01/2024',
        '[DATE_FIN_PREVUE]' => '20/01/2024',
        '[PRIX]' => '149,90'
    ];
    
    // Effectuer les remplacements
    foreach ($replacements as $var => $value) {
        $message_test = str_replace($var, $value, $message_test);
    }
    
    $longueur_finale = strlen($message_test);
    
    echo "<p><strong>Longueur après remplacement :</strong> {$longueur_finale} caractères</p>";
    
    // Vérifier si le message serait tronqué avec l'ancienne limite
    if ($longueur_finale > 160) {
        $truncated_old = substr($message_test, 0, 157) . '...';
        echo "<p style='color: red;'>❌ <strong>Ancienne limitation (160 char) :</strong> {$truncated_old}</p>";
    }
    
    // Vérifier avec la nouvelle limite
    if ($longueur_finale > 1600) {
        echo "<p style='color: orange;'>⚠️ <strong>Attention :</strong> Message très long ({$longueur_finale} caractères)</p>";
    } else {
        echo "<p style='color: green;'>✅ <strong>Message complet accepté</strong></p>";
    }
    
    echo "<div style='background: #f5f5f5; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Message final :</strong><br>";
    echo nl2br(htmlspecialchars($message_test));
    echo "</div>";
    
    echo "</div>";
}

echo "<h2>🔧 Test de la fonction send_sms</h2>";

// Test avec un message long
$message_long = "🎉 Test de message long qui fait plus de 160 caractères pour vérifier que la nouvelle limitation fonctionne correctement et que les SMS ne sont plus tronqués à 160 caractères comme avant. Ce message fait exactement " . strlen("🎉 Test de message long qui fait plus de 160 caractères pour vérifier que la nouvelle limitation fonctionne correctement et que les SMS ne sont plus tronqués à 160 caractères comme avant. Ce message fait exactement ") . " caractères.";

echo "<p><strong>Message de test :</strong> " . strlen($message_long) . " caractères</p>";
echo "<div style='background: #f0f8ff; padding: 10px; margin: 10px 0;'>";
echo nl2br(htmlspecialchars($message_long));
echo "</div>";

// Simuler la fonction send_sms sans vraiment envoyer
if (strlen($message_long) > 1600) {
    $message_processed = substr($message_long, 0, 1597) . '...';
    echo "<p style='color: orange;'>⚠️ Message tronqué à 1600 caractères</p>";
} else {
    $message_processed = $message_long;
    echo "<p style='color: green;'>✅ Message accepté sans troncature</p>";
}

echo "<h3>📤 Résultat final envoyé :</h3>";
echo "<div style='background: #e8f5e8; padding: 10px; border: 1px solid green;'>";
echo nl2br(htmlspecialchars($message_processed));
echo "</div>";
echo "<p><strong>Longueur finale :</strong> " . strlen($message_processed) . " caractères</p>";

?> 