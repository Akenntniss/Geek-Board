<?php
/**
 * Analyse complète de tous les usages de $pdo dans la codebase
 * Différencie les usages légitimes des problématiques
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Analyse Complète - Usage de \$pdo</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>";
echo ".problem { background-color: #ffebee; border-left: 4px solid #f44336; }";
echo ".ok { background-color: #e8f5e8; border-left: 4px solid #4caf50; }";
echo ".warning { background-color: #fff3e0; border-left: 4px solid #ff9800; }";
echo ".code { font-family: monospace; font-size: 12px; background: #f5f5f5; padding: 5px; margin: 5px 0; }";
echo "</style>";
echo "</head><body class='container-fluid mt-4'>";

echo "<h1>🔍 Analyse Complète - Usage de \$pdo dans la Codebase</h1>";

// Catégories d'analyse
$categories = [
    'legitimate' => [], // Usages légitimes (config, fonctions, etc.)
    'problematic' => [], // Usages problématiques (utilisation directe)
    'suspicious' => [], // Usages suspects à vérifier
    'exclusions' => []  // Fichiers à exclure de l'analyse
];

// Patterns légitimes (ne pas corriger)
$legitimate_patterns = [
    '/\$pdo.*=.*getMainDBConnection/',  // Assignation correcte main DB
    '/\$pdo.*=.*getShopDBConnection/', // Assignation correcte shop DB
    '/function.*\$pdo/',               // Paramètre de fonction
    '/\/\*.*\$pdo.*\*\//',            // Commentaires
    '/\/\/.*\$pdo/',                  // Commentaires ligne
    '/echo.*\$pdo/',                  // Echo pour debug/test
    '/print.*\$pdo/',                 // Print pour debug/test
    '/\$pdo_main/',                   // Variable main DB (légale)
    '/\$pdo_shop/',                   // Variable shop DB (légale)
];

// Patterns problématiques (à corriger)
$problematic_patterns = [
    '/global\s+\$pdo/',               // global $pdo
    '/\$pdo->(prepare|query|exec)/',  // Utilisation directe
    '/\$pdo->(beginTransaction|commit|rollBack)/', // Transactions
    '/isset\(\$pdo\)/',              // Tests d'existence
    '/\$pdo\s*instanceof/',          // Tests de type
];

// Fichiers à exclure de l'analyse
$exclude_files = [
    './config/database.php',          // Fichier de configuration légitime
    './test_',                        // Fichiers de test
    './debug_',                       // Fichiers de debug
    './analyse_',                     // Nos fichiers d'analyse
    './rapport_',                     // Nos rapports
    './validation_',                  // Nos validations
    './migration_',                   // Nos scripts de migration
    './fix_',                         // Nos scripts de correction
];

// Fonction pour vérifier si un fichier doit être exclu
function shouldExcludeFile($filepath, $exclude_patterns) {
    foreach ($exclude_patterns as $pattern) {
        if (strpos($filepath, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

// Fonction pour analyser le contenu d'un fichier
function analyzeFileContent($filepath, $legitimate_patterns, $problematic_patterns) {
    $content = file_get_contents($filepath);
    $lines = explode("\n", $content);
    $results = [];
    
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, '$pdo') !== false) {
            $lineNum++; // Numérotation à partir de 1
            
            // Vérifier si c'est légitime
            $is_legitimate = false;
            foreach ($legitimate_patterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $is_legitimate = true;
                    break;
                }
            }
            
            // Vérifier si c'est problématique
            $is_problematic = false;
            foreach ($problematic_patterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $is_problematic = true;
                    break;
                }
            }
            
            $results[] = [
                'line_num' => $lineNum,
                'line_content' => trim($line),
                'is_legitimate' => $is_legitimate,
                'is_problematic' => $is_problematic
            ];
        }
    }
    
    return $results;
}

echo "<div class='row'>";
echo "<div class='col-12'>";
echo "<div class='alert alert-info'>";
echo "<h4>📊 Analyse en cours...</h4>";
echo "<p>Scan de tous les fichiers PHP pour détecter les usages de \$pdo...</p>";
echo "</div>";
echo "</div>";
echo "</div>";

// Obtenir tous les fichiers PHP
$command = "find . -name '*.php' -type f";
$files = explode("\n", trim(shell_exec($command)));

$total_files = count($files);
$processed_files = 0;
$problem_count = 0;
$ok_count = 0;
$suspicious_count = 0;

echo "<div class='row'>";
echo "<div class='col-md-3'>";
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-primary text-white'><h5>📈 Statistiques</h5></div>";
echo "<div class='card-body' id='stats'>";
echo "<p><strong>Total fichiers :</strong> <span id='total'>{$total_files}</span></p>";
echo "<p><strong>Traités :</strong> <span id='processed'>0</span></p>";
echo "<p><strong>Problématiques :</strong> <span id='problems'>0</span></p>";
echo "<p><strong>Suspects :</strong> <span id='suspicious'>0</span></p>";
echo "<p><strong>OK :</strong> <span id='ok'>0</span></p>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-9'>";
echo "<div class='card'>";
echo "<div class='card-header'><h5>🔍 Résultats d'Analyse</h5></div>";
echo "<div class='card-body' style='height: 600px; overflow-y: auto;' id='results'>";

foreach ($files as $file) {
    if (empty($file)) continue;
    
    $processed_files++;
    
    // Vérifier si le fichier doit être exclu
    if (shouldExcludeFile($file, $exclude_files)) {
        $categories['exclusions'][] = $file;
        continue;
    }
    
    // Vérifier si le fichier contient $pdo
    $grep_result = shell_exec("grep -n '\$pdo' " . escapeshellarg($file) . " 2>/dev/null");
    
    if (empty($grep_result)) {
        $ok_count++;
        continue;
    }
    
    // Analyser le contenu du fichier
    $analysis = analyzeFileContent($file, $legitimate_patterns, $problematic_patterns);
    
    if (empty($analysis)) {
        $ok_count++;
        continue;
    }
    
    // Déterminer le statut du fichier
    $has_problems = false;
    $has_legitimate = false;
    
    foreach ($analysis as $result) {
        if ($result['is_problematic']) {
            $has_problems = true;
        }
        if ($result['is_legitimate']) {
            $has_legitimate = true;
        }
    }
    
    if ($has_problems) {
        $problem_count++;
        $categories['problematic'][] = [
            'file' => $file,
            'analysis' => $analysis
        ];
        
        echo "<div class='mb-3 p-3 problem'>";
        echo "<h6>🚨 PROBLÉMATIQUE : <code>{$file}</code></h6>";
        
        foreach ($analysis as $result) {
            if ($result['is_problematic']) {
                echo "<div class='code text-danger'>";
                echo "Ligne {$result['line_num']}: {$result['line_content']}";
                echo "</div>";
            }
        }
        echo "</div>";
        
    } elseif ($has_legitimate) {
        $ok_count++;
        $categories['legitimate'][] = [
            'file' => $file,
            'analysis' => $analysis
        ];
        
    } else {
        $suspicious_count++;
        $categories['suspicious'][] = [
            'file' => $file,
            'analysis' => $analysis
        ];
        
        echo "<div class='mb-3 p-3 warning'>";
        echo "<h6>⚠️ SUSPECT : <code>{$file}</code></h6>";
        
        foreach ($analysis as $result) {
            echo "<div class='code text-warning'>";
            echo "Ligne {$result['line_num']}: {$result['line_content']}";
            echo "</div>";
        }
        echo "</div>";
    }
    
    // Mettre à jour les statistiques en temps réel
    echo "<script>";
    echo "document.getElementById('processed').textContent = '{$processed_files}';";
    echo "document.getElementById('problems').textContent = '{$problem_count}';";
    echo "document.getElementById('suspicious').textContent = '{$suspicious_count}';";
    echo "document.getElementById('ok').textContent = '{$ok_count}';";
    echo "</script>";
    
    // Flush pour affichage en temps réel
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
echo "<div class='card border-danger'>";
echo "<div class='card-header bg-danger text-white'>";
echo "<h3>🚨 RÉSUMÉ - Fichiers Nécessitant une Correction</h3>";
echo "</div>";
echo "<div class='card-body'>";

if ($problem_count > 0) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>{$problem_count} fichiers problématiques détectés !</h4>";
    echo "<p>Ces fichiers utilisent encore l'ancienne variable \$pdo et doivent être corrigés :</p>";
    echo "<ul>";
    
    foreach ($categories['problematic'] as $item) {
        echo "<li><strong>{$item['file']}</strong>";
        echo "<ul>";
        foreach ($item['analysis'] as $result) {
            if ($result['is_problematic']) {
                echo "<li>Ligne {$result['line_num']}: <code>" . htmlspecialchars($result['line_content']) . "</code></li>";
            }
        }
        echo "</ul>";
        echo "</li>";
    }
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ Aucun fichier problématique détecté !</h4>";
    echo "<p>Tous les fichiers utilisent correctement getShopDBConnection() ou getMainDBConnection().</p>";
    echo "</div>";
}

if ($suspicious_count > 0) {
    echo "<div class='alert alert-warning'>";
    echo "<h4>⚠️ {$suspicious_count} fichiers suspects à vérifier</h4>";
    echo "<p>Ces fichiers contiennent \$pdo mais n'ont pas été automatiquement classifiés :</p>";
    echo "<ul>";
    
    foreach ($categories['suspicious'] as $item) {
        echo "<li><strong>{$item['file']}</strong>";
        echo "<ul>";
        foreach ($item['analysis'] as $result) {
            echo "<li>Ligne {$result['line_num']}: <code>" . htmlspecialchars($result['line_content']) . "</code></li>";
        }
        echo "</ul>";
        echo "</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<div class='alert alert-info'>";
echo "<h4>📊 Statistiques Finales</h4>";
echo "<ul>";
echo "<li><strong>Total fichiers analysés :</strong> {$processed_files}</li>";
echo "<li><strong>Fichiers problématiques :</strong> {$problem_count}</li>";
echo "<li><strong>Fichiers suspects :</strong> {$suspicious_count}</li>";
echo "<li><strong>Fichiers OK :</strong> {$ok_count}</li>";
echo "<li><strong>Fichiers exclus :</strong> " . count($categories['exclusions']) . "</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Bouton pour générer le script de correction automatique
if ($problem_count > 0) {
    echo "<div class='row mt-4'>";
    echo "<div class='col-12'>";
    echo "<div class='alert alert-warning'>";
    echo "<h4>🔧 Action Recommandée</h4>";
    echo "<p>Un script de correction automatique va être généré pour corriger tous les fichiers problématiques.</p>";
    echo "<button class='btn btn-danger btn-lg' onclick='generateFixScript()'>Générer Script de Correction</button>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

?>

<script>
function generateFixScript() {
    if (confirm('Voulez-vous générer un script pour corriger automatiquement tous les fichiers problématiques ?')) {
        window.location.href = 'generer_script_correction_pdo.php';
    }
}

// Auto-scroll vers le bas
setInterval(function() {
    const results = document.getElementById('results');
    if (results) {
        results.scrollTop = results.scrollHeight;
    }
}, 1000);
</script>

</body>
</html> 