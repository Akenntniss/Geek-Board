<?php
/**
 * Script d'application des corrections pour remplacer $pdo par getShopDBConnection()
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Application des Corrections - \$pdo</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>";
echo ".success { background-color: #d4edda; border-left: 4px solid #28a745; padding: 10px; margin: 5px 0; }";
echo ".error { background-color: #f8d7da; border-left: 4px solid #dc3545; padding: 10px; margin: 5px 0; }";
echo ".progress-bar-animated { animation: progress-bar-stripes 1s linear infinite; }";
echo "</style>";
echo "</head><body class='container mt-4'>";

echo "<h1>🔧 Application des Corrections - Remplacement de \$pdo</h1>";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['corrections_data'])) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Erreur</h4>";
    echo "<p>Aucune donnée de correction reçue. Veuillez retourner à l'analyse et régénérer les corrections.</p>";
    echo "<a href='analyse_complete_pdo.php' class='btn btn-primary'>Retour à l'Analyse</a>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

$corrections_data = json_decode($_POST['corrections_data'], true);

if (!is_array($corrections_data)) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Erreur</h4>";
    echo "<p>Données de correction invalides.</p>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

$total_files = count($corrections_data);
$successful_corrections = 0;
$failed_corrections = 0;
$total_changes = 0;

echo "<div class='alert alert-info'>";
echo "<h4>📊 Application en cours...</h4>";
echo "<p>Application des corrections sur {$total_files} fichiers...</p>";
echo "</div>";

// Barre de progression
echo "<div class='mb-4'>";
echo "<div class='progress' style='height: 30px;'>";
echo "<div class='progress-bar progress-bar-striped progress-bar-animated bg-success' id='progressBar' role='progressbar' style='width: 0%'>";
echo "<span id='progressText'>0%</span>";
echo "</div>";
echo "</div>";
echo "</div>";

// Statistiques en temps réel
echo "<div class='row mb-4'>";
echo "<div class='col-md-3'>";
echo "<div class='card text-center'>";
echo "<div class='card-body'>";
echo "<h5 class='card-title text-primary' id='totalFiles'>{$total_files}</h5>";
echo "<p class='card-text'>Fichiers Total</p>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='card text-center'>";
echo "<div class='card-body'>";
echo "<h5 class='card-title text-success' id='successFiles'>0</h5>";
echo "<p class='card-text'>Corrigés</p>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='card text-center'>";
echo "<div class='card-body'>";
echo "<h5 class='card-title text-danger' id='failedFiles'>0</h5>";
echo "<p class='card-text'>Échecs</p>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='card text-center'>";
echo "<div class='card-body'>";
echo "<h5 class='card-title text-info' id='totalChanges'>0</h5>";
echo "<p class='card-text'>Modifications</p>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Zone de résultats
echo "<div class='card'>";
echo "<div class='card-header'><h5>📝 Résultats de Correction</h5></div>";
echo "<div class='card-body' style='height: 400px; overflow-y: auto;' id='results'>";

// Appliquer les corrections
foreach ($corrections_data as $index => $correction_item) {
    $filepath = $correction_item['file'];
    $new_content = $correction_item['result']['content'];
    $corrections_count = $correction_item['result']['corrections'];
    
    try {
        // Vérifier que le fichier existe et est accessible en écriture
        if (!file_exists($filepath)) {
            throw new Exception("Fichier non trouvé");
        }
        
        if (!is_writable($filepath)) {
            throw new Exception("Fichier non accessible en écriture");
        }
        
        // Créer un backup avant modification
        $backup_path = $filepath . '.backup_pdo_' . date('Y-m-d_H-i-s');
        if (!copy($filepath, $backup_path)) {
            throw new Exception("Impossible de créer le backup");
        }
        
        // Appliquer la correction
        if (file_put_contents($filepath, $new_content) === false) {
            throw new Exception("Impossible d'écrire dans le fichier");
        }
        
        $successful_corrections++;
        $total_changes += $corrections_count;
        
        echo "<div class='success'>";
        echo "<strong>✅ {$filepath}</strong><br>";
        echo "<small>✓ {$corrections_count} corrections appliquées</small><br>";
        echo "<small>💾 Backup: {$backup_path}</small>";
        echo "</div>";
        
    } catch (Exception $e) {
        $failed_corrections++;
        
        echo "<div class='error'>";
        echo "<strong>❌ {$filepath}</strong><br>";
        echo "<small>Erreur: {$e->getMessage()}</small>";
        echo "</div>";
    }
    
    // Mettre à jour les statistiques et la barre de progression
    $progress = round((($index + 1) / $total_files) * 100);
    
    echo "<script>";
    echo "document.getElementById('progressBar').style.width = '{$progress}%';";
    echo "document.getElementById('progressText').textContent = '{$progress}%';";
    echo "document.getElementById('successFiles').textContent = '{$successful_corrections}';";
    echo "document.getElementById('failedFiles').textContent = '{$failed_corrections}';";
    echo "document.getElementById('totalChanges').textContent = '{$total_changes}';";
    echo "</script>";
    
    // Flush pour affichage en temps réel
    if (ob_get_level()) ob_flush();
    flush();
    
    // Petite pause pour éviter la surcharge
    usleep(100000); // 0.1 seconde
}

echo "</div>";
echo "</div>";

// Résumé final
echo "<div class='row mt-4'>";
echo "<div class='col-12'>";

if ($failed_corrections === 0) {
    echo "<div class='card border-success'>";
    echo "<div class='card-header bg-success text-white'>";
    echo "<h3>🎉 Corrections Appliquées avec Succès !</h3>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Migration Terminée !</h4>";
    echo "<ul>";
    echo "<li><strong>Fichiers corrigés :</strong> {$successful_corrections}/{$total_files}</li>";
    echo "<li><strong>Total modifications :</strong> {$total_changes}</li>";
    echo "<li><strong>Échecs :</strong> 0</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h5>📋 Actions Suivantes Recommandées :</h5>";
    echo "<ol>";
    echo "<li>Tester l'application pour vérifier que tout fonctionne</li>";
    echo "<li>Vérifier les logs pour s'assurer qu'il n'y a pas d'erreurs</li>";
    echo "<li>Effectuer une validation complète avec le script de validation</li>";
    echo "<li>Nettoyer les fichiers de backup une fois satisfait</li>";
    echo "</ol>";
    
    echo "<div class='mt-3'>";
    echo "<a href='validation_finale_pdo.php' class='btn btn-primary btn-lg me-3'>🔍 Validation Finale</a>";
    echo "<a href='index.php' class='btn btn-success btn-lg me-3'>🏠 Retour Dashboard</a>";
    echo "<button onclick='cleanBackups()' class='btn btn-warning btn-lg'>🗑️ Nettoyer Backups</button>";
    echo "</div>";
    
} else {
    echo "<div class='card border-warning'>";
    echo "<div class='card-header bg-warning text-dark'>";
    echo "<h3>⚠️ Corrections Partiellement Appliquées</h3>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<div class='alert alert-warning'>";
    echo "<h4>📊 Résumé des Corrections</h4>";
    echo "<ul>";
    echo "<li><strong>Fichiers corrigés :</strong> {$successful_corrections}/{$total_files}</li>";
    echo "<li><strong>Échecs :</strong> {$failed_corrections}</li>";
    echo "<li><strong>Total modifications :</strong> {$total_changes}</li>";
    echo "</ul>";
    echo "</div>";
    
    if ($failed_corrections > 0) {
        echo "<div class='alert alert-danger'>";
        echo "<h5>❌ Fichiers avec Échecs</h5>";
        echo "<p>Certains fichiers n'ont pas pu être corrigés. Vérifiez les permissions et l'espace disque.</p>";
        echo "<p><strong>Recommandation :</strong> Corrigez manuellement les fichiers en échec ou relancez le processus.</p>";
        echo "</div>";
    }
    
    echo "<div class='mt-3'>";
    echo "<a href='generer_script_correction_pdo.php' class='btn btn-warning btn-lg me-3'>🔄 Relancer Corrections</a>";
    echo "<a href='validation_finale_pdo.php' class='btn btn-primary btn-lg me-3'>🔍 Validation Partielle</a>";
    echo "<a href='index.php' class='btn btn-secondary btn-lg'>🏠 Retour Dashboard</a>";
    echo "</div>";
}

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Générer un rapport de correction
$report_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_files' => $total_files,
    'successful_corrections' => $successful_corrections,
    'failed_corrections' => $failed_corrections,
    'total_changes' => $total_changes,
    'corrections_applied' => $corrections_data
];

file_put_contents(
    'rapport_correction_pdo_' . date('Y-m-d_H-i-s') . '.json',
    json_encode($report_data, JSON_PRETTY_PRINT)
);

?>

<script>
function cleanBackups() {
    if (confirm('Voulez-vous supprimer tous les fichiers de backup créés ?\n\nATTENTION: Cette action est irréversible !')) {
        fetch('nettoyer_backups_pdo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clean_backups'
        })
        .then(response => response.text())
        .then(data => {
            alert('Backups nettoyés avec succès !');
        })
        .catch(error => {
            alert('Erreur lors du nettoyage: ' + error);
        });
    }
}

// Faire défiler automatiquement vers le bas pendant le traitement
setInterval(function() {
    const results = document.getElementById('results');
    if (results) {
        results.scrollTop = results.scrollHeight;
    }
}, 500);
</script>

</body>
</html> 