<?php
/**
 * Script de validation automatique de la migration multi-boutique
 * Analyse tous les fichiers pour détecter d'éventuels problèmes
 */

$validation_results = [];
$total_files = 0;
$problematic_files = 0;

function analyzeFile($filepath) {
    global $validation_results, $total_files, $problematic_files;
    
    $total_files++;
    $content = file_get_contents($filepath);
    $issues = [];
    
    // Rechercher les problèmes potentiels
    
    // 1. Vérifier s'il reste des global $pdo;
    if (preg_match('/global\s+\$pdo\s*;/', $content)) {
        $issues[] = "Contient encore 'global \$pdo;'";
    }
    
    // 2. Vérifier s'il reste des $pdo->
    if (preg_match('/\$pdo\s*->/', $content)) {
        $issues[] = "Contient encore '\$pdo->'";
    }
    
    // 3. Vérifier la présence de getShopDBConnection()
    $has_shop_connection = preg_match('/getShopDBConnection\s*\(\s*\)/', $content);
    $has_main_connection = preg_match('/getMainDBConnection\s*\(\s*\)/', $content);
    $has_database_operations = preg_match('/\$\w+\s*->\s*(prepare|query|exec|beginTransaction)/', $content);
    $uses_shop_pdo = preg_match('/\$shop_pdo\s*->/', $content);
    $uses_main_pdo = preg_match('/\$main_pdo\s*->/', $content);
    
    // Ignorer les fragments de code qui utilisent déjà $shop_pdo ou $main_pdo
    if ($has_database_operations && !$has_shop_connection && !$has_main_connection && 
        !$uses_shop_pdo && !$uses_main_pdo) {
        $issues[] = "Utilise des opérations DB sans getShopDBConnection() ou getMainDBConnection()";
    }
    
    // 4. Vérifier les connexions hardcodées (patterns dangereux)
    if (preg_match('/new\s+PDO\s*\(\s*["\']mysql:host=/', $content)) {
        $issues[] = "Contient une connexion PDO hardcodée";
    }
    
    // 5. Vérifier les includes de db.php (seulement pour les fichiers autonomes)
    $is_standalone = preg_match('/session_start\s*\(\s*\)/', $content) || 
                     preg_match('/require.*config/', $content) ||
                     preg_match('/<!DOCTYPE/', $content);
    
    if ($is_standalone && $has_database_operations && 
        !preg_match('/require.*database\.php/', $content) && 
        !preg_match('/include.*database\.php/', $content)) {
        $issues[] = "Fichier autonome utilise des opérations DB sans inclure database.php";
    }
    
    if (!empty($issues)) {
        $problematic_files++;
        $validation_results[$filepath] = $issues;
    }
    
    return empty($issues);
}

function analyzeDirectory($directory) {
    $files = glob($directory . '/*.php');
    foreach ($files as $file) {
        analyzeFile($file);
    }
}

// Analyser tous les répertoires concernés par la migration
echo "🔍 Analyse des fichiers migrés...\n\n";

analyzeDirectory('./pages');
analyzeDirectory('./ajax_handlers');
analyzeDirectory('./includes');
analyzeDirectory('./classes');

