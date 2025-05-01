<?php
/**
 * Script cron pour envoyer les notifications programmées
 * À exécuter toutes les minutes ou toutes les 5 minutes via cron
 * 
 * Exemple de configuration cron (toutes les 5 minutes):
 * Utilisez: 0/5 * * * * php /chemin/vers/send_scheduled_notifications.php
 */

// Désactiver l'affichage des erreurs en production
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Définir le chemin du fichier de log
$logFile = __DIR__ . '/../logs/notifications_cron.log';

// Fonction pour écrire dans le log
function writeLog($message) {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] $message" . PHP_EOL;
    
    // Créer le répertoire de logs s'il n'existe pas
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    // Charger les fichiers nécessaires
    $rootDir = dirname(__DIR__);
    require_once $rootDir . '/config/database.php';
    require_once $rootDir . '/includes/PushNotifications.php';
    
    writeLog("Démarrage du traitement des notifications programmées");
    
    // Initialiser la connexion à la base de données
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Initialiser la classe de notifications push
    $pushNotifications = new PushNotifications($pdo);
    
    // Envoyer toutes les notifications programmées qui sont dues
    $result = $pushNotifications->sendAllScheduledNotifications();
    
    if ($result['success']) {
        if (isset($result['sent_count']) && $result['sent_count'] > 0) {
            writeLog("Succès: {$result['sent_count']} notification(s) programmée(s) envoyée(s)");
        } else {
            writeLog("Aucune notification programmée à envoyer");
        }
    } else {
        writeLog("Erreur: {$result['message']}");
    }
    
    // Nettoyer les anciennes entrées du log si le fichier dépasse 5 Mo
    if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
        $logContent = file_get_contents($logFile);
        $logLines = explode(PHP_EOL, $logContent);
        
        // Garder seulement les 1000 dernières lignes
        if (count($logLines) > 1000) {
            $logLines = array_slice($logLines, -1000);
            file_put_contents($logFile, implode(PHP_EOL, $logLines));
            writeLog("Fichier de log nettoyé, gardé les 1000 dernières lignes");
        }
    }
    
    writeLog("Fin du traitement des notifications programmées");
    
} catch (Exception $e) {
    writeLog("Exception: " . $e->getMessage());
    writeLog("Trace: " . $e->getTraceAsString());
} 