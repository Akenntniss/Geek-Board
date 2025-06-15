<?php
require_once 'includes/task_logger.php';

// Obtenir la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Récupérer les paramètres de filtrage
$filter_user = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$filter_action = isset($_GET['action_type']) ? $_GET['action_type'] : null;
$filter_task = isset($_GET['task_id']) ? (int)$_GET['task_id'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Construire la requête avec filtres
$where_conditions = [];
$params = [];

if ($filter_user) {
    $where_conditions[] = "user_id = ?";
    $params[] = $filter_user;
}

if ($filter_action) {
    $where_conditions[] = "action_type = ?";
    $params[] = $filter_action;
}

if ($filter_task) {
    $where_conditions[] = "task_id = ?";
    $params[] = $filter_task;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Récupérer les logs
$sql = "
    SELECT 
        lt.*,
        t.titre as task_title_current
    FROM Log_tasks lt
    LEFT JOIN taches t ON lt.task_id = t.id
    $where_clause
    ORDER BY lt.action_timestamp DESC 
    LIMIT ?
";

$params[] = $limit;
$stmt = $shop_pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les utilisateurs pour le filtre
$users_stmt = $shop_pdo->query("SELECT DISTINCT user_id, user_name FROM Log_tasks WHERE user_name IS NOT NULL ORDER BY user_name");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques rapides
$stats_stmt = $shop_pdo->query("
    SELECT 
        action_type,
        COUNT(*) as count,
        DATE(action_timestamp) as date
    FROM Log_tasks 
    WHERE DATE(action_timestamp) = CURDATE()
    GROUP BY action_type, DATE(action_timestamp)
    ORDER BY count DESC
");
$today_stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

function formatActionType($action) {
    $actions = [
        'demarrer' => '🚀 Démarrer',
        'terminer' => '🎉 Terminer', 
        'modifier' => '✏️ Modifier',
        'creer' => '➕ Créer',
        'supprimer' => '🗑️ Supprimer',
        'pause' => '⏸️ Pause',
        'reprendre' => '▶️ Reprendre'
    ];
    return $actions[$action] ?? $action;
}

function formatStatus($status) {
    $statuts = [
        'a_faire' => '📋 À faire',
        'en_cours' => '⏳ En cours',
        'termine' => '✅ Terminé',
        'pause' => '⏸️ En pause',
        'annule' => '❌ Annulé'
    ];
    return $statuts[$status] ?? $status;
}
?>

<div class="logs-content-container">
    <!-- En-tête avec statistiques -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-history me-2"></i>Logs des Actions sur les Tâches</h2>
            <p class="text-muted">Historique complet des actions effectuées sur les tâches</p>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6>📊 Actions d'aujourd'hui</h6>
                    <?php if (!empty($today_stats)): ?>
                        <?php foreach ($today_stats as $stat): ?>
                            <div class="d-flex justify-content-between">
                                <span><?php echo formatActionType($stat['action_type']); ?></span>
                                <span class="fw-bold"><?php echo $stat['count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="mb-0 text-muted">Aucune action aujourd'hui</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <h6>🔍 Filtres</h6>
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="page" value="logs_taches">
                
                <div class="col-md-3">
                    <label for="user_id" class="form-label">Utilisateur</label>
                    <select class="form-select" id="user_id" name="user_id">
                        <option value="">Tous les utilisateurs</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>" 
                                    <?php echo $filter_user == $user['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['user_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="action_type" class="form-label">Type d'action</label>
                    <select class="form-select" id="action_type" name="action_type">
                        <option value="">Toutes les actions</option>
                        <option value="demarrer" <?php echo $filter_action == 'demarrer' ? 'selected' : ''; ?>>🚀 Démarrer</option>
                        <option value="terminer" <?php echo $filter_action == 'terminer' ? 'selected' : ''; ?>>🎉 Terminer</option>
                        <option value="modifier" <?php echo $filter_action == 'modifier' ? 'selected' : ''; ?>>✏️ Modifier</option>
                        <option value="creer" <?php echo $filter_action == 'creer' ? 'selected' : ''; ?>>➕ Créer</option>
                        <option value="supprimer" <?php echo $filter_action == 'supprimer' ? 'selected' : ''; ?>>🗑️ Supprimer</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="task_id" class="form-label">ID Tâche</label>
                    <input type="number" class="form-control" id="task_id" name="task_id" 
                           value="<?php echo $filter_task ?: ''; ?>" placeholder="ID">
                </div>
                
                <div class="col-md-2">
                    <label for="limit" class="form-label">Limite</label>
                    <select class="form-select" id="limit" name="limit">
                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="200" <?php echo $limit == 200 ? 'selected' : ''; ?>>200</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
            
            <?php if ($filter_user || $filter_action || $filter_task): ?>
                <div class="mt-2">
                    <a href="index.php?page=logs_taches" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-1"></i>Effacer les filtres
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tableau des logs -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h5>Aucun log trouvé</h5>
                    <p class="text-muted">Aucune action ne correspond aux critères de recherche</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>📅 Date/Heure</th>
                                <th>🎯 Action</th>
                                <th>📋 Tâche</th>
                                <th>🔄 Changement de statut</th>
                                <th>👤 Utilisateur</th>
                                <th>🌐 IP</th>
                                <th>📝 Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i:s', strtotime($log['action_timestamp'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo formatActionType($log['action_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>ID #<?php echo $log['task_id']; ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($log['task_title'] ?: $log['task_title_current'] ?: 'Titre non disponible'); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($log['old_status'] || $log['new_status']): ?>
                                            <div class="status-change">
                                                <?php if ($log['old_status']): ?>
                                                    <small><?php echo formatStatus($log['old_status']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($log['old_status'] && $log['new_status']): ?>
                                                    <br><i class="fas fa-arrow-down text-muted"></i><br>
                                                <?php endif; ?>
                                                <?php if ($log['new_status']): ?>
                                                    <small><?php echo formatStatus($log['new_status']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($log['user_name']): ?>
                                            <strong><?php echo htmlspecialchars($log['user_name']); ?></strong>
                                            <br><small class="text-muted">ID: <?php echo $log['user_id']; ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Système</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo $log['ip_address'] ?: '-'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($log['details']): ?>
                                            <small title="<?php echo htmlspecialchars($log['details']); ?>">
                                                <?php echo htmlspecialchars(substr($log['details'], 0, 50)); ?>
                                                <?php if (strlen($log['details']) > 50): ?>...<?php endif; ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <small class="text-muted"><?php echo count($logs); ?> log(s) affichés</small>
                    </div>
                    <div>
                        <?php if (count($logs) >= $limit): ?>
                            <small class="text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Limite de <?php echo $limit; ?> atteinte. Utilisez les filtres pour affiner la recherche.
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.logs-content-container {
    padding: 20px;
}

.status-change {
    font-size: 0.85rem;
    line-height: 1.2;
}

.table th {
    font-size: 0.9rem;
    font-weight: 600;
}

.table td {
    vertical-align: middle;
}

.badge {
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .logs-content-container {
        padding: 10px;
    }
    
    .table-responsive {
        font-size: 0.85rem;
    }
}
</style> 