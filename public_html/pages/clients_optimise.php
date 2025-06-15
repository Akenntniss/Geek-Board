<?php
/**
 * Page de gestion des clients - Version optimisée
 * Améliore les performances, UX et accessibilité
 */

// Configuration de la pagination
$items_per_page = 20;
$current_page = max(1, intval($_GET['p'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

// Paramètres de recherche et tri
$search = trim($_GET['search'] ?? '');
$sort_by = $_GET['sort'] ?? 'nom';
$sort_order = ($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

// Validation des paramètres de tri
$allowed_sort_fields = ['nom', 'prenom', 'telephone', 'email', 'date_creation', 'nombre_reparations'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'nom';
}

try {
    $shop_pdo = getShopDBConnection();
    
    // Construction de la requête avec recherche
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(c.nom LIKE :search OR c.prenom LIKE :search OR c.telephone LIKE :search OR c.email LIKE :search OR CONCAT(c.prenom, ' ', c.nom) LIKE :search)";
        $params['search'] = "%{$search}%";
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Requête pour compter le total (optimisée)
    $count_sql = "
        SELECT COUNT(DISTINCT c.id) as total 
        FROM clients c 
        LEFT JOIN reparations r ON c.id = r.client_id 
        {$where_clause}
    ";
    $count_stmt = $shop_pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_clients = $count_stmt->fetchColumn();
    $total_pages = ceil($total_clients / $items_per_page);
    
    // Requête principale optimisée avec pagination
    $sql = "
        SELECT 
            c.id,
            c.nom,
            c.prenom,
            c.telephone,
            c.email,
            c.date_creation,
            COUNT(r.id) as nombre_reparations,
            SUM(CASE WHEN r.statut IN ('en_cours_diagnostique', 'en_cours_intervention', 'en_attente_accord_client') THEN 1 ELSE 0 END) as reparations_en_cours
        FROM clients c 
        LEFT JOIN reparations r ON c.id = r.client_id 
        {$where_clause}
        GROUP BY c.id 
        ORDER BY {$sort_by} {$sort_order}
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $shop_pdo->prepare($sql);
    $params['limit'] = $items_per_page;
    $params['offset'] = $offset;
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des clients : " . $e->getMessage());
    $clients = [];
    $total_clients = 0;
    $total_pages = 0;
    $error_message = "Erreur lors du chargement des clients. Veuillez réessayer.";
}

// Traitement de la suppression (avec vérification CSRF)
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['client_id'])) {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        set_message("Action non autorisée.", "danger");
    } else {
        $client_id = (int)$_POST['client_id'];
        
        try {
            $shop_pdo = getShopDBConnection();
            
            // Vérifier si le client a des réparations
            $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM reparations WHERE client_id = :id");
            $stmt->execute(['id' => $client_id]);
            $repair_count = $stmt->fetchColumn();
            
            if ($repair_count > 0) {
                set_message("Impossible de supprimer ce client car il a {$repair_count} réparation(s) associée(s).", "warning");
            } else {
                // Supprimer le client
                $stmt = $shop_pdo->prepare("DELETE FROM clients WHERE id = :id");
                $stmt->execute(['id' => $client_id]);
                
                set_message("Client supprimé avec succès.", "success");
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du client: " . $e->getMessage());
            set_message("Erreur lors de la suppression du client.", "danger");
        }
        
        // Redirection pour éviter la re-soumission
        $redirect_params = [];
        if (!empty($search)) $redirect_params['search'] = $search;
        if ($current_page > 1) $redirect_params['p'] = $current_page;
        if ($sort_by !== 'nom') $redirect_params['sort'] = $sort_by;
        if ($sort_order !== 'ASC') $redirect_params['order'] = $sort_order;
        
        $redirect_url = 'index.php?page=clients';
        if (!empty($redirect_params)) {
            $redirect_url .= '&' . http_build_query($redirect_params);
        }
        
        header("Location: {$redirect_url}");
        exit;
    }
}

// Génération du token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Fonction pour générer les liens de tri
 */
function getSortLink($field, $label, $current_sort, $current_order, $search, $current_page) {
    $new_order = ($current_sort === $field && $current_order === 'ASC') ? 'DESC' : 'ASC'; 
    $params = ['sort' => $field, 'order' => $new_order];
    if (!empty($search)) $params['search'] = $search;
    if ($current_page > 1) $params['p'] = $current_page;
    
    $url = 'index.php?page=clients&' . http_build_query($params);
    $icon = '';
    if ($current_sort === $field) {
        $icon = $current_order === 'ASC' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>';
    }
    
    return "<a href=\"{$url}\" class=\"text-decoration-none text-reset\">{$label}{$icon}</a>";
}

/**
 * Fonction pour générer les liens de pagination
 */
function getPaginationLinks($current_page, $total_pages, $search, $sort_by, $sort_order) {
    if ($total_pages <= 1) return '';
    
    $links = '<nav aria-label="Navigation des pages"><ul class="pagination justify-content-center">';
    
    $base_params = [];
    if (!empty($search)) $base_params['search'] = $search;
    if ($sort_by !== 'nom') $base_params['sort'] = $sort_by;
    if ($sort_order !== 'ASC') $base_params['order'] = $sort_order;
    
    // Bouton précédent
    if ($current_page > 1) {
        $prev_params = array_merge($base_params, ['p' => $current_page - 1]);
        $prev_url = 'index.php?page=clients&' . http_build_query($prev_params);
        $links .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$prev_url}\" aria-label=\"Page précédente\"><i class=\"fas fa-chevron-left\"></i></a></li>";
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-left"></i></span></li>';
    }
    
    // Pages
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    if ($start_page > 1) {
        $first_params = array_merge($base_params, ['p' => 1]);
        $first_url = 'index.php?page=clients&' . http_build_query($first_params);
        $links .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$first_url}\">1</a></li>";
        
        if ($start_page > 2) {
            $links .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $links .= "<li class=\"page-item active\"><span class=\"page-link\">{$i}</span></li>";
        } else {
            $page_params = array_merge($base_params, ['p' => $i]);
            $page_url = 'index.php?page=clients&' . http_build_query($page_params);
            $links .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$page_url}\">{$i}</a></li>";
        }
    }
    
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $links .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        
        $last_params = array_merge($base_params, ['p' => $total_pages]);
        $last_url = 'index.php?page=clients&' . http_build_query($last_params);
        $links .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$last_url}\">{$total_pages}</a></li>";
    }
    
    // Bouton suivant
    if ($current_page < $total_pages) {
        $next_params = array_merge($base_params, ['p' => $current_page + 1]);
        $next_url = 'index.php?page=clients&' . http_build_query($next_params);
        $links .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$next_url}\" aria-label=\"Page suivante\"><i class=\"fas fa-chevron-right\"></i></a></li>";
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-right"></i></span></li>';
    }
    
    $links .= '</ul></nav>';
    return $links;
}
?>

