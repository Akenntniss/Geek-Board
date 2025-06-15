<?php
/**
 * Script de correction rapide pour les fichiers utilisant encore $pdo
 * Basé sur l'analyse des fichiers les plus problématiques
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Correction Rapide - \$pdo</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='container mt-4'>";

echo "<h1>🔧 Correction Rapide - Fichiers Problématiques</h1>";

// Liste des fichiers identifiés comme problématiques lors de l'analyse
$files_to_fix = [
    './ajax/check_product_stock.php',
    './ajax/add_product_stock.php', 
    './ajax/get_reparations_client.php',
    './ajax/verifier_retour.php',
    './ajax/delete_partenaire.php',
    './ajax/update_product_stock.php',
    './ajax/creer_colis.php',
    './ajax/inscrire_parrainage.php',
    './ajax/save_reparation.php',
    './ajax/confirm_announcement_read.php',
    './debug_gardiennage.php'
];

$corrections_applied = 0;
$files_corrected = 0;
$errors = [];

echo "<div class='alert alert-info'>";
echo "<h4>🔍 Application des corrections sur " . count($files_to_fix) . " fichiers identifiés...</h4>";
echo "</div>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header'><h5>✅ Corrections Appliquées</h5></div>";
echo "<div class='card-body' style='height: 400px; overflow-y: auto;' id='corrections'>";

foreach ($files_to_fix as $file) {
    if (!file_exists($file)) {
        $errors[] = "Fichier non trouvé : $file";
        continue;
    }
    
    $content = file_get_contents($file);
    $original_content = $content;
    
    // Vérifier si le fichier a besoin de corrections
    if (!preg_match('/\$pdo->(prepare|query|exec|beginTransaction|commit|rollBack|lastInsertId|inTransaction)|global\s+\$pdo|isset\(\$pdo\)|\$pdo\s+instanceof/', $content)) {
        echo "<div class='mb-2 p-2 border-start border-info border-3'>";
        echo "<strong>ℹ️ {$file}</strong><br>";
        echo "<small class='text-muted'>Déjà corrigé</small>";
        echo "</div>";
        continue;
    }
    
    $file_corrections = 0;
    
    // 1. Ajouter l'inclusion database.php si nécessaire
    if (!preg_match('/require.*database\.php/', $content) && !preg_match('/getShopDBConnection|getMainDBConnection/', $content)) {
        if (preg_match('/^<\?php\s*\n/', $content)) {
            $content = preg_replace(
                '/^<\?php\s*\n/',
                "<?php\nrequire_once __DIR__ . '/../config/database.php';\n\n",
                $content
            );
        } elseif (preg_match('/^<\?php/', $content)) {
            $content = preg_replace(
                '/^<\?php/',
                "<?php\nrequire_once __DIR__ . '/../config/database.php';\n",
                $content
            );
        }
        $file_corrections++;
    }
    
    // 2. Remplacer global $pdo
    if (preg_match('/global\s+\$pdo;/', $content)) {
        $content = preg_replace(
            '/global\s+\$pdo;\s*/',
            "// Utilisation de getShopDBConnection() à la place de global \$pdo\n    \$shop_pdo = getShopDBConnection();\n    ",
            $content
        );
        $file_corrections++;
    }
    
    // 3. Ajouter $shop_pdo = getShopDBConnection() si pas de global remplacé
    if (!preg_match('/\$shop_pdo\s*=\s*getShopDBConnection/', $content) && preg_match('/\$pdo->/', $content)) {
        // Trouver le bon endroit pour ajouter la déclaration
        if (preg_match('/(try\s*{|function\s+\w+[^{]*{)/m', $content)) {
            $content = preg_replace(
                '/(try\s*{)/m',
                "$1\n    // Utiliser la connexion à la base de données du magasin actuel\n    \$shop_pdo = getShopDBConnection();\n    \n    // Vérifier la connexion\n    if (!\$shop_pdo || !(\$shop_pdo instanceof PDO)) {\n        throw new Exception('Connexion à la base de données du magasin non disponible');\n    }\n",
                $content,
                1
            );
            $file_corrections++;
        }
    }
    
    // 4. Remplacer toutes les occurrences $pdo-> par $shop_pdo->
    $pdo_count = preg_match_all('/\$pdo->/', $content);
    if ($pdo_count) {
        $content = preg_replace('/\$pdo->/', '$shop_pdo->', $content);
        $file_corrections += $pdo_count;
    }
    
    // 5. Remplacer les vérifications isset($pdo)
    if (preg_match('/isset\(\$pdo\)/', $content)) {
        $content = preg_replace('/isset\(\$pdo\)/', 'isset($shop_pdo)', $content);
        $file_corrections++;
    }
    
    // 6. Remplacer les vérifications instanceof
    if (preg_match('/\$pdo\s+instanceof\s+PDO/', $content)) {
        $content = preg_replace('/\$pdo\s+instanceof\s+PDO/', '$shop_pdo instanceof PDO', $content);
        $file_corrections++;
    }
    
    // 7. Corriger les messages d'erreur
    $content = preg_replace(
        '/Connexion à la base de données non disponible/',
        'Connexion à la base de données du magasin non disponible',
        $content
    );
    
    // Appliquer les corrections si des changements ont été faits
    if ($content !== $original_content && $file_corrections > 0) {
        // Créer un backup
        $backup_file = $file . '.backup_' . date('Y-m-d_H-i-s');
        copy($file, $backup_file);
        
        // Appliquer les corrections
        if (file_put_contents($file, $content) !== false) {
            $files_corrected++;
            $corrections_applied += $file_corrections;
            
            echo "<div class='mb-2 p-2 border-start border-success border-3'>";
            echo "<strong>✅ {$file}</strong><br>";
            echo "<small class='text-success'>✓ {$file_corrections} corrections appliquées</small><br>";
            echo "<small class='text-muted'>💾 Backup: {$backup_file}</small>";
            echo "</div>";
        } else {
            $errors[] = "Impossible d'écrire dans : $file";
        }
    } else {
        echo "<div class='mb-2 p-2 border-start border-info border-3'>";
        echo "<strong>ℹ️ {$file}</strong><br>";
        echo "<small class='text-muted'>Aucune correction nécessaire</small>";
        echo "</div>";
    }
}

echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header'><h5>📊 Résumé</h5></div>";
echo "<div class='card-body'>";

echo "<div class='alert alert-info'>";
echo "<h5>📈 Statistiques</h5>";
echo "<ul>";
echo "<li><strong>Fichiers traités :</strong> " . count($files_to_fix) . "</li>";
echo "<li><strong>Fichiers corrigés :</strong> {$files_corrected}</li>";
echo "<li><strong>Corrections appliquées :</strong> {$corrections_applied}</li>";
echo "<li><strong>Erreurs :</strong> " . count($errors) . "</li>";
echo "</ul>";
echo "</div>";

if (count($errors) > 0) {
    echo "<div class='alert alert-warning'>";
    echo "<h6>⚠️ Erreurs Rencontrées</h6>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if ($files_corrected > 0) {
    echo "<div class='alert alert-success'>";
    echo "<h5>🎉 Corrections Terminées !</h5>";
    echo "<p>{$files_corrected} fichiers ont été corrigés avec succès.</p>";
    echo "<p>Total de {$corrections_applied} modifications appliquées.</p>";
    echo "</div>";
    
    echo "<div class='mt-3'>";
    echo "<a href='validation_finale_pdo.php' class='btn btn-primary btn-lg me-2'>🔍 Validation Finale</a>";
    echo "<a href='analyse_complete_pdo.php' class='btn btn-secondary btn-lg'>📊 Nouvelle Analyse</a>";
    echo "</div>";
} else {
    echo "<div class='alert alert-info'>";
    echo "<h5>ℹ️ Aucune Correction Nécessaire</h5>";
    echo "<p>Tous les fichiers semblent déjà être conformes.</p>";
    echo "</div>";
    
    echo "<div class='mt-3'>";
    echo "<a href='validation_finale_pdo.php' class='btn btn-primary btn-lg me-2'>🔍 Validation Finale</a>";
    echo "<a href='analyse_complete_pdo.php' class='btn btn-secondary btn-lg'>📊 Analyser Tout</a>";
    echo "</div>";
}

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Créer un rapport de correction
if ($files_corrected > 0) {
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'files_processed' => count($files_to_fix),
        'files_corrected' => $files_corrected,
        'corrections_applied' => $corrections_applied,
        'errors' => $errors,
        'corrected_files' => array_filter($files_to_fix, function($file) use ($files_corrected) {
            return file_exists($file . '.backup_' . date('Y-m-d'));
        })
    ];
    
    file_put_contents(
        'rapport_correction_rapide_' . date('Y-m-d_H-i-s') . '.json',
        json_encode($report, JSON_PRETTY_PRINT)
    );
    
    echo "<div class='row mt-4'>";
    echo "<div class='col-12'>";
    echo "<div class='alert alert-info'>";
    echo "<p><strong>📄 Rapport sauvegardé :</strong> rapport_correction_rapide_" . date('Y-m-d_H-i-s') . ".json</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

?>

</body>
</html> 