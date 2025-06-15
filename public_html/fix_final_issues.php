<?php
/**
 * Script final pour corriger les derniers problèmes de migration Phase 4
 */

function fixFinalIssues($filepath) {
    $content = file_get_contents($filepath);
    $original_content = $content;
    $changes = 0;
    
    // 1. Détecter et corriger les connexions hardcodées restantes
    $hardcoded_patterns = [
        '/\$\w+\s*=\s*new\s+PDO\s*\([^)]+\)\s*;/i',
        '/\$\w+\s*=\s*new\s+mysqli\s*\([^)]+\)\s*;/i'
    ];
    
    foreach ($hardcoded_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, '$shop_pdo = getShopDBConnection();', $content);
            $changes++;
            
            // S'assurer que database.php est inclus
            if (!preg_match('/require.*database\.php/', $content) && !preg_match('/include.*database\.php/', $content)) {
                $content = preg_replace('/(<\?php\s*\n?)/', '$1require_once __DIR__ . \'/../config/database.php\';\n', $content, 1);
                $changes++;
            }
        }
    }
    
    // 2. Corriger les fichiers avec opérations DB mais sans connexion appropriée
    $has_database_operations = preg_match('/\$\w+\s*->\s*(prepare|query|exec|beginTransaction)/', $content);
    $has_shop_connection = preg_match('/getShopDBConnection\s*\(\s*\)/', $content);
    $has_main_connection = preg_match('/getMainDBConnection\s*\(\s*\)/', $content);
    $uses_shop_pdo = preg_match('/\$shop_pdo\s*->/', $content);
    $uses_main_pdo = preg_match('/\$main_pdo\s*->/', $content);
    
    if ($has_database_operations && !$has_shop_connection && !$has_main_connection && 
        !$uses_shop_pdo && !$uses_main_pdo) {
        
        // Ajouter getShopDBConnection() au début du fichier
        if (!preg_match('/require.*database\.php/', $content) && !preg_match('/include.*database\.php/', $content)) {
            $content = preg_replace('/(<\?php\s*\n?)/', '$1require_once __DIR__ . \'/../config/database.php\';\n', $content, 1);
            $changes++;
        }
        
        // Ajouter la connexion après les includes
        if (preg_match('/(require_once.*database\.php.*\n)/i', $content, $matches)) {
            $replacement = $matches[1] . "\$shop_pdo = getShopDBConnection();\n";
            $content = str_replace($matches[1], $replacement, $content);
            $changes++;
        }
    }
    
    // 3. Corriger les variables de connexion incorrectes
    $content = preg_replace('/\$conn\s*->/', '$shop_pdo->', $content);
    $content = preg_replace('/\$connection\s*->/', '$shop_pdo->', $content);
    $content = preg_replace('/\$db\s*->/', '$shop_pdo->', $content);
    
    if ($content !== $original_content) {
        $changes += 3;
    }
    
    // Sauvegarder si des changements ont été effectués
    if ($changes > 0) {
        file_put_contents($filepath, $content);
        return $changes;
    }
    
    return 0;
}

// Liste des fichiers problématiques identifiés
$problematic_files = [
    './pages/kb_add_article.php',
    './pages/kb_article.php', 
    './pages/kb_print.php',
    './pages/notification_preferences.php',
    './pages/notifications.php',
    './pages/nouvelle_transaction.php',
    './pages/partenaires.php',
    './pages/reparation_log.php',
    './pages/transactions_partenaire.php',
    './ajax_handlers/save_partenaire.php',
    './ajax_handlers/save_transaction.php',
    './ajax_handlers/sync_data.php',
    './ajax_handlers/ajouter_tache_ajax.php',
    './classes/PushNotifications.php',
    './classes/Database.php',
    './includes/db.php'
];

echo "🔧 Correction finale des problèmes restants...\n\n";

$total_changes = 0;
$files_modified = 0;

foreach ($problematic_files as $file) {
    if (file_exists($file)) {
        $changes = fixFinalIssues($file);
        if ($changes > 0) {
            $files_modified++;
            $total_changes += $changes;
            echo "✅ " . basename($file) . " - $changes corrections finales\n";
        } else {
            echo "⚠️  " . basename($file) . " - Aucune correction nécessaire\n";
        }
    } else {
        echo "❌ " . basename($file) . " - Fichier introuvable\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RÉSUMÉ CORRECTIONS FINALES\n";
echo str_repeat("=", 50) . "\n";
echo "Fichiers modifiés: $files_modified\n";
echo "Total des corrections: $total_changes\n";

if ($total_changes > 0) {
    echo "\n🎉 Corrections finales terminées !\n";
} else {
    echo "\n✅ Aucune correction finale nécessaire.\n";
}
?> 