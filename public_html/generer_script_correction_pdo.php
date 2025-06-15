<?php
/**
 * Script de génération automatique de corrections pour tous les fichiers
 * qui utilisent encore l'ancienne variable $pdo
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Génération Script de Correction - \$pdo</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='container mt-4'>";

echo "<h1>🔧 Génération du Script de Correction Automatique</h1>";

// Patterns de remplacement
$replacements = [
    // Connexions de base
    '/global\s+\$pdo;\s*/' => "// Utilisation de getShopDBConnection() à la place de global \$pdo\n    \$shop_pdo = getShopDBConnection();\n    ",
    
    '/if\s*\(\s*!isset\(\$pdo\)\s*\|\|\s*!\s*\(\s*\$pdo\s+instanceof\s+PDO\s*\)\s*\)\s*{/' => 'if (!isset($shop_pdo) || !($shop_pdo instanceof PDO)) {',
    
    '/\$pdo->prepare\(/' => '$shop_pdo->prepare(',
    '/\$pdo->query\(/' => '$shop_pdo->query(',
    '/\$pdo->exec\(/' => '$shop_pdo->exec(',
    '/\$pdo->beginTransaction\(\)/' => '$shop_pdo->beginTransaction()',
    '/\$pdo->commit\(\)/' => '$shop_pdo->commit()',
    '/\$pdo->rollBack\(\)/' => '$shop_pdo->rollBack()',
    '/\$pdo->lastInsertId\(\)/' => '$shop_pdo->lastInsertId()',
    '/\$pdo->inTransaction\(\)/' => '$shop_pdo->inTransaction()',
    
    // Messages d'erreur
    '/Connexion à la base de données non disponible/' => 'Connexion à la base de données du magasin non disponible',
    '/Erreur de base de données/' => 'Erreur de base de données du magasin',
];

// Fichiers à exclure absolument
$exclude_files = [
    './config/database.php',
    './test_', './debug_', './analyse_', './rapport_', './validation_', './migration_', './fix_',
    './generer_', './create_superadmin.php'
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

// Fonction pour détecter si un fichier a besoin d'inclusion database.php
function needsDatabaseInclude($content) {
    // Chercher si le fichier utilise déjà getShopDBConnection ou si il y a des require database.php
    return !preg_match('/require.*database\.php|getShopDBConnection|getMainDBConnection/', $content);
}

// Fonction pour corriger un fichier
function correctFile($filepath, $replacements) {
    $content = file_get_contents($filepath);
    $original_content = $content;
    $corrections_made = 0;
    
    // Appliquer les remplacements
    foreach ($replacements as $pattern => $replacement) {
        $new_content = preg_replace($pattern, $replacement, $content);
        if ($new_content !== $content) {
            $corrections_made += substr_count($content, $pattern) - substr_count($new_content, $pattern);
            $content = $new_content;
        }
    }
    
    // Vérifier si on a besoin d'ajouter l'inclusion de database.php
    if ($corrections_made > 0 && needsDatabaseInclude($original_content)) {
        // Chercher le début du fichier PHP et ajouter l'inclusion
        if (preg_match('/^<\?php\s*\n/', $content)) {
            $content = preg_replace(
                '/^<\?php\s*\n/',
                "<?php\n// Inclure la configuration de la base de données\nrequire_once __DIR__ . '/../config/database.php';\n\n",
                $content
            );
        } elseif (preg_match('/^<\?php/', $content)) {
            $content = preg_replace(
                '/^<\?php/',
                "<?php\n// Inclure la configuration de la base de données\nrequire_once __DIR__ . '/../config/database.php';\n",
                $content
            );
        }
    }
    
    return [
        'content' => $content,
        'corrections' => $corrections_made,
        'changed' => $content !== $original_content
    ];
}

echo "<div class='alert alert-info'>";
echo "<h4>🔍 Recherche des fichiers à corriger...</h4>";
echo "</div>";

// Obtenir tous les fichiers PHP
$command = "find . -name '*.php' -type f";
$files = explode("\n", trim(shell_exec($command)));

$total_files = 0;
$corrected_files = 0;
$total_corrections = 0;
$files_to_correct = [];

echo "<div class='row'>";
echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-header bg-primary text-white'><h5>📊 Statistiques</h5></div>";
echo "<div class='card-body' id='stats'>";
echo "<p><strong>Fichiers analysés :</strong> <span id='analyzed'>0</span></p>";
echo "<p><strong>Fichiers corrigés :</strong> <span id='corrected'>0</span></p>";
echo "<p><strong>Total corrections :</strong> <span id='corrections'>0</span></p>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-8'>";
echo "<div class='card'>";
echo "<div class='card-header'><h5>🔧 Corrections en Cours</h5></div>";
echo "<div class='card-body' style='height: 400px; overflow-y: auto;' id='results'>";

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
    
    // Vérifier si c'est un usage problématique (pas juste des commentaires ou assignations légitimes)
    $content = file_get_contents($file);
    
    if (preg_match('/\$pdo->(prepare|query|exec|beginTransaction|commit|rollBack|lastInsertId|inTransaction)|global\s+\$pdo|isset\(\$pdo\)|\$pdo\s+instanceof/', $content)) {
        
        // Corriger le fichier
        $result = correctFile($file, $replacements);
        
        if ($result['changed']) {
            $files_to_correct[] = [
                'file' => $file,
                'result' => $result
            ];
            
            $corrected_files++;
            $total_corrections += $result['corrections'];
            
            echo "<div class='mb-2 p-2 border-start border-success border-3'>";
            echo "<strong>✅ {$file}</strong><br>";
            echo "<small class='text-muted'>{$result['corrections']} corrections appliquées</small>";
            echo "</div>";
            
            // Mettre à jour les stats
            echo "<script>";
            echo "document.getElementById('analyzed').textContent = '{$total_files}';";
            echo "document.getElementById('corrected').textContent = '{$corrected_files}';";
            echo "document.getElementById('corrections').textContent = '{$total_corrections}';";
            echo "</script>";
            
            if (ob_get_level()) ob_flush();
            flush();
        }
    }
}

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Afficher le résumé et permettre l'application des corrections
echo "<div class='row mt-4'>";
echo "<div class='col-12'>";

