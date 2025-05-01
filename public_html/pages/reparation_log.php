<?php
// Configuration de l'affichage des erreurs (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit();
}

// Récupérer les ID des utilisateurs pour le filtre
$query_users = "SELECT id, nom, prenom FROM users ORDER BY nom, prenom";
$stmt_users = $conn->prepare($query_users);
$stmt_users->execute();
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// Initialiser les variables de filtrage
$filter_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filter_date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$filter_date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

// Construire la requête SQL de base
$sql = "SELECT l.*, r.num_reparation, r.appareil, r.marque, r.modele, c.nom as client_nom, c.prenom as client_prenom, u.nom as user_nom, u.prenom as user_prenom 
        FROM reparation_logs l 
        LEFT JOIN reparations r ON l.reparation_id = r.id 
        LEFT JOIN clients c ON r.client_id = c.id 
        LEFT JOIN users u ON l.user_id = u.id 
        WHERE 1=1";

// Ajouter les conditions de filtrage si nécessaire
$params = [];

if ($filter_user > 0) {
    $sql .= " AND l.user_id = :user_id";
    $params[':user_id'] = $filter_user;
}

if (!empty($filter_date_debut)) {
    $sql .= " AND DATE(l.date_action) >= :date_debut";
    $params[':date_debut'] = $filter_date_debut;
}

if (!empty($filter_date_fin)) {
    $sql .= " AND DATE(l.date_action) <= :date_fin";
    $params[':date_fin'] = $filter_date_fin;
}

if (!empty($filter_type)) {
    $sql .= " AND l.type_action = :type_action";
    $params[':type_action'] = $filter_type;
}

// Ajouter l'ordre de tri
$sql .= " ORDER BY l.date_action DESC";

// Exécuter la requête
$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les types d'actions uniques pour le filtre
$query_types = "SELECT DISTINCT type_action FROM reparation_logs ORDER BY type_action";
$stmt_types = $conn->prepare($query_types);
$stmt_types->execute();
$types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Historique des activités de réparation</h5>
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" action="" class="mb-4">
                        <input type="hidden" name="page" value="reparation_log">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="user_id" class="form-label">Utilisateur</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="0">Tous les utilisateurs</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>" <?= $filter_user == $user['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_debut" class="form-label">Date début</label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?= $filter_date_debut ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_fin" class="form-label">Date fin</label>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?= $filter_date_fin ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="type" class="form-label">Type d'action</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">Tous les types</option>
                                    <?php foreach ($types as $type): ?>
                                        <option value="<?= $type ?>" <?= $filter_type == $type ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                            </div>
                        </div>
                    </form>

                    <!-- Tableau des logs -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <th>Type</th>
                                    <th>Réparation</th>
                                    <th>Client</th>
                                    <th>Appareil</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($logs) > 0): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($log['date_action'])) ?></td>
                                            <td>
                                                <?= htmlspecialchars($log['user_nom'] . ' ' . $log['user_prenom']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getLogTypeBadgeClass($log['type_action']) ?>">
                                                    <?= htmlspecialchars($log['type_action']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($log['num_reparation'])): ?>
                                                    <a href="index.php?page=details_reparation&id=<?= $log['reparation_id'] ?>">
                                                        <?= htmlspecialchars($log['num_reparation']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= !empty($log['client_nom']) ? htmlspecialchars($log['client_nom'] . ' ' . $log['client_prenom']) : '-' ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($log['marque']) && !empty($log['modele'])): ?>
                                                    <?= htmlspecialchars($log['appareil'] . ' ' . $log['marque'] . ' ' . $log['modele']) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($log['description']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucune activité trouvée</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Fonction pour déterminer la classe de badge en fonction du type d'action
function getLogTypeBadgeClass($type) {
    switch ($type) {
        case 'Création':
            return 'success';
        case 'Modification':
            return 'warning';
        case 'Suppression':
            return 'danger';
        case 'Changement statut':
            return 'info';
        case 'Commentaire':
            return 'primary';
        default:
            return 'secondary';
    }
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser datepickers si nécessaire ou autres fonctionnalités JS
});
</script> 