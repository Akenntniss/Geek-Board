<?php
// Optimisation majeure : Réduire les requêtes SQL de 12 à 2
// Avant : 6 requêtes de comptage + 6 sous-requêtes + 1 requête principale = 13 requêtes
// Après : 1 requête de comptage + 1 requête principale = 2 requêtes

ini_set('display_errors', 1);
error_reporting(E_ALL);

$shop_pdo = getShopDBConnection();
$current_shop_id = $_SESSION['shop_id'] ?? $_GET['shop_id'] ?? null;

if (!$current_shop_id && $current_shop_id !== null) {
    $_SESSION['shop_id'] = $current_shop_id;
}

// Paramètres de filtrage
$statut_ids = isset($_GET['statut_ids']) ? cleanInput($_GET['statut_ids']) : '1,2,3,4,5';
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// OPTIMISATION 1 : Une seule requête pour tous les compteurs
$counters_sql = "
    SELECT 
        SUM(CASE WHEN s.id BETWEEN 1 AND 5 THEN 1 ELSE 0 END) as total_reparations,
        SUM(CASE WHEN s.id IN (1,2,3) THEN 1 ELSE 0 END) as total_nouvelles,
        SUM(CASE WHEN s.id IN (4,5) THEN 1 ELSE 0 END) as total_en_cours,
        SUM(CASE WHEN s.id IN (6,7,8) THEN 1 ELSE 0 END) as total_en_attente,
        SUM(CASE WHEN s.id IN (9,10) THEN 1 ELSE 0 END) as total_termines,
        SUM(CASE WHEN s.id IN (11,12,13) THEN 1 ELSE 0 END) as total_archives
    FROM reparations r 
    LEFT JOIN statuts s ON r.statut = s.code
";

$counters = $shop_pdo->query($counters_sql)->fetch(PDO::FETCH_ASSOC);
extract($counters);

// OPTIMISATION 2 : Requête principale optimisée
$sql = "
    SELECT r.id, r.client_id, r.type_appareil, r.marque, r.modele, 
           r.description_probleme, r.date_reception, r.prix_final, r.statut,
           c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone,
           s.nom as statut_nom, s.couleur as statut_couleur
    FROM reparations r
    LEFT JOIN clients c ON r.client_id = c.id
    LEFT JOIN statuts s ON r.statut = s.code
    WHERE 1=1
";

$params = [];

// Filtres
if (!empty($search)) {
    $sql .= " AND (c.nom LIKE ? OR c.prenom LIKE ? OR c.telephone LIKE ? OR r.type_appareil LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
}

if (!empty($statut_ids)) {
    $ids = explode(',', $statut_ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql .= " AND s.id IN ($placeholders)";
    $params = array_merge($params, $ids);
}

$sql .= " ORDER BY r.date_reception DESC LIMIT 1000"; // Limite pour éviter les requêtes trop lourdes

$stmt = $shop_pdo->prepare($sql);
$stmt->execute($params);
$reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- CSS optimisé -->
<style>
:root {
    --primary: #667eea;
    --secondary: #764ba2;
    --success: #28a745;
    --warning: #ffc107;
    --danger: #dc3545;
    --info: #17a2b8;
    --light: #f8f9fa;
    --dark: #343a40;
}

/* Optimisation des performances : Réduire les reflows */
* { box-sizing: border-box; }

body {
    background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    padding: 0;
}

.container-fluid {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1rem;
}

.card {
    border: none;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    background: white;
    margin-bottom: 1rem;
}

.table-container {
    max-height: 70vh;
    overflow-y: auto;
    contain: layout;
}

.table {
    margin: 0;
    width: 100%;
}

.table th {
    background: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 10;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.875rem;
    padding: 12px 8px;
}

.table td {
    padding: 8px;
    vertical-align: middle;
    border-top: 1px solid #f1f1f1;
}

.table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

.btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s;
    padding: 0.25rem 0.5rem;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.8rem;
}

.filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    margin-bottom: 1rem;
}

.filter-btn {
    padding: 0.5rem 1rem;
    border: 2px solid transparent;
    border-radius: 6px;
    background: white;
    color: var(--dark);
    text-decoration: none;
    transition: all 0.2s;
    font-weight: 500;
}

