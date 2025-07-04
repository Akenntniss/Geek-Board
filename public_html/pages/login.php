<?php
/**
 * Redirection automatique vers login_auto.php
 * Ce fichier existe uniquement pour la compatibilité avec l'ancien système
 */

// Récupérer tous les paramètres GET
$params = [];
if (!empty($_GET)) {
    $params = http_build_query($_GET);
}

// Construire l'URL de redirection
$redirect_url = '/pages/login_auto.php';
if (!empty($params)) {
    $redirect_url .= '?' . $params;
}

// Redirection permanente (301)
header('HTTP/1.1 301 Moved Permanently');
header('Location: ' . $redirect_url);
exit;
?> 