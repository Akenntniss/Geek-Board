<?php
// Paramètres de connexion à la base de données principale
define('MAIN_DB_HOST', 'srv931.hstgr.io');
define('MAIN_DB_PORT', '3306');
define('MAIN_DB_USER', 'u139954273_Vscodetest');
define('MAIN_DB_PASS', 'Maman01#');
define('MAIN_DB_NAME', 'u139954273_Vscodetest');

// Ajouter une fonction de journalisation pour les opérations DB
function dbDebugLog($message) {
    error_log("[DEBUG DB] " . $message);
}

dbDebugLog("Chargement du fichier database.php");

// Cette base de données principale contient les informations sur les magasins
// et sera utilisée pour la gestion globale des magasins

// Initialisation de la variable $pdo pour la base de données principale
$main_pdo = null;
$shop_pdo = null; // Pour la connexion au magasin actuel

// Nombre maximum de tentatives de connexion
$max_attempts = 3;
$attempt = 0;
$wait_time = 2; // secondes entre les tentatives

// Connexion à la base principale
while ($attempt < $max_attempts && $main_pdo === null) {
    try {
        $attempt++;
        error_log("Tentative $attempt de connexion à la base de données principale");
        dbDebugLog("Tentative $attempt de connexion à la base de données principale");
        
        // Création de la connexion PDO
        $dsn = "mysql:host=" . MAIN_DB_HOST . ";port=" . MAIN_DB_PORT . ";dbname=" . MAIN_DB_NAME . ";charset=utf8mb4";
        
        $main_pdo = new PDO(
            $dsn,
            MAIN_DB_USER,
            MAIN_DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ]
        );
        
        // Si on arrive ici, la connexion a réussi
        if ($attempt > 1) {
            error_log("Connexion à la base de données principale réussie après $attempt tentatives");
            dbDebugLog("Connexion à la base de données principale réussie après $attempt tentatives");
        } else {
            error_log("Connexion à la base de données principale réussie");
            dbDebugLog("Connexion à la base de données principale réussie");
        }
        
    } catch (PDOException $e) {
        error_log("Tentative $attempt: Erreur de connexion à la base de données principale: " . $e->getMessage());
        dbDebugLog("Tentative $attempt: Erreur de connexion à la base de données principale: " . $e->getMessage());
        
        if ($attempt >= $max_attempts) {
            error_log("Échec de connexion à la base de données principale après $max_attempts tentatives. Erreur : " . $e->getMessage());
            dbDebugLog("Échec de connexion à la base de données principale après $max_attempts tentatives. Erreur : " . $e->getMessage());
            $main_pdo = null;
        } else {
            error_log("Attente de $wait_time secondes avant nouvelle tentative...");
            sleep($wait_time);
            $wait_time *= 2;
        }
    }
}

// Vérifier que la connexion principale a bien été établie
if ($main_pdo === null) {
    error_log("ERREUR CRITIQUE: Impossible d'établir une connexion à la base de données principale");
    dbDebugLog("ERREUR CRITIQUE: Impossible d'établir une connexion à la base de données principale");
    throw new PDOException("Impossible d'établir une connexion à la base de données principale");
}

/**
 * Fonction pour obtenir une connexion à la base de données principale
 * @return PDO Instance de connexion PDO à la base principale
 */
function getMainDBConnection() {
    global $main_pdo;
    dbDebugLog("Demande de connexion à la base principale");
    return $main_pdo;
}

/**
 * Fonction pour connecter à la base de données d'un magasin spécifique
 * @param array $shop_config Configuration du magasin (host, user, pass, db)
 * @return PDO|null Connexion à la base de données du magasin ou la connexion principale en cas d'échec
 */
