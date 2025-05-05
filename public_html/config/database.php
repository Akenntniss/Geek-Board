<?php
// Paramètres de connexion à la base de données principale
define('MAIN_DB_HOST', 'srv931.hstgr.io');
define('MAIN_DB_PORT', '3306');
define('MAIN_DB_USER', 'u139954273_Vscodetest');
define('MAIN_DB_PASS', 'Maman01#');
define('MAIN_DB_NAME', 'u139954273_Vscodetest');

// Variables globales pour les connexions PDO
$main_pdo = null;   // Connexion à la base principale
$shop_pdo = null;   // Connexion à la base du magasin actuel

// Configuration pour les tentatives de connexion
$max_attempts = 3;  // Nombre maximum de tentatives
$wait_time = 2;     // Temps d'attente initial (secondes)

// Fonction pour le débogage des opérations de base de données
function dbDebugLog($message) {
    // Activer/Désactiver le journal de débogage DB
    $debug_enabled = true;
    
    if ($debug_enabled) {
        // Ajouter un horodatage
        $timestamp = date('Y-m-d H:i:s');
        $formatted_message = "[{$timestamp}] DB: {$message}";
        error_log($formatted_message);
    }
}

// Débogage des variables de session
dbDebugLog("Session au début de database.php: " . print_r($_SESSION ?? [], true));
dbDebugLog("shop_id en session: " . ($_SESSION['shop_id'] ?? 'non défini'));

// Assurons-nous que $shop_pdo est toujours null au départ
// pour forcer une nouvelle connexion si nécessaire
$shop_pdo = null;

dbDebugLog("Chargement du fichier database.php");

// Cette base de données principale contient les informations sur les magasins
// et sera utilisée pour la gestion globale des magasins

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
 * @return PDO|null Instance de connexion PDO à la base principale ou null en cas d'échec
 */
