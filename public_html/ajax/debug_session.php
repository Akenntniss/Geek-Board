<?php
// Inclure la configuration de session avant de démarrer la session
require_once dirname(__DIR__) . '/config/session_config.php';
// La session est déjà démarrée dans session_config.php

// Configuration des en-têtes
header('Content-Type: application/json');

// Récupérer les informations de session
$session_info = [
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_status' => session_status(),
    'session_data' => $_SESSION,
    'user_id' => $_SESSION['user_id'] ?? null,
    'is_valid' => isset($_SESSION['user_id']),
    'cookies' => $_COOKIE,
    'server' => [
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'request_time' => $_SERVER['REQUEST_TIME'] ?? null,
        'http_host' => $_SERVER['HTTP_HOST'] ?? null,
        'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? null,
        'php_self' => $_SERVER['PHP_SELF'] ?? null,
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? null,
        'https' => isset($_SERVER['HTTPS']) ? 'on' : 'off',
    ],
    'session_save_path' => session_save_path(),
    'session_cookie_params' => session_get_cookie_params(),
    'php_info' => [
        'version' => PHP_VERSION,
        'os' => PHP_OS,
        'sapi' => PHP_SAPI,
        'int_size' => PHP_INT_SIZE,
    ],
    'memory_usage' => memory_get_usage(true),
];

// Ajouter des informations sur l'existence des fichiers de session
if (!empty(session_id())) {
    $session_filename = session_save_path() . '/sess_' . session_id();
    $session_info['session_file'] = [
        'path' => $session_filename,
        'exists' => file_exists($session_filename),
        'size' => file_exists($session_filename) ? filesize($session_filename) : null,
        'permissions' => file_exists($session_filename) ? substr(sprintf('%o', fileperms($session_filename)), -4) : null,
        'contents' => file_exists($session_filename) ? file_get_contents($session_filename) : null,
    ];
}

// Journaliser les informations de session dans les logs
error_log("Debug session info: " . print_r($session_info, true));

// Envoyer les informations sous forme de JSON
echo json_encode($session_info);
?> 