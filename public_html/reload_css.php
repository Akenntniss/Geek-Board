<?php
// Désactiver complètement le cache pour ce fichier
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Chemin vers le fichier CSS de la barre de navigation
$css_file = 'assets/css/bottom-nav.css';

// Vérifier si le fichier existe
if (file_exists($css_file)) {
    // Lire le contenu du fichier CSS
    $css_content = file_get_contents($css_file);
    
    // Envoyer les en-têtes pour indiquer que c'est du CSS
    header('Content-Type: text/css');
    
    // Pour le débogage
    $timestamp = time();
    echo "/* CSS rechargé sans cache à " . date('H:i:s', $timestamp) . " */\n";
    
    // Renvoyer le contenu du fichier CSS
    echo $css_content;
} else {
    // Si le fichier n'existe pas, renvoyer une erreur
    header("HTTP/1.0 404 Not Found");
    echo "/* Erreur: Fichier CSS non trouvé */";
}
?> 