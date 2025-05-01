<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le chemin de base
define('BASE_PATH', __DIR__);

// Inclure les fichiers nécessaires
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Créer un fichier de log
$log_file = BASE_PATH . '/debug_gardiennage.log';
file_put_contents($log_file, "=== DÉBUT DU DÉBOGAGE DE GARDIENNAGE ===\n", FILE_APPEND);

// Vérifier si le fichier gardiennage.php existe
$gardiennage_file = BASE_PATH . '/pages/gardiennage.php';
file_put_contents($log_file, "Fichier gardiennage.php existe: " . (file_exists($gardiennage_file) ? "Oui" : "Non") . "\n", FILE_APPEND);

// Vérifier si la fonction demarrer_gardiennage existe
file_put_contents($log_file, "Fonction demarrer_gardiennage existe: " . (function_exists('demarrer_gardiennage') ? "Oui" : "Non") . "\n", FILE_APPEND);

// Vérifier si la fonction terminer_gardiennage existe
file_put_contents($log_file, "Fonction terminer_gardiennage existe: " . (function_exists('terminer_gardiennage') ? "Oui" : "Non") . "\n", FILE_APPEND);

// Vérifier si la fonction envoyer_rappel_gardiennage existe
file_put_contents($log_file, "Fonction envoyer_rappel_gardiennage existe: " . (function_exists('envoyer_rappel_gardiennage') ? "Oui" : "Non") . "\n", FILE_APPEND);

// Vérifier si la table gardiennage existe
try {
    global $pdo;
    $stmt = $pdo->query("SHOW TABLES LIKE 'gardiennage'");
    $table_exists = $stmt->rowCount() > 0;
    file_put_contents($log_file, "Table gardiennage existe: " . ($table_exists ? "Oui" : "Non") . "\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($log_file, "Erreur lors de la vérification de la table gardiennage: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Tester une requête SELECT sur la table gardiennage
try {
    if ($table_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM gardiennage");
        $count = $stmt->fetchColumn();
        file_put_contents($log_file, "Nombre d'enregistrements dans la table gardiennage: " . $count . "\n", FILE_APPEND);
    }
} catch (Exception $e) {
    file_put_contents($log_file, "Erreur lors de l'exécution de la requête SELECT: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Vérifier si le code gardiennage existe dans la table statuts
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM statuts WHERE code = 'gardiennage'");
    $gardiennage_statut_exists = $stmt->fetchColumn() > 0;
    file_put_contents($log_file, "Statut 'gardiennage' existe: " . ($gardiennage_statut_exists ? "Oui" : "Non") . "\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($log_file, "Erreur lors de la vérification du statut gardiennage: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Tester s'il y a des erreurs dans le code PHP de gardiennage.php
try {
    ob_start();
    include $gardiennage_file;
    $output = ob_get_clean();
    file_put_contents($log_file, "Inclusion de gardiennage.php: Succès\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($log_file, "Erreur lors de l'inclusion de gardiennage.php: " . $e->getMessage() . "\n", FILE_APPEND);
}

file_put_contents($log_file, "=== FIN DU DÉBOGAGE DE GARDIENNAGE ===\n", FILE_APPEND);

echo "<h1>Débogage de la page gardiennage</h1>";
echo "<p>Les résultats du débogage ont été enregistrés dans le fichier " . $log_file . "</p>";
echo "<h2>Contenu du fichier log :</h2>";
echo "<pre>" . file_get_contents($log_file) . "</pre>";
?> 