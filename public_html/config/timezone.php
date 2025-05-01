<?php
/**
 * Gestionnaire de fuseau horaire
 * 
 * Ce fichier permet d'appliquer le fuseau horaire défini par l'utilisateur
 * dans ses préférences.
 */

// Définir le fuseau horaire par défaut (GMT+0)
date_default_timezone_set('UTC');

// Si l'utilisateur est connecté, récupérer et appliquer son fuseau horaire
if (isset($_SESSION['user_id']) && isset($_SESSION['user_preferences']['timezone_offset'])) {
    // Récupérer le décalage GMT depuis les préférences utilisateur
    $timezone_offset = (int) $_SESSION['user_preferences']['timezone_offset'];
    
    // Construire la chaîne de fuseau horaire au format GMT+X ou GMT-X
    $timezone_string = 'GMT' . ($timezone_offset >= 0 ? '+' : '') . $timezone_offset;
    
    // Appliquer le fuseau horaire
    date_default_timezone_set($timezone_string);
}

/**
 * Fonction pour formater une date selon le fuseau horaire de l'utilisateur
 * 
 * @param string|int $date Date à formater (timestamp Unix ou chaîne de date)
 * @param string $format Format de sortie (par défaut: 'Y-m-d H:i:s')
 * @return string Date formatée
 */
function format_date_user($date, $format = 'Y-m-d H:i:s') {
    // Convertir la date en timestamp si ce n'est pas déjà le cas
    if (!is_numeric($date)) {
        $timestamp = strtotime($date);
    } else {
        $timestamp = $date;
    }
    
    // Formater la date avec le fuseau horaire actuel (celui de l'utilisateur)
    return date($format, $timestamp);
} 