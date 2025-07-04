<?php
/**
 * Configuration du Gestionnaire de Base de Données
 * Ce fichier contient les paramètres de configuration pour l'interface de gestion de BDD
 */

// Configuration générale
define('DB_MANAGER_VERSION', '1.0.0');
define('DB_MANAGER_PAGE_SIZE', 50); // Nombre de lignes par page
define('DB_MANAGER_MAX_EXPORT_ROWS', 10000); // Limite pour l'export
define('DB_MANAGER_QUERY_TIMEOUT', 30); // Timeout des requêtes en secondes

// Sécurité
define('DB_MANAGER_ENABLE_DANGEROUS_QUERIES', true); // Autoriser les requêtes dangereuses
define('DB_MANAGER_REQUIRE_CONFIRMATION', true); // Exiger confirmation pour requêtes dangereuses
define('DB_MANAGER_LOG_QUERIES', true); // Logger les requêtes exécutées

// Interface utilisateur
define('DB_MANAGER_THEME', 'default'); // Thème de l'éditeur SQL
define('DB_MANAGER_AUTO_SAVE', true); // Sauvegarde automatique des requêtes
define('DB_MANAGER_SHOW_TOOLTIPS', true); // Afficher les tooltips d'aide

// Configuration des exports
$DB_MANAGER_EXPORT_FORMATS = [
    'csv' => [
        'name' => 'CSV',
        'extension' => 'csv',
        'mime_type' => 'text/csv',
        'enabled' => true
    ],
    'json' => [
        'name' => 'JSON',
        'extension' => 'json',
        'mime_type' => 'application/json',
        'enabled' => false // Désactivé pour l'instant
    ],
    'xml' => [
        'name' => 'XML',
        'extension' => 'xml',
        'mime_type' => 'application/xml',
        'enabled' => false // Désactivé pour l'instant
    ]
];

// Mots-clés dangereux (en plus de ceux par défaut)
$DB_MANAGER_DANGEROUS_KEYWORDS = [
    'DROP',
    'DELETE',
    'TRUNCATE',
    'ALTER',
    'CREATE',
    'INSERT',
    'UPDATE',
    'GRANT',
    'REVOKE',
    'FLUSH',
    'RESET',
    'KILL'
];

// Tables système à masquer (optionnel)
$DB_MANAGER_HIDDEN_TABLES = [
    'information_schema',
    'performance_schema',
    'mysql',
    'sys'
];

// Configuration de l'éditeur SQL
$DB_MANAGER_EDITOR_CONFIG = [
    'theme' => 'default',
    'fontSize' => 14,
    'tabSize' => 2,
    'lineNumbers' => true,
    'wordWrap' => true,
    'autoCloseBrackets' => true,
    'matchBrackets' => true,
    'highlightSelectionMatches' => true,
    'foldGutter' => true
];

// Permissions par rôle (extensible)
$DB_MANAGER_PERMISSIONS = [
    'superadmin' => [
        'can_view_data' => true,
        'can_execute_queries' => true,
        'can_export_data' => true,
        'can_view_structure' => true,
        'can_execute_dangerous_queries' => true
    ],
    'admin' => [
        'can_view_data' => true,
        'can_execute_queries' => true,
        'can_export_data' => true,
        'can_view_structure' => true,
        'can_execute_dangerous_queries' => false
    ]
];

// Configuration des logs
$DB_MANAGER_LOG_CONFIG = [
    'enabled' => true,
    'file' => '../logs/database_manager.log',
    'max_size' => 10 * 1024 * 1024, // 10MB
    'log_level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
    'include_user_info' => true,
    'include_query_params' => false // Pour la sécurité
];

// Messages personnalisés
$DB_MANAGER_MESSAGES = [
    'fr' => [
        'no_shop_selected' => 'Veuillez sélectionner un magasin',
        'connection_error' => 'Erreur de connexion à la base de données',
        'query_executed' => 'Requête exécutée avec succès',
        'dangerous_query_warning' => 'Cette requête contient des mots-clés potentiellement dangereux',
        'export_started' => 'Export en cours...',
        'export_completed' => 'Export terminé',
        'no_results' => 'Aucun résultat trouvé',
        'table_empty' => 'Cette table ne contient aucune donnée'
    ]
];

// Fonctions utilitaires
class DatabaseManagerConfig {
    
    /**
     * Obtient la configuration de l'éditeur
     */
    public static function getEditorConfig() {
        global $DB_MANAGER_EDITOR_CONFIG;
        return $DB_MANAGER_EDITOR_CONFIG;
    }
    
