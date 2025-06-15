<?php
/**
 * Configuration pour la page clients optimisée
 * Centralise tous les paramètres modifiables
 */

// Configuration de pagination
define('CLIENTS_PER_PAGE', 20);
define('CLIENTS_MAX_PER_PAGE', 100);

// Configuration de recherche
define('CLIENTS_MIN_SEARCH_LENGTH', 2);
define('CLIENTS_SEARCH_DEBOUNCE_MS', 500);

// Configuration de tri
define('CLIENTS_DEFAULT_SORT_FIELD', 'nom');
define('CLIENTS_DEFAULT_SORT_ORDER', 'ASC');

// Champs autorisés pour le tri (sécurité)
define('CLIENTS_ALLOWED_SORT_FIELDS', [
    'id',
    'nom', 
    'prenom', 
    'telephone', 
    'email', 
    'date_creation', 
    'nombre_reparations'
]);

// Statuts des réparations pour les badges
define('CLIENTS_REPAIR_STATUS_COLORS', [
    'termine' => 'success',
    'livre' => 'success',
    'annule' => 'danger',
    'refuse' => 'danger',
    'en_cours_diagnostique' => 'primary',
    'en_cours_intervention' => 'primary',
    'en_attente_accord_client' => 'warning',
    'en_attente_livraison' => 'warning',
    'en_attente_responsable' => 'warning',
    'default' => 'secondary'
]);

// Configuration des colonnes responsives
define('CLIENTS_RESPONSIVE_COLUMNS', [
    'id' => ['hidden' => ['xs', 'sm', 'md'], 'visible' => ['lg', 'xl']],
    'nom' => ['hidden' => [], 'visible' => ['xs', 'sm', 'md', 'lg', 'xl']],
    'prenom' => ['hidden' => ['xs', 'sm'], 'visible' => ['md', 'lg', 'xl']],
    'telephone' => ['hidden' => ['xs', 'sm', 'md'], 'visible' => ['lg', 'xl']],
    'email' => ['hidden' => ['xs', 'sm', 'md'], 'visible' => ['lg', 'xl']],
    'reparations' => ['hidden' => [], 'visible' => ['xs', 'sm', 'md', 'lg', 'xl']],
    'date_creation' => ['hidden' => ['xs', 'sm', 'md', 'lg'], 'visible' => ['xl']],
    'actions' => ['hidden' => [], 'visible' => ['xs', 'sm', 'md', 'lg', 'xl']]
]);

// Messages d'interface utilisateur
define('CLIENTS_UI_MESSAGES', [
    'no_results' => 'Aucun résultat trouvé',
    'no_clients' => 'Aucun client enregistré',
    'search_placeholder' => 'Nom, prénom, téléphone ou email...',
    'search_help' => 'Tapez au moins 2 caractères pour rechercher',
    'loading' => 'Chargement...',
    'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer ce client ?',
    'cannot_delete_with_repairs' => 'Impossible de supprimer ce client car il a des réparations associées.',
    'delete_success' => 'Client supprimé avec succès.',
    'delete_error' => 'Erreur lors de la suppression du client.',
    'unauthorized_action' => 'Action non autorisée.',
    'loading_history' => 'Chargement de l\'historique...',
    'no_repairs' => 'Ce client n\'a pas encore de réparations enregistrées.'
]);

// Configuration AJAX
define('CLIENTS_AJAX_TIMEOUT', 30000); // 30 secondes
define('CLIENTS_AJAX_RETRY_COUNT', 3);
define('CLIENTS_AJAX_ENDPOINTS', [
    'history' => 'ajax/get_client_history.php',
    'search' => 'ajax/search_clients.php',
    'delete' => 'ajax/delete_client.php'
]);

// Configuration de sécurité
define('CLIENTS_CSRF_TOKEN_LENGTH', 32);
define('CLIENTS_MAX_SEARCH_TERMS', 10);
define('CLIENTS_RATE_LIMIT_REQUESTS', 100); // par minute
define('CLIENTS_RATE_LIMIT_WINDOW', 60); // secondes

// Configuration de performance
define('CLIENTS_CACHE_TTL', 300); // 5 minutes
define('CLIENTS_ENABLE_QUERY_CACHE', true);
define('CLIENTS_LOG_SLOW_QUERIES', true);
define('CLIENTS_SLOW_QUERY_THRESHOLD', 1.0); // 1 seconde

// Configuration d'export
define('CLIENTS_EXPORT_FORMATS', ['pdf', 'excel', 'csv']);
define('CLIENTS_EXPORT_MAX_ROWS', 10000);
define('CLIENTS_EXPORT_FILENAME_PREFIX', 'clients_geekboard_');

