<?php
// Test de sous-domaine simplifié
header('Content-Type: text/plain');

// Informations de l'hôte
$host = $_SERVER['HTTP_HOST'] ?? 'Non défini';
echo "Hôte: $host\n";

// Détection du sous-domaine
if (strpos($host, 'mdgeek.top') !== false) {
    $subdomain = str_replace('.mdgeek.top', '', $host);
    echo "Sous-domaine détecté: $subdomain\n";
} else {
    echo "Aucun sous-domaine mdgeek.top détecté\n";
}

// Afficher d'autres informations utiles
echo "\nINFO SERVEUR:\n";
echo "URI: " . ($_SERVER['REQUEST_URI'] ?? 'Non défini') . "\n";
echo "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Non défini') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Non défini') . "\n";
?> 