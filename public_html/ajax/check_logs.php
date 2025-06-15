<?php
// Script pour afficher les logs d'erreur PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>📋 Vérification des Logs</h2>";

$log_files = [
    '../logs/php-errors.log',
    '../logs/ajax_debug.log',
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log',
    ini_get('error_log')
];

foreach ($log_files as $log_file) {
    if (empty($log_file)) continue;
    
    echo "<h3>📄 Log: $log_file</h3>";
    
    if (file_exists($log_file) && is_readable($log_file)) {
        $content = file_get_contents($log_file);
        if (!empty($content)) {
            // Afficher seulement les 50 dernières lignes
            $lines = explode("\n", $content);
            $recent_lines = array_slice($lines, -50);
            
            echo "<div style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;'>";
            foreach ($recent_lines as $line) {
                if (!empty(trim($line))) {
                    // Colorer les lignes selon le type d'erreur
                    $color = '#000';
                    if (strpos($line, 'ERROR') !== false || strpos($line, 'Fatal') !== false) {
                        $color = '#dc3545';
                    } elseif (strpos($line, 'WARNING') !== false || strpos($line, 'Warning') !== false) {
                        $color = '#fd7e14';
                    } elseif (strpos($line, 'update_commande_status') !== false) {
                        $color = '#007bff';
                    }
                    
                    echo "<div style='color: $color; margin: 2px 0;'>" . htmlspecialchars($line) . "</div>";
                }
            }
            echo "</div>";
        } else {
            echo "<p style='color: #6c757d;'>📝 Fichier vide</p>";
        }
    } else {
        echo "<p style='color: #6c757d;'>❌ Fichier non accessible ou inexistant</p>";
    }
}

// Afficher la configuration PHP pertinente
echo "<hr>";
echo "<h3>⚙️ Configuration PHP</h3>";
echo "<div style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6;'>";
echo "<strong>Log des erreurs:</strong> " . (ini_get('log_errors') ? 'Activé' : 'Désactivé') . "<br>";
echo "<strong>Affichage des erreurs:</strong> " . (ini_get('display_errors') ? 'Activé' : 'Désactivé') . "<br>";
echo "<strong>Fichier de log:</strong> " . (ini_get('error_log') ?: 'Non défini') . "<br>";
echo "<strong>Niveau d'erreur:</strong> " . error_reporting() . "<br>";
echo "<strong>Version PHP:</strong> " . PHP_VERSION . "<br>";
echo "</div>";

// Bouton pour vider les logs
echo "<hr>";
echo "<h3>🧹 Actions</h3>";
echo "<button onclick='clearLogs()' style='background: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;'>Vider les logs</button>";
echo "<button onclick='location.reload()' style='background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; margin-left: 10px;'>Actualiser</button>";

?>

<script>
function clearLogs() {
    if (confirm('Êtes-vous sûr de vouloir vider tous les logs ?')) {
        fetch('clear_logs.php', { method: 'POST' })
        .then(response => response.text())
        .then(data => {
            alert('Logs vidés !');
            location.reload();
        })
        .catch(error => {
            alert('Erreur lors du vidage des logs: ' + error);
        });
    }
}

// Auto-refresh toutes les 10 secondes
setTimeout(() => {
    location.reload();
}, 10000);
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #495057; }
</style> 