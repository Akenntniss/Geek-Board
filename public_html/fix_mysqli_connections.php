<?php
/**
 * Script pour migrer les connexions MySQLi vers PDO avec getShopDBConnection
 */

function fixMySQLiConnections($filepath) {
    $content = file_get_contents($filepath);
    $original_content = $content;
    $changes = 0;
    
    // Détecter les connexions MySQLi hardcodées
    $mysqli_pattern = '/\$\w+\s*=\s*new\s+mysqli\s*\([^)]+\)\s*;/i';
    
    if (preg_match($mysqli_pattern, $content)) {
        // Remplacer par getShopDBConnection()
        $content = preg_replace($mysqli_pattern, '$shop_pdo = getShopDBConnection();', $content);
        $changes++;
        
        // Ajouter l'include de database.php si nécessaire
        if (!preg_match('/require.*database\.php/', $content) && !preg_match('/include.*database\.php/', $content)) {
            $content = preg_replace('/(<\?php\s*\n?)/', '$1require_once __DIR__ . \'/../config/database.php\';\n', $content, 1);
            $changes++;
        }
        
        // Remplacer les vérifications de connexion MySQLi
        $content = preg_replace('/if\s*\(\s*\$\w+->connect_error\s*\)\s*\{[^}]+\}/', '', $content);
        $changes++;
        
        // Remplacer les appels MySQLi par PDO
        $content = preg_replace('/\$\w+->query\s*\(/', '$shop_pdo->query(', $content);
        $content = preg_replace('/\$\w+->prepare\s*\(/', '$shop_pdo->prepare(', $content);
        $content = preg_replace('/\$\w+->fetch_assoc\s*\(\s*\)/', '$stmt->fetch(PDO::FETCH_ASSOC)', $content);
        $content = preg_replace('/\$\w+->num_rows/', '$stmt->rowCount()', $content);
        $changes += 4;
        
        // Corriger les boucles while avec fetch_assoc
        $content = preg_replace('/while\s*\(\s*\$(\w+)\s*=\s*\$\w+->fetch_assoc\s*\(\s*\)\s*\)/', 'while($1 = $stmt->fetch(PDO::FETCH_ASSOC))', $content);
        $changes++;
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
        $changes = fixMySQLiConnections($file);
        if ($changes > 0) {
            $files_modified++;
            $total_changes += $changes;
            echo "✅ " . basename($file) . " - $changes corrections MySQLi\n";
        }
    }
    
    return [$files_modified, $total_changes];
}

echo "🔧 Migration des connexions MySQLi vers PDO...\n\n";

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
        echo "   Aucune connexion MySQLi trouvée\n";
    }
    echo "\n";
}

echo str_repeat("=", 50) . "\n";
echo "📊 RÉSUMÉ\n";
echo str_repeat("=", 50) . "\n";
echo "Fichiers modifiés: $total_files\n";
echo "Total des corrections: $total_changes\n";

if ($total_changes > 0) {
    echo "\n🎉 Migration MySQLi terminée !\n";
} else {
    echo "\n✅ Aucune connexion MySQLi trouvée.\n";
}
?> 