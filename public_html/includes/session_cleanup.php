<?php
/**
 * Script de nettoyage des performances - Session
 * Supprime les données de debug qui ralentissent l'application
 */

// Démarrer la session si nécessaire
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Nettoyer les messages de debug qui s'accumulent
 */
function cleanupSessionDebug() {
    // Supprimer les messages de debug qui s'accumulent
    if (isset($_SESSION['debug_messages'])) {
        // Garder seulement les 10 derniers messages si on veut garder un minimum
        // Ou complètement supprimer pour de meilleures performances
        unset($_SESSION['debug_messages']); // Suppression complète pour performance
        
        // Alternative: garder seulement les 10 derniers
        // if (count($_SESSION['debug_messages']) > 10) {
        //     $_SESSION['debug_messages'] = array_slice($_SESSION['debug_messages'], -10);
        // }
    }
    
    // Nettoyer d'autres données temporaires si elles existent
    $temp_keys = ['temp_data', 'debug_info', 'diagnostic_data', 'log_entries'];
    foreach ($temp_keys as $key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
}

/**
 * Optimiser la session en supprimant les données inutiles
 */
function optimizeSession() {
    cleanupSessionDebug();
    
    // Sauvegarder la session optimisée
    session_write_close();
    
    // Redémarrer la session pour les prochaines utilisations
    session_start();
}

// Auto-cleanup quand ce fichier est inclus
cleanupSessionDebug(); 