// Configuration de notification
define('CLIENTS_NOTIFICATION_TYPES', [
    'new_client' => ['enabled' => true, 'delay' => 0],
    'client_updated' => ['enabled' => true, 'delay' => 2000],
    'client_deleted' => ['enabled' => true, 'delay' => 3000]
]);

/**
 * Fonctions utilitaires pour la configuration
 */

/**
 * Obtient la couleur du badge pour un statut de réparation
 */
function getRepairStatusColor($status) {
    $colors = CLIENTS_REPAIR_STATUS_COLORS;
    return $colors[$status] ?? $colors['default'];
}

/**
 * Vérifie si un champ de tri est autorisé
 */
function isValidSortField($field) {
    return in_array($field, CLIENTS_ALLOWED_SORT_FIELDS);
}

/**
 * Obtient la configuration responsive pour une colonne
 */
function getColumnResponsiveClass($column) {
    $config = CLIENTS_RESPONSIVE_COLUMNS[$column] ?? null;
    if (!$config) return '';
    
    $classes = [];
    
    // Classes pour cacher sur certaines tailles
    foreach ($config['hidden'] as $size) {
        switch ($size) {
            case 'xs':
                $classes[] = 'd-none';
                break;
            case 'sm':
                $classes[] = 'd-sm-none';
                break;
            case 'md':
                $classes[] = 'd-md-none';
                break;
            case 'lg':
                $classes[] = 'd-lg-none';
                break;
            case 'xl':
                $classes[] = 'd-xl-none';
                break;
        }
    }
    
    // Classes pour afficher sur certaines tailles
    foreach ($config['visible'] as $size) {
        switch ($size) {
            case 'xs':
                $classes[] = 'd-block';
                break;
            case 'sm':
                $classes[] = 'd-sm-table-cell';
                break;
            case 'md':
                $classes[] = 'd-md-table-cell';
                break;
            case 'lg':
                $classes[] = 'd-lg-table-cell';
                break;
            case 'xl':
                $classes[] = 'd-xl-table-cell';
                break;
        }
    }
    
    return implode(' ', array_unique($classes));
}

/**
 * Génère un token CSRF sécurisé
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CLIENTS_CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valide un token CSRF
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtient un message d'interface utilisateur
 */
function getUIMessage($key, $default = '') {
    $messages = CLIENTS_UI_MESSAGES;
    return $messages[$key] ?? $default;
}

/**
 * Vérifie la limite de taux pour éviter le spam
 */
function checkRateLimit($identifier) {
    $cache_key = "rate_limit_clients_{$identifier}";
    $current_time = time();
    $window_start = $current_time - CLIENTS_RATE_LIMIT_WINDOW;
    
    // Récupérer les requêtes existantes (simulation avec session)
    if (!isset($_SESSION[$cache_key])) {
        $_SESSION[$cache_key] = [];
    }
    
    // Nettoyer les anciennes entrées
    $_SESSION[$cache_key] = array_filter($_SESSION[$cache_key], function($timestamp) use ($window_start) {
        return $timestamp > $window_start;
    });
    
    // Vérifier la limite
    if (count($_SESSION[$cache_key]) >= CLIENTS_RATE_LIMIT_REQUESTS) {
        return false;
    }
    
    // Ajouter la requête actuelle
    $_SESSION[$cache_key][] = $current_time;
    return true;
}

/**
 * Logs une requête lente si activé
 */
function logSlowQuery($query, $execution_time) {
    if (CLIENTS_LOG_SLOW_QUERIES && $execution_time > CLIENTS_SLOW_QUERY_THRESHOLD) {
        error_log("SLOW QUERY - Clients page: {$execution_time}s - Query: " . substr($query, 0, 200));
    }
}

/**
 * Configuration de développement/debug
 */
define('CLIENTS_DEBUG_MODE', false);
define('CLIENTS_ENABLE_PROFILING', false);

if (CLIENTS_DEBUG_MODE) {
    // Logs détaillés en mode debug
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

/**
 * Fonction de debug pour développement
 */
function clientsDebugLog($message) {
    if (CLIENTS_DEBUG_MODE) {
        error_log("[CLIENTS DEBUG] " . $message);
    }
}

/**
 * Profiling des performances si activé
 */
function clientsStartProfiling() {
    if (CLIENTS_ENABLE_PROFILING) {
        return microtime(true);
    }
    return null;
}

function clientsEndProfiling($start_time, $operation) {
    if (CLIENTS_ENABLE_PROFILING && $start_time) {
        $execution_time = microtime(true) - $start_time;
        error_log("[CLIENTS PROFILING] {$operation}: " . round($execution_time * 1000, 2) . "ms");
    }
}
?> 