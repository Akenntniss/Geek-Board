<?php
/**
 * Configuration des paramètres de domaine et sous-domaines
 * Ce fichier centralise les paramètres liés aux domaines pour faciliter la maintenance
 */

// Domaine principal de l'application
define('MAIN_DOMAIN', 'mdgeek.top'); // Domaine principal de l'application

// Activer ou désactiver la détection automatique de sous-domaine
define('ENABLE_SUBDOMAIN_DETECTION', true);

// Liste des sous-domaines système à ignorer (ne pas les traiter comme des magasins)
$system_subdomains = ['www', 'api', 'admin', 'dev', 'test'];

/**
 * Vérifie si un sous-domaine est un sous-domaine système à ignorer
 * @param string $subdomain Le sous-domaine à vérifier
 * @return bool True si c'est un sous-domaine système, false sinon
 */
function isSystemSubdomain($subdomain) {
    global $system_subdomains;
    return in_array($subdomain, $system_subdomains);
}

/**
 * Obtient le sous-domaine à partir de l'hôte actuel
 * @return string|null Le sous-domaine ou null si sur le domaine principal
 */
function getCurrentSubdomain() {
    $host = $_SERVER['HTTP_HOST'];
    $main_domain = MAIN_DOMAIN;
    
    // Vérifier si nous sommes sur un sous-domaine
    if ($host !== $main_domain && strpos($host, '.' . $main_domain) !== false) {
        // Extraire le sous-domaine
        return str_replace('.' . $main_domain, '', $host);
    }
    
    return null;
}

/**
 * Construit l'URL complète vers un sous-domaine spécifique
 * @param string $subdomain Le sous-domaine à utiliser
 * @param string $path Le chemin à ajouter après le domaine (doit commencer par /)
 * @return string L'URL complète
 */
function buildSubdomainUrl($subdomain, $path = '/') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $subdomain . '.' . MAIN_DOMAIN . $path;
}
?> 