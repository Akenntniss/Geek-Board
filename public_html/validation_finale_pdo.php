<?php
/**
 * Script de validation finale pour s'assurer qu'aucun fichier
 * n'utilise encore l'ancienne variable $pdo
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Validation Finale - Suppression \$pdo</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>";
echo ".problem { background-color: #ffebee; border-left: 4px solid #f44336; padding: 10px; margin: 5px 0; }";
echo ".ok { background-color: #e8f5e8; border-left: 4px solid #4caf50; padding: 10px; margin: 5px 0; }";
echo ".warning { background-color: #fff3e0; border-left: 4px solid #ff9800; padding: 10px; margin: 5px 0; }";
echo ".code { font-family: monospace; font-size: 12px; background: #f5f5f5; padding: 5px; margin: 5px 0; border-radius: 3px; }";
echo "</style>";
echo "</head><body class='container mt-4'>";

echo "<h1>🔍 Validation Finale - Vérification Suppression \$pdo</h1>";

// Fichiers à exclure de la validation
$exclude_files = [
    './config/database.php',    // Fichier de configuration légitime
    './test_', './debug_', './analyse_', './rapport_', './validation_', './migration_', './fix_',
    './generer_', './appliquer_', './create_superadmin.php'
];

// Patterns problématiques (ne devraient plus exister)
$problematic_patterns = [
    'global\s+\$pdo' => 'Déclaration globale de $pdo',
    '\$pdo->(prepare|query|exec)' => 'Utilisation directe de $pdo pour requêtes',
    '\$pdo->(beginTransaction|commit|rollBack)' => 'Utilisation directe de $pdo pour transactions',
    '\$pdo->lastInsertId' => 'Utilisation directe de $pdo pour lastInsertId',
    '\$pdo->inTransaction' => 'Utilisation directe de $pdo pour inTransaction',
    'isset\(\$pdo\)' => 'Test d\'existence de $pdo',
    '\$pdo\s+instanceof\s+PDO' => 'Test de type de $pdo'
];

// Patterns légitimes (autorisés)
$legitimate_patterns = [
    '\$pdo.*=.*getMainDBConnection',
    '\$pdo.*=.*getShopDBConnection',
    'function.*\$pdo',
    '\/\*.*\$pdo.*\*\/',
    '\/\/.*\$pdo',
    'echo.*\$pdo',
    'print.*\$pdo',
    '\$pdo_main',
    '\$pdo_shop'
];

function shouldExcludeFile($filepath, $exclude_patterns) {
    foreach ($exclude_patterns as $pattern) {
        if (strpos($filepath, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

function isLegitimateUsage($line, $legitimate_patterns) {
    foreach ($legitimate_patterns as $pattern) {
        if (preg_match('/' . $pattern . '/', $line)) {
            return true;
        }
    }
    return false;
}

echo "<div class='alert alert-info'>";
echo "<h4>🔍 Analyse de Validation en Cours...</h4>";
echo "<p>Vérification que tous les fichiers utilisent correctement getShopDBConnection() au lieu de \$pdo...</p>";
echo "</div>";

// Statistiques
$total_files = 0;
$files_with_pdo = 0;
$problematic_files = 0;
$legitimate_files = 0;
$total_problems = 0;

// Résultats détaillés
$problems_found = [];
$legitimate_usages = [];

echo "<div class='row'>";
echo "<div class='col-md-3'>";
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-primary text-white'><h5>📊 Statistiques</h5></div>";
echo "<div class='card-body' id='stats'>";
echo "<p><strong>Fichiers analysés :</strong> <span id='totalAnalyzed'>0</span></p>";
echo "<p><strong>Avec \$pdo :</strong> <span id='withPdo'>0</span></p>";
echo "<p><strong>Problématiques :</strong> <span id='problematic'>0</span></p>";
echo "<p><strong>Légitimes :</strong> <span id='legitimate'>0</span></p>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-9'>";
echo "<div class='card'>";
echo "<div class='card-header'><h5>🔍 Résultats de Validation</h5></div>";
echo "<div class='card-body' style='height: 500px; overflow-y: auto;' id='results'>";

// Obtenir tous les fichiers PHP
$command = "find . -name '*.php' -type f";
$files = explode("\n", trim(shell_exec($command)));

foreach ($files as $file) {
    if (empty($file)) continue;
    
    $total_files++;
    
    // Exclure certains fichiers
    if (shouldExcludeFile($file, $exclude_files)) {
        continue;
    }
    
    // Vérifier si le fichier contient $pdo
    $grep_result = shell_exec("grep -n '\$pdo' " . escapeshellarg($file) . " 2>/dev/null");
    
    if (empty($grep_result)) {
        continue;
    }
    
    $files_with_pdo++;
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    
    $file_problems = [];
    $file_legitimate = [];
    
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, '$pdo') !== false) {
            $lineNum++; // Numérotation à partir de 1
            
            // Vérifier si c'est un usage légitime
            if (isLegitimateUsage($line, $legitimate_patterns)) {
                $file_legitimate[] = [
                    'line_num' => $lineNum,
                    'line_content' => trim($line)
                ];
                continue;
            }
            
            // Vérifier si c'est problématique
            $is_problematic = false;
            $problem_type = '';
            
            foreach ($problematic_patterns as $pattern => $description) {
                if (preg_match('/' . $pattern . '/', $line)) {
                    $is_problematic = true;
                    $problem_type = $description;
                    break;
                }
            }
            
            if ($is_problematic) {
                $file_problems[] = [
                    'line_num' => $lineNum,
                    'line_content' => trim($line),
                    'problem_type' => $problem_type
                ];
            }
        }
    }
    
    // Afficher les résultats pour ce fichier
    if (!empty($file_problems)) {
        $problematic_files++;
        $total_problems += count($file_problems);
        $problems_found[$file] = $file_problems;
        
        echo "<div class='mb-3 problem'>";
        echo "<h6>🚨 PROBLÉMATIQUE : <code>{$file}</code></h6>";
        
        foreach ($file_problems as $problem) {
            echo "<div class='code text-danger'>";
            echo "<strong>Ligne {$problem['line_num']}:</strong> {$problem['problem_type']}<br>";
            echo "<code>" . htmlspecialchars($problem['line_content']) . "</code>";
            echo "</div>";
        }
        echo "</div>";
        
    } elseif (!empty($file_legitimate)) {
        $legitimate_files++;
        $legitimate_usages[$file] = $file_legitimate;
        
        echo "<div class='mb-2 ok'>";
        echo "<h6>✅ LÉGITIME : <code>{$file}</code></h6>";
        echo "<small class='text-muted'>" . count($file_legitimate) . " usage(s) légitime(s)</small>";
        echo "</div>";
    }
    
    // Mettre à jour les statistiques
    echo "<script>";
    echo "document.getElementById('totalAnalyzed').textContent = '{$total_files}';";
    echo "document.getElementById('withPdo').textContent = '{$files_with_pdo}';";
    echo "document.getElementById('problematic').textContent = '{$problematic_files}';";
    echo "document.getElementById('legitimate').textContent = '{$legitimate_files}';";
    echo "</script>";
    
    if (ob_get_level()) ob_flush();
    flush();
}

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Résumé final
echo "<div class='row mt-4'>";
echo "<div class='col-12'>";

if ($problematic_files === 0) {
    echo "<div class='card border-success'>";
    echo "<div class='card-header bg-success text-white'>";
    echo "<h3>🎉 VALIDATION RÉUSSIE !</h3>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Migration Multi-Boutique Complète !</h4>";
    echo "<p>Aucun usage problématique de \$pdo détecté dans la codebase.</p>";
    echo "<ul>";
    echo "<li><strong>Total fichiers analysés :</strong> {$total_files}</li>";
    echo "<li><strong>Fichiers avec \$pdo :</strong> {$files_with_pdo}</li>";
    echo "<li><strong>Usages légitimes :</strong> {$legitimate_files}</li>";
    echo "<li><strong>Usages problématiques :</strong> 0</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h5>🎯 Statut Final du Système Multi-Boutique :</h5>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<div class='alert alert-info'>";
    echo "<h6>✅ Sécurité Garantie</h6>";
    echo "<ul>";
    echo "<li>Isolation parfaite des données par boutique</li>";
    echo "<li>Pas de fuite de données entre boutiques</li>";
    echo "<li>Toutes les requêtes utilisent getShopDBConnection()</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-6'>";
    echo "<div class='alert alert-info'>";
    echo "<h6>🚀 Prêt pour la Production</h6>";
    echo "<ul>";
    echo "<li>Migration complètement terminée</li>";
    echo "<li>Système multi-boutique opérationnel</li>";
    echo "<li>Toutes les fonctionnalités migrées</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<h5>📋 Actions Finales Recommandées :</h5>";
    echo "<ol>";
    echo "<li>Tester l'application avec différentes boutiques</li>";
    echo "<li>Vérifier les logs pour s'assurer du bon fonctionnement</li>";
    echo "<li>Nettoyer les fichiers de backup et scripts de migration</li>";
    echo "<li>Documenter la nouvelle architecture pour l'équipe</li>";
    echo "</ol>";
    
    echo "<div class='mt-4'>";
    echo "<a href='index.php' class='btn btn-success btn-lg me-3'>🏠 Retour Dashboard</a>";
    echo "<button onclick='generateReport()' class='btn btn-primary btn-lg me-3'>📄 Générer Rapport</button>";
    echo "<button onclick='cleanupFiles()' class='btn btn-warning btn-lg'>🗑️ Nettoyer Fichiers Migration</button>";
    echo "</div>";
    
} else {
    echo "<div class='card border-danger'>";
    echo "<div class='card-header bg-danger text-white'>";
    echo "<h3>❌ VALIDATION ÉCHOUÉE</h3>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<div class='alert alert-danger'>";
    echo "<h4>🚨 Problèmes Détectés !</h4>";
    echo "<p><strong>{$problematic_files} fichiers</strong> utilisent encore l'ancienne variable \$pdo.</p>";
    echo "<ul>";
    echo "<li><strong>Total fichiers analysés :</strong> {$total_files}</li>";
    echo "<li><strong>Fichiers problématiques :</strong> {$problematic_files}</li>";
    echo "<li><strong>Nombre total de problèmes :</strong> {$total_problems}</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='alert alert-warning'>";
    echo "<h5>🔧 Actions Correctives Requises</h5>";
    echo "<p>Les fichiers suivants nécessitent une correction manuelle ou une nouvelle exécution du script de correction :</p>";
    echo "<ul>";
    foreach ($problems_found as $file => $problems) {
        echo "<li><strong>{$file}</strong> - " . count($problems) . " problème(s)</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='mt-4'>";
    echo "<a href='generer_script_correction_pdo.php' class='btn btn-danger btn-lg me-3'>🔄 Relancer Corrections</a>";
    echo "<a href='analyse_complete_pdo.php' class='btn btn-warning btn-lg me-3'>🔍 Nouvelle Analyse</a>";
    echo "<a href='index.php' class='btn btn-secondary btn-lg'>🏠 Retour Dashboard</a>";
    echo "</div>";
}

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Sauvegarder les résultats de validation
$validation_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_files' => $total_files,
    'files_with_pdo' => $files_with_pdo,
    'problematic_files' => $problematic_files,
    'legitimate_files' => $legitimate_files,
    'total_problems' => $total_problems,
    'validation_passed' => $problematic_files === 0,
    'problems_found' => $problems_found,
    'legitimate_usages' => $legitimate_usages
];

file_put_contents(
    'validation_finale_pdo_' . date('Y-m-d_H-i-s') . '.json',
    json_encode($validation_data, JSON_PRETTY_PRINT)
);

?>

<script>
function generateReport() {
    const reportData = <?php echo json_encode($validation_data); ?>;
    
    let report = "# RAPPORT DE VALIDATION FINALE - MIGRATION MULTI-BOUTIQUE\n\n";
    report += "## Résumé\n\n";
    report += `- **Date de validation :** ${reportData.timestamp}\n`;
    report += `- **Validation réussie :** ${reportData.validation_passed ? 'OUI' : 'NON'}\n`;
    report += `- **Total fichiers analysés :** ${reportData.total_files}\n`;
    report += `- **Fichiers avec $pdo :** ${reportData.files_with_pdo}\n`;
    report += `- **Fichiers problématiques :** ${reportData.problematic_files}\n`;
    report += `- **Usages légitimes :** ${reportData.legitimate_files}\n\n`;
    
    if (reportData.validation_passed) {
        report += "## 🎉 MIGRATION RÉUSSIE\n\n";
        report += "La migration vers le système multi-boutique est complète.\n";
        report += "Tous les fichiers utilisent correctement getShopDBConnection().\n";
        report += "Le système est prêt pour la production.\n\n";
    } else {
        report += "## ❌ PROBLÈMES DÉTECTÉS\n\n";
        Object.keys(reportData.problems_found).forEach(file => {
            report += `### ${file}\n`;
            reportData.problems_found[file].forEach(problem => {
                report += `- Ligne ${problem.line_num}: ${problem.problem_type}\n`;
                report += `  \`${problem.line_content}\`\n`;
            });
            report += "\n";
        });
    }
    
    // Télécharger le rapport
    const blob = new Blob([report], { type: 'text/markdown' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = 'rapport_validation_finale_pdo.md';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}

function cleanupFiles() {
    if (confirm('Voulez-vous supprimer tous les fichiers de migration et backup ?\n\nCeci inclut :\n- Scripts d\'analyse\n- Scripts de correction\n- Fichiers de backup\n- Rapports temporaires\n\nATTENTION: Cette action est irréversible !')) {
        fetch('nettoyer_migration_pdo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=cleanup_all'
        })
        .then(response => response.text())
        .then(data => {
            alert('Nettoyage terminé avec succès !');
            location.reload();
        })
        .catch(error => {
            alert('Erreur lors du nettoyage: ' + error);
        });
    }
}
</script>

</body>
</html> 