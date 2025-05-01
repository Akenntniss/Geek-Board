<?php
// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'u139954273_Vscodetest');
define('DB_PASS', 'Maman01#');
define('DB_NAME', 'u139954273_Vscodetest');

// Initialisation de la variable $pdo
$pdo = null;

// Nombre maximum de tentatives de connexion
$max_attempts = 3;
$attempt = 0;
$wait_time = 2; // secondes entre les tentatives

while ($attempt < $max_attempts && $pdo === null) {
    try {
        $attempt++;
        error_log("Tentative $attempt de connexion à la base de données");
        
        // Création de la connexion PDO
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false // Désactiver les connexions persistantes pour résoudre les problèmes de timeout
            ]
        );
        
        // Si on arrive ici, la connexion a réussi
        if ($attempt > 1) {
            error_log("Connexion à la base de données réussie après $attempt tentatives");
        } else {
            error_log("Connexion à la base de données réussie");
        }
        
    } catch (PDOException $e) {
        error_log("Tentative $attempt: Erreur de connexion à la base de données: " . $e->getMessage());
        
        // Si nous avons atteint le nombre maximum de tentatives, lancer l'erreur
        if ($attempt >= $max_attempts) {
            error_log("Échec de connexion à la base de données après $max_attempts tentatives. Erreur : " . $e->getMessage());
            $pdo = null;
        } else {
            // Attendre avant de réessayer
            error_log("Attente de $wait_time secondes avant nouvelle tentative...");
            sleep($wait_time);
            $wait_time *= 2; // Augmenter le temps d'attente à chaque échec
        }
    }
}

// Vérifier que la connexion a bien été établie
if ($pdo === null) {
    error_log("ERREUR CRITIQUE: Impossible d'établir une connexion à la base de données");
    // Au lieu de die(), on lance une exception qui sera attrapée par le code appelant
    throw new PDOException("Impossible d'établir une connexion à la base de données");
}

/**
 * Fonction pour obtenir une connexion à la base de données
 * @return PDO Instance de connexion PDO
 */
function getDBConnection() {
    global $pdo;
    
    // La connexion est déjà établie dans ce fichier
    // Pas besoin de réinclure le fichier database.php
    
    return $pdo;
}
?>