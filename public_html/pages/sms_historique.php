<?php
// Vérifier les droits d'accès de base
if (!isset($_SESSION['user_id'])) {
    set_message("Vous devez être connecté pour accéder à cette page.", "danger");
    redirect("");
    exit;
}

// Variable pour déterminer le niveau d'accès
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Paramètres de pagination
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Paramètres de filtrage
$reparation_id = isset($_GET['reparation_id']) ? (int)$_GET['reparation_id'] : null;
$statut_id = isset($_GET['statut_id']) ? (int)$_GET['statut_id'] : null;
$date_debut = isset($_GET['date_debut']) ? clean_input($_GET['date_debut']) : null;
$date_fin = isset($_GET['date_fin']) ? clean_input($_GET['date_fin']) : null;

// Construction de la requête SQL
$sql_count = "
    SELECT COUNT(*) as total
    FROM reparation_sms rs
    JOIN reparations r ON rs.reparation_id = r.id
    JOIN clients c ON r.client_id = c.id
    JOIN sms_templates t ON rs.template_id = t.id
    LEFT JOIN statuts s ON rs.statut_id = s.id
    WHERE 1=1
";

$sql = "
    SELECT rs.*, r.id as repair_id, r.type_appareil, r.marque, r.modele,
           c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone,
           t.nom as template_nom, s.nom as statut_nom
    FROM reparation_sms rs
    JOIN reparations r ON rs.reparation_id = r.id
    JOIN clients c ON r.client_id = c.id
    JOIN sms_templates t ON rs.template_id = t.id
    LEFT JOIN statuts s ON rs.statut_id = s.id
    WHERE 1=1
";

$params = [];

// Appliquer les filtres
if ($reparation_id) {
    $sql .= " AND rs.reparation_id = ?";
    $sql_count .= " AND rs.reparation_id = ?";
    $params[] = $reparation_id;
}

if ($statut_id) {
    $sql .= " AND rs.statut_id = ?";
    $sql_count .= " AND rs.statut_id = ?";
    $params[] = $statut_id;
}

if ($date_debut) {
    $sql .= " AND DATE(rs.date_envoi) >= ?";
    $sql_count .= " AND DATE(rs.date_envoi) >= ?";
    $params[] = $date_debut;
}

if ($date_fin) {
    $sql .= " AND DATE(rs.date_envoi) <= ?";
    $sql_count .= " AND DATE(rs.date_envoi) <= ?";
    $params[] = $date_fin;
}

// Ajouter tri et pagination
$sql .= " ORDER BY rs.date_envoi DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;

// Exécuter les requêtes
try {
    // Compter le nombre total d'enregistrements
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute(array_slice($params, 0, -2)); // Exclure les paramètres de pagination
    $total_items = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Récupérer les données
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer le nombre total de pages
    $total_pages = ceil($total_items / $items_per_page);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération de l'historique des SMS: " . $e->getMessage());
    $historique = [];
    $total_items = 0;
    $total_pages = 1;
}

