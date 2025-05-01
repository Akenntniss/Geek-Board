<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/Cache.php';

// Configuration du cache
define('CACHE_ENABLED', true);
define('CACHE_TTL', 3600); // 1 heure par défaut

// Exemple d'utilisation du cache
function getCachedData($key, $callback, $ttl = null) {
    if (!CACHE_ENABLED) {
        return $callback();
    }

    $cache = Cache::getInstance();
    $cachedValue = $cache->get($key);

    if ($cachedValue !== null) {
        return $cachedValue;
    }

    $value = $callback();
    $cache->set($key, $value, $ttl ?? CACHE_TTL);
    return $value;
}

// Exemple d'utilisation:
/*
$data = getCachedData('user_list', function() {
    // Votre requête SQL ou logique coûteuse ici
    return $db->query("SELECT * FROM users")->fetchAll();
}, 1800); // TTL de 30 minutes
*/ 