<!-- Messages d'alerte -->
<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endif; ?>

<!-- En-tête avec statistiques -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="mb-1">
                    <i class="fas fa-users me-2 text-primary"></i>
                    Gestion des Clients
                </h1>
                <p class="text-muted mb-0">
                    <?php 
                    if (!empty($search)) {
                        echo "Recherche : \"" . htmlspecialchars($search) . "\" - ";
                    }
                    echo number_format($total_clients) . " client" . ($total_clients > 1 ? 's' : '') . " trouvé" . ($total_clients > 1 ? 's' : '');
                    ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php?page=ajouter_client" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>
                    <span class="d-none d-sm-inline">Nouveau Client</span>
                    <span class="d-sm-none">Nouveau</span>
                </a>
                <button type="button" class="btn btn-outline-secondary" id="toggleFilters" aria-expanded="false">
                    <i class="fas fa-filter me-2"></i>
                    <span class="d-none d-sm-inline">Filtres</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Barre de recherche et filtres -->
<div class="card mb-4" id="filtersCard" style="<?php echo empty($search) ? 'display: none;' : ''; ?>">
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="clients">
            
            <div class="col-md-8">
                <label for="search" class="form-label">Rechercher un client</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="search" 
                        name="search" 
                        value="<?php echo htmlspecialchars($search); ?>" 
                        placeholder="Nom, prénom, téléphone ou email..."
                        aria-describedby="searchHelp"
                    >
                    <?php if (!empty($search)): ?>
                        <a href="index.php?page=clients" class="btn btn-outline-secondary" title="Effacer la recherche">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div id="searchHelp" class="form-text">Tapez au moins 2 caractères pour rechercher</div>
            </div>
            
            <div class="col-md-4">
                <div class="d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search me-2"></i>Rechercher
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="index.php?page=clients" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Effacer
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des clients -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($clients)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" aria-label="Liste des clients">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="d-none d-lg-table-cell">
                                <?php echo getSortLink('id', 'ID', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                            <th scope="col">
                                <?php echo getSortLink('nom', 'Nom', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                            <th scope="col" class="d-none d-md-table-cell">
                                <?php echo getSortLink('prenom', 'Prénom', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                            <th scope="col">
                                <?php echo getSortLink('telephone', 'Téléphone', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                            <th scope="col" class="d-none d-lg-table-cell">
                                <?php echo getSortLink('email', 'Email', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                            <th scope="col" class="text-center">
                                <?php echo getSortLink('nombre_reparations', 'Réparations', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                            <th scope="col" class="d-none d-xl-table-cell">
                                <?php echo getSortLink('date_creation', 'Inscrit le', $sort_by, $sort_order, $search, $current_page); ?>
                            </th>
                            <th scope="col" class="text-center" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge bg-light text-dark">#<?php echo $client['id']; ?></span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <strong><?php echo htmlspecialchars($client['nom']); ?></strong>
                                        <span class="d-md-none text-muted small">
                                            <?php echo htmlspecialchars($client['prenom']); ?>
                                        </span>
                                        <span class="d-lg-none text-muted small">
                                            <i class="fas fa-phone fa-xs me-1"></i>
                                            <?php echo htmlspecialchars($client['telephone']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo htmlspecialchars($client['prenom']); ?>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <a href="tel:<?php echo htmlspecialchars($client['telephone']); ?>" 
                                       class="text-decoration-none">
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo htmlspecialchars($client['telephone']); ?>
                                    </a>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php if (!empty($client['email'])): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($client['email']); ?>" 
                                           class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?php echo htmlspecialchars($client['email']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Non renseigné</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($client['nombre_reparations'] > 0): ?>
                                        <span class="badge bg-primary position-relative">
                                            <?php echo $client['nombre_reparations']; ?>
                                            <?php if ($client['reparations_en_cours'] > 0): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                                                    <?php echo $client['reparations_en_cours']; ?>
                                                    <span class="visually-hidden">réparations en cours</span>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-xl-table-cell">
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($client['date_creation'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Actions client">
                                        <?php if ($client['nombre_reparations'] > 0): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#historiqueModal" 
                                                    data-client-id="<?php echo $client['id']; ?>"
                                                    data-client-nom="<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>"
                                                    title="Voir l'historique">
                                                <i class="fas fa-history"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="index.php?page=modifier_client&id=<?php echo $client['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Modifier le client">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                title="Supprimer le client"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#confirmDeleteModal"
                                                data-client-id="<?php echo $client['id']; ?>"
                                                data-client-nom="<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>"
                                                data-client-reparations="<?php echo $client['nombre_reparations']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        Page <?php echo $current_page; ?> sur <?php echo $total_pages; ?> 
                        (<?php echo number_format($total_clients); ?> client<?php echo $total_clients > 1 ? 's' : ''; ?> au total)
                    </div>
                    <?php echo getPaginationLinks($current_page, $total_pages, $search, $sort_by, $sort_order); ?>
                </div>
            </div>
            
        <?php else: ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-users text-muted" style="font-size: 4rem;"></i>
                </div>
                <h3 class="text-muted mb-3">
                    <?php echo !empty($search) ? 'Aucun résultat trouvé' : 'Aucun client enregistré'; ?>
                </h3>
                <p class="text-muted mb-4">
                    <?php echo !empty($search) 
                        ? 'Essayez avec d\'autres termes de recherche ou' 
                        : 'Vous pouvez'; ?> 
                    ajouter votre premier client.
                </p>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <?php if (!empty($search)): ?>
                        <a href="index.php?page=clients" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Effacer la recherche
                        </a>
                    <?php endif; ?>
                    <a href="index.php?page=ajouter_client" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Ajouter un client
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">Êtes-vous sûr de vouloir supprimer le client <strong id="clientName"></strong> ?</p>
                <div class="alert alert-warning d-none" id="deleteWarning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Ce client a <span id="repairCount"></span> réparation(s) associée(s). La suppression est impossible.
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Cette action est irréversible.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <form method="POST" action="" class="d-inline" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="client_id" id="deleteClientId" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-2"></i>Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'historique des réparations -->
<div class="modal fade" id="historiqueModal" tabindex="-1" aria-labelledby="historiqueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historiqueModalLabel">
                    <i class="fas fa-history me-2"></i>
                    Historique des réparations - <span id="clientNameHistorique"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div id="historiqueContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement de l'historique...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle des filtres
    const toggleFiltersBtn = document.getElementById('toggleFilters');
    const filtersCard = document.getElementById('filtersCard');
    
    if (toggleFiltersBtn && filtersCard) {
        toggleFiltersBtn.addEventListener('click', function() {
            const isVisible = filtersCard.style.display !== 'none';
            filtersCard.style.display = isVisible ? 'none' : 'block';
            this.setAttribute('aria-expanded', !isVisible);
            
            // Focus sur le champ de recherche si on affiche les filtres
            if (!isVisible) {
                const searchInput = document.getElementById('search');
                if (searchInput) {
                    setTimeout(() => searchInput.focus(), 150);
                }
            }
        });
    }
    
    // Modal de confirmation de suppression
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    if (confirmDeleteModal) {
        confirmDeleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const clientId = button.getAttribute('data-client-id');
            const clientNom = button.getAttribute('data-client-nom');
            const clientReparations = parseInt(button.getAttribute('data-client-reparations')) || 0;
            
            const clientNameSpan = document.getElementById('clientName');
            const deleteClientIdInput = document.getElementById('deleteClientId');
            const deleteWarning = document.getElementById('deleteWarning');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const repairCountSpan = document.getElementById('repairCount');
            
            if (clientNameSpan) clientNameSpan.textContent = clientNom;
            if (deleteClientIdInput) deleteClientIdInput.value = clientId;
            if (repairCountSpan) repairCountSpan.textContent = clientReparations;
            
            // Gérer l'avertissement si le client a des réparations
            if (clientReparations > 0) {
                if (deleteWarning) deleteWarning.classList.remove('d-none');
                if (confirmDeleteBtn) {
                    confirmDeleteBtn.disabled = true;
                    confirmDeleteBtn.classList.add('d-none');
                }
            } else {
                if (deleteWarning) deleteWarning.classList.add('d-none');
                if (confirmDeleteBtn) {
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.classList.remove('d-none');
                }
            }
        });
    }
    
    // Modal d'historique des réparations (chargement AJAX)
    const historiqueModal = document.getElementById('historiqueModal');
    if (historiqueModal) {
        historiqueModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const clientId = button.getAttribute('data-client-id');
            const clientNom = button.getAttribute('data-client-nom');
            
            const clientNameSpan = document.getElementById('clientNameHistorique');
            const historiqueContent = document.getElementById('historiqueContent');
            
            if (clientNameSpan) clientNameSpan.textContent = clientNom;
            
            if (historiqueContent && clientId) {
                // Réinitialiser le contenu avec un loader
                historiqueContent.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement de l'historique...</p>
                    </div>
                `;
                
                // Charger l'historique via AJAX
                fetch(`ajax/get_client_history.php?client_id=${clientId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            historiqueContent.innerHTML = data.html;
                        } else {
                            historiqueContent.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Erreur lors du chargement de l'historique: ${data.message}
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        historiqueContent.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Erreur lors du chargement de l'historique.
                            </div>
                        `;
                    });
            }
        });
    }
    
    // Auto-focus sur le champ de recherche avec Ctrl+F ou Cmd+F
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.getElementById('search');
            if (searchInput) {
                if (filtersCard && filtersCard.style.display === 'none') {
                    filtersCard.style.display = 'block';
                    toggleFiltersBtn.setAttribute('aria-expanded', 'true');
                }
                searchInput.focus();
                searchInput.select();
            }
        }
    });
    
    // Recherche en temps réel (optionnel - debounced)
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    // Auto-submit après 500ms d'inactivité
                    this.form.submit();
                }, 500);
            }
        });
    }
});
</script>

<style>
/* Améliorations visuelles */
.table th a {
    color: inherit;
    text-decoration: none;
}

.table th a:hover {
    color: var(--bs-primary);
}

.badge {
    font-size: 0.75em;
}

.btn-group .btn {
    border-radius: 0.375rem;
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .card-body {
        padding: 0.75rem;
    }
    
    .table-responsive {
        border: none;
    }
    
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
        margin-right: 0;
        border-radius: 0.375rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .table-light {
        --bs-table-bg: #2d3748;
        --bs-table-color: #e2e8f0;
    }
    
    .card {
        --bs-card-bg: #1a202c;
        --bs-card-color: #e2e8f0;
    }
}

/* Animations */
.modal {
    --bs-modal-fade-transform: scale(0.9);
}

.table-hover tbody tr:hover {
    --bs-table-hover-bg: rgba(var(--bs-primary-rgb), 0.075);
}

/* Loading states */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Print styles */
@media print {
    .btn, .pagination, .modal {
        display: none !important;
    }
    
    .table {
        font-size: 0.8rem;
    }
}
</style> 