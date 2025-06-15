<?php
// Fichier de test pour les sous-domaines
header('Content-Type: text/plain');

echo "=== Test de détection de sous-domaine ===\n\n";

echo "Nom d'hôte (HTTP_HOST): " . ($_SERVER['HTTP_HOST'] ?? 'Non défini') . "\n";
echo "Nom du serveur (SERVER_NAME): " . ($_SERVER['SERVER_NAME'] ?? 'Non défini') . "\n";
echo "Variables d'environnement définies par mod_rewrite:\n";
echo "SUBDOMAIN: " . ($_SERVER['SUBDOMAIN'] ?? 'Non défini') . "\n\n";

// Essai de détection du sous-domaine manuellement
$host = $_SERVER['HTTP_HOST'] ?? '';
$domain_base = 'mdgeek.top';

echo "Test manuel de détection de sous-domaine:\n";
if ($host === $domain_base) {
    echo "Pas de sous-domaine détecté (domaine principal)\n";
} else if (strpos($host, $domain_base) !== false) {
    $subdomain = str_replace('.' . $domain_base, '', $host);
    echo "Sous-domaine détecté: $subdomain\n";
} else {
    echo "Domaine non reconnu\n";
}

echo "\n=== Informations sur le serveur ===\n\n";
echo "Version de PHP: " . phpversion() . "\n";
echo "Version du serveur: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Non défini') . "\n";
echo "URL demandée: " . ($_SERVER['REQUEST_URI'] ?? 'Non définie') . "\n";

echo "\n=== Variables serveur ===\n\n";
foreach ($_SERVER as $key => $value) {
    echo "$key: $value\n";
}
?> 