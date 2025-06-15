<?php
/**
 * Script de diagnostic pour identifier les SMS envoyés en triple
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

$shop_pdo = getShopDBConnection();

echo "<h1>🔍 Diagnostic SMS Envoyés en Triple</h1>";

// 1. Vérifier les logs de SMS récents
echo "<h2>📋 Logs SMS des dernières 24h</h2>";
try {
    $stmt = $shop_pdo->query("
        SELECT * FROM sms_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($logs)) {
        echo "<p>❌ Aucun log SMS trouvé (la table sms_logs n'existe peut-être pas ou est vide)</p>";
    } else {
        echo "<div style='overflow-x: auto;'>";
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Destinataire</th><th>Message (50 premiers chars)</th><th>Statut</th><th>Date</th><th>Référence</th>";
        echo "</tr>";
        
        $grouped_messages = [];
        foreach ($logs as $log) {
            $message_key = substr($log['message'], 0, 50) . '|' . $log['recipient'];
            if (!isset($grouped_messages[$message_key])) {
                $grouped_messages[$message_key] = [];
            }
            $grouped_messages[$message_key][] = $log;
            
            $color = '';
            if (count($grouped_messages[$message_key]) > 1) {
                $color = 'background: #ffcccc;'; // Rouge pour les doublons
            }
            
            echo "<tr style='$color'>";
            echo "<td>{$log['id']}</td>";
            echo "<td>{$log['recipient']}</td>";
            echo "<td>" . htmlspecialchars(substr($log['message'], 0, 50)) . "...</td>";
            echo "<td>{$log['status']}</td>";
            echo "<td>{$log['created_at']}</td>";
            echo "<td>{$log['reference_type']}:{$log['reference_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        // Analyser les doublons
        $duplicates_found = false;
        echo "<h3>🔍 Analyse des Doublons</h3>";
        foreach ($grouped_messages as $key => $messages) {
            if (count($messages) > 1) {
                $duplicates_found = true;
                echo "<div style='border: 1px solid red; padding: 10px; margin: 10px 0; background: #ffe6e6;'>";
                echo "<h4>⚠️ Message envoyé " . count($messages) . " fois</h4>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars(substr($messages[0]['message'], 0, 100)) . "...</p>";
                echo "<p><strong>Destinataire:</strong> {$messages[0]['recipient']}</p>";
                echo "<p><strong>Heures d'envoi:</strong></p>";
                echo "<ul>";
                foreach ($messages as $msg) {
                    echo "<li>{$msg['created_at']} - Statut: {$msg['status']}</li>";
                }
                echo "</ul>";
                echo "</div>";
            }
        }
        
        if (!$duplicates_found) {
            echo "<p style='color: green;'>✅ Aucun doublon détecté dans les dernières 24h</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur lors de la lecture des logs: " . $e->getMessage() . "</p>";
}

// 2. Analyser la configuration de retry dans NewSmsService
echo "<h2>⚙️ Configuration Retry NewSmsService</h2>";
try {
    require_once 'classes/NewSmsService.php';
    $smsService = new NewSmsService();
    $reflection = new ReflectionClass($smsService);
    $maxRetriesProperty = $reflection->getProperty('maxRetries');
    $maxRetriesProperty->setAccessible(true);
    $maxRetries = $maxRetriesProperty->getValue($smsService);
    
    if ($maxRetries == 3) {
        echo "<div style='border: 1px solid orange; padding: 10px; background: #fff3cd;'>";
        echo "<h4>⚠️ PROBLÈME POTENTIEL DÉTECTÉ</h4>";
        echo "<p><strong>Nombre de tentatives configuré :</strong> $maxRetries</p>";
        echo "<p>Si l'API retourne des codes d'erreur temporaires mais envoie quand même le SMS, ";
        echo "la classe NewSmsService va réessayer $maxRetries fois, causant l'envoi en triple.</p>";
        echo "</div>";
    } else {
        echo "<p style='color: green;'>✅ Nombre de tentatives corrigé: $maxRetries</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur lors de l'analyse: " . $e->getMessage() . "</p>";
}

// 2.5. Vérifier la protection contre les doublons
echo "<h2>🛡️ Protection Anti-Doublons</h2>";
try {
    require_once 'classes/SmsDeduplication.php';
    $deduplication = new SmsDeduplication();
    $stats = $deduplication->getStats();
    
    if (!empty($stats)) {
        echo "<div style='border: 1px solid green; padding: 10px; background: #f0fff0;'>";
        echo "<h4>✅ Protection Anti-Doublons Active</h4>";
        echo "<p><strong>Tentatives d'envoi (24h):</strong> {$stats['total_attempts']}</p>";
        echo "<p><strong>Téléphones uniques:</strong> {$stats['unique_phones']}</p>";
        echo "<p><strong>Messages uniques:</strong> {$stats['unique_messages']}</p>";
        if ($stats['first_attempt']) {
            echo "<p><strong>Premier envoi:</strong> {$stats['first_attempt']}</p>";
            echo "<p><strong>Dernier envoi:</strong> {$stats['last_attempt']}</p>";
        }
        echo "</div>";
    } else {
        echo "<p>📊 Aucune statistique disponible (première utilisation)</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur protection anti-doublons: " . $e->getMessage() . "</p>";
}

// 3. Vérifier les logs d'erreur récents
echo "<h2>📝 Logs d'Erreur NewSmsService</h2>";
$logFile = __DIR__ . '/logs/new_sms_' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $recentLines = array_slice($lines, -20); // 20 dernières lignes
    
    echo "<div style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd; font-family: monospace; white-space: pre-wrap;'>";
    echo htmlspecialchars(implode("\n", $recentLines));
    echo "</div>";
} else {
    echo "<p>❌ Fichier de log non trouvé: $logFile</p>";
}

// 4. Test de diagnostic en temps réel
echo "<h2>🧪 Test de Diagnostic (SIMULATION)</h2>";
echo "<div style='border: 1px solid blue; padding: 15px; background: #e6f3ff;'>";
echo "<h4>🔧 Actions Recommandées</h4>";
echo "<ol>";
echo "<li><strong>Réduire le nombre de tentatives</strong> dans NewSmsService de 3 à 1</li>";
echo "<li><strong>Ajouter des logs détaillés</strong> pour tracer chaque appel</li>";
echo "<li><strong>Vérifier les codes de retour</strong> de l'API SMS Gateway</li>";
echo "<li><strong>Implémenter une protection</strong> contre les doublons basée sur l'heure</li>";
echo "</ol>";
echo "</div>";

// 5. Proposer une correction immédiate
echo "<h2>🛠️ Correction Proposée</h2>";
echo "<div style='border: 2px solid green; padding: 15px; background: #f0fff0;'>";
echo "<h4>✅ Correction Temporaire Disponible</h4>";
echo "<p>Nous pouvons modifier temporairement le nombre de tentatives de 3 à 1 pour éviter les triples envois.</p>";
echo "<p><strong>Fichier à modifier :</strong> <code>public_html/classes/NewSmsService.php</code></p>";
echo "<p><strong>Ligne à changer :</strong> <code>private \$maxRetries = 3;</code> → <code>private \$maxRetries = 1;</code></p>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style> 