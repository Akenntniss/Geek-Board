<?php
// Interface de gestion de base de données pour le super administrateur (VERSION LOCALHOST)
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Configuration localhost directe
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

// Récupérer les magasins depuis la table shops dans geekboard_main
$main_pdo = connectLocalhost($localhost_config, $localhost_config['main_db']);
$shops = [];

if ($main_pdo) {
    try {
        $stmt = $main_pdo->query("SELECT * FROM shops WHERE status = 'active' ORDER BY id");
        $shops = $stmt->fetchAll();
    } catch (Exception $e) {
        $error_message = "Erreur lors de la récupération des magasins : " . $e->getMessage();
    }
}

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
if ($shop_id > 0) {
    // Trouver le magasin
    foreach ($shops as $shop) {
        if ($shop['id'] == $shop_id) {
            $shop_info = $shop;
            break;
        }
    }
    
    if ($shop_info) {
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
            $error_message = "Impossible de se connecter à la base de données du magasin '{$shop_info['db_name']}'";
        }
    } else {
        $error_message = "Magasin non trouvé";
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

// Export de données
if (isset($_GET['export']) && $selected_table && $shop_db) {
    $export_format = $_GET['export'];
    
    try {
        $stmt = $shop_db->prepare("SELECT * FROM `$selected_table`");
        $stmt->execute();
        $export_data = $stmt->fetchAll();
        
        if ($export_format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $selected_table . '_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            if (!empty($export_data)) {
                // En-têtes
                fputcsv($output, array_keys($export_data[0]));
                
                // Données
                foreach ($export_data as $row) {
                    fputcsv($output, $row);
                }
            }
            
            fclose($output);
            exit;
        }
    } catch (PDOException $e) {
        $error_message = "Erreur lors de l'export : " . $e->getMessage();
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
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
            <h5><i class="fas fa-check-circle me-2"></i>Gestionnaire Localhost Opérationnel</h5>
            <p class="mb-1">
                <strong><?php echo count($shops); ?> magasin(s) GeekBoard détecté(s)</strong> en localhost avec données de test
            </p>
            <small class="text-muted">
                Configuration automatique réussie. Toutes les bases de données pointent vers localhost avec les tables GeekBoard complètes.
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
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-store me-2"></i>Magasins Localhost
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($shops)): ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                                <p>Aucun magasin configuré</p>
                                <small>Exécutez le script de configuration</small>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($shops as $shop): ?>
                                    <a href="?shop_id=<?php echo $shop['id']; ?>" 
                                       class="list-group-item list-group-item-action <?php echo ($shop_id == $shop['id']) ? 'active' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($shop['name']); ?></h6>
                                            <small><i class="fas fa-database"></i></small>
                                        </div>
                                        <p class="mb-1">
                                            <strong>Base:</strong> <?php echo $shop['db_name']; ?>
                                        </p>
                                        <small><i class="fas fa-server me-1"></i><?php echo $shop['db_host']; ?></small>
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
                        <div class="card-header bg-success text-white">
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
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-success"><?php echo $shop_info['status']; ?></span>
                                    </p>
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
                                        <div>
                                            <button class="btn btn-sm btn-info" onclick="showTableStructure()">
                                                <i class="fas fa-info-circle"></i> Structure
                                            </button>
                                            <a href="?shop_id=<?php echo $shop_id; ?>&table=<?php echo urlencode($selected_table); ?>&export=csv" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-download"></i> Export CSV
                                            </a>
                                        </div>
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
                                          placeholder="Entrez votre requête SQL ici... (Ex: SELECT * FROM clients LIMIT 10)"><?php echo isset($_POST['sql_query']) ? htmlspecialchars($_POST['sql_query']) : ''; ?></textarea>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="confirm_dangerous" id="confirmDangerous">
                                        <label class="form-check-label text-warning" for="confirmDangerous">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Confirmer les requêtes dangereuses (UPDATE, DELETE, etc.)
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

    <!-- Modal pour la structure de table -->
    <?php if (isset($table_structure) && !empty($table_structure)): ?>
    <div class="modal fade" id="tableStructureModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>
                        Structure de la table: <?php echo htmlspecialchars($selected_table); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Champ</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Clé</th>
                                <th>Défaut</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_structure as $field): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($field['Field']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($field['Type']); ?></td>
                                    <td><?php echo $field['Null'] === 'YES' ? 'Oui' : 'Non'; ?></td>
                                    <td><?php echo htmlspecialchars($field['Key']); ?></td>
                                    <td><?php echo htmlspecialchars($field['Default'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($field['Extra']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour afficher la structure de table
        function showTableStructure() {
            const modal = new bootstrap.Modal(document.getElementById('tableStructureModal'));
            modal.show();
        }

        // Auto-refresh des alertes
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html> 