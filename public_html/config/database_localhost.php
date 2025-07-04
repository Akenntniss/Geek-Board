<?php
// Configuration localhost pour le gestionnaire de base de données
// Paramètres de connexion à la base de données principale (localhost)
define('MAIN_DB_HOST', 'localhost');
define('MAIN_DB_PORT', '3306');
define('MAIN_DB_USER', 'root');
define('MAIN_DB_PASS', '');
define('MAIN_DB_NAME', 'geekboard_main');

// Variables globales pour les connexions PDO
$main_pdo = null;   // Connexion à la base principale
$shop_pdo = null;   // Connexion à la base du magasin actuel

// Configuration pour les tentatives de connexion
$max_attempts = 3;  // Nombre maximum de tentatives
$wait_time = 2;     // Temps d'attente initial (secondes)

// Fonction pour le débogage des opérations de base de données
function dbDebugLog($message) {
    // Activer/Désactiver le journal de débogage DB
    $debug_enabled = true; // Activé pour localhost
    
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
$shop_pdo = null;

dbDebugLog("Chargement du fichier database.php (version localhost)");

// Connexion à la base principale
while ($attempt < $max_attempts && $main_pdo === null) {
    try {
        $attempt++;
        error_log("Tentative $attempt de connexion à la base de données principale (localhost)");
        dbDebugLog("Tentative $attempt de connexion à la base de données principale (localhost)");
        
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
            error_log("Connexion à la base de données principale réussie (localhost)");
            dbDebugLog("Connexion à la base de données principale réussie (localhost)");
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
    error_log("ERREUR CRITIQUE: Impossible d'établir une connexion à la base de données principale (localhost)");
    dbDebugLog("ERREUR CRITIQUE: Impossible d'établir une connexion à la base de données principale (localhost)");
    throw new PDOException("Impossible d'établir une connexion à la base de données principale (localhost)");
}

/**
 * Fonction pour obtenir une connexion à la base de données principale
 * @return PDO|null Instance de connexion PDO à la base principale ou null en cas d'échec
 */
function getMainDBConnection() {
    global $main_pdo;
    dbDebugLog("Demande de connexion à la base principale (localhost)");
    
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
            
            dbDebugLog("Reconnexion à la base principale réussie (localhost)");
            error_log("Reconnexion à la base principale réussie (localhost)");
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
            dbDebugLog("Connexion à la base principale active et fonctionnelle (localhost)");
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
 * @return PDO|null Connexion à la base de données du magasin
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
 * @return PDO|null Instance de connexion PDO au magasin actuel
 */
function getShopDBConnection() {
    global $shop_pdo, $main_pdo;
    
    // Shop ID magasin de l'URL (prioritaire)
    $shop_id_from_url = $_GET['shop_id'] ?? null;
    if ($shop_id_from_url) {
        $_SESSION['shop_id'] = $shop_id_from_url; 
    }
    
    // Shop ID de la session
    $shop_id = $_SESSION['shop_id'] ?? null;
    
    // Cache la connexion pour éviter de se reconnecter à chaque appel
    if ($shop_pdo !== null) {
        try {
            $test_stmt = $shop_pdo->query("SELECT 1");
            $test_stmt->fetch();
            return $shop_pdo;
        } catch (PDOException $e) {
            $shop_pdo = null;
        }
    }
    
    // Vérifie si l'ID du magasin est défini
    if (!$shop_id) {
        throw new PDOException("ID du magasin non défini");
    }
    
    // Récupérer les informations de connexion pour ce magasin depuis la base principale
    $main_pdo = getMainDBConnection();
    if ($main_pdo === null) {
        throw new PDOException("Impossible d'obtenir la connexion principale");
    }
    
    try {
        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop = $stmt->fetch();
        
        if (!$shop) {
            throw new PDOException("Magasin non trouvé");
        }
        
        $shop_config = [
            'host' => $shop['db_host'] ?? MAIN_DB_HOST,
            'port' => $shop['db_port'] ?? MAIN_DB_PORT,
            'user' => $shop['db_user'] ?? MAIN_DB_USER,
            'pass' => $shop['db_pass'] ?? MAIN_DB_PASS,
            'dbname' => $shop['db_name'] ?? MAIN_DB_NAME
        ];
        
        // Vérifier si les données sont complètes
        $missing_keys = [];
        foreach (['host', 'user', 'pass', 'dbname'] as $required_key) {
            if (empty($shop_config[$required_key])) {
                $missing_keys[] = $required_key;
            }
        }
        
        if (!empty($missing_keys)) {
            throw new PDOException("Configuration du magasin incomplète");
        }
            
        // Connexion à la base du magasin
        $shop_pdo = connectToShopDB($shop_config);
        if ($shop_pdo === null) {
            throw new PDOException("Échec de connexion à la base du magasin");
        }
        
        return $shop_pdo;
        
    } catch (Exception $e) {
        throw new PDOException("Erreur lors de la connexion au magasin: " . $e->getMessage());
    }
}
?> 