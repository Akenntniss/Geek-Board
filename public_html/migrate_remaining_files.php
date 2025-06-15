<?php
/**
 * Script de migration automatique pour les fichiers restants
 * Corrige tous les fichiers qui utilisent encore $pdo au lieu de getShopDBConnection()
 */

function migrateFile($filepath) {
    $content = file_get_contents($filepath);
    $original_content = $content;
    $changes = 0;
    
    // 1. Ajouter getShopDBConnection() au début si nécessaire
    if (preg_match('/\$pdo\s*->/', $content) && !preg_match('/getShopDBConnection\s*\(\s*\)/', $content)) {
        // Chercher un endroit approprié pour ajouter la connexion
        if (preg_match('/(require_once.*database\.php.*\n)/i', $content, $matches)) {
            $replacement = $matches[1] . "\n\$shop_pdo = getShopDBConnection();\n";
            $content = str_replace($matches[1], $replacement, $content);
            $changes++;
        } elseif (preg_match('/(\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*\$pdo\s*->)/i', $content)) {
            // Si on trouve une première utilisation de $pdo->, ajouter la connexion juste avant
            $content = preg_replace('/(\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*)\$pdo(\s*->)/', '$shop_pdo = getShopDBConnection();' . "\n" . '$1$shop_pdo$2', $content, 1);
            $changes++;
        }
    }
    
    // 2. Remplacer toutes les occurrences de $pdo-> par $shop_pdo->
    $new_content = preg_replace('/\$pdo\s*->/', '$shop_pdo->', $content);
    if ($new_content !== $content) {
        $content = $new_content;
        $changes += substr_count($original_content, '$pdo->');
    }
    
    // 3. Remplacer global $pdo; par $shop_pdo = getShopDBConnection();
    $new_content = preg_replace('/global\s+\$pdo\s*;/', '$shop_pdo = getShopDBConnection();', $content);
    if ($new_content !== $content) {
        $content = $new_content;
        $changes++;
    }
    
    // Sauvegarder si des changements ont été effectués
    if ($changes > 0) {
        file_put_contents($filepath, $content);
        return $changes;
    }
    
    return 0;
}

function migrateDirectory($directory) {
    $files = glob($directory . '/*.php');
    $total_changes = 0;
    $files_modified = 0;
    
    foreach ($files as $file) {
        $changes = migrateFile($file);
        if ($changes > 0) {
            $files_modified++;
            $total_changes += $changes;
            echo "✅ " . basename($file) . " - $changes corrections\n";
        }
    }
    
    return [$files_modified, $total_changes];
}

echo "🔧 Migration automatique des fichiers restants...\n\n";

// Migrer pages/
echo "📁 Migration du dossier pages/\n";
list($files_pages, $changes_pages) = migrateDirectory('./pages');

// Migrer ajax_handlers/
echo "\n📁 Migration du dossier ajax_handlers/\n";
list($files_ajax, $changes_ajax) = migrateDirectory('./ajax_handlers');

// Migrer includes/
echo "\n📁 Migration du dossier includes/\n";
list($files_includes, $changes_includes) = migrateDirectory('./includes');

// Migrer classes/
echo "\n📁 Migration du dossier classes/\n";
list($files_classes, $changes_classes) = migrateDirectory('./classes');

// Résumé
$total_files = $files_pages + $files_ajax + $files_includes + $files_classes;
$total_changes = $changes_pages + $changes_ajax + $changes_includes + $changes_classes;

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RÉSUMÉ DE LA MIGRATION\n";
echo str_repeat("=", 50) . "\n";
echo "Fichiers modifiés: $total_files\n";
echo "Total des corrections: $total_changes\n";
echo "\nDétail par dossier:\n";
echo "- pages/: $files_pages fichiers, $changes_pages corrections\n";
echo "- ajax_handlers/: $files_ajax fichiers, $changes_ajax corrections\n";
echo "- includes/: $files_includes fichiers, $changes_includes corrections\n";
echo "- classes/: $files_classes fichiers, $changes_classes corrections\n";

if ($total_changes > 0) {
    echo "\n🎉 Migration terminée avec succès !\n";
} else {
    echo "\n✅ Aucune migration nécessaire - tous les fichiers sont déjà à jour.\n";
}
?> 