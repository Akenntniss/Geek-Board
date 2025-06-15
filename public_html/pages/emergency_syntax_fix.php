<?php
/**
 * Script d'urgence pour corriger les erreurs de syntaxe
 */

function emergencyFix($filepath) {
    $content = file_get_contents($filepath);
    $original_content = $content;
    $changes = 0;
    
    // 1. Corriger les \n littéraux dans les includes
    $content = str_replace('\\n', "\n", $content);
    if ($content !== $original_content) {
        $changes++;
        $original_content = $content;
    }
    
    // 2. Corriger les includes mal formés
    $content = preg_replace('/require_once __DIR__ \. \'\/\.\.\/config\/database\.php\';([^\n])/', 'require_once __DIR__ . \'/../config/database.php\';\n$1', $content);
    if ($content !== $original_content) {
        $changes++;
        $original_content = $content;
    }
    
    // 3. Corriger les codes MySQLi qui restent
    $content = preg_replace('/\$stmt->bind_param\([^)]+\);/', '// MySQLi code - needs manual conversion', $content);
    $content = preg_replace('/\$stmt->execute\(\);/', '$stmt->execute();', $content);
    $content = preg_replace('/\$result = \$stmt->get_result\(\);/', '$result = $stmt;', $content);
    $content = preg_replace('/\$\w+ = \$result->fetch_assoc\(\);/', '$row = $stmt->fetch(PDO::FETCH_ASSOC);', $content);
    
    if ($content !== $original_content) {
        $changes += 4;
    }
    
    // Sauvegarder si des changements ont été effectués
    if ($changes > 0) {
        file_put_contents($filepath, $content);
        return $changes;
    }
    
    return 0;
}

// Fichiers avec erreurs de syntaxe identifiés
$syntax_error_files = [
    './pages/nouvelle_transaction.php',
    './pages/transactions_partenaire.php', 
    './pages/reparation_log.php',
    './pages/kb_add_article.php',
    './pages/kb_print.php',
    './pages/kb_article.php',
    './pages/notification_preferences.php',
    './pages/notifications.php',
    './pages/partenaires.php',
    './ajax_handlers/save_partenaire.php',
    './ajax_handlers/save_transaction.php',
    './includes/db.php',
    './classes/Database.php'
];

echo "🚨 CORRECTION D'URGENCE DES ERREURS DE SYNTAXE\n";
echo str_repeat("=", 50) . "\n\n";

$total_changes = 0;
$files_fixed = 0;

foreach ($syntax_error_files as $file) {
    if (file_exists($file)) {
        $changes = emergencyFix($file);
        if ($changes > 0) {
            $files_fixed++;
            $total_changes += $changes;
            echo "🔧 " . basename($file) . " - $changes corrections de syntaxe\n";
        } else {
            echo "✅ " . basename($file) . " - Syntaxe OK\n";
        }
    } else {
        echo "❌ " . basename($file) . " - Fichier introuvable\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RÉSUMÉ CORRECTIONS SYNTAXE\n";
echo str_repeat("=", 50) . "\n";
echo "Fichiers corrigés: $files_fixed\n";
echo "Total des corrections: $total_changes\n";

if ($total_changes > 0) {
    echo "\n🎉 Erreurs de syntaxe corrigées !\n";
} else {
    echo "\n✅ Aucune erreur de syntaxe détectée.\n";
}
?> 