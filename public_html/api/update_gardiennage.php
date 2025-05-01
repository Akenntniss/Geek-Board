<?php
/**
 * Script de mise à jour quotidienne des gardiennages
 * Ce script doit être exécuté quotidiennement via un cron job
 * 
 * Exemple d'utilisation via cron:
 * 0 1 * * * php /chemin/vers/api/update_gardiennage.php > /dev/null 2>&1
 */

// Chemin vers le répertoire racine du projet (deux niveaux au-dessus)
$root_path = dirname(dirname(__FILE__));

// Définir les constantes nécessaires
define('BASE_PATH', $root_path);
define('CRON_JOB', true);

// Inclusion des fichiers nécessaires
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Création d'un fichier de log
$log_dir = BASE_PATH . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$log_file = $log_dir . '/gardiennage_update_' . date('Y-m-d') . '.log';

// Fonction pour écrire dans le log
function write_log($message) {
    global $log_file;
    $log_entry = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $log_entry; // Afficher aussi dans la sortie standard pour le débogage
}

// Début du script
write_log("=== DÉBUT DE LA MISE À JOUR DES GARDIENNAGES ===");

try {
    // Vérifier si la fonction existe
    if (!function_exists('mettre_a_jour_tous_gardiennages')) {
        throw new Exception("La fonction mettre_a_jour_tous_gardiennages n'est pas disponible");
    }
    
    // Exécuter la mise à jour
    $resultat = mettre_a_jour_tous_gardiennages();
    
    if ($resultat['success']) {
        write_log("Mise à jour réussie : " . $resultat['message']);
        
        // Log des détails pour chaque gardiennage
        if (!empty($resultat['resultats'])) {
            foreach ($resultat['resultats'] as $index => $res) {
                $status = $res['success'] ? 'OK' : 'ERREUR';
                $details = $res['success'] ? 
                    (isset($res['jours_factures']) ? $res['jours_factures'] . ' jours facturés, ' . 
                    number_format($res['montant_facture'], 2, '.', '') . '€' : 'Aucun jour à facturer') : 
                    $res['message'];
                
                write_log("Gardiennage #" . ($index + 1) . ": $status - $details");
            }
        }
    } else {
        write_log("ERREUR lors de la mise à jour : " . $resultat['message']);
    }
} catch (Exception $e) {
    write_log("ERREUR CRITIQUE : " . $e->getMessage());
}

// Fin du script
write_log("=== FIN DE LA MISE À JOUR DES GARDIENNAGES ===");
?> 