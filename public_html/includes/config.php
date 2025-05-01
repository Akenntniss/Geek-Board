// Configuration de la base de données
define('DB_HOST', 'srv931.hstgr.io');
define('DB_PORT', '3306');
define('DB_NAME', 'u139954273_Vscodetest');
define('DB_USER', 'u139954273_Vscodetest');
define('DB_PASS', 'Maman01#');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
} 