function getMainDBConnection() {
    global $main_pdo;
    dbDebugLog("Demande de connexion à la base principale");
    
    // Vérifier si la connexion est établie
    if ($main_pdo === null) {
        dbDebugLog("ALERTE: Connexion à la base principale inexistante ou perdue - tentative de reconnexion");
        error_log("ALERTE: Connexion à la base principale inexistante ou perdue - tentative de reconnexion");
        
        try {
            // Tentative de reconnexion
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
            
            dbDebugLog("Reconnexion à la base principale réussie");
            error_log("Reconnexion à la base principale réussie");
        } catch (PDOException $e) {
            dbDebugLog("ÉCHEC de reconnexion à la base principale: " . $e->getMessage());
            error_log("ÉCHEC de reconnexion à la base principale: " . $e->getMessage());
            // La connexion reste null
        }
    } else {
        // Test de la connexion existante
        try {
            $stmt = $main_pdo->query("SELECT 1");
            $stmt->fetch();
            dbDebugLog("Connexion à la base principale active et fonctionnelle");
        } catch (PDOException $e) {
            dbDebugLog("La connexion à la base principale existe mais semble invalide: " . $e->getMessage());
            error_log("La connexion à la base principale existe mais semble invalide: " . $e->getMessage());
            
            // Réinitialiser et tenter une reconnexion
            $main_pdo = null;
            return getMainDBConnection(); // Appel récursif une seule fois
        }
    }
    
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
                dbDebugLog("Échec de connexion à la base " . $shop_config['dbname'] . " après $max_attempts tentatives");
                // Ne pas retourner la connexion principale, retourner null pour indiquer l'échec
                return null;
            } else {
                sleep($wait_time);
                $wait_time *= 2;
            }
        }
    }
    
    // Vérifier que la connexion est à la bonne base de données
    if ($pdo !== null) {
        try {
            $stmt = $pdo->query("SELECT DATABASE() as db_name");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            dbDebugLog("Connexion établie à la base: " . ($result['db_name'] ?? 'Inconnue'));
            
            // Vérifier si on a bien la base du magasin
            if (isset($result['db_name']) && $result['db_name'] !== $shop_config['dbname']) {
                error_log("ALERTE: La base connectée (" . $result['db_name'] . ") ne correspond pas à la base demandée (" . $shop_config['dbname'] . ")");
                // Si la base est différente, considérer comme échec
                return null;
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la vérification de la base connectée: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * Fonction pour obtenir la connexion à la base de données du magasin actuel
 * @return PDO|null Instance de connexion PDO au magasin actuel ou à la base principale, ou null en cas d'échec total
 */
function getShopDBConnection() {
    global $shop_pdo, $main_pdo;
    
    // Débogage du mode superadmin
    if (isset($_SESSION['superadmin_mode']) && $_SESSION['superadmin_mode'] === true) {
        dbDebugLog("Mode superadmin détecté mais ignoré pour permettre l'utilisation de la BD du magasin");
        // COMMENTÉ: return getMainDBConnection();
        // On continue avec la connexion au magasin même en mode superadmin
    }
    
    // Cache la connexion pour éviter de se reconnecter à chaque appel
    if ($shop_pdo !== null) {
        // Tester si la connexion est encore valide
        try {
            $test_stmt = $shop_pdo->query("SELECT 1");
            $test_stmt->fetch();
            dbDebugLog("Connexion magasin existante validée");
            return $shop_pdo;
        } catch (PDOException $e) {
            // La connexion n'est plus valide
            dbDebugLog("Connexion magasin existante non valide: " . $e->getMessage());
            error_log("Connexion magasin existante non valide: " . $e->getMessage());
            // On va réinitialiser la connexion et continuer
            $shop_pdo = null;
        }
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
    
    // Vérifier que la connexion principale a bien été établie
    if ($main_pdo === null) {
        dbDebugLog("ERREUR CRITIQUE: Impossible d'obtenir la connexion principale pour récupérer les infos du magasin");
        error_log("ERREUR CRITIQUE: Impossible d'obtenir la connexion principale pour récupérer les infos du magasin");
        return null; // On ne peut pas continuer sans connexion principale
    }
    
    try {
        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop = $stmt->fetch();
        
        if ($shop) {
            dbDebugLog("Informations du magasin " . $shop['name'] . " récupérées");
            
            // Vérifions si la base du magasin est différente de la base principale
            if ($shop['db_name'] == MAIN_DB_NAME && 
                $shop['db_host'] == MAIN_DB_HOST && 
                $shop['db_user'] == MAIN_DB_USER) {
                
                dbDebugLog("ATTENTION: Configuration identique à la base principale - création d'une nouvelle connexion malgré tout");
            }
            
            $shop_config = [
                'host' => $shop['db_host'],
                'port' => $shop['db_port'],
                'user' => $shop['db_user'],
                'pass' => $shop['db_pass'],
                'dbname' => $shop['db_name']
            ];
            
            // Forcer la reconnexion avec les informations du magasin
            dbDebugLog("Tentative de connexion à la DB du magasin: " . $shop_config['dbname']);
            $shop_pdo = connectToShopDB($shop_config);
            
            // Si la connexion au magasin a échoué, on utilise la base principale
            if ($shop_pdo === null) {
                error_log("Échec de connexion à la base du magasin " . $shop_config['dbname'] . " - Utilisation de la base principale comme fallback");
                dbDebugLog("Échec de connexion à la base du magasin, utilisation de la base principale comme fallback");
                $shop_pdo = $main_pdo;
            } else {
                // Vérification supplémentaire que nous sommes bien connectés à la bonne base
                try {
                    $check_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
                    $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    $current_db = $check_result['current_db'] ?? 'inconnue';
                    
                    if ($current_db != $shop_config['dbname']) {
                        dbDebugLog("ERREUR CRITIQUE: Connexion établie à " . $current_db . " au lieu de " . $shop_config['dbname']);
                        // Tentative de correction - forcer le USE de la bonne base
                        $shop_pdo->exec("USE `" . $shop_config['dbname'] . "`");
                        
                        // Re-vérifier après la correction
                        $check_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
                        $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                        $current_db = $check_result['current_db'] ?? 'inconnue';
                        
                        dbDebugLog("Après correction, base connectée: " . $current_db);
                    } else {
                        dbDebugLog("Vérification OK: Connexion bien établie à " . $current_db);
                    }
                } catch (Exception $e) {
                    dbDebugLog("Erreur lors de la vérification de la base: " . $e->getMessage());
                }
                
                dbDebugLog("Connexion réussie à la base du magasin " . $shop_config['dbname']);
            }
            
            // La fonction connectToShopDB peut maintenant retourner null en cas d'échec
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