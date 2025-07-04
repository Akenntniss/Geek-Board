<?php
// Script robuste pour mettre à jour la configuration des sous-domaines

if ($argc < 3) {
    echo "Usage: php update_subdomain_mapping.php <subdomain> <db_name>\n";
    exit(1);
}

$subdomain = $argv[1];
$db_name = $argv[2];

$config_file = __DIR__ . '/../config/subdomain_database_detector.php';
$backup_file = $config_file . '.bak.' . date('Ymd-His');

if (!is_readable($config_file) || !is_writable($config_file)) {
    error_log("Mise à jour mapping: Le fichier de configuration n'est pas accessible en lecture/écriture.");
    exit(1);
}

// Créer une sauvegarde
if (!copy($config_file, $backup_file)) {
    error_log("Mise à jour mapping: Impossible de créer la sauvegarde.");
    exit(1);
}

$content = file_get_contents($config_file);

// Vérifier si le mapping existe déjà pour éviter les doublons
$check_pattern = "/" . preg_quote("'$subdomain'", '/') . "\\s*=>\\s*" . preg_quote("'$db_name'", '/') . "/";
if (preg_match($check_pattern, $content)) {
    // Le mapping existe déjà, pas besoin de continuer
    exit(0);
}

// Préparer la nouvelle ligne à insérer
$new_mapping_line = "        '$subdomain' => '$db_name',";

// Pattern pour trouver le début du tableau et insérer la nouvelle ligne
$search_pattern = '/(private\s+\$subdomain_mappings\s*=\s*\[)/';
$replacement = '$1' . "\n" . $new_mapping_line;

$new_content = preg_replace($search_pattern, $replacement, $content, 1, $count);

if ($count > 0) {
    if (file_put_contents($config_file, $new_content) === false) {
        error_log("Mise à jour mapping: Impossible d'écrire dans le fichier. Restauration de la sauvegarde.");
        copy($backup_file, $config_file); // Restaurer
        exit(1);
    }
} else {
    error_log("Mise à jour mapping: Le pattern de recherche n'a pas été trouvé. Le fichier n'a pas été modifié.");
    exit(1);
}
?>