// Récupérer les statuts pour le filtre
try {
    $stmt = $pdo->query("
        SELECT s.id, s.nom, c.nom as categorie_nom
        FROM statuts s
        JOIN statut_categories c ON s.categorie_id = c.id
        ORDER BY c.ordre, s.ordre
    ");
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $statuts = [];
}
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-history me-2"></i>Historique des SMS</h1>
                <div>
                    <a href="index.php?page=campagne_sms" class="btn btn-success me-2">
                        <i class="fas fa-paper-plane me-2"></i>Campagnes SMS
                    </a>
                    <?php if ($is_admin): ?>
                    <a href="index.php?page=sms_templates" class="btn btn-primary">
                        <i class="fas fa-cog me-2"></i>Gérer les modèles
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <input type="hidden" name="page" value="sms_historique">
                        
                        <div class="col-md-3">
                            <label for="reparation_id" class="form-label">ID Réparation</label>
                            <input type="number" class="form-control" id="reparation_id" name="reparation_id" 
                                value="<?php echo $reparation_id; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="statut_id" class="form-label">Statut</label>
                            <select class="form-select" id="statut_id" name="statut_id">
                                <option value="">Tous les statuts</option>
                                <?php
                                $current_categorie = '';
                                foreach ($statuts as $statut):
                                    // Ajouter un optgroup lorsque la catégorie change
                                    if ($current_categorie != $statut['categorie_nom']) {
                                        if ($current_categorie != '') {
                                            echo '</optgroup>';
                                        }
                                        $current_categorie = $statut['categorie_nom'];
                                        echo '<optgroup label="' . htmlspecialchars($current_categorie) . '">';
                                    }
                                ?>
                                    <option value="<?php echo $statut['id']; ?>" <?php echo $statut_id == $statut['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($statut['nom']); ?>
                                    </option>
                                <?php 
                                endforeach;
                                if ($current_categorie != '') {
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" 
                                value="<?php echo $date_debut; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" 
                                value="<?php echo $date_fin; ?>">
                        </div>
                        
                        <div class="col-12 text-end">
                            <a href="index.php?page=sms_historique" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-times me-1"></i>Réinitialiser
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Filtrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Résultats -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>SMS envoyés</h5>
                    <span class="badge bg-light text-dark"><?php echo $total_items; ?> résultat(s)</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($historique)): ?>
                    <div class="text-center p-4">
                        <p class="mb-0">Aucun SMS dans l'historique</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date d'envoi</th>
                                    <th>Client</th>
                                    <th>Réparation</th>
                                    <th>Statut</th>
                                    <th>Modèle</th>
                                    <th>Contenu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historique as $sms): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($sms['date_envoi'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($sms['client_nom'] . ' ' . $sms['client_prenom']); ?></strong>
                                        <div class="small text-muted"><?php echo htmlspecialchars($sms['telephone']); ?></div>
                                    </td>
                                    <td>
                                        <a href="index.php?page=modifier_reparation&id=<?php echo $sms['repair_id']; ?>">
                                            #<?php echo $sms['repair_id']; ?>
                                        </a>
                                        <div class="small text-muted">
                                            <?php echo htmlspecialchars($sms['type_appareil'] . ' ' . $sms['marque'] . ' ' . $sms['modele']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($sms['statut_nom']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($sms['statut_nom']); ?></span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Non spécifié</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($sms['template_nom']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info view-sms" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#smsContentModal"
                                            data-content="<?php echo htmlspecialchars($sms['message']); ?>"
                                            data-date="<?php echo date('d/m/Y H:i', strtotime($sms['date_envoi'])); ?>"
                                            data-client="<?php echo htmlspecialchars($sms['client_nom'] . ' ' . $sms['client_prenom']); ?>">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="javascript:void(0);" onclick="changePage(<?php echo $page - 1; ?>)" aria-label="Précédent">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="javascript:void(0);" onclick="changePage(<?php echo $i; ?>)"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="javascript:void(0);" onclick="changePage(<?php echo $page + 1; ?>)" aria-label="Suivant">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour afficher le contenu d'un SMS -->
<div class="modal fade" id="smsContentModal" tabindex="-1" aria-labelledby="smsContentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="smsContentModalLabel"><i class="fas fa-sms me-2"></i>Contenu du SMS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <small class="text-muted">Envoyé le <span id="smsDate"></span> à <span id="smsClient"></span></small>
                </div>
                <div class="card bg-light">
                    <div class="card-body">
                        <p class="mb-0" id="smsContent"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration du modal d'affichage du contenu SMS
    const smsContentModal = document.getElementById('smsContentModal');
    smsContentModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const content = button.getAttribute('data-content');
        const date = button.getAttribute('data-date');
        const client = button.getAttribute('data-client');
        
        document.getElementById('smsContent').textContent = content;
        document.getElementById('smsDate').textContent = date;
        document.getElementById('smsClient').textContent = client;
    });
    
    // Validation du formulaire de filtres
    const filterForm = document.querySelector('form');
    filterForm.addEventListener('submit', function(event) {
        // Supprimer les champs vides pour éviter les paramètres inutiles dans l'URL
        const formElements = Array.from(this.elements);
        formElements.forEach(element => {
            if (element.value === '' && element.name !== 'page') {
                element.disabled = true;
            }
        });
    });
});

// Fonction pour ajouter des paramètres à l'URL actuelle
function changePage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page_num', page);
    window.location.href = url.toString();
}
</script>

<?php if ($is_admin): ?>
<div class="mt-4 text-center">
    <p class="mb-0">Pour configurer ou modifier des modèles de SMS, accédez à la <a href="index.php?page=sms_templates">gestion des modèles de SMS</a>.</p>
</div>
<?php endif; ?> 