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
 * Fonction pour générer les URLs de tri
 */
function getSortUrl($field, $order, $search) {
    $params = [
        'page' => 'clients',
        'sort' => $field,
        'order' => strtoupper($order)
    ];
    
    if (!empty($search)) {
        $params['search'] = $search;
    }
    
    return 'index.php?' . http_build_query($params);
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
            </div>
        </div>
    </div>
</div>

<!-- Barre de recherche dynamique -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col-md-8 col-lg-6">
                <div class="position-relative">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input 
                            type="text" 
                            class="form-control border-start-0 ps-0" 
                            id="searchInput" 
                            value="<?php echo htmlspecialchars($search); ?>" 
                            placeholder="Rechercher un client (nom, prénom, téléphone, email)..."
                            autocomplete="off"
                        >
                        <button type="button" class="btn btn-outline-secondary d-none" id="clearSearch" title="Effacer la recherche">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Indicateur de recherche -->
                    <div class="position-absolute top-50 end-0 translate-middle-y me-3 d-none" id="searchSpinner">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Recherche...</span>
                        </div>
                    </div>
                </div>
                
                <!-- Suggestions de recherche -->
                <div class="mt-2 d-none" id="searchSuggestions">
                    <small class="text-muted">
                        <i class="fas fa-lightbulb me-1"></i>
                        Suggestions : essayez "Martin", "06", "@gmail.com"
                    </small>
                </div>
                
                <!-- Résultats de recherche -->
                <div class="mt-2" id="searchResults">
                    <?php if (!empty($search)): ?>
                        <small class="text-muted">
                            <i class="fas fa-filter me-1"></i>
                            Recherche active : "<strong><?php echo htmlspecialchars($search); ?></strong>" 
                            - <?php echo number_format($total_clients); ?> résultat<?php echo $total_clients > 1 ? 's' : ''; ?>
                            <a href="index.php?page=clients" class="text-decoration-none ms-2">
                                <i class="fas fa-times"></i> Effacer
                            </a>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-6">
                <div class="d-flex justify-content-end gap-2 mt-3 mt-md-0">
                    <!-- Tri rapide -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-sort me-2"></i>
                            <span class="d-none d-sm-inline">Trier</span>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item" href="<?php echo getSortUrl('nom', 'ASC', $search); ?>">
                                <i class="fas fa-sort-alpha-down me-2"></i>Nom A-Z
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo getSortUrl('nom', 'DESC', $search); ?>">
                                <i class="fas fa-sort-alpha-up me-2"></i>Nom Z-A
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo getSortUrl('date_creation', 'DESC', $search); ?>">
                                <i class="fas fa-clock me-2"></i>Plus récents
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo getSortUrl('nombre_reparations', 'DESC', $search); ?>">
                                <i class="fas fa-tools me-2"></i>Plus de réparations
                            </a></li>
                        </ul>
                    </div>
                    
                    <!-- Export -->
                    <button type="button" class="btn btn-outline-success" title="Exporter la liste">
                        <i class="fas fa-download"></i>
                        <span class="d-none d-lg-inline ms-2">Export</span>
                    </button>
                </div>
            </div>
        </div>
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
                                        <span class="d-lg-none text-muted small d-flex align-items-center justify-content-between">
                                            <span>
                                                <i class="fas fa-phone fa-xs me-1"></i>
                                                <?php echo htmlspecialchars($client['telephone']); ?>
                                            </span>
                                            <?php if (!empty($client['telephone'])): ?>
                                                <button type="button" 
                                                        class="btn btn-xs btn-outline-success ms-2" 
                                                        style="font-size: 0.7rem; padding: 0.1rem 0.3rem;"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#smsModal"
                                                        data-client-id="<?php echo $client['id']; ?>"
                                                        data-client-nom="<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>"
                                                        data-client-telephone="<?php echo htmlspecialchars($client['telephone']); ?>"
                                                        title="Envoyer un SMS">
                                                    <i class="fas fa-sms"></i>
                                                </button>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo htmlspecialchars($client['prenom']); ?>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="tel:<?php echo htmlspecialchars($client['telephone']); ?>" 
                                           class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i>
                                            <?php echo htmlspecialchars($client['telephone']); ?>
                                        </a>
                                        <?php if (!empty($client['telephone'])): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#smsModal"
                                                    data-client-id="<?php echo $client['id']; ?>"
                                                    data-client-nom="<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>"
                                                    data-client-telephone="<?php echo htmlspecialchars($client['telephone']); ?>"
                                                    title="Envoyer un SMS">
                                                <i class="fas fa-sms"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="confirmDeleteModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Êtes-vous sûr de vouloir supprimer le client <strong id="clientNameToDelete"></strong> ?</p>
                <div class="alert alert-warning d-none" id="deleteWarning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention :</strong> Ce client a <span id="repairCount"></span> réparation(s) associée(s). 
                    Il ne pourra pas être supprimé.
                </div>
                <p class="text-muted small mb-0">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <form method="POST" action="index.php?page=clients" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="client_id" id="clientIdToDelete">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-2"></i>Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour l'historique des réparations -->
<div class="modal fade" id="historiqueModal" tabindex="-1" aria-labelledby="historiqueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historiqueModalLabel">
                    <i class="fas fa-history me-2"></i>
                    Historique des réparations - <span id="clientNameHistory"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div id="historyContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2 text-muted">Chargement de l'historique...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour envoyer un SMS -->
<div class="modal fade" id="smsModal" tabindex="-1" aria-labelledby="smsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="smsModalLabel">
                    <i class="fas fa-sms me-2"></i>
                    Envoyer un SMS - <span id="clientNameSms"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="smsForm">
                    <!-- Informations du client -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Client</label>
                            <p class="form-control-plaintext" id="smsClientInfo"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Téléphone</label>
                            <p class="form-control-plaintext" id="smsClientPhone"></p>
                        </div>
                    </div>
                    
                    <!-- Type de message -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Type de message</label>
                        <div class="btn-group w-100" role="group" aria-label="Type de message">
                            <input type="radio" class="btn-check" name="messageType" id="templateType" value="template" checked>
                            <label class="btn btn-outline-primary" for="templateType">
                                <i class="fas fa-file-text me-2"></i>Template prédéfini
                            </label>
                            
                            <input type="radio" class="btn-check" name="messageType" id="customType" value="custom">
                            <label class="btn btn-outline-primary" for="customType">
                                <i class="fas fa-edit me-2"></i>Message personnalisé
                            </label>
                        </div>
                    </div>
                    
                    <!-- Sélection du template -->
                    <div class="mb-3" id="templateSection">
                        <label for="smsTemplate" class="form-label fw-bold">Choisir un template</label>
                        <select class="form-select" id="smsTemplate" name="template_id">
                            <option value="">Chargement des templates...</option>
                        </select>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Les variables {CLIENT_NOM}, {CLIENT_PRENOM}, {DATE} seront automatiquement remplacées.
                        </div>
                    </div>
                    
                    <!-- Message personnalisé -->
                    <div class="mb-3 d-none" id="customSection">
                        <label for="customMessage" class="form-label fw-bold">Message personnalisé</label>
                        <textarea class="form-control" id="customMessage" name="custom_message" rows="4" 
                                  placeholder="Tapez votre message ici..." maxlength="160"></textarea>
                        <div class="form-text d-flex justify-content-between">
                            <span>
                                <i class="fas fa-info-circle me-1"></i>
                                Maximum 160 caractères pour un SMS
                            </span>
                            <span id="charCount">0/160</span>
                        </div>
                    </div>
                    
                    <!-- Aperçu du message -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Aperçu du message</label>
                        <div class="card bg-light">
                            <div class="card-body">
                                <div id="messagePreview" class="text-muted fst-italic">
                                    Sélectionnez un template ou tapez un message pour voir l'aperçu...
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Longueur : <span id="previewLength">0</span> caractères
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Messages d'alerte -->
                    <div id="smsAlert" class="alert d-none" role="alert"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-success" id="sendSmsBtn">
                    <i class="fas fa-paper-plane me-2"></i>Envoyer le SMS
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la recherche dynamique
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    const searchSpinner = document.getElementById('searchSpinner');
    const searchSuggestions = document.getElementById('searchSuggestions');
    const searchResults = document.getElementById('searchResults');
    
    let searchTimeout;
    
    if (searchInput) {
        // Afficher le bouton clear si il y a du texte
        function toggleClearButton() {
            if (searchInput.value.length > 0) {
                clearSearchBtn.classList.remove('d-none');
            } else {
                clearSearchBtn.classList.add('d-none');
            }
        }
        
        // Afficher les suggestions si le champ est vide et focus
        function toggleSuggestions() {
            if (searchInput.value.length === 0 && document.activeElement === searchInput) {
                searchSuggestions.classList.remove('d-none');
            } else {
                searchSuggestions.classList.add('d-none');
            }
        }
        
        // Recherche avec debounce
        function performSearch() {
            const query = searchInput.value.trim();
            
            if (query.length === 0) {
                // Rediriger vers la page sans recherche
                window.location.href = 'index.php?page=clients';
                return;
            }
            
            if (query.length < 2) {
                return; // Attendre au moins 2 caractères
            }
            
            // Afficher le spinner
            searchSpinner.classList.remove('d-none');
            
            // Simuler un délai puis rediriger
            setTimeout(() => {
                const params = new URLSearchParams();
                params.append('page', 'clients');
                params.append('search', query);
                
                window.location.href = 'index.php?' + params.toString();
            }, 300);
        }
        
        // Événements
        searchInput.addEventListener('input', function() {
            toggleClearButton();
            toggleSuggestions();
            
            // Debounce la recherche
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 800);
        });
        
        searchInput.addEventListener('focus', function() {
            toggleSuggestions();
        });
        
        searchInput.addEventListener('blur', function() {
            setTimeout(() => {
                searchSuggestions.classList.add('d-none');
            }, 200);
        });
        
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                performSearch();
            }
            if (e.key === 'Escape') {
                searchInput.blur();
            }
        });
        
        // Bouton clear
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                toggleClearButton();
                searchInput.focus();
                
                // Rediriger vers la page sans recherche
                setTimeout(() => {
                    window.location.href = 'index.php?page=clients';
                }, 100);
            });
        }
        
        // Initialiser l'état
        toggleClearButton();
    }
    
    // Gestion de la modal de confirmation de suppression
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    if (confirmDeleteModal) {
        confirmDeleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const clientId = button.getAttribute('data-client-id');
            const clientName = button.getAttribute('data-client-nom');
            const repairCount = parseInt(button.getAttribute('data-client-reparations'));
            
            // Mise à jour du contenu de la modal
            document.getElementById('clientNameToDelete').textContent = clientName;
            document.getElementById('clientIdToDelete').value = clientId;
            
            // Gestion de l'avertissement pour les clients avec réparations
            const warningAlert = document.getElementById('deleteWarning');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            if (repairCount > 0) {
                document.getElementById('repairCount').textContent = repairCount;
                warningAlert.classList.remove('d-none');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fas fa-ban me-2"></i>Suppression impossible';
                confirmBtn.classList.replace('btn-danger', 'btn-secondary');
            } else {
                warningAlert.classList.add('d-none');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Supprimer définitivement';
                confirmBtn.classList.replace('btn-secondary', 'btn-danger');
            }
        });
    }
    
    // Gestion de la modal d'historique
    const historiqueModal = document.getElementById('historiqueModal');
    if (historiqueModal) {
        // Gestionnaire de clic direct sur les boutons d'historique
        document.addEventListener('click', function(event) {
            const button = event.target.closest('[data-bs-target="#historiqueModal"]');
            if (button) {
                const clientId = button.getAttribute('data-client-id');
                const clientName = button.getAttribute('data-client-nom');
                
                if (clientId && clientName) {
                    // Stocker les données dans la modal
                    historiqueModal.setAttribute('data-current-client-id', clientId);
                    historiqueModal.setAttribute('data-current-client-name', clientName);
                }
            }
        });
        
        historiqueModal.addEventListener('show.bs.modal', function(event) {
            // Récupérer les données stockées dans la modal ou depuis le bouton déclencheur
            let clientId = historiqueModal.getAttribute('data-current-client-id');
            let clientName = historiqueModal.getAttribute('data-current-client-name');
            
            // Fallback: essayer de récupérer depuis event.relatedTarget
            if (!clientId || !clientName) {
                const button = event.relatedTarget;
                if (button) {
                    clientId = button.getAttribute('data-client-id');
                    clientName = button.getAttribute('data-client-nom');
                }
            }
            
            // Vérification des données requises
            if (!clientId || !clientName) {
                console.error('Impossible de récupérer les données du client pour l\'historique');
                const historyContent = document.getElementById('historyContent');
                historyContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Erreur: Impossible de charger l\'historique du client.</div>';
                return;
            }
            
            // Mise à jour du titre
            document.getElementById('clientNameHistory').textContent = clientName;
            
            // Chargement du contenu via AJAX
            const historyContent = document.getElementById('historyContent');
            historyContent.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2 text-muted">Chargement de l'historique...</p>
                </div>
            `;
            
            // Requête AJAX pour récupérer l'historique avec gestion d'erreur améliorée
            // Toujours transmettre le shop_id actuel de la session
            const shopId = '<?php echo $_SESSION["shop_id"] ?? ""; ?>';
            const ajaxUrl = shopId ? 
                `ajax/get_client_history.php?client_id=${clientId}&shop_id=${shopId}` :
                `ajax/get_client_history.php?client_id=${clientId}`;
            
            console.log('🔍 [HISTORIQUE] Début du chargement');
            console.log('📊 [HISTORIQUE] Client ID:', clientId);
            console.log('🏪 [HISTORIQUE] Shop ID:', shopId);
            console.log('🌐 [HISTORIQUE] URL AJAX:', ajaxUrl);
            
            fetch(ajaxUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP: ${response.status}`);
                    }
                    return response.text();
                })
                .then(data => {
                    historyContent.innerHTML = data;
                    
                    // Ajouter le script pour les détails collapsibles
                    if (!window.toggleDetails) {
                        window.toggleDetails = function(repairId) {
                            const detailsRow = document.getElementById('details-' + repairId);
                            if (detailsRow) {
                                detailsRow.classList.toggle('d-none');
                            }
                        };
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement de l\'historique:', error);
                    historyContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erreur lors du chargement de l'historique: ${error.message}
                            <br><small class="text-muted">Vérifiez que vous êtes connecté et réessayez.</small>
                        </div>
                    `;
                });
        });
    }
    
    // Amélioration de l'accessibilité - navigation au clavier
    document.addEventListener('keydown', function(event) {
        // Ctrl + F pour ouvrir la recherche
        if (event.ctrlKey && event.key === 'f' && !event.shiftKey && !event.altKey) {
            event.preventDefault();
            const searchInput = document.getElementById('search');
            if (searchInput) {
                // Afficher les filtres si masqués
                if (filtersCard && filtersCard.style.display === 'none') {
                    toggleFiltersBtn.click();
                }
                searchInput.focus();
                searchInput.select();
            }
        }
    });
    
    // Auto-soumission de la recherche avec debounce
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2 || query.length === 0) {
                searchTimeout = setTimeout(() => {
                    // Auto-soumission du formulaire
                    const form = this.closest('form');
                    if (form) {
                        form.submit();
                    }
                }, 500); // Debounce de 500ms
            }
        });
    }
    
    // Indicateur de chargement pour les liens de navigation
    document.querySelectorAll('a[href*="page=clients"]').forEach(link => {
        link.addEventListener('click', function() {
            // Ajout d'un indicateur de chargement visuel
            const icon = this.querySelector('i');
            if (icon && !icon.classList.contains('fa-spinner')) {
                const originalClass = icon.className;
                icon.className = 'fas fa-spinner fa-spin';
                
                // Restaurer l'icône originale après un délai
                setTimeout(() => {
                    icon.className = originalClass;
                }, 2000);
            }
        });
    });
    
    // Gestion du modal SMS
    const smsModal = document.getElementById('smsModal');
    let currentClientData = {};
    let smsTemplates = [];
    
    if (smsModal) {
        // Charger les templates SMS au chargement de la page
        loadSmsTemplates();
        
        // Gestionnaire d'ouverture du modal SMS
        document.addEventListener('click', function(event) {
            const button = event.target.closest('[data-bs-target="#smsModal"]');
            if (button) {
                const clientId = button.getAttribute('data-client-id');
                const clientName = button.getAttribute('data-client-nom');
                const clientPhone = button.getAttribute('data-client-telephone');
                
                if (clientId && clientName && clientPhone) {
                    currentClientData = {
                        id: clientId,
                        name: clientName,
                        phone: clientPhone
                    };
                }
            }
        });
        
        smsModal.addEventListener('show.bs.modal', function(event) {
            if (!currentClientData.id) {
                console.error('Données client manquantes pour le SMS');
                return;
            }
            
            // Mise à jour des informations client
            document.getElementById('clientNameSms').textContent = currentClientData.name;
            document.getElementById('smsClientInfo').textContent = currentClientData.name;
            document.getElementById('smsClientPhone').textContent = currentClientData.phone;
            
            // Réinitialiser le formulaire
            resetSmsForm();
        });
        
        // Gestion du changement de type de message
        document.querySelectorAll('input[name="messageType"]').forEach(radio => {
            radio.addEventListener('change', function() {
                toggleMessageType(this.value);
                updatePreview();
            });
        });
        
        // Gestion du changement de template
        document.getElementById('smsTemplate').addEventListener('change', function() {
            updatePreview();
        });
        
        // Gestion du message personnalisé
        document.getElementById('customMessage').addEventListener('input', function() {
            updateCharCount();
            updatePreview();
        });
        
        // Bouton d'envoi SMS
        document.getElementById('sendSmsBtn').addEventListener('click', function() {
            sendSms();
        });
    }
    
    // Fonctions pour le modal SMS
    function loadSmsTemplates() {
        fetch('ajax/get_sms_templates.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    smsTemplates = data.templates;
                    populateTemplateSelect();
                } else {
                    console.error('Erreur lors du chargement des templates:', data.error);
                    showSmsAlert('Erreur lors du chargement des templates SMS', 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur AJAX:', error);
                showSmsAlert('Erreur de connexion lors du chargement des templates', 'danger');
            });
    }
    
    function populateTemplateSelect() {
        const select = document.getElementById('smsTemplate');
        select.innerHTML = '<option value="">-- Choisir un template --</option>';
        
        smsTemplates.forEach(template => {
            const option = document.createElement('option');
            option.value = template.id;
            option.textContent = template.nom;
            option.setAttribute('data-content', template.contenu);
            select.appendChild(option);
        });
    }
    
    function toggleMessageType(type) {
        const templateSection = document.getElementById('templateSection');
        const customSection = document.getElementById('customSection');
        
        if (type === 'template') {
            templateSection.classList.remove('d-none');
            customSection.classList.add('d-none');
        } else {
            templateSection.classList.add('d-none');
            customSection.classList.remove('d-none');
        }
    }
    
    function updateCharCount() {
        const textarea = document.getElementById('customMessage');
        const charCount = document.getElementById('charCount');
        const length = textarea.value.length;
        
        charCount.textContent = `${length}/160`;
        
        if (length > 160) {
            charCount.classList.add('text-danger');
            textarea.classList.add('is-invalid');
        } else {
            charCount.classList.remove('text-danger');
            textarea.classList.remove('is-invalid');
        }
    }
    
    function updatePreview() {
        const messageType = document.querySelector('input[name="messageType"]:checked').value;
        const preview = document.getElementById('messagePreview');
        const previewLength = document.getElementById('previewLength');
        let message = '';
        
        if (messageType === 'template') {
            const templateSelect = document.getElementById('smsTemplate');
            const selectedOption = templateSelect.options[templateSelect.selectedIndex];
            
            if (selectedOption && selectedOption.value) {
                message = selectedOption.getAttribute('data-content') || '';
                
                // Remplacer les variables
                message = message.replace(/{CLIENT_NOM}/g, currentClientData.name.split(' ')[0] || '');
                message = message.replace(/{CLIENT_PRENOM}/g, currentClientData.name.split(' ')[1] || '');
                message = message.replace(/{DATE}/g, new Date().toLocaleDateString('fr-FR'));
            }
        } else {
            message = document.getElementById('customMessage').value;
        }
        
        if (message.trim()) {
            preview.textContent = message;
            preview.classList.remove('text-muted', 'fst-italic');
        } else {
            preview.textContent = 'Sélectionnez un template ou tapez un message pour voir l\'aperçu...';
            preview.classList.add('text-muted', 'fst-italic');
        }
        
        previewLength.textContent = message.length;
        
        // Colorer en rouge si trop long
        if (message.length > 160) {
            previewLength.classList.add('text-danger');
        } else {
            previewLength.classList.remove('text-danger');
        }
    }
    
    function resetSmsForm() {
        // Réinitialiser les radios
        document.getElementById('templateType').checked = true;
        document.getElementById('customType').checked = false;
        
        // Réinitialiser les sections
        toggleMessageType('template');
        
        // Réinitialiser les champs
        document.getElementById('smsTemplate').value = '';
        document.getElementById('customMessage').value = '';
        
        // Réinitialiser l'aperçu
        updatePreview();
        updateCharCount();
        
        // Masquer les alertes
        hideSmsAlert();
    }
    
    function sendSms() {
        const messageType = document.querySelector('input[name="messageType"]:checked').value;
        const sendBtn = document.getElementById('sendSmsBtn');
        
        // Validation
        if (messageType === 'template') {
            const templateId = document.getElementById('smsTemplate').value;
            if (!templateId) {
                showSmsAlert('Veuillez sélectionner un template', 'warning');
                return;
            }
        } else {
            const customMessage = document.getElementById('customMessage').value.trim();
            if (!customMessage) {
                showSmsAlert('Veuillez saisir un message', 'warning');
                return;
            }
            if (customMessage.length > 160) {
                showSmsAlert('Le message est trop long (maximum 160 caractères)', 'warning');
                return;
            }
        }
        
        // Désactiver le bouton et afficher le spinner
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi en cours...';
        
        // Préparer les données
        const data = {
            client_id: currentClientData.id,
            telephone: currentClientData.phone,
            message_type: messageType
        };
        
        if (messageType === 'template') {
            data.template_id = document.getElementById('smsTemplate').value;
        } else {
            data.custom_message = document.getElementById('customMessage').value.trim();
        }
        
        // Envoyer la requête
        fetch('ajax/send_client_sms.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showSmsAlert(`SMS envoyé avec succès à ${currentClientData.phone}`, 'success');
                
                // Fermer le modal après 2 secondes
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(smsModal);
                    modal.hide();
                }, 2000);
            } else {
                showSmsAlert(`Erreur: ${result.error}`, 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'envoi du SMS:', error);
            showSmsAlert('Erreur de connexion lors de l\'envoi du SMS', 'danger');
        })
        .finally(() => {
            // Réactiver le bouton
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Envoyer le SMS';
        });
    }
    
    function showSmsAlert(message, type) {
        const alert = document.getElementById('smsAlert');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'} me-2"></i>${message}`;
        alert.classList.remove('d-none');
    }
    
    function hideSmsAlert() {
        const alert = document.getElementById('smsAlert');
        alert.classList.add('d-none');
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des modals
    const modals = document.querySelectorAll('.modal');
    let isModalTransitioning = false;

    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function(e) {
            if (isModalTransitioning) {
                e.preventDefault();
                return;
            }
            isModalTransitioning = true;
            this.classList.add('modal-ready');
        });
        
        modal.addEventListener('shown.bs.modal', function() {
            isModalTransitioning = false;
        });
        
        modal.addEventListener('hide.bs.modal', function(e) {
            if (isModalTransitioning) {
                e.preventDefault();
                return;
            }
            isModalTransitioning = true;
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            isModalTransitioning = false;
            this.classList.remove('modal-ready');
        });

        // Empêcher la fermeture en cliquant en dehors
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                e.preventDefault();
            }
        });
    });

    // Empêcher la fermeture avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                e.preventDefault();
            }
        }
    });
});
</script>

<style>
.modal {
    transition: opacity 0.15s linear;
    pointer-events: none;
}
.modal.show {
    pointer-events: auto;
}
.modal-dialog {
    transition: transform 0.15s ease-out;
}
.modal.fade .modal-dialog {
    transform: scale(0.98);
}
.modal.show .modal-dialog {
    transform: scale(1);
}
.modal-backdrop {
    transition: opacity 0.15s linear;
}
</style>