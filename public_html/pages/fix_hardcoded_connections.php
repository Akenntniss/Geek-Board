<?php
/**
 * Script pour corriger les connexions PDO hardcodées restantes
 */

function fixHardcodedConnections($filepath) {
    $content = file_get_contents($filepath);
    $original_content = $content;
    $changes = 0;
    
    // Pattern pour détecter les connexions PDO hardcodées
    $pattern = '/\$\w+\s*=\s*new\s+PDO\s*\(\s*["\']mysql:host=[^;]+;[^"\']*["\'][^)]*\)\s*;/i';
    
    if (preg_match($pattern, $content)) {
        // Remplacer par getShopDBConnection()
        $new_content = preg_replace($pattern, '$shop_pdo = getShopDBConnection();', $content);
        
        if ($new_content !== $content) {
            $content = $new_content;
            $changes++;
            
            // S'assurer que database.php est inclus
            if (!preg_match('/require.*database\.php/', $content) && !preg_match('/include.*database\.php/', $content)) {
                // Ajouter l'include au début du fichier après <?php
                $content = preg_replace('/(<\?php\s*\n?)/', '$1require_once __DIR__ . \'/../config/database.php\';\n', $content, 1);
                $changes++;
            }
        }
    }
    
    // Sauvegarder si des changements ont été effectués
    if ($changes > 0) {
        file_put_contents($filepath, $content);
        return $changes;
    }
    
    return 0;
}

function fixDirectory($directory) {
    $files = glob($directory . '/*.php');
    $total_changes = 0;
    $files_modified = 0;
    
    foreach ($files as $file) {
        $changes = fixHardcodedConnections($file);
        if ($changes > 0) {
            $files_modified++;
            $total_changes += $changes;
            echo "✅ " . basename($file) . " - $changes corrections\n";
        }
    }
    
    return [$files_modified, $total_changes];
}

echo "🔧 Correction des connexions PDO hardcodées...\n\n";

// Corriger tous les dossiers
$directories = ['./pages', './ajax_handlers', './includes', './classes'];
$total_files = 0;
$total_changes = 0;

foreach ($directories as $dir) {
    echo "📁 Correction du dossier $dir\n";
    list($files, $changes) = fixDirectory($dir);
    $total_files += $files;
    $total_changes += $changes;
    
    if ($files == 0) {
        echo "   Aucune correction nécessaire\n";
    }
    echo "\n";
}

echo str_repeat("=", 50) . "\n";
echo "📊 RÉSUMÉ\n";
echo str_repeat("=", 50) . "\n";
echo "Fichiers modifiés: $total_files\n";
echo "Total des corrections: $total_changes\n";

if ($total_changes > 0) {
    echo "\n🎉 Corrections terminées !\n";
} else {
    echo "\n✅ Aucune connexion hardcodée trouvée.\n";
}
?> 