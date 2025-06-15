<?php
// Script pour vider les logs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_files = [
        '../logs/php-errors.log',
        '../logs/ajax_debug.log'
    ];
    
    $cleared = 0;
    foreach ($log_files as $log_file) {
        if (file_exists($log_file) && is_writable($log_file)) {
            file_put_contents($log_file, '');
            $cleared++;
        }
    }
    
    echo "Logs vidés: $cleared fichiers";
} else {
    echo "Méthode non autorisée";
}
?> 