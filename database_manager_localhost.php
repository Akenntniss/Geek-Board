<?php
// Interface de gestion de base de données pour le super administrateur (VERSION LOCALHOST)
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Configuration localhost
$localhost_config = [
    'host' => 'localhost',
    'port' => '3306',
    'user' => 'root',
    'pass' => '',
    'main_db' => 'geekboard_main'
];

// Fonction pour connecter à localhost
function connectLocalhost($config, $database = null) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']}";
        if ($database) {
            $dsn .= ";dbname={$database}";
        }
        $dsn .= ";charset=utf8mb4";
        
        return new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        return null;
    }
}

// Fonction pour découvrir automatiquement les magasins
function discoverShops($config) {
    $main_pdo = connectLocalhost($config);
    if (!$main_pdo) {
        return [];
    }
    
    $shops = [];
    $shop_id = 1;
    
    try {
        // Obtenir la liste de toutes les bases de données
        $stmt = $main_pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Bases à ignorer
        $ignore_dbs = ['information_schema', 'performance_schema', 'mysql', 'sys', 'phpmyadmin'];
        
        foreach ($databases as $db_name) {
            if (in_array($db_name, $ignore_dbs)) {
                continue;
            }
            
            // Vérifier si c'est une base de magasin GeekBoard
            $shop_pdo = connectLocalhost($config, $db_name);
            if (!$shop_pdo) {
                continue;
            }
            
            // Vérifier la présence de tables typiques GeekBoard
            $stmt = $shop_pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $geekboard_tables = ['clients', 'reparations', 'employes', 'categories'];
            $matches = array_intersect($tables, $geekboard_tables);
            
            if (count($matches) >= 2) { // Au moins 2 tables GeekBoard
                // Déterminer le nom du magasin
                $shop_name = ucfirst($db_name);
                
                // Essayer de récupérer le nom depuis la configuration si elle existe
                try {
                    $config_stmt = $shop_pdo->query("SELECT * FROM configuration LIMIT 1");
                    if ($config_stmt) {
                        $config_data = $config_stmt->fetch();
                        if ($config_data && isset($config_data['nom_entreprise'])) {
                            $shop_name = $config_data['nom_entreprise'];
                        }
                    }
                } catch (Exception $e) {
                    // Table configuration n'existe pas, utiliser le nom de la base
                }
                
                $shops[] = [
                    'id' => $shop_id++,
                    'name' => $shop_name,
                    'subdomain' => $db_name,
                    'db_host' => $config['host'],
                    'db_port' => $config['port'],
                    'db_name' => $db_name,
                    'db_user' => $config['user'],
                    'db_pass' => $config['pass'],
                    'tables_count' => count($tables),
                    'geekboard_tables' => $matches
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la découverte des magasins: " . $e->getMessage());
    }
    
    return $shops;
}

// Découvrir automatiquement les magasins
$shops = discoverShops($localhost_config);

// Récupérer l'ID du magasin sélectionné
$shop_id = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
$selected_table = isset($_GET['table']) ? $_GET['table'] : '';

// Variables pour stocker les données
$shop_info = null;
$shop_db = null;
$tables = [];
$table_data = [];
$query_result = null;
$error_message = '';
$success_message = '';

// Si un magasin est sélectionné
if ($shop_id > 0 && isset($shops[$shop_id - 1])) {
    $shop_info = $shops[$shop_id - 1];
    
    // Connexion à la base de données du magasin
    $shop_db = connectLocalhost($localhost_config, $shop_info['db_name']);
    
    if ($shop_db) {
        try {
            // Récupérer la liste des tables
            $result = $shop_db->query("SHOW TABLES");
            $tables = $result->fetchAll(PDO::FETCH_COLUMN);
            
            // Si une table est sélectionnée, récupérer ses données
            if ($selected_table && in_array($selected_table, $tables)) {
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $limit = 50;
                $offset = ($page - 1) * $limit;
                
                // Compter le nombre total de lignes
                $count_stmt = $shop_db->prepare("SELECT COUNT(*) FROM `$selected_table`");
                $count_stmt->execute();
                $total_rows = $count_stmt->fetchColumn();
                
                // Récupérer les données paginées
                $data_stmt = $shop_db->prepare("SELECT * FROM `$selected_table` LIMIT $limit OFFSET $offset");
                $data_stmt->execute();
                $table_data = $data_stmt->fetchAll();
                
                // Récupérer la structure de la table
                $structure_stmt = $shop_db->prepare("DESCRIBE `$selected_table`");
                $structure_stmt->execute();
                $table_structure = $structure_stmt->fetchAll();
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'accès aux données : " . $e->getMessage();
        }
    } else {
        $error_message = "Impossible de se connecter à la base de données du magasin";
    }
}

// Traitement des requêtes SQL personnalisées
if (isset($_POST['execute_query']) && $shop_db) {
    $sql_query = trim($_POST['sql_query']);
    
    if (!empty($sql_query)) {
        try {
            // Vérifier que la requête n'est pas dangereuse
            $dangerous_keywords = ['DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 'INSERT', 'UPDATE'];
            $is_dangerous = false;
            
            foreach ($dangerous_keywords as $keyword) {
                if (stripos($sql_query, $keyword) !== false) {
                    $is_dangerous = true;
                    break;
                }
            }
            
            if ($is_dangerous && !isset($_POST['confirm_dangerous'])) {
                $error_message = "Cette requête contient des mots-clés potentiellement dangereux. Cochez la case de confirmation pour l'exécuter.";
            } else {
                $stmt = $shop_db->prepare($sql_query);
                $stmt->execute();
                
                if (stripos($sql_query, 'SELECT') === 0) {
                    $query_result = $stmt->fetchAll();
                    $success_message = "Requête exécutée avec succès. " . count($query_result) . " résultat(s) trouvé(s).";
                } else {
                    $affected_rows = $stmt->rowCount();
                    $success_message = "Requête exécutée avec succès. $affected_rows ligne(s) affectée(s).";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'exécution de la requête : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionnaire de Base de Données - GeekBoard Admin (Localhost)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .localhost-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: bold;
        }
        .shop-discovery-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .table-item {
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 2px;
            transition: background-color 0.2s;
        }
        .table-item:hover {
            background-color: #f8f9fa;
        }
        .table-item.active {
            background-color: #007bff;
            color: white;
        }
        .result-table {
            max-height: 500px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-database me-2"></i>
                GeekBoard Admin DB <span class="localhost-badge">LOCALHOST</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="shop-discovery-info">
            <h5><i class="fas fa-search me-2"></i>Découverte automatique des magasins</h5>
            <p class="mb-1">
                <strong><?php echo count($shops); ?> magasin(s) GeekBoard détecté(s)</strong> dans localhost
            </p>
            <small class="text-muted">
                Les magasins sont automatiquement détectés en recherchant les bases de données contenant les tables GeekBoard typiques.
            </small>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-store me-2"></i>Magasins Détectés
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($shops)): ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                                <p>Aucun magasin GeekBoard détecté</p>
                                <small>Vérifiez que MySQL est démarré et que les bases de données existent</small>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($shops as $index => $shop): ?>
                                    <a href="?shop_id=<?php echo ($index + 1); ?>" 
                                       class="list-group-item list-group-item-action <?php echo ($shop_id == $index + 1) ? 'active' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($shop['name']); ?></h6>
                                            <small><?php echo $shop['tables_count']; ?> tables</small>
                                        </div>
                                        <p class="mb-1">
                                            <strong>Base:</strong> <?php echo $shop['db_name']; ?>
                                        </p>
                                        <small>Tables: <?php echo implode(', ', $shop['geekboard_tables']); ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <?php if ($shop_info): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo htmlspecialchars($shop_info['name']); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Base de données:</strong> <?php echo $shop_info['db_name']; ?></p>
                                    <p><strong>Hôte:</strong> <?php echo $shop_info['db_host']; ?>:<?php echo $shop_info['db_port']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Tables totales:</strong> <?php echo count($tables); ?></p>
                                    <p><strong>Tables GeekBoard:</strong> <?php echo implode(', ', $shop_info['geekboard_tables']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Tables (<?php echo count($tables); ?>)</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div style="max-height: 400px; overflow-y: auto;">
                                        <?php foreach ($tables as $table): ?>
                                            <div class="table-item <?php echo ($selected_table === $table) ? 'active' : ''; ?>"
                                                 onclick="window.location.href='?shop_id=<?php echo $shop_id; ?>&table=<?php echo urlencode($table); ?>'">
                                                <i class="fas fa-table me-2"></i>
                                                <?php echo htmlspecialchars($table); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-9">
                            <?php if ($selected_table && !empty($table_data)): ?>
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-table me-2"></i>
                                            <?php echo htmlspecialchars($selected_table); ?>
                                            <span class="badge bg-secondary ms-2"><?php echo $total_rows ?? count($table_data); ?> lignes</span>
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="result-table">
                                            <table class="table table-striped table-hover mb-0">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <?php if (!empty($table_data)): ?>
                                                            <?php foreach (array_keys($table_data[0]) as $column): ?>
                                                                <th><?php echo htmlspecialchars($column); ?></th>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($table_data as $row): ?>
                                                        <tr>
                                                            <?php foreach ($row as $value): ?>
                                                                <td><?php echo htmlspecialchars($value ?? ''); ?></td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($selected_table): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Table sélectionnée: <strong><?php echo htmlspecialchars($selected_table); ?></strong> (aucune donnée)
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-hand-pointer me-2"></i>
                                    Sélectionnez une table dans la liste de gauche pour voir son contenu.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Requête SQL -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-code me-2"></i>Requête SQL
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <textarea name="sql_query" class="form-control mb-3" rows="4" 
                                          placeholder="Entrez votre requête SQL ici..."><?php echo isset($_POST['sql_query']) ? htmlspecialchars($_POST['sql_query']) : ''; ?></textarea>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="confirm_dangerous" id="confirmDangerous">
                                        <label class="form-check-label text-warning" for="confirmDangerous">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Confirmer les requêtes dangereuses
                                        </label>
                                    </div>
                                    <button type="submit" name="execute_query" class="btn btn-primary">
                                        <i class="fas fa-play me-1"></i>Exécuter
                                    </button>
                                </div>
                            </form>

                            <?php if (isset($query_result) && is_array($query_result)): ?>
                                <div class="mt-3">
                                    <h6>Résultat de la requête (<?php echo count($query_result); ?> ligne(s))</h6>
                                    <?php if (!empty($query_result)): ?>
                                        <div class="result-table">
                                            <table class="table table-striped table-hover">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <?php foreach (array_keys($query_result[0]) as $column): ?>
                                                            <th><?php echo htmlspecialchars($column); ?></th>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($query_result as $row): ?>
                                                        <tr>
                                                            <?php foreach ($row as $value): ?>
                                                                <td><?php echo htmlspecialchars($value ?? ''); ?></td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">Aucun résultat</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-arrow-left me-2"></i>
                        Sélectionnez un magasin dans la liste de gauche pour commencer.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 