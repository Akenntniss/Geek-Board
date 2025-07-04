<?php
/**
 * Script de correction pour navbar.php
 * Corrige le problème où getShopDBConnection() retourne null
 */

echo "=== Correction de navbar.php ===\n";

$navbar_file = '/var/www/mdgeek.top/components/navbar.php';

if (!file_exists($navbar_file)) {
    echo "Erreur : Le fichier navbar.php n'existe pas à $navbar_file\n";
    exit(1);
}

// Lire le contenu du fichier
$content = file_get_contents($navbar_file);

if ($content === false) {
    echo "Erreur : Impossible de lire le fichier navbar.php\n";
    exit(1);
}

// Rechercher et remplacer le code problématique
$old_code = 'try {
    if (isset($_SESSION[\'shop_id\'])) {
        $shop_pdo = getShopDBConnection();
        $query = $shop_pdo->query("SELECT DATABASE() as db_name");
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result && isset($result[\'db_name\'])) {
            $db_name = $result[\'db_name\'];
        }
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du nom de la base de données: " . $e->getMessage());
}';

$new_code = 'try {
    if (isset($_SESSION[\'shop_id\'])) {
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo !== null) {
            $query = $shop_pdo->query("SELECT DATABASE() as db_name");
            $result = $query->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result[\'db_name\'])) {
                $db_name = $result[\'db_name\'];
            }
        }
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du nom de la base de données: " . $e->getMessage());
}';

// Vérifier si le problème existe
if (strpos($content, '$query = $shop_pdo->query("SELECT DATABASE() as db_name");') !== false) {
    // Effectuer la correction
    $content = str_replace($old_code, $new_code, $content);
    
    // Créer une sauvegarde
    $backup_file = $navbar_file . '.backup.' . date('Y-m-d-H-i-s');
    copy($navbar_file, $backup_file);
    echo "Sauvegarde créée : $backup_file\n";
    
    // Écrire le contenu corrigé
    if (file_put_contents($navbar_file, $content) !== false) {
        echo "✅ Correction appliquée avec succès !\n";
        echo "Le problème getShopDBConnection() dans navbar.php a été corrigé.\n";
    } else {
        echo "❌ Erreur lors de l'écriture du fichier corrigé\n";
        exit(1);
    }
} else {
    echo "ℹ️  Le problème n'existe pas ou a déjà été corrigé dans navbar.php\n";
}

echo "\n=== Correction terminée ===\n";
?> 