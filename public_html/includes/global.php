// Inclure les fichiers nécessaires
require_once 'functions.php';
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/session_manager.php');

// Inclure les nouvelles fonctions SMS (migration API)
require_once(__DIR__ . '/sms_functions.php');

// Vérifier et nettoyer les variables GET, POST, COOKIE
$_GET = cleanInput($_GET);
$_POST = cleanInput($_POST); 