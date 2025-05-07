// Inclure les fichiers nécessaires
require_once 'functions.php';
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/session_manager.php');

// Inclure l'API SMS pour rendre la fonction send_sms disponible globalement
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/sms/send.php');

// Vérifier et nettoyer les variables GET, POST, COOKIE
$_GET = cleanInput($_GET);
$_POST = cleanInput($_POST); 