// Configuration de la base de données
define('DB_HOST', 'srv931.hstgr.io');
define('DB_PORT', '3306');
define('DB_NAME', 'u139954273_Vscodetest');
define('DB_USER', 'u139954273_Vscodetest');
define('DB_PASS', 'Maman01#');

try {
    $shop_pdo = getShopDBConnection();
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
} 