?><!DOCTYPE html>
<html>
<head>
    <title>Validation Migration Multi-boutique</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .file-ok { background: #e8f5e8; padding: 10px; margin: 5px 0; border-left: 4px solid green; }
        .file-issue { background: #ffeaea; padding: 10px; margin: 5px 0; border-left: 4px solid red; }
        .summary { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>

<h1>🔍 Validation de la Migration Multi-boutique</h1>

<div class="summary">
    <h2>📊 Résumé de l'analyse</h2>
    <p><strong>Total de fichiers analysés:</strong> <?php echo $total_files; ?></p>
    <p><strong>Fichiers avec problèmes:</strong> 
        <span style="color: <?php echo $problematic_files > 0 ? 'red' : 'green'; ?>;">
            <?php echo $problematic_files; ?>
        </span>
    </p>
    <p><strong>Fichiers corrects:</strong> 
        <span style="color: green;"><?php echo $total_files - $problematic_files; ?></span>
    </p>
    <p><strong>Taux de réussite:</strong> 
        <span style="color: <?php echo $problematic_files > 0 ? 'orange' : 'green'; ?>;">
            <?php echo $total_files > 0 ? round((($total_files - $problematic_files) / $total_files) * 100, 1) : 0; ?>%
        </span>
    </p>
</div>

<?php if ($problematic_files > 0): ?>
<h2>❌ Fichiers avec problèmes détectés</h2>
<?php foreach ($validation_results as $file => $issues): ?>
<div class="file-issue">
    <strong><?php echo htmlspecialchars(basename($file)); ?></strong>
    <small>(<?php echo htmlspecialchars($file); ?>)</small>
    <ul>
        <?php foreach ($issues as $issue): ?>
        <li><?php echo htmlspecialchars($issue); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="file-ok">
    <h2>✅ Excellent ! Aucun problème détecté</h2>
    <p>Tous les fichiers analysés respectent le nouveau pattern de connexion multi-boutique.</p>
</div>
<?php endif; ?>

<h2>✅ Validation des bonnes pratiques</h2>

<div class="file-ok">
    <h3>✅ Pattern de connexion correct</h3>
    <p><strong>Recommandé:</strong></p>
    <pre style="background: #f5f5f5; padding: 10px; border-radius: 3px;">
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT * FROM table WHERE id = ?");
$stmt->execute([$id]);
    </pre>
</div>

<div class="file-ok">
    <h3>✅ Gestion des transactions</h3>
    <p><strong>Correct:</strong></p>
    <pre style="background: #f5f5f5; padding: 10px; border-radius: 3px;">
$shop_pdo = getShopDBConnection();
$shop_pdo->beginTransaction();
try {
    // opérations...
    $shop_pdo->commit();
} catch (Exception $e) {
    $shop_pdo->rollBack();
    throw $e;
}
    </pre>
</div>

<h2>🎯 État de la migration par phase</h2>

<div class="summary">
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="background: #f0f0f0;">
            <th style="padding: 10px; border: 1px solid #ddd;">Phase</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Description</th>
            <th style="padding: 10px; border: 1px solid #ddd;">État</th>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;"><strong>Phase 1</strong></td>
            <td style="padding: 10px; border: 1px solid #ddd;">Migration pages/</td>
            <td style="padding: 10px; border: 1px solid #ddd; color: green;"><strong>✅ Complète</strong></td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;"><strong>Phase 2</strong></td>
            <td style="padding: 10px; border: 1px solid #ddd;">Migration includes/ & classes/</td>
            <td style="padding: 10px; border: 1px solid #ddd; color: green;"><strong>✅ Complète</strong></td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;"><strong>Phase 3</strong></td>
            <td style="padding: 10px; border: 1px solid #ddd;">Migration ajax_handlers/</td>
            <td style="padding: 10px; border: 1px solid #ddd; color: green;"><strong>✅ Complète</strong></td>
        </tr>
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;"><strong>Phase 4</strong></td>
            <td style="padding: 10px; border: 1px solid #ddd;">Tests et validation</td>
            <td style="padding: 10px; border: 1px solid #ddd; color: blue;"><strong>🧪 En cours</strong></td>
        </tr>
    </table>
</div>

<?php if ($problematic_files == 0): ?>
<div class="file-ok" style="text-align: center; margin: 30px 0;">
    <h2>🎉 MIGRATION MULTI-BOUTIQUE VALIDÉE !</h2>
    <p>Toutes les phases sont complètes et aucun problème n'a été détecté.</p>
    <p>L'application est maintenant <strong>100% compatible multi-boutique</strong>.</p>
</div>
<?php endif; ?>

</body>
</html> 