if (count($files_to_correct) > 0) {
    echo "<div class='card border-warning'>";
    echo "<div class='card-header bg-warning text-dark'>";
    echo "<h3>⚠️ Confirmation Requise</h3>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<div class='alert alert-warning'>";
    echo "<h4>{$corrected_files} fichiers nécessitent des corrections</h4>";
    echo "<p><strong>Total de corrections à appliquer :</strong> {$total_corrections}</p>";
    echo "<p><strong>ATTENTION :</strong> Cette opération va modifier {$corrected_files} fichiers de votre codebase.</p>";
    echo "</div>";
    
    echo "<h5>Fichiers qui seront modifiés :</h5>";
    echo "<div style='max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;'>";
    foreach ($files_to_correct as $item) {
        echo "<div class='mb-2'>";
        echo "<strong>{$item['file']}</strong> - {$item['result']['corrections']} corrections";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='mt-4'>";
    echo "<button class='btn btn-success btn-lg me-3' onclick='applyCorrections()'>✅ Appliquer les Corrections</button>";
    echo "<button class='btn btn-secondary btn-lg me-3' onclick='downloadBackup()'>💾 Télécharger Backup</button>";
    echo "<button class='btn btn-info btn-lg' onclick='previewChanges()'>👀 Prévisualiser</button>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
} else {
    echo "<div class='alert alert-success'>";
    echo "<h3>✅ Aucune correction nécessaire !</h3>";
    echo "<p>Tous les fichiers utilisent déjà correctement <code>getShopDBConnection()</code> ou <code>getMainDBConnection()</code>.</p>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// JavaScript pour les actions
?>

<script>
const filesToCorrect = <?php echo json_encode($files_to_correct); ?>;

function applyCorrections() {
    if (!confirm(`ATTENTION: Cette action va modifier ${filesToCorrect.length} fichiers.\n\nVoulez-vous continuer ?`)) {
        return;
    }
    
    // Créer un formulaire pour envoyer les données
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'appliquer_corrections_pdo.php';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'corrections_data';
    input.value = JSON.stringify(filesToCorrect);
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

function downloadBackup() {
    // Créer une liste des fichiers à sauvegarder
    const fileList = filesToCorrect.map(item => item.file).join('\n');
    
    // Créer un blob et télécharger
    const blob = new Blob([fileList], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = 'fichiers_a_corriger_pdo_backup.txt';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    
    alert('Liste des fichiers sauvegardée. Vous pouvez également faire un backup complet avec: tar -czf backup_avant_correction_pdo.tar.gz ' + filesToCorrect.map(item => item.file).join(' '));
}

function previewChanges() {
    window.open('previsualiser_corrections_pdo.php?data=' + encodeURIComponent(JSON.stringify(filesToCorrect)), '_blank');
}
</script>

</body>
</html> 