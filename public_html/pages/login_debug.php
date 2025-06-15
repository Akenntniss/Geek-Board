<?php
/**
 * Script de diagnostic des problèmes de connexion
 * 
 * Ce script aide à identifier les problèmes qui peuvent affecter la connexion
 * en testant différents aspects de la configuration
 */

// Afficher les erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Inclure la configuration de la base de données
require_once 'config/database.php';

// Fonction pour journaliser les messages
function logMessage($message, $type = 'info') {
    $class = 'info';
    switch($type) {
        case 'success': $class = 'success'; break;
        case 'warning': $class = 'warning'; break;
        case 'error': $class = 'danger'; break;
        default: $class = 'info';
    }
    echo "<div class='alert alert-$class'>" . htmlspecialchars($message) . "</div>";
    error_log($message);
}

// Fonction pour tester la connexion à une base de données
function testDBConnection($dsn, $user, $pass) {
    try {
        $pdo = new PDO(
            $dsn,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
        
        // Vérifier quelle base de données est utilisée
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch();
        $db_name = $result['db_name'] ?? 'inconnu';
        
        return [
            'success' => true,
            'db_name' => $db_name,
            'pdo' => $pdo
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Variables pour stocker les résultats des tests
$main_db_result = null;
$shop_db_result = null;
$shop_info = null;
$test_users = null;
$selected_shop_id = null;

// Si un magasin est spécifié dans l'URL, l'utiliser
if (isset($_GET['shop_id'])) {
    $_SESSION['shop_id'] = (int)$_GET['shop_id'];
    $selected_shop_id = $_SESSION['shop_id'];
    logMessage("Magasin sélectionné depuis l'URL: ID=" . $selected_shop_id, 'info');
} elseif (isset($_SESSION['shop_id'])) {
    $selected_shop_id = $_SESSION['shop_id'];
    logMessage("Magasin trouvé en session: ID=" . $selected_shop_id, 'info');
}

// Tester la connexion à la base principale
$dsn_main = "mysql:host=" . MAIN_DB_HOST . ";port=" . MAIN_DB_PORT . ";dbname=" . MAIN_DB_NAME . ";charset=utf8mb4";
$main_db_result = testDBConnection($dsn_main, MAIN_DB_USER, MAIN_DB_PASS);

if ($main_db_result['success']) {
    logMessage("Connexion à la base principale réussie: " . $main_db_result['db_name'], 'success');
    
    // Récupérer la liste des magasins depuis la base principale
    try {
        $shops = $main_db_result['pdo']->query("SELECT id, name, db_host, db_name, db_user FROM shops WHERE active = 1 ORDER BY name")->fetchAll();
        
        if (count($shops) === 0) {
            logMessage("Aucun magasin actif trouvé dans la base principale.", 'warning');
        } else {
            logMessage("Nombre de magasins actifs trouvés: " . count($shops), 'success');
        }
    } catch (PDOException $e) {
        logMessage("Erreur lors de la récupération des magasins: " . $e->getMessage(), 'error');
    }
    
    // Si un magasin est sélectionné, récupérer ses informations
    if ($selected_shop_id) {
        try {
            $stmt = $main_db_result['pdo']->prepare("SELECT * FROM shops WHERE id = ?");
            $stmt->execute([$selected_shop_id]);
            $shop_info = $stmt->fetch();
            
            if ($shop_info) {
                logMessage("Informations du magasin récupérées: " . $shop_info['name'] . " (DB: " . $shop_info['db_name'] . ")", 'success');
                
                // Tester la connexion à la base de données du magasin
                $dsn_shop = "mysql:host=" . $shop_info['db_host'] . ";port=" . ($shop_info['db_port'] ?? '3306') . ";dbname=" . $shop_info['db_name'] . ";charset=utf8mb4";
                $shop_db_result = testDBConnection($dsn_shop, $shop_info['db_user'], $shop_info['db_pass']);
                
                if ($shop_db_result['success']) {
                    logMessage("Connexion à la base du magasin réussie: " . $shop_db_result['db_name'], 'success');
                    
                    // Vérifier que les tables nécessaires existent
                    try {
                        $tables_stmt = $shop_db_result['pdo']->query("SHOW TABLES");
                        $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (in_array('users', $tables)) {
                            logMessage("Table 'users' trouvée dans la base du magasin", 'success');
                            
                            // Récupérer les utilisateurs
                            $users_stmt = $shop_db_result['pdo']->query("SELECT id, username, role, active FROM users");
                            $test_users = $users_stmt->fetchAll();
                            
                            if (count($test_users) === 0) {
                                logMessage("Aucun utilisateur trouvé dans la table 'users'", 'warning');
                            } else {
                                logMessage("Nombre d'utilisateurs trouvés: " . count($test_users), 'success');
                                
                                // Vérifier si un utilisateur Admin existe
                                $admin_found = false;
                                foreach ($test_users as $user) {
                                    if ($user['username'] === 'Admin') {
                                        $admin_found = true;
                                        break;
                                    }
                                }
                                
                                if ($admin_found) {
                                    logMessage("Utilisateur 'Admin' trouvé dans la base du magasin", 'success');
                                } else {
                                    logMessage("Utilisateur 'Admin' non trouvé dans la base du magasin", 'warning');
                                }
                            }
                        } else {
                            logMessage("Table 'users' non trouvée dans la base du magasin", 'error');
                        }
                    } catch (PDOException $e) {
                        logMessage("Erreur lors de la vérification des tables: " . $e->getMessage(), 'error');
                    }
                } else {
                    logMessage("Échec de connexion à la base du magasin: " . $shop_db_result['message'], 'error');
                }
            } else {
                logMessage("Magasin non trouvé avec l'ID: " . $selected_shop_id, 'error');
            }
        } catch (PDOException $e) {
            logMessage("Erreur lors de la récupération des informations du magasin: " . $e->getMessage(), 'error');
        }
    }
    
    // Tester getShopDBConnection()
    logMessage("Test de la fonction getShopDBConnection()", 'info');
    try {
        $shop_pdo = getShopDBConnection();
        
        if ($shop_pdo === null) {
            logMessage("La fonction getShopDBConnection() a retourné NULL", 'error');
        } else {
            $stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
            $current_db = $stmt->fetch(PDO::FETCH_ASSOC)['current_db'];
            
            logMessage("getShopDBConnection() connecté à: " . $current_db, 'success');
            
            if ($shop_info && $current_db != $shop_info['db_name']) {
                logMessage("ATTENTION: getShopDBConnection() n'utilise pas la base de données du magasin sélectionné!", 'warning');
            }
        }
    } catch (Exception $e) {
        logMessage("Erreur lors du test de getShopDBConnection(): " . $e->getMessage(), 'error');
    }
} else {
    logMessage("Échec de connexion à la base principale: " . $main_db_result['message'], 'error');
}

// Tester l'état de la session
logMessage("Variables de session actuelles:", 'info');
$session_data = [
    'user_id' => $_SESSION['user_id'] ?? 'non défini',
    'username' => $_SESSION['username'] ?? 'non défini',
    'shop_id' => $_SESSION['shop_id'] ?? 'non défini',
    'shop_name' => $_SESSION['shop_name'] ?? 'non défini',
    'superadmin_mode' => $_SESSION['superadmin_mode'] ?? false,
    'superadmin_id' => $_SESSION['superadmin_id'] ?? 'non défini'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic de connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 30px;
            background-color: #f8f9fa;
        }
        .diagnostic-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .shop-card {
            border-radius: 5px;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.2s;
        }
        .shop-card:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }
        .user-table {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container diagnostic-container">
        <h1 class="text-center mb-4">Diagnostic de connexion</h1>
        
        <h3 class="mt-4 mb-3">Variables de session</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Variable</th>
                        <th>Valeur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($session_data as $key => $value): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                        <td>
                            <?php 
                            if (is_bool($value)) {
                                echo $value ? 'true' : 'false';
                            } else {
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <h3 class="mt-4 mb-3">Connexion aux bases de données</h3>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Base de données principale</h5>
                <?php if ($main_db_result['success']): ?>
                <span class="badge bg-success">Connecté</span>
                <?php else: ?>
                <span class="badge bg-danger">Échec</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($main_db_result['success']): ?>
                <p><strong>Base connectée:</strong> <?php echo htmlspecialchars($main_db_result['db_name']); ?></p>
                <?php else: ?>
                <p class="text-danger"><strong>Erreur:</strong> <?php echo htmlspecialchars($main_db_result['message']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($shop_info): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Base de données du magasin</h5>
                <?php if ($shop_db_result && $shop_db_result['success']): ?>
                <span class="badge bg-success">Connecté</span>
                <?php else: ?>
                <span class="badge bg-danger">Échec</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p><strong>Magasin:</strong> <?php echo htmlspecialchars($shop_info['name']); ?> (ID: <?php echo $shop_info['id']; ?>)</p>
                <p><strong>Host:</strong> <?php echo htmlspecialchars($shop_info['db_host']); ?></p>
                <p><strong>Base:</strong> <?php echo htmlspecialchars($shop_info['db_name']); ?></p>
                <p><strong>Utilisateur DB:</strong> <?php echo htmlspecialchars($shop_info['db_user']); ?></p>
                
                <?php if ($shop_db_result && $shop_db_result['success']): ?>
                <p><strong>Base connectée:</strong> <?php echo htmlspecialchars($shop_db_result['db_name']); ?></p>
                <?php else: ?>
                <p class="text-danger"><strong>Erreur:</strong> <?php echo $shop_db_result ? htmlspecialchars($shop_db_result['message']) : 'Connexion non testée'; ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($test_users): ?>
        <h3 class="mt-4 mb-3">Utilisateurs disponibles</h3>
        <div class="table-responsive">
            <table class="table table-striped user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($test_users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <?php if ($user['active'] == 1): ?>
                            <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Inactif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if (isset($shops) && count($shops) > 0): ?>
        <h3 class="mt-4 mb-3">Magasins disponibles</h3>
        <div class="shop-list">
            <?php foreach ($shops as $shop): ?>
            <a href="?shop_id=<?php echo $shop['id']; ?>" class="text-decoration-none">
                <div class="shop-card <?php echo ($selected_shop_id == $shop['id']) ? 'bg-light border-primary' : ''; ?>">
                    <h5 class="mb-1"><?php echo htmlspecialchars($shop['name']); ?></h5>
                    <div class="small text-muted">
                        ID: <?php echo $shop['id']; ?> | 
                        Base: <?php echo htmlspecialchars($shop['db_name']); ?> |
                        Host: <?php echo htmlspecialchars($shop['db_host']); ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="mt-4 d-flex flex-column gap-2">
            <a href="reset_admin_password.php" class="btn btn-outline-danger">Réinitialiser mot de passe Admin (magasin)</a>
            <a href="reset_superadmin_password.php" class="btn btn-outline-danger">Réinitialiser mot de passe Superadmin</a>
            <a href="pages/login.php" class="btn btn-primary">Retour à la page de connexion</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 