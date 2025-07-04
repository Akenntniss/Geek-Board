<?php
// Interface de gestion de base de données pour le super administrateur
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

// Récupérer l'ID du magasin sélectionné
$shop_id = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
$selected_table = isset($_GET['table']) ? $_GET['table'] : '';
$query_mode = isset($_GET['mode']) ? $_GET['mode'] : 'tables';

// Récupérer les informations du super administrateur
$main_pdo = getMainDBConnection();
$stmt = $main_pdo->prepare("SELECT * FROM superadmins WHERE id = ?");
$stmt->execute([$_SESSION['superadmin_id']]);
$superadmin = $stmt->fetch();

// Récupérer la liste des magasins
$shops = $main_pdo->query("SELECT * FROM shops ORDER BY name")->fetchAll();

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
    // Récupérer les informations du magasin
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop_info = $stmt->fetch();
    
    if ($shop_info) {
        // Connexion à la base de données du magasin
        $shop_config = [
            'host' => $shop_info['db_host'],
            'port' => $shop_info['db_port'],
            'dbname' => $shop_info['db_name'],
            'user' => $shop_info['db_user'],
            'pass' => $shop_info['db_pass']
        ];
        
        try {
            $shop_db = connectToShopDB($shop_config);
            
            if ($shop_db) {
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
            }
        } catch (PDOException $e) {
            $error_message = "Erreur de connexion : " . $e->getMessage();
        }
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
    <title>GeekBoard - Gestionnaire de Base de Données</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            max-width: 95%;
            width: 100%;
            margin: 0 auto;
        }
        @media (min-width: 1400px) {
            .main-container {
                max-width: 1800px;
            }
        }
        @media (min-width: 1200px) and (max-width: 1399px) {
            .main-container {
                max-width: 90%;
            }
        }
        @media (max-width: 768px) {
            .main-container {
                max-width: 95%;
                margin: 10px;
                border-radius: 15px;
            }
            body {
                padding: 10px 0;
            }
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header-section h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 300;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .header-section p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .user-info {
            background: rgba(255,255,255,0.15);
            border-radius: 15px;
            padding: 12px 20px;
            margin-top: 15px;
            backdrop-filter: blur(10px);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }
        .content-section {
            padding: 30px;
        }
        @media (min-width: 1400px) {
            .content-section {
                padding: 40px 50px;
            }
        }
        @media (max-width: 768px) {
            .content-section {
                padding: 20px 15px;
            }
        }
        .action-buttons {
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-action {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 25px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        }
        .btn-secondary:hover {
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.4);
        }
        .database-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            min-height: 600px;
        }
        @media (min-width: 1400px) {
            .database-container {
                grid-template-columns: 350px 1fr;
                gap: 40px;
            }
        }
        @media (max-width: 1024px) {
            .database-container {
                grid-template-columns: 280px 1fr;
                gap: 20px;
            }
        }
        @media (max-width: 768px) {
            .database-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        .sidebar {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            height: fit-content;
        }
        .shop-selector {
            margin-bottom: 25px;
        }
        .shop-selector label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: block;
            font-size: 1.1rem;
        }
        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
        }
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .tables-section h3 {
            color: #333;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .table-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .table-item {
            cursor: pointer;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
            background: white;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table-item:hover {
            background: #f0f2ff;
            border-color: #667eea;
        }
        .table-item.active {
            background: #667eea;
            color: white;
        }
        .main-content {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
        }
        .no-shop-selected {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .no-shop-selected i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ccc;
        }
        .no-shop-selected h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .sql-editor-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 2px solid #e9ecef;
        }
        .sql-editor-section h4 {
            color: #333;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sql-editor {
            min-height: 150px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .table-data-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #e9ecef;
        }
        .table-data-section h4 {
            color: #333;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table-responsive {
            max-height: 500px;
            overflow: auto;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .table {
            margin: 0;
            font-size: 0.9rem;
        }
        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #333;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table td {
            border-bottom: 1px solid #dee2e6;
            max-width: 200px;
            word-wrap: break-word;
        }
        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
        }
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        .btn-execute {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-execute:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .btn-export {
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
        }
        .btn-export:hover {
            background: #667eea;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-section">
            <h1><i class="fas fa-database"></i>Gestionnaire de Base de Données</h1>
            <p>Administration des données des magasins</p>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span>Connecté en tant que <strong><?php echo htmlspecialchars($superadmin['full_name']); ?></strong></span>
            </div>
        </div>
        
        <div class="content-section">
            <div class="action-buttons">
                <a href="index.php" class="btn-action btn-secondary">
                    <i class="fas fa-arrow-left"></i>Retour à l'accueil
                </a>
                <a href="create_shop.php" class="btn-action">
                    <i class="fas fa-plus-circle"></i>Nouveau magasin
                </a>
                <a href="configure_domains.php" class="btn-action btn-secondary">
                    <i class="fas fa-globe"></i>Configuration domaines
                </a>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="database-container">
                <div class="sidebar">
                    <div class="shop-selector">
                        <label><i class="fas fa-store me-2"></i>Sélectionner un magasin</label>
                        <form method="get" action="">
                            <select name="shop_id" class="form-select" onchange="this.form.submit()">
                                <option value="0">-- Choisir un magasin --</option>
                                <?php foreach ($shops as $shop): ?>
                                    <option value="<?php echo $shop['id']; ?>" <?php echo $shop_id == $shop['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($shop['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    
                    <?php if ($shop_id > 0 && $shop_info): ?>
                        <div class="shop-info-card" style="background: rgba(102, 126, 234, 0.1); border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                            <h5 style="color: #667eea; margin-bottom: 10px; font-size: 1.1rem;">
                                <i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($shop_info['name']); ?>
                            </h5>
                            <div style="font-size: 0.9rem; color: #666;">
                                <div><strong>Base :</strong> <?php echo htmlspecialchars($shop_info['db_name']); ?></div>
                                <div><strong>Host :</strong> <?php echo htmlspecialchars($shop_info['db_host']); ?></div>
                                <div><strong>Tables :</strong> <?php echo count($tables); ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($tables)): ?>
                            <div class="tables-section">
                                <h3><i class="fas fa-table"></i>Tables (<?php echo count($tables); ?>)</h3>
                                
                                <!-- Recherche dans les tables -->
                                <div class="table-search-container" style="margin-bottom: 15px;">
                                    <div style="position: relative;">
                                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #667eea; font-size: 1rem;"></i>
                                        <input type="text" id="table-search" placeholder="Rechercher une table..." 
                                               style="width: 100%; padding: 10px 15px 10px 35px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; transition: all 0.3s ease; background: white;">
                                    </div>
                                </div>
                                
                                <div class="table-list">
                                    <?php foreach ($tables as $table): ?>
                                        <div class="table-item <?php echo $selected_table === $table ? 'active' : ''; ?>" 
                                             onclick="selectTable('<?php echo htmlspecialchars($table); ?>')">
                                            <i class="fas fa-table"></i>
                                            <span><?php echo htmlspecialchars($table); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="main-content">
                    <?php if ($shop_id == 0): ?>
                        <div class="no-shop-selected">
                            <i class="fas fa-database"></i>
                            <h3>Aucun magasin sélectionné</h3>
                            <p>Veuillez sélectionner un magasin dans la liste de gauche pour accéder à sa base de données.</p>
                        </div>
                    <?php elseif (!$shop_info): ?>
                        <div class="no-shop-selected">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Magasin introuvable</h3>
                            <p>Le magasin sélectionné n'existe pas ou a été supprimé.</p>
                        </div>
                    <?php elseif (empty($tables)): ?>
                        <div class="no-shop-selected">
                            <i class="fas fa-database"></i>
                            <h3>Aucune table trouvée</h3>
                            <p>La base de données de ce magasin ne contient aucune table ou la connexion a échoué.</p>
                        </div>
                    <?php else: ?>
                        <!-- Éditeur SQL -->
                        <div class="sql-editor-section">
                            <h4><i class="fas fa-code"></i>Éditeur SQL</h4>
                            <form method="post">
                                <textarea name="sql_query" class="form-control sql-editor" placeholder="Tapez votre requête SQL ici... (ex: SELECT * FROM users LIMIT 10)"><?php echo htmlspecialchars($_POST['sql_query'] ?? ''); ?></textarea>
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="confirm_dangerous" id="confirm_dangerous">
                                    <label class="form-check-label" for="confirm_dangerous">
                                        <small>J'autorise les requêtes potentiellement dangereuses (INSERT, UPDATE, DELETE, DROP, etc.)</small>
                                    </label>
                                </div>
                                <button type="submit" name="execute_query" class="btn btn-execute mt-3">
                                    <i class="fas fa-play me-2"></i>Exécuter la requête
                                </button>
                            </form>
                        </div>
                        
                        <!-- Résultats de requête personnalisée -->
                        <?php if (isset($query_result)): ?>
                            <div class="table-data-section">
                                <h4><i class="fas fa-search"></i>Résultats de la requête</h4>
                                <?php if (!empty($query_result)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <?php foreach (array_keys($query_result[0]) as $column): ?>
                                                        <th><?php echo htmlspecialchars($column); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($query_result, 0, 100) as $row): ?>
                                                    <tr>
                                                        <?php foreach ($row as $value): ?>
                                                            <td><?php echo htmlspecialchars(substr($value ?? '', 0, 100) . (strlen($value ?? '') > 100 ? '...' : '')); ?></td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if (count($query_result) > 100): ?>
                                        <p class="text-muted mt-2"><small>Affichage des 100 premiers résultats sur <?php echo count($query_result); ?> total.</small></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted">Aucun résultat trouvé.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Données de table sélectionnée -->
                        <?php if ($selected_table && !empty($table_data)): ?>
                            <div class="table-data-section">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4><i class="fas fa-table"></i>Table : <?php echo htmlspecialchars($selected_table); ?></h4>
                                    <div class="export-buttons">
                                        <a href="?shop_id=<?php echo $shop_id; ?>&table=<?php echo urlencode($selected_table); ?>&export=csv" class="btn-export">
                                            <i class="fas fa-download me-1"></i>Export CSV
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Recherche dans les données -->
                                <div style="margin-bottom: 15px;">
                                    <div style="position: relative; max-width: 400px;">
                                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #667eea; font-size: 1rem;"></i>
                                        <input type="text" id="data-search" placeholder="Rechercher dans les données..." 
                                               style="width: 100%; padding: 10px 15px 10px 35px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; transition: all 0.3s ease; background: white;">
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <?php foreach (array_keys($table_data[0]) as $column): ?>
                                                    <th><?php echo htmlspecialchars($column); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($table_data as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $value): ?>
                                                        <td><?php echo htmlspecialchars(substr($value ?? '', 0, 100) . (strlen($value ?? '') > 100 ? '...' : '')); ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (isset($total_rows) && $total_rows > 50): ?>
                                    <p class="text-muted mt-2"><small>Affichage de 50 résultats sur <?php echo $total_rows; ?> total.</small></p>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($selected_table): ?>
                            <div class="no-shop-selected">
                                <i class="fas fa-table"></i>
                                <h3>Table vide</h3>
                                <p>La table "<?php echo htmlspecialchars($selected_table); ?>" ne contient aucune donnée.</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js"></script>
    <script>
        // Animation d'entrée
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.main-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(50px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
            
            // Initialiser CodeMirror pour l'éditeur SQL
            const sqlTextarea = document.querySelector('textarea[name="sql_query"]');
            if (sqlTextarea) {
                const editor = CodeMirror.fromTextArea(sqlTextarea, {
                    mode: 'sql',
                    theme: 'default',
                    lineNumbers: true,
                    lineWrapping: true,
                    indentUnit: 2,
                    tabSize: 2
                });
                editor.setSize(null, '150px');
            }
        });
        
        function selectTable(tableName) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('table', tableName);
            window.location.href = currentUrl.toString();
        }
        
        // Recherche dans les tables
        const tableSearch = document.getElementById('table-search');  
        if (tableSearch) {
            tableSearch.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                const tableItems = document.querySelectorAll('.table-item');
                
                tableItems.forEach(function(item) {
                    const tableName = item.querySelector('span')?.textContent.toLowerCase() || '';
                    if (searchTerm === '' || tableName.includes(searchTerm)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
        
        // Recherche dans les données de table
        const dataSearch = document.getElementById('data-search');
        if (dataSearch) {
            dataSearch.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                const tableRows = document.querySelectorAll('.table-data-section tbody tr');
                
                tableRows.forEach(function(row) {
                    const rowText = row.textContent.toLowerCase();
                    if (searchTerm === '' || rowText.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Style pour les champs de recherche au focus
        document.querySelectorAll('#table-search, #data-search').forEach(function(input) {
            input.addEventListener('focus', function() {
                this.style.borderColor = '#667eea';
                this.style.boxShadow = '0 0 0 0.2rem rgba(102, 126, 234, 0.25)';
            });
            
            input.addEventListener('blur', function() {
                this.style.borderColor = '#e9ecef';
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>
</html>
