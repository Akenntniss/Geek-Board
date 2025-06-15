<?php
/**
 * Rapport final de migration - Phase 4
 * Analyse détaillée des problèmes restants
 */

echo "🔍 RAPPORT FINAL DE MIGRATION - PHASE 4\n";
echo str_repeat("=", 60) . "\n\n";

// Compter les différents types de problèmes
$total_files = 0;
$problematic_files = 0;
$issues_by_type = [];

function analyzeFile($filepath) {
    global $total_files, $problematic_files, $issues_by_type;
    
    $total_files++;
    $content = file_get_contents($filepath);
    $issues = [];
    
    // 1. Vérifier s'il reste des global $pdo;
    if (preg_match('/global\s+\$pdo\s*;/', $content)) {
        $issues[] = "global \$pdo";
        $issues_by_type["global \$pdo"][] = basename($filepath);
    }
    
    // 2. Vérifier s'il reste des $pdo->
    if (preg_match('/\$pdo\s*->/', $content)) {
        $issues[] = "\$pdo->";
        $issues_by_type["\$pdo->"][] = basename($filepath);
    }
    
    // 3. Connexions hardcodées
    if (preg_match('/new\s+(PDO|mysqli)\s*\(/', $content)) {
        $issues[] = "Connexion hardcodée";
        $issues_by_type["Connexion hardcodée"][] = basename($filepath);
    }
    
    // 4. Opérations DB sans connexion appropriée
    $has_shop_connection = preg_match('/getShopDBConnection\s*\(\s*\)/', $content);
    $has_main_connection = preg_match('/getMainDBConnection\s*\(\s*\)/', $content);
    $has_database_operations = preg_match('/\$\w+\s*->\s*(prepare|query|exec|beginTransaction)/', $content);
    $uses_shop_pdo = preg_match('/\$shop_pdo\s*->/', $content);
    $uses_main_pdo = preg_match('/\$main_pdo\s*->/', $content);
    $uses_this_pdo = preg_match('/\$this->pdo\s*->/', $content); // Classes qui gèrent leur propre PDO
    
    if ($has_database_operations && !$has_shop_connection && !$has_main_connection && 
        !$uses_shop_pdo && !$uses_main_pdo && !$uses_this_pdo) {
        $issues[] = "Opérations DB sans connexion appropriée";
        $issues_by_type["Opérations DB sans connexion appropriée"][] = basename($filepath);
    }
    
    if (!empty($issues)) {
        $problematic_files++;
    }
    
    return $issues;
}

function analyzeDirectory($directory) {
    if (!is_dir($directory)) return;
    
    $files = glob($directory . '/*.php');
    foreach ($files as $file) {
        analyzeFile($file);
    }
}

// Analyser tous les répertoires
analyzeDirectory('./pages');
analyzeDirectory('./ajax_handlers');
analyzeDirectory('./includes');
analyzeDirectory('./classes');

echo "📊 STATISTIQUES GLOBALES\n";
echo str_repeat("-", 30) . "\n";
echo "Total de fichiers analysés: $total_files\n";
echo "Fichiers avec problèmes: $problematic_files\n";
echo "Fichiers corrects: " . ($total_files - $problematic_files) . "\n";
$success_rate = $total_files > 0 ? round((($total_files - $problematic_files) / $total_files) * 100, 1) : 0;
echo "Taux de réussite: $success_rate%\n\n";

if (!empty($issues_by_type)) {
    echo "🚨 PROBLÈMES PAR TYPE\n";
    echo str_repeat("-", 30) . "\n";
    
    foreach ($issues_by_type as $issue_type => $files) {
        echo "\n📋 $issue_type (" . count($files) . " fichiers):\n";
        foreach ($files as $file) {
            echo "   • $file\n";
        }
    }
} else {
    echo "🎉 AUCUN PROBLÈME DÉTECTÉ !\n";
    echo "✅ MIGRATION 100% VALIDÉE\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Rapport généré le: " . date('Y-m-d H:i:s') . "\n";
?> 