function connectToShopDB($shop_config) {
    global $max_attempts, $main_pdo;
    
    dbDebugLog("Tentative de connexion à une DB de magasin: " . $shop_config['dbname'] . " sur " . $shop_config['host']);
    
    $pdo = null;
    $attempt = 0;
    $wait_time = 2;
    
    while ($attempt < $max_attempts && $pdo === null) {
        try {
            $attempt++;
            dbDebugLog("Tentative $attempt pour " . $shop_config['dbname']);
            
            $dsn = "mysql:host=" . $shop_config['host'] . ";port=" . 
                   ($shop_config['port'] ?? '3306') . ";dbname=" . 
                   $shop_config['dbname'] . ";charset=utf8mb4";
            
            $pdo = new PDO(
                $dsn,
                $shop_config['user'],
                $shop_config['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false
                ]
            );
            
            dbDebugLog("Connexion réussie à " . $shop_config['dbname']);
            
        } catch (PDOException $e) {
            error_log("Tentative $attempt: Erreur de connexion à la base du magasin: " . $e->getMessage());
            dbDebugLog("Tentative $attempt: Erreur de connexion à la base " . $shop_config['dbname'] . ": " . $e->getMessage());
            
            if ($attempt >= $max_attempts) {
                error_log("Échec de connexion à la base du magasin après $max_attempts tentatives.");
                dbDebugLog("Échec de connexion à la base " . $shop_config['dbname'] . " après $max_attempts tentatives - Utilisation de la base principale");
                // Au lieu de retourner null, on retourne la connexion principale
                $pdo = $main_pdo;
            } else {
                sleep($wait_time);
                $wait_time *= 2;
            }
        }
    }
    
    // Si après toutes les tentatives la connexion est toujours null,
    // retourner la connexion principale pour éviter les erreurs
    if ($pdo === null) {
        dbDebugLog("Aucune connexion établie - Utilisation de la base principale");
        return $main_pdo;
    }
    
    return $pdo;
}

/**
 * Fonction pour obtenir la connexion à la base de données du magasin actuel
 * @return PDO Instance de connexion PDO au magasin actuel ou à la base principale
 */
function getShopDBConnection() {
    global $shop_pdo, $main_pdo;
    
    // Si mode superadmin est actif, retourner toujours la connexion principale
    if (isset($_SESSION['superadmin_mode']) && $_SESSION['superadmin_mode'] === true) {
        dbDebugLog("Mode superadmin actif: utilisation forcée de la base principale");
        return getMainDBConnection();
    }
    
    // Cache la connexion pour éviter de se reconnecter à chaque appel
    if ($shop_pdo !== null) {
        dbDebugLog("Utilisation de la connexion magasin déjà établie");
        return $shop_pdo;
    }
    
    dbDebugLog("Initialisation nouvelle connexion magasin");
    
    // Si aucun magasin n'est sélectionné, on utilise la base principale
    if (!isset($_SESSION['shop_id'])) {
        dbDebugLog("Aucun magasin en session, utilisation de la base principale");
        $shop_pdo = getMainDBConnection(); // Stocker dans shop_pdo pour la mise en cache
        return $shop_pdo;
    }
    
    // Récupérer les informations de connexion pour ce magasin depuis la base principale
    $shop_id = $_SESSION['shop_id'];
    dbDebugLog("Magasin en session trouvé: ID=" . $shop_id);
    $main_pdo = getMainDBConnection();
    
    try {
        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop = $stmt->fetch();
        
        if ($shop) {
            dbDebugLog("Informations du magasin " . $shop['name'] . " récupérées");
            $shop_config = [
                'host' => $shop['db_host'],
                'port' => $shop['db_port'],
                'user' => $shop['db_user'],
                'pass' => $shop['db_pass'],
                'dbname' => $shop['db_name']
            ];
            
            dbDebugLog("Tentative de connexion à la DB du magasin: " . $shop_config['dbname']);
            $shop_pdo = connectToShopDB($shop_config);
            
            // La fonction connectToShopDB retourne maintenant toujours une connexion valide
            // (soit celle du magasin, soit celle principale)
            dbDebugLog("Connexion établie (magasin ou principale)");
        } else {
            // Si le magasin n'existe pas, on utilise la base principale
            dbDebugLog("Magasin " . $shop_id . " non trouvé, utilisation de la base principale");
            error_log("Magasin {$shop_id} non trouvé, utilisation de la base principale");
            $shop_pdo = $main_pdo;
        }
    } catch (Exception $e) {
        // En cas d'erreur lors de la récupération des infos du magasin,
        // utiliser la connexion principale
        dbDebugLog("Exception lors de la récupération des infos magasin: " . $e->getMessage());
        error_log("Exception lors de la récupération des infos magasin: " . $e->getMessage());
        $shop_pdo = $main_pdo;
    }
    
    // Si malgré tout shop_pdo est null, utiliser la connexion principale
    if ($shop_pdo === null) {
        dbDebugLog("Connexion shop_pdo toujours null, utilisation forcée de la base principale");
        $shop_pdo = $main_pdo;
    }
    
    return $shop_pdo;
}

// Pour la compatibilité avec le code existant
$pdo = getMainDBConnection();

// Fonction de compatibilité pour l'ancien code
function getDBConnection() {
    // Cette fonction retourne maintenant la connexion au magasin actuel
    // au lieu de la connexion principale
    dbDebugLog("Appel à getDBConnection(), redirection vers getShopDBConnection()");
    return getShopDBConnection();
}
?>