    /**
     * Vérifie si un format d'export est activé
     */
    public static function isExportFormatEnabled($format) {
        global $DB_MANAGER_EXPORT_FORMATS;
        return isset($DB_MANAGER_EXPORT_FORMATS[$format]) && 
               $DB_MANAGER_EXPORT_FORMATS[$format]['enabled'];
    }
    
    /**
     * Obtient les formats d'export disponibles
     */
    public static function getAvailableExportFormats() {
        global $DB_MANAGER_EXPORT_FORMATS;
        return array_filter($DB_MANAGER_EXPORT_FORMATS, function($format) {
            return $format['enabled'];
        });
    }
    
    /**
     * Vérifie si un mot-clé est dangereux
     */
    public static function isDangerousKeyword($keyword) {
        global $DB_MANAGER_DANGEROUS_KEYWORDS;
        return in_array(strtoupper($keyword), $DB_MANAGER_DANGEROUS_KEYWORDS);
    }
    
    /**
     * Vérifie si une table doit être masquée
     */
    public static function isTableHidden($tableName) {
        global $DB_MANAGER_HIDDEN_TABLES;
        return in_array($tableName, $DB_MANAGER_HIDDEN_TABLES);
    }
    
    /**
     * Obtient un message dans la langue spécifiée
     */
    public static function getMessage($key, $lang = 'fr') {
        global $DB_MANAGER_MESSAGES;
        return $DB_MANAGER_MESSAGES[$lang][$key] ?? $key;
    }
    
    /**
     * Vérifie les permissions d'un utilisateur
     */
    public static function hasPermission($role, $permission) {
        global $DB_MANAGER_PERMISSIONS;
        return $DB_MANAGER_PERMISSIONS[$role][$permission] ?? false;
    }
    
    /**
     * Log une action
     */
    public static function logAction($message, $level = 'INFO', $context = []) {
        global $DB_MANAGER_LOG_CONFIG;
        
        if (!$DB_MANAGER_LOG_CONFIG['enabled']) {
            return;
        }
        
        $logFile = $DB_MANAGER_LOG_CONFIG['file'];
        $timestamp = date('Y-m-d H:i:s');
        $userInfo = $DB_MANAGER_LOG_CONFIG['include_user_info'] ? 
                   (' [User: ' . ($_SESSION['superadmin_id'] ?? 'unknown') . ']') : '';
        
        $logEntry = "[$timestamp][$level]$userInfo $message";
        
        if (!empty($context) && $DB_MANAGER_LOG_CONFIG['include_query_params']) {
            $logEntry .= ' Context: ' . json_encode($context);
        }
        
        $logEntry .= PHP_EOL;
        
        // Créer le répertoire de logs s'il n'existe pas
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Rotation des logs si nécessaire
        if (file_exists($logFile) && filesize($logFile) > $DB_MANAGER_LOG_CONFIG['max_size']) {
            rename($logFile, $logFile . '.old');
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Valide une requête SQL
     */
    public static function validateQuery($query) {
        $query = trim($query);
        
        if (empty($query)) {
            return ['valid' => false, 'error' => 'Requête vide'];
        }
        
        // Vérifier les mots-clés dangereux
        $dangerous = false;
        foreach ($GLOBALS['DB_MANAGER_DANGEROUS_KEYWORDS'] as $keyword) {
            if (stripos($query, $keyword) !== false) {
                $dangerous = true;
                break;
            }
        }
        
        return [
            'valid' => true,
            'dangerous' => $dangerous,
            'query' => $query
        ];
    }
    
    /**
     * Formate une valeur pour l'affichage
     */
    public static function formatValue($value, $maxLength = 100) {
        if (is_null($value)) {
            return '<em class="text-muted">NULL</em>';
        }
        
        if (is_bool($value)) {
            return $value ? '<span class="badge bg-success">TRUE</span>' : '<span class="badge bg-danger">FALSE</span>';
        }
        
        if (is_numeric($value)) {
            return '<span class="text-primary">' . number_format($value) . '</span>';
        }
        
        $strValue = (string)$value;
        if (strlen($strValue) > $maxLength) {
            return '<span title="' . htmlspecialchars($strValue) . '">' . 
                   htmlspecialchars(substr($strValue, 0, $maxLength)) . '...</span>';
        }
        
        return htmlspecialchars($strValue);
    }
}

// Initialisation des logs
DatabaseManagerConfig::logAction('Database Manager initialized', 'INFO');