.filter-btn:hover {
    background: var(--primary);
    color: white;
}

.filter-btn.active {
    background: var(--primary);
    color: white;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: 0.5rem;
}

.search-form {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.form-control {
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    padding: 0.5rem;
    font-size: 0.9rem;
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Responsive */
@media (max-width: 768px) {
    .container-fluid { padding: 0.5rem; }
    .table-container { max-height: 60vh; }
    .table th, .table td { padding: 6px 4px; font-size: 0.8rem; }
    .filter-buttons { flex-direction: column; }
}
</style>

<!-- Interface optimisée -->
<div class="container-fluid">
    <!-- Recherche -->
    <div class="search-form">
        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="reparations">
            <div class="input-group">
                <input type="text" class="form-control" name="search" 
                       placeholder="Rechercher..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Filtres -->
    <div class="filter-buttons">
        <a href="?page=reparations&statut_ids=1,2,3,4,5" 
           class="filter-btn <?php echo ($statut_ids === '1,2,3,4,5') ? 'active' : ''; ?>">
            Récentes <span class="badge bg-primary"><?php echo $total_reparations; ?></span>
        </a>
        <a href="?page=reparations&statut_ids=1,2,3" 
           class="filter-btn <?php echo ($statut_ids === '1,2,3') ? 'active' : ''; ?>">
            Nouvelles <span class="badge bg-success"><?php echo $total_nouvelles; ?></span>
        </a>
        <a href="?page=reparations&statut_ids=4,5" 
           class="filter-btn <?php echo ($statut_ids === '4,5') ? 'active' : ''; ?>">
            En cours <span class="badge bg-warning"><?php echo $total_en_cours; ?></span>
        </a>
        <a href="?page=reparations&statut_ids=6,7,8" 
           class="filter-btn <?php echo ($statut_ids === '6,7,8') ? 'active' : ''; ?>">
            En attente <span class="badge bg-info"><?php echo $total_en_attente; ?></span>
        </a>
        <a href="?page=reparations&statut_ids=9,10" 
           class="filter-btn <?php echo ($statut_ids === '9,10') ? 'active' : ''; ?>">
            Terminées <span class="badge bg-success"><?php echo $total_termines; ?></span>
        </a>
    </div>

    <!-- Tableau -->
    <div class="card">
        <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Appareil</th>
                        <th>Problème</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Prix</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reparations)): ?>
                        <tr>
                            <td colspan="8" class="text-center p-4">
                                <i class="fas fa-inbox fa-2x text-muted"></i>
                                <p class="text-muted mt-2">Aucune réparation trouvée</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reparations as $rep): ?>
                            <tr>
                                <td><strong>#<?php echo $rep['id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($rep['client_nom'] . ' ' . $rep['client_prenom']); ?></strong>
                                    <?php if ($rep['client_telephone']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($rep['client_telephone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($rep['type_appareil']); ?></strong>
                                    <?php if ($rep['marque']): ?>
                                        <br><small><?php echo htmlspecialchars($rep['marque'] . ' ' . $rep['modele']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars(substr($rep['description_probleme'], 0, 80)); ?>
                                        <?php if (strlen($rep['description_probleme']) > 80): ?>...<?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <small><?php echo date('d/m', strtotime($rep['date_reception'])); ?></small>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: <?php echo $rep['statut_couleur'] ?? '#6c757d'; ?>">
                                        <?php echo htmlspecialchars($rep['statut_nom'] ?? $rep['statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($rep['prix_final']): ?>
                                        <strong><?php echo number_format($rep['prix_final'], 2); ?>€</strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="index.php?page=detail_reparation&id=<?php echo $rep['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?page=modifier_reparation&id=<?php echo $rep['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="pdf/etiquette_reparation.php?id=<?php echo $rep['id']; ?>" 
                                           class="btn btn-sm btn-outline-info" title="Imprimer">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JavaScript optimisé -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Performance : Initialisation simple
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => this.style.transform = '', 100);
        });
    });
    
    // Recherche avec debounce
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (this.value.length > 2 || this.value.length === 0) {
                    // Auto-submit si nécessaire
                }
            }, 300);
        });
    }
});
</script> 