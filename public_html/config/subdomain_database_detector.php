<?php
/**
 * Détecteur automatique de base de données basé sur les sous-domaines
 * Système multi-magasin GeekBoard
 */

class SubdomainDatabaseDetector {
    
    private $main_config = [
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'root',
        'pass' => 'Mamanmaman01#',
        'main_db' => 'geekboard_general'
    ];
    
    private $subdomain_mappings = [
        'cannesphones' => 'geekboard_cannesphones',
        'pscannes' => 'geekboard_pscannes', 
        'psphonac' => 'geekboard_psphonac',
        'mdgeek' => 'geekboard_general',
        'www' => 'geekboard_general',
        '' => 'geekboard_general' // domaine principal sans sous-domaine
    ];
    
    /**
     * Détecter le sous-domaine actuel
     */
    public function detectSubdomain() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Nettoyer le host
        $host = strtolower(trim($host));
        
        // Cas spéciaux pour le développement
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            // En local, vérifier s'il y a un paramètre shop_id ou subdomain
            $subdomain = $_GET['subdomain'] ?? $_SESSION['current_subdomain'] ?? 'mdgeek';
            $this->logDebug("Mode local détecté, sous-domaine: $subdomain");
            return $subdomain;
        }
        
        // Extraire le sous-domaine du host
        $parts = explode('.', $host);
        
        if (count($parts) >= 3) {
            // Sous-domaine présent (ex: cannesphones.mdgeek.top)
            $subdomain = $parts[0];
        } elseif (count($parts) == 2) {
            // Domaine principal (ex: mdgeek.top)
            $subdomain = '';
        } else {
            // Cas par défaut
            $subdomain = '';
        }
        
        $this->logDebug("Host: $host, Sous-domaine détecté: '$subdomain'");
        return $subdomain;
    }
    
    /**
     * Obtenir la configuration de base de données pour le sous-domaine
     */
    public function getDatabaseConfig($subdomain = null) {
        if ($subdomain === null) {
            $subdomain = $this->detectSubdomain();
        }
        
        // Normaliser le sous-domaine
        $subdomain = strtolower(trim($subdomain));
        
        // Vérifier d'abord dans les mappings statiques
        if (isset($this->subdomain_mappings[$subdomain])) {
            $db_name = $this->subdomain_mappings[$subdomain];
            $this->logDebug("Mapping statique trouvé pour '$subdomain': $db_name");
        } else {
            // Chercher dynamiquement dans la base principale
            $db_name = $this->findDatabaseBySubdomain($subdomain);
            if (!$db_name) {
                // Fallback vers la base principale
                $db_name = $this->main_config['main_db'];
                $this->logDebug("Aucune base trouvée pour '$subdomain', utilisation de la base principale: $db_name");
            } else {
                $this->logDebug("Base trouvée dynamiquement pour '$subdomain': $db_name");
            }
        }
        
        return [
            'host' => $this->main_config['host'],
            'port' => $this->main_config['port'],
            'user' => $this->main_config['user'],
            'pass' => $this->main_config['pass'],
            'dbname' => $db_name,
            'subdomain' => $subdomain
        ];
    }
    
    /**
     * Chercher la base de données correspondant au sous-domaine dans la table shops
     */
    private function findDatabaseBySubdomain($subdomain) {
        try {
            $dsn = "mysql:host={$this->main_config['host']};port={$this->main_config['port']};dbname={$this->main_config['main_db']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->main_config['user'], $this->main_config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $stmt = $pdo->prepare("SELECT db_name FROM shops WHERE subdomain = ? AND active = 1 LIMIT 1");
            $stmt->execute([$subdomain]);
            $result = $stmt->fetch();
            
            if ($result) {
                return $result['db_name'];
            }
            
            // Essayer aussi par nom (fallback)
            $stmt = $pdo->prepare("SELECT db_name FROM shops WHERE LOWER(name) LIKE ? AND active = 1 LIMIT 1");
            $stmt->execute(['%' . $subdomain . '%']);
            $result = $stmt->fetch();
            
            return $result ? $result['db_name'] : null;
            
        } catch (PDOException $e) {
            $this->logDebug("Erreur lors de la recherche dynamique: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtenir une connexion PDO configurée pour le sous-domaine actuel
     */
    public function getConnection($subdomain = null) {
        $config = $this->getDatabaseConfig($subdomain);
        
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            $this->logDebug("Connexion réussie à la base: {$config['dbname']} pour le sous-domaine: {$config['subdomain']}");
            
            // Stocker le sous-domaine actuel en session
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['current_subdomain'] = $config['subdomain'];
            $_SESSION['current_database'] = $config['dbname'];
            
            return $pdo;
            
        } catch (PDOException $e) {
            $this->logDebug("Erreur de connexion à {$config['dbname']}: " . $e->getMessage());
            throw new PDOException("Impossible de se connecter à la base de données du magasin: " . $e->getMessage());
        }
    }
    
    /**
     * Obtenir les informations du magasin actuel
     */
    public function getCurrentShopInfo() {
        $subdomain = $this->detectSubdomain();
        
        try {
            $dsn = "mysql:host={$this->main_config['host']};port={$this->main_config['port']};dbname={$this->main_config['main_db']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->main_config['user'], $this->main_config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $stmt = $pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1 LIMIT 1");
            $stmt->execute([$subdomain]);
            $shop = $stmt->fetch();
            
            if (!$shop && $subdomain) {
                // Essayer par nom
                $stmt = $pdo->prepare("SELECT * FROM shops WHERE LOWER(name) LIKE ? AND active = 1 LIMIT 1");
                $stmt->execute(['%' . $subdomain . '%']);
                $shop = $stmt->fetch();
            }
            
            return $shop;
            
        } catch (PDOException $e) {
            $this->logDebug("Erreur lors de la récupération des infos magasin: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Tester toutes les connexions disponibles
     */
    public function testAllConnections() {
        $results = [];
        
        foreach ($this->subdomain_mappings as $subdomain => $db_name) {
            try {
                $config = $this->getDatabaseConfig($subdomain);
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
                $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $results[$subdomain] = ['status' => 'OK', 'database' => $config['dbname']];
            } catch (PDOException $e) {
                $results[$subdomain] = ['status' => 'ERREUR', 'database' => $config['dbname'], 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    /**
     * Ajouter un nouveau magasin dynamiquement
     */
    public function addShop($name, $subdomain, $database_name = null) {
        if (!$database_name) {
            $database_name = 'geekboard_' . strtolower($subdomain);
        }
        
        try {
            $dsn = "mysql:host={$this->main_config['host']};port={$this->main_config['port']};dbname={$this->main_config['main_db']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->main_config['user'], $this->main_config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $stmt = $pdo->prepare("INSERT INTO shops (name, subdomain, db_host, db_port, db_name, db_user, db_pass, active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $name,
                $subdomain,
                $this->main_config['host'],
                $this->main_config['port'],
                $database_name,
                $this->main_config['user'],
                $this->main_config['pass']
            ]);
            
            $this->logDebug("Nouveau magasin ajouté: $name ($subdomain) -> $database_name");
            return true;
            
        } catch (PDOException $e) {
            $this->logDebug("Erreur lors de l'ajout du magasin: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log de débogage
     */
    private function logDebug($message) {
        $debug_enabled = true; // Activer/désactiver selon l'environnement
        
        if ($debug_enabled) {
            $timestamp = date('Y-m-d H:i:s');
            error_log("[SubdomainDetector] [$timestamp] $message");
        }
    }
}

// Instance globale pour faciliter l'utilisation
$subdomain_detector = new SubdomainDatabaseDetector();

/**
 * Fonction helper pour obtenir une connexion dynamique
 */
function getShopConnection($subdomain = null) {
    global $subdomain_detector;
    return $subdomain_detector->getConnection($subdomain);
}

/**
 * Fonction helper pour obtenir la configuration du magasin actuel
 */
function getCurrentShopConfig() {
    global $subdomain_detector;
    return $subdomain_detector->getDatabaseConfig();
}

/**
 * Fonction helper pour obtenir les informations du magasin actuel
 */
function getCurrentShop() {
    global $subdomain_detector;
    return $subdomain_detector->getCurrentShopInfo();
}
?> 
/**
 * Détecteur automatique de base de données basé sur les sous-domaines
 * Système multi-magasin GeekBoard
 */

class SubdomainDatabaseDetector {
    
    private $main_config = [
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'root',
        'pass' => 'Mamanmaman01#',
        'main_db' => 'geekboard_general'
    ];
    
    private $subdomain_mappings = [
        'cannesphones' => 'geekboard_cannesphones',
        'pscannes' => 'geekboard_pscannes', 
        'psphonac' => 'geekboard_psphonac',
        'mdgeek' => 'geekboard_general',
        'www' => 'geekboard_general',
        '' => 'geekboard_general' // domaine principal sans sous-domaine
    ];
    
    /**
     * Détecter le sous-domaine actuel
     */
    public function detectSubdomain() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Nettoyer le host
        $host = strtolower(trim($host));
        
        // Cas spéciaux pour le développement
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            // En local, vérifier s'il y a un paramètre shop_id ou subdomain
            $subdomain = $_GET['subdomain'] ?? $_SESSION['current_subdomain'] ?? 'mdgeek';
            $this->logDebug("Mode local détecté, sous-domaine: $subdomain");
            return $subdomain;
        }
        
        // Extraire le sous-domaine du host
        $parts = explode('.', $host);
        
        if (count($parts) >= 3) {
            // Sous-domaine présent (ex: cannesphones.mdgeek.top)
            $subdomain = $parts[0];
        } elseif (count($parts) == 2) {
            // Domaine principal (ex: mdgeek.top)
            $subdomain = '';
        } else {
            // Cas par défaut
            $subdomain = '';
        }
        
        $this->logDebug("Host: $host, Sous-domaine détecté: '$subdomain'");
        return $subdomain;
    }
    
    /**
     * Obtenir la configuration de base de données pour le sous-domaine
     */
    public function getDatabaseConfig($subdomain = null) {
        if ($subdomain === null) {
            $subdomain = $this->detectSubdomain();
        }
        
        // Normaliser le sous-domaine
        $subdomain = strtolower(trim($subdomain));
        
        // Vérifier d'abord dans les mappings statiques
        if (isset($this->subdomain_mappings[$subdomain])) {
            $db_name = $this->subdomain_mappings[$subdomain];
            $this->logDebug("Mapping statique trouvé pour '$subdomain': $db_name");
        } else {
            // Chercher dynamiquement dans la base principale
            $db_name = $this->findDatabaseBySubdomain($subdomain);
            if (!$db_name) {
                // Fallback vers la base principale
                $db_name = $this->main_config['main_db'];
                $this->logDebug("Aucune base trouvée pour '$subdomain', utilisation de la base principale: $db_name");
            } else {
                $this->logDebug("Base trouvée dynamiquement pour '$subdomain': $db_name");
            }
        }
        
        return [
            'host' => $this->main_config['host'],
            'port' => $this->main_config['port'],
            'user' => $this->main_config['user'],
            'pass' => $this->main_config['pass'],
            'dbname' => $db_name,
            'subdomain' => $subdomain
        ];
    }
    
    /**
     * Chercher la base de données correspondant au sous-domaine dans la table shops
     */
    private function findDatabaseBySubdomain($subdomain) {
        try {
            $dsn = "mysql:host={$this->main_config['host']};port={$this->main_config['port']};dbname={$this->main_config['main_db']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->main_config['user'], $this->main_config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $stmt = $pdo->prepare("SELECT db_name FROM shops WHERE subdomain = ? AND active = 1 LIMIT 1");
            $stmt->execute([$subdomain]);
            $result = $stmt->fetch();
            
            if ($result) {
                return $result['db_name'];
            }
            
            // Essayer aussi par nom (fallback)
            $stmt = $pdo->prepare("SELECT db_name FROM shops WHERE LOWER(name) LIKE ? AND active = 1 LIMIT 1");
            $stmt->execute(['%' . $subdomain . '%']);
            $result = $stmt->fetch();
            
            return $result ? $result['db_name'] : null;
            
        } catch (PDOException $e) {
            $this->logDebug("Erreur lors de la recherche dynamique: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtenir une connexion PDO configurée pour le sous-domaine actuel
     */
    public function getConnection($subdomain = null) {
        $config = $this->getDatabaseConfig($subdomain);
        
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            $this->logDebug("Connexion réussie à la base: {$config['dbname']} pour le sous-domaine: {$config['subdomain']}");
            
            // Stocker le sous-domaine actuel en session
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['current_subdomain'] = $config['subdomain'];
            $_SESSION['current_database'] = $config['dbname'];
            
            return $pdo;
            
        } catch (PDOException $e) {
            $this->logDebug("Erreur de connexion à {$config['dbname']}: " . $e->getMessage());
            throw new PDOException("Impossible de se connecter à la base de données du magasin: " . $e->getMessage());
        }
    }
    
    /**
     * Obtenir les informations du magasin actuel
     */
    public function getCurrentShopInfo() {
        $subdomain = $this->detectSubdomain();
        
        try {
            $dsn = "mysql:host={$this->main_config['host']};port={$this->main_config['port']};dbname={$this->main_config['main_db']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->main_config['user'], $this->main_config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $stmt = $pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1 LIMIT 1");
            $stmt->execute([$subdomain]);
            $shop = $stmt->fetch();
            
            if (!$shop && $subdomain) {
                // Essayer par nom
                $stmt = $pdo->prepare("SELECT * FROM shops WHERE LOWER(name) LIKE ? AND active = 1 LIMIT 1");
                $stmt->execute(['%' . $subdomain . '%']);
                $shop = $stmt->fetch();
            }
            
            return $shop;
            
        } catch (PDOException $e) {
            $this->logDebug("Erreur lors de la récupération des infos magasin: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Tester toutes les connexions disponibles
     */
    public function testAllConnections() {
        $results = [];
        
        foreach ($this->subdomain_mappings as $subdomain => $db_name) {
            try {
                $config = $this->getDatabaseConfig($subdomain);
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
                $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $results[$subdomain] = ['status' => 'OK', 'database' => $config['dbname']];
            } catch (PDOException $e) {
                $results[$subdomain] = ['status' => 'ERREUR', 'database' => $config['dbname'], 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    /**
     * Ajouter un nouveau magasin dynamiquement
     */
    public function addShop($name, $subdomain, $database_name = null) {
        if (!$database_name) {
            $database_name = 'geekboard_' . strtolower($subdomain);
        }
        
        try {
            $dsn = "mysql:host={$this->main_config['host']};port={$this->main_config['port']};dbname={$this->main_config['main_db']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->main_config['user'], $this->main_config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $stmt = $pdo->prepare("INSERT INTO shops (name, subdomain, db_host, db_port, db_name, db_user, db_pass, active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $name,
                $subdomain,
                $this->main_config['host'],
                $this->main_config['port'],
                $database_name,
                $this->main_config['user'],
                $this->main_config['pass']
            ]);
            
            $this->logDebug("Nouveau magasin ajouté: $name ($subdomain) -> $database_name");
            return true;
            
        } catch (PDOException $e) {
            $this->logDebug("Erreur lors de l'ajout du magasin: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log de débogage
     */
    private function logDebug($message) {
        $debug_enabled = true; // Activer/désactiver selon l'environnement
        
        if ($debug_enabled) {
            $timestamp = date('Y-m-d H:i:s');
            error_log("[SubdomainDetector] [$timestamp] $message");
        }
    }
}

// Instance globale pour faciliter l'utilisation
$subdomain_detector = new SubdomainDatabaseDetector();

/**
 * Fonction helper pour obtenir une connexion dynamique
 */
function getShopConnection($subdomain = null) {
    global $subdomain_detector;
    return $subdomain_detector->getConnection($subdomain);
}

/**
 * Fonction helper pour obtenir la configuration du magasin actuel
 */
function getCurrentShopConfig() {
    global $subdomain_detector;
    return $subdomain_detector->getDatabaseConfig();
}

/**
 * Fonction helper pour obtenir les informations du magasin actuel
 */
function getCurrentShop() {
    global $subdomain_detector;
    return $subdomain_detector->getCurrentShopInfo();
}
?> 