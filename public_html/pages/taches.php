<?php
// Récupération des filtres
$status = isset($_GET['status']) ? $_GET['status'] : null;
$priorite = isset($_GET['priorite']) ? $_GET['priorite'] : null;
$employe_id = isset($_GET['employe_id']) ? $_GET['employe_id'] : null;

// Récupérer l'ID de l'utilisateur connecté
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Obtenir la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Construction de la requête SQL
$sql = "SELECT t.*, 
        e.full_name as employe_nom,
        c.full_name as createur_nom
        FROM taches t
        LEFT JOIN users e ON t.employe_id = e.id
        LEFT JOIN users c ON t.created_by = c.id
        WHERE 1=1";

// Ajout des conditions de filtrage
if ($status) {
    $sql .= " AND t.statut = ?";
}
if ($priorite) {
    $sql .= " AND t.priorite = ?";
}
if ($employe_id) {
    $sql .= " AND t.employe_id = ?";
}

// Ajout de la condition pour ne montrer que les tâches de l'utilisateur connecté ou non assignées
$sql .= " AND (t.employe_id = ? OR t.employe_id IS NULL)";

// Ajout du tri
$sql .= " ORDER BY t.date_creation DESC";

try {
    $stmt = $shop_pdo->prepare($sql);
    $params = [];
    if ($status) {
        $params[] = $status;
    }
    if ($priorite) {
        $params[] = $priorite;
    }
    if ($employe_id) {
        $params[] = $employe_id;
    }
    // Ajout de l'ID de l'utilisateur connecté
    $params[] = $current_user_id;
    $stmt->execute($params);
    $taches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des tâches: " . $e->getMessage(), "error");
    $taches = [];
}

// Récupération des utilisateurs pour le filtre
try {
    $stmt = $shop_pdo->query("SELECT id, full_name FROM users ORDER BY full_name ASC");
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des utilisateurs: " . $e->getMessage(), "error");
    $utilisateurs = [];
}

// Comptage des tâches par statut
try {
    $stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM taches");
    $total_taches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM taches WHERE statut = ?");
    $stmt->execute(['a_faire']);
    $total_a_faire = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM taches WHERE statut = ?");
    $stmt->execute(['en_cours']);
    $total_en_cours = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM taches WHERE statut = ?");
    $stmt->execute(['termine']);
    $total_terminees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM taches WHERE priorite = ?");
    $stmt->execute(['haute']);
    $total_haute_priorite = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    set_message("Erreur lors du comptage des tâches: " . $e->getMessage(), "error");
    $total_taches = $total_a_faire = $total_en_cours = $total_terminees = $total_haute_priorite = 0;
}

// Traitement de la suppression
if (isset($_GET['action']) && $_GET['action'] == 'supprimer' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $shop_pdo->prepare("DELETE FROM taches WHERE id = ?");
        $stmt->execute([$id]);
        set_message("Tâche supprimée avec succès!", "success");
        redirect("taches");
    } catch (PDOException $e) {
        set_message("Erreur lors de la suppression de la tâche: " . $e->getMessage(), "error");
    }
}
?>

<div class="taches-content-container">
<!-- Boutons de filtre pour les statuts -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="filter-buttons" role="group" aria-label="Filtres rapides">
            <a href="index.php?page=taches" class="filter-btn <?php echo empty($status) && empty($priorite) ? 'active' : ''; ?>">
                <i class="fas fa-tasks fa-2x mb-2"></i>
                <span>Toutes</span>
                <span class="count"><?php echo $total_taches ?? 0; ?></span>
            </a>
            
            <!-- Bouton À faire -->
            <a href="index.php?page=taches&status=a_faire" class="filter-btn <?php echo $status == 'a_faire' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                <span>À faire</span>
                <span class="count"><?php echo $total_a_faire ?? 0; ?></span>
            </a>
            
            <!-- Bouton En cours -->
            <a href="index.php?page=taches&status=en_cours" class="filter-btn <?php echo $status == 'en_cours' ? 'active' : ''; ?>">
                <i class="fas fa-spinner fa-2x mb-2"></i>
                <span>En cours</span>
                <span class="count"><?php echo $total_en_cours ?? 0; ?></span>
            </a>
            
            <!-- Bouton Terminé -->
            <a href="index.php?page=taches&status=termine" class="filter-btn <?php echo $status == 'termine' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <span>Terminé</span>
                <span class="count"><?php echo $total_terminees ?? 0; ?></span>
            </a>
            
            <!-- Bouton Haute priorité -->
            <a href="index.php?page=taches&priorite=haute" class="filter-btn <?php echo $priorite == 'haute' ? 'active' : ''; ?>">
                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                <span>Priorité haute</span>
                <span class="count"><?php echo $total_haute_priorite ?? 0; ?></span>
            </a>
        </div>
    </div>
</div>

<!-- Barre de recherche optimisée -->
<div class="card mb-4 search-card">
    <div class="card-body">
        <form method="GET" action="index.php" class="search-form">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0">
                    <i class="fas fa-search text-primary"></i>
                </span>
                <input type="hidden" name="page" value="taches">
                <input type="text" class="form-control border-start-0 ps-0" name="search" placeholder="Rechercher une tâche..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button class="btn btn-primary" type="submit">Rechercher</button>
                <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="index.php?page=taches" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i>
                </a>
                <?php endif; ?>
            </div>
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <a href="index.php?page=ajouter_tache" class="btn btn-success btn-sm">
                    <i class="fas fa-plus-circle me-1"></i>Nouvelle tâche
                </a>
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class="fas fa-filter me-1"></i>Filtres avancés
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Filtres avec affichage adaptatif -->
<div class="card mb-4">
    <div class="card-body collapse" id="filterCollapse">
        <form method="GET" action="index.php" class="row g-3">
            <input type="hidden" name="page" value="taches">
            
            <div class="col-md-3">
                <label for="status" class="form-label">Statut</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tous</option>
                    <option value="a_faire" <?php echo $status == 'a_faire' ? 'selected' : ''; ?>>À faire</option>
                    <option value="en_cours" <?php echo $status == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                    <option value="termine" <?php echo $status == 'termine' ? 'selected' : ''; ?>>Terminé</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="priorite" class="form-label">Priorité</label>
                <select class="form-select" id="priorite" name="priorite">
                    <option value="">Toutes</option>
                    <option value="basse" <?php echo $priorite == 'basse' ? 'selected' : ''; ?>>Basse</option>
                    <option value="moyenne" <?php echo $priorite == 'moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                    <option value="haute" <?php echo $priorite == 'haute' ? 'selected' : ''; ?>>Haute</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="employe_id" class="form-label">Utilisateur</label>
                <select class="form-select" id="employe_id" name="employe_id">
                    <option value="">Tous</option>
                    <?php foreach ($utilisateurs as $utilisateur): ?>
                        <option value="<?php echo $utilisateur['id']; ?>" <?php echo $employe_id == $utilisateur['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($utilisateur['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Filtrer
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <!-- Boutons de sélection de vue -->
        <div class="view-selector mb-3">
            <div class="btn-group" role="group" aria-label="Sélection de vue">
                <button type="button" class="btn btn-outline-primary active" id="table-view-btn" onclick="switchView('table')">
                    <i class="fas fa-table me-2"></i>Tableau
                </button>
                <button type="button" class="btn btn-outline-primary" id="card-view-btn" onclick="switchView('cards')">
                    <i class="fas fa-th-large me-2"></i>Cartes
                </button>
            </div>
        </div>
        
        <?php if (empty($taches)): ?>
            <div class="text-center py-5">
                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                <h5>Aucune tâche trouvée</h5>
                <p class="text-muted">Ajoutez une nouvelle tâche pour commencer</p>
                <a href="index.php?page=ajouter_tache" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nouvelle Tâche
                </a>
            </div>
        <?php else: ?>
            <!-- Vue Tableau - Design moderne -->
            <div id="table-view" class="modern-table-view">
                <div class="modern-table-container">
                    <?php foreach ($taches as $tache): ?>
                        <div class="table-row" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(<?php echo $tache['id']; ?>)">
                            <div class="row-content">
                                <div class="task-info">
                                    <div class="task-header">
                                        <div class="priority-indicator priority-<?php echo $tache['priorite']; ?>">
                                            <?php if ($tache['priorite'] == 'haute'): ?>
                                                <i class="fas fa-exclamation-circle"></i>
                                            <?php elseif ($tache['priorite'] == 'moyenne'): ?>
                                                <i class="fas fa-exclamation-triangle"></i>
                                            <?php else: ?>
                                                <i class="fas fa-circle"></i>
                                            <?php endif; ?>
                                        </div>
                                        <h6 class="task-title-table"><?php echo htmlspecialchars($tache['titre']); ?></h6>
                                    </div>
                                    <p class="task-description-table"><?php echo htmlspecialchars($tache['description']); ?></p>
                                </div>
                                
                                <div class="task-status-section">
                                    <div class="status-badges">
                                        <span class="status-badge priority-badge priority-<?php echo $tache['priorite']; ?>" 
                                              onclick="event.stopPropagation(); afficherModalPriorite(event, this)" 
                                              data-task-id="<?php echo $tache['id']; ?>">
                                            <?php echo ucfirst($tache['priorite']); ?>
                                        </span>
                                        
                                        <span class="status-badge task-status-badge status-<?php echo $tache['statut']; ?>" 
                                              onclick="event.stopPropagation(); afficherModalStatut(event, this)" 
                                              data-task-id="<?php echo $tache['id']; ?>">
                                            <i class="fas <?php 
                                                echo $tache['statut'] == 'termine' ? 'fa-check' : 
                                                    ($tache['statut'] == 'en_cours' ? 'fa-spinner' : 'fa-clock'); 
                                            ?>"></i>
                                            <?php echo $tache['statut'] == 'termine' ? 'Terminé' : 
                                                ($tache['statut'] == 'en_cours' ? 'En cours' : 'À faire'); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="task-actions-section" onclick="event.stopPropagation()">
                                    <div class="action-buttons">
                                        <button class="action-btn edit-btn" onclick="afficherModalEdition(<?php echo $tache['id']; ?>)" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn details-btn" onclick="afficherDetailsTache(<?php echo $tache['id']; ?>)" title="Détails">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $tache['id']; ?>)" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Vue Cartes -->
            <div id="card-view" class="tasks-grid" style="display: none;">
                <?php foreach ($taches as $tache): ?>
                    <div class="task-card" data-task-id="<?php echo $tache['id']; ?>" onclick="afficherDetailsTache(<?php echo $tache['id']; ?>)">
                        <div class="task-card-header">
                            <div class="task-title-section">
                                <div class="task-priority-icon">
                                    <?php if ($tache['priorite'] == 'haute'): ?>
                                        <i class="fas fa-exclamation-circle text-danger"></i>
                                    <?php elseif ($tache['priorite'] == 'moyenne'): ?>
                                        <i class="fas fa-exclamation-circle text-warning"></i>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-circle text-success"></i>
                                    <?php endif; ?>
                                </div>
                                <h6 class="task-title"><?php echo htmlspecialchars($tache['titre']); ?></h6>
                            </div>
                            <div class="task-actions" onclick="event.stopPropagation()">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="afficherModalEdition(<?php echo $tache['id']; ?>)">
                                            <i class="fas fa-edit me-2"></i>Modifier
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="afficherDetailsTache(<?php echo $tache['id']; ?>)">
                                            <i class="fas fa-comments me-2"></i>Détails
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $tache['id']; ?>)">
                                            <i class="fas fa-trash me-2"></i>Supprimer
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="task-card-body">
                            <p class="task-description"><?php echo htmlspecialchars($tache['description']); ?></p>
                        </div>
                        
                        <div class="task-card-footer">
                            <div class="task-badges">
                                <span class="task-badge task-priority <?php 
                                    echo $tache['priorite'] == 'haute' ? 'priority-high' : 
                                        ($tache['priorite'] == 'moyenne' ? 'priority-medium' : 'priority-low'); 
                                ?>" onclick="event.stopPropagation(); afficherModalPriorite(event, this)" data-task-id="<?php echo $tache['id']; ?>">
                                    <i class="fas <?php 
                                        echo $tache['priorite'] == 'haute' ? 'fa-arrow-up' : 
                                            ($tache['priorite'] == 'moyenne' ? 'fa-minus' : 'fa-arrow-down'); 
                                    ?>"></i>
                                    <?php echo ucfirst($tache['priorite']); ?>
                                </span>
                                
                                <span class="task-badge task-status <?php 
                                    echo $tache['statut'] == 'termine' ? 'status-completed' : 
                                        ($tache['statut'] == 'en_cours' ? 'status-in-progress' : 'status-new'); 
                                ?>" onclick="event.stopPropagation(); afficherModalStatut(event, this)" data-task-id="<?php echo $tache['id']; ?>">
                                    <i class="fas <?php 
                                        echo $tache['statut'] == 'termine' ? 'fa-check' : 
                                            ($tache['statut'] == 'en_cours' ? 'fa-spinner' : 'fa-clock'); 
                                    ?>"></i>
                                    <?php echo $tache['statut'] == 'termine' ? 'Terminé' : 
                                        ($tache['statut'] == 'en_cours' ? 'En cours' : 'À faire'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- Modal pour afficher les détails d'une tâche -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="taskDetailsModalLabel">Détails de la tâche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="task-detail-container">
                    <div class="mb-3">
                        <h5 id="task-title" class="fw-bold"></h5>
                        <div class="mt-2">
                            <span class="me-2">Priorité:</span>
                            <span id="task-priority" class="fw-medium"></span>
                        </div>
                        <div class="mt-2">
                            <span class="me-2">Description:</span>
                            <p id="task-description" class="mt-2"></p>
                        </div>
                    </div>
                    
                    <div class="task-actions d-flex gap-2 mb-3">
                        <button id="start-task-btn" class="btn btn-primary" data-task-id="" data-status="en_cours">
                            <i class="fas fa-play me-2"></i>Démarrer
                        </button>
                        <button id="complete-task-btn" class="btn btn-success" data-task-id="" data-status="termine">
                            <i class="fas fa-check me-2"></i>Terminer
                        </button>
                        <button id="edit-task-btn" class="btn btn-warning" onclick="afficherModalEdition(document.getElementById('start-task-btn').getAttribute('data-task-id'))">
                            <i class="fas fa-edit me-2"></i>Modifier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour changer le statut d'une tâche -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="changeStatusModalLabel">Changer le statut</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statusTaskId" value="">
                <div class="d-grid gap-3">
                    <button type="button" class="btn btn-lg btn-outline-secondary w-100 d-flex align-items-center justify-content-center" 
                            onclick="updateStatus('a_faire')">
                        <i class="fas fa-clock me-2"></i>À faire
                    </button>
                    <button type="button" class="btn btn-lg btn-outline-primary w-100 d-flex align-items-center justify-content-center" 
                            onclick="updateStatus('en_cours')">
                        <i class="fas fa-spinner me-2"></i>En cours
                    </button>
                    <button type="button" class="btn btn-lg btn-outline-success w-100 d-flex align-items-center justify-content-center" 
                            onclick="updateStatus('termine')">
                        <i class="fas fa-check me-2"></i>Terminé
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour changer la priorité d'une tâche -->
<div class="modal fade" id="changePrioriteModal" tabindex="-1" aria-labelledby="changePrioriteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="changePrioriteModalLabel">Changer la priorité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="prioriteTaskId" value="">
                <div class="d-grid gap-3">
                    <button type="button" class="btn btn-lg btn-outline-success w-100 d-flex align-items-center justify-content-center" 
                            onclick="updatePriorite('basse')">
                        <i class="fas fa-arrow-down me-2"></i>Basse
                    </button>
                    <button type="button" class="btn btn-lg btn-outline-warning w-100 d-flex align-items-center justify-content-center" 
                            onclick="updatePriorite('moyenne')">
                        <i class="fas fa-minus me-2"></i>Moyenne
                    </button>
                    <button type="button" class="btn btn-lg btn-outline-danger w-100 d-flex align-items-center justify-content-center" 
                            onclick="updatePriorite('haute')">
                        <i class="fas fa-arrow-up me-2"></i>Haute
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour changer l'employé assigné à une tâche -->
<div class="modal fade" id="changeEmployeModal" tabindex="-1" aria-labelledby="changeEmployeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="changeEmployeModalLabel">Assigner la tâche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="employeTaskId" value="">
                
                <!-- Boutons de sélection rapide des employés -->
                <div class="quick-assign-buttons d-grid gap-3 mb-3">
                    <button type="button" class="btn btn-outline-secondary btn-lg employee-option employee-unassign" onclick="updateEmploye('')">
                        <i class="fas fa-user-slash me-2"></i>Non assigné
                    </button>
                    
                    <?php 
                    // Afficher les 3 premiers employés (ou moins s'il y en a moins)
                    $top_employees = array_slice($utilisateurs, 0, min(3, count($utilisateurs)));
                    foreach ($top_employees as $index => $employe): 
                        $btn_classes = ['primary', 'success', 'warning'];
                        $btn_class = isset($btn_classes[$index]) ? $btn_classes[$index] : 'primary';
                    ?>
                        <button type="button" class="btn btn-outline-<?php echo $btn_class; ?> btn-lg employee-option" 
                                data-employee-id="<?php echo $employe['id']; ?>"
                                onclick="updateEmploye('<?php echo $employe['id']; ?>')">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($employe['full_name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($utilisateurs) > 3): ?>
                <!-- Bouton pour afficher tous les employés -->
                <div class="all-employees-section" style="display: none;">
                    <div class="d-grid gap-2 employee-grid">
                        <?php foreach (array_slice($utilisateurs, 3) as $employe): ?>
                            <button type="button" class="btn btn-outline-secondary employee-option" 
                                    data-employee-id="<?php echo $employe['id']; ?>"
                                    onclick="updateEmploye('<?php echo $employe['id']; ?>')">
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($employe['full_name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Bouton pour afficher plus d'options -->
                <div class="d-grid">
                    <button type="button" class="btn btn-outline-dark mt-2" id="showMoreEmployees">
                        <i class="fas fa-chevron-down me-2"></i>Plus d'options
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Bottom Navigation Bar for PWA -->
<?php if (($isPWA || (isset($_SESSION['pwa_mode']) && $_SESSION['pwa_mode']) || isset($isMobile) && $isMobile) && !isset($isDesktop)): ?>
<div id="mobile-dock" class="d-block d-lg-none">
    <div class="mobile-dock-container">
        <a href="index.php" class="dock-item <?php echo empty($_GET['page']) ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Accueil</span>
        </a>
        <a href="index.php?page=reparations" class="dock-item <?php echo isset($_GET['page']) && $_GET['page'] == 'reparations' ? 'active' : ''; ?>">
            <i class="fas fa-tools"></i>
            <span>Réparations</span>
        </a>
        
        <!-- Bouton + au centre -->
        <div class="dock-item-center">
            <button class="btn-nouvelle-action" type="button" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        
        <a href="index.php?page=taches" class="dock-item position-relative <?php echo $currentPage == 'taches' ? 'active' : ''; ?>">
            <i class="fas fa-tasks"></i>
            <span>Tâches</span>
            <?php if (isset($tasks_count) && $tasks_count > 0): ?>
                <span class="badge-count"><?php echo $tasks_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="#" class="dock-item" data-bs-toggle="modal" data-bs-target="#menu_navigation_modal">
            <i class="fas fa-bars"></i>
            <span>Menu</span>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Modal pour Nouvelle Action (si pas déjà présent ailleurs) -->
<?php /* Modal supprimé car maintenant géré par includes/modals.php */ ?>

<style>
/* Styles pour les boutons de filtre */
.filter-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    background: transparent;
    padding: 0;
    box-shadow: none;
    justify-content: center;
    width: 100%;
}

.filter-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    border-radius: 1rem;
    font-weight: 500;
    color: #6c757d;
    background-color: white;
    border: 1px solid #e9ecef;
    text-decoration: none;
    transition: all 0.2s ease;
    min-width: 150px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    position: relative;
    animation: fadeIn 0.3s ease-out forwards;
    opacity: 0;
}

.filter-btn:nth-child(1) { animation-delay: 0.05s; }
.filter-btn:nth-child(2) { animation-delay: 0.1s; }
.filter-btn:nth-child(3) { animation-delay: 0.15s; }
.filter-btn:nth-child(4) { animation-delay: 0.2s; }
.filter-btn:nth-child(5) { animation-delay: 0.25s; }

.filter-btn:hover {
    background-color: #f8f9fa;
    color: #495057;
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.filter-btn.active {
    background-color: #4361ee;
    color: white;
    border-color: #4361ee;
    box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
}

.filter-btn i {
    color: inherit;
    margin-bottom: 0.5rem;
    font-size: 2.5rem;
}

.filter-btn span {
    font-size: 1rem;
    text-align: center;
    font-weight: 600;
}

.filter-btn .count {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: #e9ecef;
    color: #495057;
    border-radius: 1rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.filter-btn:hover .count {
    background: #dee2e6;
}

.filter-btn.active .count {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

/* Style pour la barre de recherche */
.search-card {
    border-radius: 0.75rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: none;
    transition: all 0.2s ease;
    animation: slideDown 0.3s ease-out forwards;
    opacity: 0;
    animation-delay: 0.3s;
}

.search-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.search-form .input-group {
    border-radius: 0.5rem;
    overflow: hidden;
}

.search-form .form-control {
    border-color: #dee2e6;
    height: 46px;
}

.search-form .btn {
    font-weight: 500;
    padding-left: 1.25rem;
    padding-right: 1.25rem;
}

/* Styles pour les boutons de sélection de vue */
.view-selector {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.view-selector .btn-group {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-radius: 0.75rem;
    overflow: hidden;
    background: white;
}

.view-selector .btn-group .btn {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    position: relative;
    background: transparent;
}

.view-selector .btn-group .btn:not(.active) {
    color: #6c757d;
    background: white;
}

.view-selector .btn-group .btn:not(.active):hover {
    color: #4361ee;
    background: rgba(67, 97, 238, 0.05);
    transform: translateY(-1px);
}

.view-selector .btn-group .btn.active {
    background: linear-gradient(135deg, #4361ee 0%, #3b82f6 100%);
    border-color: transparent;
    color: white;
    box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
    transform: translateY(-2px);
}

.view-selector .btn-group .btn.active::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}

/* Nouveau design moderne pour le tableau */
.modern-table-view {
    display: block;
}

.modern-table-container {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.table-row {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
    cursor: pointer;
    overflow: hidden;
}

.table-row:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: #4361ee;
}

.row-content {
    display: grid;
    grid-template-columns: 1fr auto auto;
    align-items: center;
    padding: 1.25rem;
    gap: 1.5rem;
}

.task-info {
    flex: 1;
    min-width: 0;
}

.task-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.priority-indicator {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.priority-indicator.priority-haute {
    background: linear-gradient(135deg, #fee2e2, #fca5a5);
    color: #dc2626;
}

.priority-indicator.priority-moyenne {
    background: linear-gradient(135deg, #fef3c7, #fcd34d);
    color: #d97706;
}

.priority-indicator.priority-basse {
    background: linear-gradient(135deg, #d1fae5, #6ee7b7);
    color: #059669;
}

.task-title-table {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.3;
}

.task-description-table {
    margin: 0;
    color: #6b7280;
    line-height: 1.5;
    font-size: 0.95rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.task-status-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.status-badges {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
}

.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-transform: capitalize;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    white-space: nowrap;
}

.status-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.priority-badge.priority-haute {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

.priority-badge.priority-moyenne {
    background: #fef3c7;
    color: #d97706;
    border: 1px solid #fcd34d;
}

.priority-badge.priority-basse {
    background: #d1fae5;
    color: #059669;
    border: 1px solid #6ee7b7;
}

.task-status-badge.status-a_faire {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.task-status-badge.status-en_cours {
    background: #dbeafe;
    color: #1d4ed8;
    border: 1px solid #93c5fd;
}

.task-status-badge.status-termine {
    background: #d1fae5;
    color: #059669;
    border: 1px solid #6ee7b7;
}

.task-actions-section {
    display: flex;
    align-items: center;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.edit-btn {
    background: #eff6ff;
    color: #2563eb;
}

.edit-btn:hover {
    background: #dbeafe;
    transform: translateY(-1px);
}

.details-btn {
    background: #f0f9ff;
    color: #0284c7;
}

.details-btn:hover {
    background: #e0f2fe;
    transform: translateY(-1px);
}

.delete-btn {
    background: #fef2f2;
    color: #dc2626;
}

.delete-btn:hover {
    background: #fee2e2;
    transform: translateY(-1px);
}

/* Styles pour la grille de cartes */
.tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    padding: 0;
}

.task-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    overflow: hidden;
    border: 1px solid #e9ecef;
    animation: fadeInUp 0.5s ease forwards;
    opacity: 0;
}

.task-card:nth-child(1) { animation-delay: 0.1s; }
.task-card:nth-child(2) { animation-delay: 0.2s; }
.task-card:nth-child(3) { animation-delay: 0.3s; }
.task-card:nth-child(4) { animation-delay: 0.4s; }
.task-card:nth-child(5) { animation-delay: 0.5s; }
.task-card:nth-child(n+6) { animation-delay: 0.6s; }

.task-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: #4361ee;
}

.task-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.25rem 1.25rem 0.75rem;
    border-bottom: 1px solid #f0f0f0;
}

.task-title-section {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.task-priority-icon {
    font-size: 1.25rem;
    display: flex;
    align-items: center;
}

.task-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #2d3748;
    line-height: 1.3;
}

.task-actions {
    position: relative;
}

.task-card-body {
    padding: 1rem 1.25rem;
}

.task-description {
    margin: 0;
    color: #4a5568;
    line-height: 1.5;
    font-size: 0.95rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.task-card-footer {
    padding: 0.75rem 1.25rem 1.25rem;
}

.task-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.task-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-transform: capitalize;
}

.task-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Styles des badges de priorité */
.priority-high {
    background-color: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

.priority-medium {
    background-color: #fef3c7;
    color: #d97706;
    border: 1px solid #fcd34d;
}

.priority-low {
    background-color: #d1fae5;
    color: #059669;
    border: 1px solid #6ee7b7;
}

/* Styles des badges de statut */
.status-new {
    background-color: #e5e7eb;
    color: #374151;
    border: 1px solid #d1d5db;
}

.status-in-progress {
    background-color: #dbeafe;
    color: #1d4ed8;
    border: 1px solid #93c5fd;
}

.status-completed {
    background-color: #d1fae5;
    color: #059669;
    border: 1px solid #6ee7b7;
}

/* Badge de statut amélioré */
.badge-status {
    padding: 0.2em 0.4em; /* Padding encore plus réduit */
    border-radius: 15px; /* Bordure plus petite */
    font-weight: 600;
    font-size: 0.65rem; /* Taille de police encore plus réduite */
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    white-space: nowrap;
    width: 100%; /* Pour que les deux badges aient la même largeur */
    justify-content: center; /* Centre le contenu du badge */
}

.badge-status i {
    font-size: 0.6rem;
}

.badge-status.status-new {
    background-color: #e3f2fd;
    color: #1976d2;
}

.badge-status.status-in-progress {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.badge-status.status-completed {
    background-color: #e8eaf6;
    color: #3f51b5;
}

.badge-status.status-high {
    background-color: #ffebee;
    color: #c62828;
}

.badge-status.status-medium {
    background-color: #fff8e1;
    color: #f57f17;
}

.badge-status.status-low {
    background-color: #e8f5e9;
    color: #2e7d32;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInUp {
    from { 
        opacity: 0; 
        transform: translateY(30px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

.card {
    animation: fadeIn 0.3s ease-out forwards;
    opacity: 0;
    animation-delay: 0.35s;
}

/* Ajustements pour mobile avec décalage de 90px vers le bas */
@media (max-width: 768px) {
    /* Container principal pour déplacer tout le contenu */
    .taches-content-container {
        position: relative;
        top: 50px;
        margin-bottom: 90px;
        width: 100%;
        max-width: 100vw;
        overflow-x: hidden;
    }
    
    /* Boutons de vue plus petits sur mobile */
    .view-selector .btn-group .btn {
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
    }
    
    .view-selector .btn-group {
        border-radius: 0.5rem;
    }
    
    /* Adaptation du nouveau tableau pour mobile */
    .row-content {
        grid-template-columns: 1fr;
        gap: 1rem;
        padding: 1rem;
    }
    
    .task-header {
        gap: 0.5rem;
    }
    
    .priority-indicator {
        width: 20px;
        height: 20px;
        font-size: 0.75rem;
    }
    
    .task-title-table {
        font-size: 1rem;
    }
    
    .task-description-table {
        font-size: 0.9rem;
        -webkit-line-clamp: 3;
    }
    
    .task-status-section {
        flex-direction: row;
        justify-content: center;
    }
    
    .status-badges {
        flex-direction: row;
        gap: 0.5rem;
    }
    
    .status-badge {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
    }
    
    .action-buttons {
        justify-content: center;
    }
    
    .action-btn {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    /* Adaptation de la grille pour mobile */
    .tasks-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        padding: 0 0.5rem;
    }
    
    .task-card {
        margin: 0;
    }
    
    .task-card-header {
        padding: 1rem 1rem 0.5rem;
    }
    
    .task-title {
        font-size: 1rem;
    }
    
    .task-card-body {
        padding: 0.75rem 1rem;
    }
    
    .task-description {
        font-size: 0.9rem;
        -webkit-line-clamp: 2;
    }
    
    .task-card-footer {
        padding: 0.5rem 1rem 1rem;
    }
    
    .task-badges {
        gap: 0.25rem;
    }
    
    .task-badge {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
    }
    
    .filter-buttons {
        gap: 0.5rem;
    }
    
    .filter-btn {
        padding: 1rem;
        min-width: calc(50% - 0.5rem);
    }
    
    .filter-btn i {
        font-size: 1.75rem;
    }
    
    .filter-btn span {
        font-size: 0.875rem;
    }

    .filter-btn .count {
        padding: 0.15rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .search-form .btn {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
}

/* Removed old scroll indicator CSS - no longer needed with card layout */

/* Styles pour le modal */
.modal-dialog-centered {
    display: flex;
    align-items: center;
    min-height: calc(100% - 1rem);
}

.modal-content {
    border-radius: 0.75rem;
    border: none;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.modal-header {
    border-bottom: 1px solid #f8f9fa;
    padding: 1.25rem 1.5rem;
}

.modal-header .modal-title {
    font-weight: 600;
    color: #4361ee;
}

.modal-header .btn-close {
    box-shadow: none;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid #f8f9fa;
    padding: 1.25rem 1.5rem;
}

#tacheCommentairesListe {
    max-height: 300px;
    overflow-y: auto;
}

.commentaire-item {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    background-color: #f8f9fa;
    border-left: 4px solid #4361ee;
}

.commentaire-item:last-child {
    margin-bottom: 0;
}

.commentaire-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.commentaire-auteur {
    font-weight: 600;
}

.commentaire-date {
    font-size: 0.875rem;
    color: #6c757d;
}

.commentaire-texte {
    margin-bottom: 0;
}

#formAjouterCommentaire .form-control:focus {
    border-color: #4361ee;
    box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
}

@media (max-width: 768px) {
    .modal-dialog {
        margin: 0.5rem;
    }
}

.task-detail-container {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 10px;
}

#task-title {
    color: #333;
    font-size: 1.25rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.75rem;
}

#task-priority {
    background-color: #e9ecef;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

#task-description {
    font-size: 0.95rem;
    line-height: 1.5;
    color: #495057;
}

/* Style pour le modal de changement de statut */
#changeStatusModal .btn {
    font-weight: 600;
    font-size: 1.1rem;
    padding: 1rem;
    transition: all 0.2s ease;
}

#changeStatusModal .btn-outline-secondary:hover {
    background-color: #6c757d;
    color: white;
}

#changeStatusModal .btn-outline-primary:hover {
    background-color: #4361ee;
    color: white;
}

#changeStatusModal .btn-outline-success:hover {
    background-color: #198754;
    color: white;
}

.status-btn {
    cursor: pointer;
    transition: all 0.2s ease;
}

.status-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.status-btn:active {
    transform: translateY(0);
}

/* Style pour le modal de changement de priorité */
#changePrioriteModal .btn {
    font-weight: 600;
    font-size: 1.1rem;
    padding: 1rem;
    transition: all 0.2s ease;
}

#changePrioriteModal .btn-outline-success:hover {
    background-color: #198754;
    color: white;
}

#changePrioriteModal .btn-outline-warning:hover {
    background-color: #ffc107;
    color: #212529;
}

#changePrioriteModal .btn-outline-danger:hover {
    background-color: #dc3545;
    color: white;
}

/* Style pour les badges d'employé */
.employee-badge {
    display: inline-block;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    background-color: #e9ecef;
    color: #495057;
    transition: all 0.2s ease;
}

.employee-badge:hover {
    background-color: #4361ee;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.employee-badge i {
    font-size: 0.9rem;
}

/* Style pour le modal d'assignation d'employé */
.employee-option {
    font-weight: 600;
    padding: 1rem;
    text-align: left;
    transition: all 0.2s ease;
    border-radius: 0.5rem;
}

.employee-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.employee-option.active {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.employee-unassign:hover {
    background-color: #6c757d;
    color: white;
}

.employee-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
    padding: 0.5rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}

#showMoreEmployees {
    font-size: 0.9rem;
    padding: 0.5rem;
}

#showMoreEmployees:hover {
    background-color: #f8f9fa;
}

#showMoreEmployees i {
    transition: transform 0.3s ease;
}

#showMoreEmployees.expanded i {
    transform: rotate(180deg);
}

/* Styles pour la barre de navigation mobile en mode PWA */
#mobile-dock {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    z-index: 1030;
    background-color: white;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    padding-bottom: env(safe-area-inset-bottom, 0px);
    /* Supprimer le display block pour respecter les media queries */
}

.mobile-dock-container {
    height: var(--dock-height, 55px);
    display: flex;
    align-items: center;
    justify-content: space-around;
}

.dock-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #6c757d;
    transition: all 0.2s ease;
    padding: 4px 0;
    position: relative;
}

.dock-item i {
    font-size: 17px;
    margin-bottom: 2px;
}

.dock-item span {
    font-size: 9px;
    text-align: center;
}

.dock-item.active {
    color: var(--primary-color, #0078e8);
}

.dock-item-center {
    display: flex;
    justify-content: center;
    align-items: center;
    flex: 1;
}

.btn-nouvelle-action {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--primary-color, #0078e8);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 120, 232, 0.5);
    transition: all 0.2s ease;
    transform: translateY(-7px);
}

.btn-nouvelle-action i {
    font-size: 17px;
}

.btn-nouvelle-action:hover, 
.btn-nouvelle-action:focus {
    background-color: #0066cc;
    transform: translateY(-10px);
}

.btn-nouvelle-action:active {
    transform: translateY(-5px);
}

.badge-count {
    position: absolute;
    top: 0;
    right: calc(50% - 14px);
    background-color: var(--danger-color, #dc3545);
    color: white;
    border-radius: 50%;
    width: 14px;
    height: 14px;
    font-size: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Style pour tablettes - dock centré et réduit */
@media (min-width: 768px) and (max-width: 991.98px) {
    .mobile-dock-container {
        width: 80%;
        margin: 0 auto;
        border-radius: 12px 12px 0 0;
        overflow: hidden;
    }
    
    /* Garantir que la barre apparaît sur iPad en mode PWA */
    body.pwa-mode #mobile-dock {
        display: block !important;
    }
}

.dock-item:active {
    transform: scale(0.95);
}

@supports (padding-bottom: env(safe-area-inset-bottom)) {
    #mobile-dock {
        height: calc(var(--dock-height, 55px) + env(safe-area-inset-bottom, 0px));
    }
}

/* Ajustement pour le mode PWA */
body.pwa-mode .taches-content-container {
    padding-bottom: calc(var(--dock-height, 55px) + env(safe-area-inset-bottom, 0px) + 16px);
}

/* Garantir que la barre apparaît sur iPad */
@media (min-width: 768px) and (max-width: 1199.98px) {
    body.pwa-mode #mobile-dock {
        display: block !important;
    }
}

/* Style général pour le conteneur des tâches */
.taches-content-container {
    width: 100%;
    position: relative;
    padding: 15px;
}

/* Décalage du contenu vers le bas uniquement sur PC */
@media (min-width: 992px) {
    .taches-content-container {
        padding-top: 85px !important; /* Augmentation du décalage à 85px avec !important */
    }
}

/* Styles pour les badges de statut */
.badge-status {
    display: inline-flex;
    align-items: center;
    padding: 5px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    gap: 6px;
    min-width: 90px;
    justify-content: center;
    color: #fff;
}

.badge-status:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.status-completed {
    background-color: #28a745;
}

.status-in-progress {
    background-color: #007bff;
}

.status-new {
    background-color: #6c757d;
}

.status-high {
    background-color: #dc3545;
}

.status-medium {
    background-color: #ffc107;
    color: #212529;
}

.status-low {
    background-color: #17a2b8;
}

.employee-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    background-color: #f8f9fa;
    color: #495057;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.employee-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    background-color: #e9ecef;
}

.table-row-hover {
    cursor: pointer;
    transition: all 0.2s ease;
}

.table-row-hover:hover {
    background-color: rgba(0,123,255,0.05);
}

.col-description {
    max-width: 350px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@media (max-width: 768px) {
    .col-description {
        max-width: 150px;
    }
    
    .col-status-priority {
        min-width: 110px;
    }
    
    .badge-status {
        min-width: 80px;
        padding: 4px 6px;
        font-size: 0.7rem;
    }
}

.filter-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 1rem 0.75rem;
    text-decoration: none;
    color: #6c757d;
    background-color: #fff;
    border-radius: 0.75rem;
    transition: all 0.3s ease;
    min-width: 110px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    border: 1px solid #e9ecef;
}

.filter-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    color: #4361ee;
    border-color: rgba(67, 97, 238, 0.3);
}

.filter-btn.active {
    background-color: #4361ee;
    color: white;
    border-color: #4361ee;
}

.filter-btn i {
    color: inherit;
    margin-bottom: 0.5rem;
    font-size: 2rem;
}

.filter-btn span {
    font-size: 0.9rem;
    text-align: center;
    font-weight: 600;
}

.filter-btn .count {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    background: #e9ecef;
    color: #495057;
    border-radius: 1rem;
    padding: 0.15rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.search-card {
    width: 100%;
    margin-bottom: 1.5rem;
    border-radius: 0.75rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    border: 1px solid #e9ecef;
}

.taches-content-container {
    padding: 15px;
}

/* Styles pour le mode nuit */
.dark-mode .filter-btn {
    background-color: #1f2937;
    color: #94a3b8;
    border-color: #374151;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.dark-mode .filter-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    color: #60a5fa;
    border-color: rgba(96, 165, 250, 0.3);
}

.dark-mode .filter-btn.active {
    background-color: #3b82f6;
    color: #f8fafc;
    border-color: #3b82f6;
}

.dark-mode .filter-btn .count {
    background: #374151;
    color: #f8fafc;
}

.dark-mode .badge-status {
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.dark-mode .status-new {
    background-color: #4b5563;
}

.dark-mode .status-in-progress {
    background-color: #2563eb;
}

.dark-mode .status-completed {
    background-color: #059669;
}

.dark-mode .status-high {
    background-color: #dc2626;
}

.dark-mode .status-medium {
    background-color: #1e40af;
    color: #f8fafc;
}

.dark-mode .status-low {
    background-color: #0891b2;
}

.dark-mode .employee-badge {
    background-color: #1f2937;
    color: #f8fafc;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.dark-mode .employee-badge:hover {
    background-color: #374151;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.dark-mode .search-card {
    background-color: #1f2937;
    border-color: #374151;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.dark-mode .table-row-hover:hover {
    background-color: #2d3748;
}

/* Amélioration des couleurs pour les cartes de tâches en mode nuit */
.dark-mode .task-card {
    background-color: #1f2937;
    border-color: #374151;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.dark-mode .task-card:hover {
    background-color: #1f2937;
    border-color: #60a5fa;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.6);
}

.dark-mode .task-card-header {
    border-bottom-color: #374151;
}

.dark-mode .task-title {
    color: #f8fafc;
}

.dark-mode .task-description {
    color: #9ca3af;
}

/* Amélioration des couleurs pour les tableaux en mode nuit */
.dark-mode .table-row {
    background-color: #1f2937;
    border-color: #374151;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.dark-mode .table-row:hover {
    background-color: #1f2937;
    border-color: #60a5fa;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.6);
}

.dark-mode .task-title-table {
    color: #f8fafc;
}

.dark-mode .task-description-table {
    color: #9ca3af;
}

/* Amélioration des badges en mode nuit */
.dark-mode .priority-badge.priority-haute {
    background-color: #1f2937;
    color: #dc2626;
    border-color: #374151;
}

.dark-mode .priority-badge.priority-moyenne {
    background-color: #1f2937;
    color: #1e40af;
    border-color: #374151;
}

.dark-mode .priority-badge.priority-basse {
    background-color: #1f2937;
    color: #059669;
    border-color: #374151;
}

.dark-mode .task-status-badge.status-a_faire {
    background-color: #1f2937;
    color: #9ca3af;
    border-color: #374151;
}

.dark-mode .task-status-badge.status-en_cours {
    background-color: #1f2937;
    color: #2563eb;
    border-color: #374151;
}

.dark-mode .task-status-badge.status-termine {
    background-color: #1f2937;
    color: #059669;
    border-color: #374151;
}

/* Amélioration des indicateurs de priorité en mode nuit */
.dark-mode .priority-indicator.priority-haute {
    background: linear-gradient(135deg, #374151, #4b5563);
    color: #dc2626;
}

.dark-mode .priority-indicator.priority-moyenne {
    background: linear-gradient(135deg, #374151, #4b5563);
    color: #1e40af;
}

.dark-mode .priority-indicator.priority-basse {
    background: linear-gradient(135deg, #374151, #4b5563);
    color: #059669;
}

/* Amélioration des boutons d'action en mode nuit */
.dark-mode .action-btn {
    background-color: #374151;
    color: #f8fafc;
}

.dark-mode .edit-btn {
    background-color: #1e40af;
    color: #f8fafc;
}

.dark-mode .edit-btn:hover {
    background-color: #2563eb;
}

.dark-mode .details-btn {
    background-color: #0f766e;
    color: #f8fafc;
}

.dark-mode .details-btn:hover {
    background-color: #0d9488;
}

.dark-mode .delete-btn {
    background-color: #991b1b;
    color: #f8fafc;
}

.dark-mode .delete-btn:hover {
    background-color: #dc2626;
}

/* Amélioration des sélecteurs de vue en mode nuit */
.dark-mode .view-selector .btn-group {
    background: #1f2937;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.dark-mode .view-selector .btn-group .btn:not(.active) {
    color: #9ca3af;
    background: #1f2937;
}

.dark-mode .view-selector .btn-group .btn:not(.active):hover {
    color: #60a5fa;
    background: #374151;
}

.dark-mode #taskDetailsModal .modal-content,
.dark-mode #changeStatusModal .modal-content,
.dark-mode #changePrioriteModal .modal-content {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode #taskDetailsModal .modal-header,
.dark-mode #changeStatusModal .modal-header,
.dark-mode #changePrioriteModal .modal-header {
    border-bottom-color: #374151;
}

.dark-mode #taskDetailsModal .modal-body,
.dark-mode #changeStatusModal .modal-body,
.dark-mode #changePrioriteModal .modal-body {
    color: #f8fafc;
}

.dark-mode #changeStatusModal .btn-outline-secondary,
.dark-mode #changeStatusModal .btn-outline-primary,
.dark-mode #changeStatusModal .btn-outline-success {
    color: #f8fafc;
    border-color: #374151;
}

.dark-mode #changeStatusModal .btn-outline-secondary:hover,
.dark-mode #changeStatusModal .btn-outline-primary:hover,
.dark-mode #changeStatusModal .btn-outline-success:hover {
    background-color: #374151;
}

/* Amélioration des formulaires de recherche en mode nuit */
.dark-mode .search-form .form-control {
    background-color: #374151;
    border-color: #4b5563;
    color: #f8fafc;
}

.dark-mode .search-form .form-control:focus {
    background-color: #374151;
    border-color: #60a5fa;
    color: #f8fafc;
    box-shadow: 0 0 0 0.25rem rgba(96, 165, 250, 0.25);
}

.dark-mode .search-form .form-control::placeholder {
    color: #9ca3af;
}

.dark-mode .search-form .input-group-text {
    background-color: #374151;
    border-color: #4b5563;
    color: #f8fafc;
}

.dark-mode .search-form .btn {
    background-color: #3b82f6;
    border-color: #3b82f6;
    color: #f8fafc;
}

.dark-mode .search-form .btn:hover {
    background-color: #2563eb;
    border-color: #2563eb;
}

/* Amélioration des descriptions de tâches en mode nuit */
.dark-mode #description-content {
    background-color: #374151;
    color: #f8fafc;
    border: 1px solid #4b5563;
}

/* Garantir que la barre n'apparaît pas sur les grands écrans (PC) */
@media (min-width: 992px) {
    #mobile-dock {
        display: none !important;
    }
}

/* Style pour le modal de description */
#descriptionModal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

#description-content {
    white-space: pre-wrap;
    font-size: 1rem;
    line-height: 1.6;
    color: #212529;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
}

/* Animation simple pour tous les modaux */
.modal.fade .modal-dialog {
    transform: translateY(-50px);
    opacity: 0;
    transition: all 0.3s ease;
}

.modal.show .modal-dialog {
    transform: translateY(0);
    opacity: 1;
}

/* Styles simples pour tous les modaux */
.modal-content {
    border-radius: 10px;
    border: none;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 10px 10px 0 0;
}

.modal-title {
    font-weight: 600;
    color: #495057;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-radius: 0 0 10px 10px;
}

/* Animation pour les messages d'erreur */
.error-animation {
    animation: error-appear 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes error-appear {
    0% { transform: scale(0.8); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.fade-out {
    animation: fade-out 0.5s ease forwards;
}

@keyframes fade-out {
    0% { opacity: 1; transform: translateY(0); }
    100% { opacity: 0; transform: translateY(-10px); }
}

/* Animation de secousse pour formulaire invalide */
.shake-animation {
    animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

@keyframes shake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-3px); }
    40%, 60% { transform: translateX(3px); }
}
</style>

<script>
// Fonction pour basculer entre les vues
function switchView(view) {
    const tableView = document.getElementById('table-view');
    const cardView = document.getElementById('card-view');
    const tableBtn = document.getElementById('table-view-btn');
    const cardBtn = document.getElementById('card-view-btn');
    
    if (view === 'table') {
        tableView.style.display = 'block';
        cardView.style.display = 'none';
        tableBtn.classList.add('active');
        cardBtn.classList.remove('active');
        
        // Sauvegarder la préférence dans localStorage
        localStorage.setItem('taskViewPreference', 'table');
    } else if (view === 'cards') {
        tableView.style.display = 'none';
        cardView.style.display = 'grid';
        tableBtn.classList.remove('active');
        cardBtn.classList.add('active');
        
        // Sauvegarder la préférence dans localStorage
        localStorage.setItem('taskViewPreference', 'cards');
    }
}

// Fonction utilitaire pour extraire les données d'une tâche depuis une carte
function extractTaskDataFromCard(card) {
    if (!card) return null;
    
    const taskId = card.getAttribute('data-task-id');
    const title = card.querySelector('.task-title')?.textContent?.trim() || 'Titre non trouvé';
    const description = card.querySelector('.task-description')?.textContent?.trim() || 'Description non trouvée';
    
    // Récupérer la priorité depuis le badge de priorité
    const priorityElement = card.querySelector('.task-priority');
    const priority = priorityElement?.textContent?.trim() || 'Non définie';
    
    // Récupérer le statut depuis le badge de statut
    const statusElement = card.querySelector('.task-status');
    const status = statusElement?.textContent?.trim() || 'Non défini';
    
    // Déterminer le statut pour les boutons
    let currentStatus = 'a_faire'; // Valeur par défaut
    if (status.includes('En cours')) {
        currentStatus = 'en_cours';
    } else if (status.includes('Terminé')) {
        currentStatus = 'termine';
    }
    
    return {
        id: taskId,
        title,
        description,
        priority,
        status,
        currentStatus
    };
}

// Fonction utilitaire pour extraire les données d'une tâche depuis une ligne de tableau moderne
function extractTaskDataFromRow(row) {
    if (!row) return null;
    
    const taskId = row.getAttribute('data-task-id');
    const title = row.querySelector('.task-title-table')?.textContent?.trim() || 'Titre non trouvé';
    const description = row.querySelector('.task-description-table')?.textContent?.trim() || 'Description non trouvée';
    
    // Récupérer la priorité depuis le badge de priorité
    const priorityElement = row.querySelector('.priority-badge');
    const priority = priorityElement?.textContent?.trim() || 'Non définie';
    
    // Récupérer le statut depuis le badge de statut
    const statusElement = row.querySelector('.task-status-badge');
    const status = statusElement?.textContent?.trim() || 'Non défini';
    
    // Déterminer le statut pour les boutons
    let currentStatus = 'a_faire'; // Valeur par défaut
    if (status.includes('En cours')) {
        currentStatus = 'en_cours';
    } else if (status.includes('Terminé')) {
        currentStatus = 'termine';
    }
    
    return {
        id: taskId,
        title,
        description,
        priority,
        status,
        currentStatus
    };
}

document.addEventListener('DOMContentLoaded', function() {
    // Charger la préférence de vue depuis localStorage
    const savedView = localStorage.getItem('taskViewPreference') || 'table';
    switchView(savedView);
    
    // Ajouter des événements de clic pour les lignes du nouveau tableau
    document.querySelectorAll('#table-view .table-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // Ne pas déclencher si on clique sur un bouton d'action
            if (e.target.closest('.action-buttons')) return;
            
            const taskId = this.getAttribute('data-task-id');
            if (taskId) {
                afficherDetailsTache(taskId);
            }
        });
    });
    
    // Événement de clic sur les descriptions pour le modal (cartes)
    document.querySelectorAll('.task-description').forEach(descElement => {
        descElement.addEventListener('click', function(e) {
            e.stopPropagation(); // Empêcher la propagation vers la carte
            
            // Récupérer les informations de la tâche
            const description = this.textContent.trim();
            const title = this.closest('.task-card').querySelector('.task-title').textContent.trim();
            
            // Remplir le modal avec les informations
            document.getElementById('description-title').textContent = title;
            document.getElementById('description-content').textContent = description;
            
            // Afficher le modal
            const descModal = new bootstrap.Modal(document.getElementById('descriptionModal'));
            descModal.show();
        });
    });
    
    // Événement de clic sur les descriptions pour le modal (nouveau tableau)
    document.querySelectorAll('#table-view .task-description-table').forEach(descElement => {
        descElement.addEventListener('click', function(e) {
            e.stopPropagation(); // Empêcher la propagation vers la ligne du tableau
            
            // Récupérer les informations de la tâche
            const description = this.textContent.trim();
            const title = this.closest('.table-row').querySelector('.task-title-table').textContent.trim();
            
            // Remplir le modal avec les informations
            document.getElementById('description-title').textContent = title;
            document.getElementById('description-content').textContent = description;
            
            // Afficher le modal
            const descModal = new bootstrap.Modal(document.getElementById('descriptionModal'));
            descModal.show();
        });
    });
    
    // Ajouter les gestionnaires d'événements pour les boutons de changement de statut de tâche
    document.getElementById('start-task-btn').addEventListener('click', updateTaskStatus);
    document.getElementById('complete-task-btn').addEventListener('click', updateTaskStatus);
    
    // Fonction pour mettre à jour le statut d'une tâche
    function updateTaskStatus(e) {
        const taskId = this.getAttribute('data-task-id');
        const newStatus = this.getAttribute('data-status');
        
        if (!taskId) {
            console.error("ID de tâche manquant");
            alert("Erreur: Impossible d'identifier la tâche");
            return;
        }
        
        // Afficher un spinner pendant le traitement
        const originalContent = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
        this.disabled = true;
        
        // Envoyer la requête pour mettre à jour le statut
        const formData = new FormData();
        formData.append('tache_id', taskId);
        formData.append('statut', newStatus);
        formData.append('action', 'changer_statut');
        
        fetch('ajax_handlers/tache_commentaires.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Afficher une notification de succès
                alert(`Statut de la tâche mis à jour avec succès.`);
                
                // Fermer le modal
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('taskDetailsModal'));
                if (modalInstance) modalInstance.hide();
                
                // Recharger la page pour afficher les changements
                window.location.reload();
            } else {
                alert(data.message || "Erreur lors de la mise à jour du statut de la tâche");
                // Rétablir le contenu original du bouton
                this.innerHTML = originalContent;
                this.disabled = false;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert("Erreur lors de la communication avec le serveur. Veuillez réessayer.");
            // Rétablir le contenu original du bouton
            this.innerHTML = originalContent;
            this.disabled = false;
        });
    }
    
    // Vérifier si on doit ouvrir le modal automatiquement (venant de la page d'accueil)
    <?php if(isset($_GET['task_id']) && isset($_GET['open_modal']) && $_GET['open_modal'] == '1'): ?>
    const taskId = "<?php echo $_GET['task_id']; ?>";
    // Ouvrir directement le modal de détails pour cette tâche
    setTimeout(() => {
        afficherDetailsTache(taskId);
    }, 100); // Petit délai pour s'assurer que la page est complètement chargée
    <?php endif; ?>
});

// Fonction pour afficher les détails d'une tâche
function afficherDetailsTache(taskId) {
    // Trouver l'élément correspondant dans la vue active
    const taskCard = document.querySelector(`.task-card[data-task-id="${taskId}"]`);
    const taskRow = document.querySelector(`.table-row[data-task-id="${taskId}"]`);
    
    let taskData = null;
    
    // Extraire les données selon la vue active
    if (taskCard && taskCard.closest('#card-view').style.display !== 'none') {
        taskData = extractTaskDataFromCard(taskCard);
    } else if (taskRow && taskRow.closest('#table-view').style.display !== 'none') {
        taskData = extractTaskDataFromRow(taskRow);
    } else {
        // Fallback - essayer les deux méthodes
        if (taskCard) {
            taskData = extractTaskDataFromCard(taskCard);
        } else if (taskRow) {
            taskData = extractTaskDataFromRow(taskRow);
        }
    }
    
    if (taskData) {
        // Remplir le modal avec les informations de la tâche
        document.getElementById('task-title').textContent = taskData.title;
        document.getElementById('task-description').textContent = taskData.description;
        document.getElementById('task-priority').textContent = taskData.priority;
        
        // Mettre à jour les attributs data-task-id des boutons
        document.getElementById('start-task-btn').setAttribute('data-task-id', taskId);
        document.getElementById('complete-task-btn').setAttribute('data-task-id', taskId);
        
        // Gérer l'état actif/inactif des boutons en fonction du statut actuel
        const startButton = document.getElementById('start-task-btn');
        const completeButton = document.getElementById('complete-task-btn');
        
        // Bouton Démarrer actif uniquement si la tâche est "À faire"
        if (taskData.currentStatus === 'a_faire') {
            startButton.disabled = false;
            startButton.classList.remove('btn-secondary');
            startButton.classList.add('btn-primary');
        } else {
            startButton.disabled = true;
            startButton.classList.remove('btn-primary');
            startButton.classList.add('btn-secondary');
        }
        
        // Bouton Terminer actif uniquement si la tâche est "À faire" ou "En cours"
        if (taskData.currentStatus === 'a_faire' || taskData.currentStatus === 'en_cours') {
            completeButton.disabled = false;
            completeButton.classList.remove('btn-secondary');
            completeButton.classList.add('btn-success');
        } else {
            completeButton.disabled = true;
            completeButton.classList.remove('btn-success');
            completeButton.classList.add('btn-secondary');
        }
        
        // Afficher le modal
        const taskModal = new bootstrap.Modal(document.getElementById('taskDetailsModal'));
        taskModal.show();
    } else {
        console.error('Données de tâche non trouvées pour l\'ID:', taskId);
    }
}

function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
        window.location.href = 'index.php?page=taches&action=supprimer&id=' + id;
    }
}

// Ajustement de la taille du conteneur pour les cartes
document.addEventListener('DOMContentLoaded', function() {
    // S'assurer que le conteneur a la bonne hauteur sur mobile
    if (window.innerWidth <= 768) {
        const contentHeight = document.querySelector('.taches-content-container').scrollHeight;
        document.body.style.minHeight = (contentHeight + 90) + 'px';
    }
});

function afficherModalStatut(event, element) {
    // Empêcher la propagation de l'événement
    event.stopPropagation();
    
    // Récupérer l'ID de la tâche
    const taskId = element.getAttribute('data-task-id');
    document.getElementById('statusTaskId').value = taskId;
    
    // Ouvrir le modal
    const statusModal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
    statusModal.show();
}

function updateStatus(status) {
    const taskId = document.getElementById('statusTaskId').value;
    
    if (!taskId) {
        console.error("ID de tâche manquant");
        return;
    }
    
    // Désactiver tous les boutons pendant le traitement
    const buttons = document.querySelectorAll('#changeStatusModal .btn');
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement...';
    });
    
    // Préparer les données
    const formData = new FormData();
    formData.append('tache_id', taskId);
    formData.append('statut', status);
    formData.append('action', 'changer_statut');
    
    // Envoyer la requête AJAX
    fetch('ajax_handlers/tache_commentaires.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau ou serveur');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const statusModal = bootstrap.Modal.getInstance(document.getElementById('changeStatusModal'));
            statusModal.hide();
            
            // Recharger la page pour afficher les changements
            window.location.reload();
        } else {
            alert(data.message || "Erreur lors de la mise à jour du statut");
            // Réactiver les boutons
            resetStatusButtons();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert("Erreur lors de la communication avec le serveur");
        // Réactiver les boutons
        resetStatusButtons();
    });
}

function resetStatusButtons() {
    const buttons = document.querySelectorAll('#changeStatusModal .btn');
    
    // Restaurer le contenu original des boutons
    const btnTexts = ['<i class="fas fa-clock me-2"></i>À faire', 
                     '<i class="fas fa-spinner me-2"></i>En cours', 
                     '<i class="fas fa-check me-2"></i>Terminé'];
    
    buttons.forEach((btn, index) => {
        btn.disabled = false;
        btn.innerHTML = btnTexts[index];
    });
}

function afficherModalPriorite(event, element) {
    // Empêcher la propagation de l'événement
    event.stopPropagation();
    
    // Récupérer l'ID de la tâche
    const taskId = element.getAttribute('data-task-id');
    document.getElementById('prioriteTaskId').value = taskId;
    
    // Ouvrir le modal
    const prioriteModal = new bootstrap.Modal(document.getElementById('changePrioriteModal'));
    prioriteModal.show();
}

function updatePriorite(priorite) {
    const taskId = document.getElementById('prioriteTaskId').value;
    
    if (!taskId) {
        console.error("ID de tâche manquant");
        return;
    }
    
    // Désactiver tous les boutons pendant le traitement
    const buttons = document.querySelectorAll('#changePrioriteModal .btn');
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement...';
    });
    
    // Préparer les données
    const formData = new FormData();
    formData.append('tache_id', taskId);
    formData.append('priorite', priorite);
    formData.append('action', 'changer_priorite');
    
    // Envoyer la requête AJAX
    fetch('ajax_handlers/tache_commentaires.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau ou serveur');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const prioriteModal = bootstrap.Modal.getInstance(document.getElementById('changePrioriteModal'));
            prioriteModal.hide();
            
            // Recharger la page pour afficher les changements
            window.location.reload();
        } else {
            alert(data.message || "Erreur lors de la mise à jour de la priorité");
            // Réactiver les boutons
            resetPrioriteButtons();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert("Erreur lors de la communication avec le serveur");
        // Réactiver les boutons
        resetPrioriteButtons();
    });
}

function resetPrioriteButtons() {
    const buttons = document.querySelectorAll('#changePrioriteModal .btn');
    
    // Restaurer le contenu original des boutons
    const btnTexts = ['<i class="fas fa-arrow-down me-2"></i>Basse', 
                     '<i class="fas fa-minus me-2"></i>Moyenne', 
                     '<i class="fas fa-arrow-up me-2"></i>Haute'];
    
    buttons.forEach((btn, index) => {
        btn.disabled = false;
        btn.innerHTML = btnTexts[index];
    });
}

function afficherModalEmploye(event, element) {
    // Empêcher la propagation de l'événement
    event.stopPropagation();
    
    // Récupérer l'ID de la tâche
    const taskId = element.getAttribute('data-task-id');
    document.getElementById('employeTaskId').value = taskId;
    
    // Récupérer l'employé actuellement assigné pour marquer le bouton actif
    fetch('ajax_handlers/get_tache_details.php?id=' + taskId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau ou serveur');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Réinitialiser tous les boutons
                document.querySelectorAll('.employee-option').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.classList.contains('btn-primary')) btn.classList.replace('btn-primary', 'btn-outline-primary');
                    if (btn.classList.contains('btn-success')) btn.classList.replace('btn-success', 'btn-outline-success');
                    if (btn.classList.contains('btn-warning')) btn.classList.replace('btn-warning', 'btn-outline-warning');
                    if (btn.classList.contains('btn-secondary')) btn.classList.replace('btn-secondary', 'btn-outline-secondary');
                });
                
                // Marquer le bouton actif
                if (data.tache.employe_id) {
                    const activeBtn = document.querySelector(`.employee-option[data-employee-id="${data.tache.employe_id}"]`);
                    if (activeBtn) {
                        activeBtn.classList.add('active');
                        if (activeBtn.classList.contains('btn-outline-primary')) activeBtn.classList.replace('btn-outline-primary', 'btn-primary');
                        if (activeBtn.classList.contains('btn-outline-success')) activeBtn.classList.replace('btn-outline-success', 'btn-success');
                        if (activeBtn.classList.contains('btn-outline-warning')) activeBtn.classList.replace('btn-outline-warning', 'btn-warning');
                        if (activeBtn.classList.contains('btn-outline-secondary')) activeBtn.classList.replace('btn-outline-secondary', 'btn-secondary');
                    }
                } else {
                    // Si non assigné, marquer ce bouton comme actif
                    const unassignBtn = document.querySelector('.employee-unassign');
                    if (unassignBtn) {
                        unassignBtn.classList.add('active');
                        unassignBtn.classList.replace('btn-outline-secondary', 'btn-secondary');
                    }
                }
                
                // Ouvrir le modal
                const employeModal = new bootstrap.Modal(document.getElementById('changeEmployeModal'));
                employeModal.show();
            } else {
                alert(data.message || 'Erreur lors du chargement des détails de la tâche');
            }
        })
        .catch(error => {
            alert('Erreur: ' + error.message);
        });
}

function updateEmploye(employeId) {
    const taskId = document.getElementById('employeTaskId').value;
    
    if (!taskId) {
        console.error("ID de tâche manquant");
        return;
    }
    
    // Désactiver tous les boutons pendant le traitement
    const buttons = document.querySelectorAll('#changeEmployeModal .btn');
    buttons.forEach(btn => {
        btn.disabled = true;
        
        // Ajouter un spinner uniquement au bouton cliqué
        if ((employeId === '' && btn.classList.contains('employee-unassign')) || 
            (employeId !== '' && btn.getAttribute('data-employee-id') === employeId)) {
            const originalText = btn.innerHTML;
            btn.setAttribute('data-original-text', originalText);
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement...';
        }
    });
    
    // Préparer les données
    const formData = new FormData();
    formData.append('tache_id', taskId);
    formData.append('employe_id', employeId);
    formData.append('action', 'changer_employe');
    
    // Envoyer la requête AJAX
    fetch('ajax_handlers/tache_commentaires.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau ou serveur');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const employeModal = bootstrap.Modal.getInstance(document.getElementById('changeEmployeModal'));
            employeModal.hide();
            
            // Recharger la page pour afficher les changements
            window.location.reload();
        } else {
            alert(data.message || "Erreur lors de la mise à jour de l'assignation");
            // Réactiver les boutons
            resetEmployeeButtons();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert("Erreur lors de la communication avec le serveur");
        // Réactiver les boutons
        resetEmployeeButtons();
    });
}

function resetEmployeeButtons() {
    const buttons = document.querySelectorAll('#changeEmployeModal .btn');
    buttons.forEach(btn => {
        btn.disabled = false;
        
        // Restaurer le texte original pour les boutons qui avaient un spinner
        if (btn.hasAttribute('data-original-text')) {
            btn.innerHTML = btn.getAttribute('data-original-text');
            btn.removeAttribute('data-original-text');
        }
    });
}

// Gestionnaire pour afficher plus d'options d'employés
document.addEventListener('DOMContentLoaded', function() {
    const showMoreBtn = document.getElementById('showMoreEmployees');
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', function() {
            const allEmployeesSection = document.querySelector('.all-employees-section');
            if (allEmployeesSection) {
                const isVisible = allEmployeesSection.style.display !== 'none';
                allEmployeesSection.style.display = isVisible ? 'none' : 'block';
                
                // Changer le texte et l'icône du bouton
                this.innerHTML = isVisible 
                    ? '<i class="fas fa-chevron-down me-2"></i>Plus d\'options'
                    : '<i class="fas fa-chevron-up me-2"></i>Moins d\'options';
                
                this.classList.toggle('expanded');
            }
        });
    }
});

// Fonction pour afficher le modal d'édition
function afficherModalEdition(taskId) {
    // Empêcher la propagation de l'événement
    event.stopPropagation();
    
    // Récupérer les données de la tâche via AJAX
    fetch('ajax_handlers/get_tache_details.php?id=' + taskId, {
        method: 'GET',
        credentials: 'same-origin', // Important pour transmettre les cookies de session
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau ou serveur');
        }
        return response.json();
    })
    .then(data => {
        console.log("Réponse reçue:", data);
        if (data.success) {
            // Remplir le formulaire avec les données de la tâche
            document.getElementById('edit_task_id').value = data.tache.id;
            document.getElementById('edit_titre').value = data.tache.titre;
            document.getElementById('edit_description').value = data.tache.description;
            document.getElementById('edit_priorite').value = data.tache.priorite;
            document.getElementById('edit_statut').value = data.tache.statut;
            document.getElementById('edit_employe_id').value = data.tache.employe_id || '';
            
            // Formater la date pour l'input date
            if (data.tache.date_limite) {
                const date = new Date(data.tache.date_limite);
                const formattedDate = date.toISOString().split('T')[0];
                document.getElementById('edit_date_limite').value = formattedDate;
            } else {
                document.getElementById('edit_date_limite').value = '';
            }
            
            // Appliquer des couleurs visuelles en fonction de la priorité et du statut
            const prioriteSelect = document.getElementById('edit_priorite');
            const prioriteOption = prioriteSelect.querySelector(`option[value="${data.tache.priorite}"]`);
            if (prioriteOption && prioriteOption.dataset.color) {
                document.querySelector('.futuristic-modal').style.borderColor = prioriteOption.dataset.color + '40'; // Ajouter 40 pour l'alpha (25%)
            }
            
            // Créer un effet d'apparition progressive des champs
            const inputGroups = document.querySelectorAll('.input-group-futuristic');
            inputGroups.forEach((group, index) => {
                group.style.opacity = '0';
                group.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    group.style.transition = 'all 0.5s ease';
                    group.style.opacity = '1';
                    group.style.transform = 'translateY(0)';
                }, 100 + (index * 100)); // Décalage de 100ms par élément
            });
            
            // Effet visuel sur le modal
            const modalContent = document.querySelector('.futuristic-modal');
            modalContent.classList.add('modal-appear');
            
            // Afficher le modal
            const editModal = new bootstrap.Modal(document.getElementById('editTaskModal'));
            editModal.show();
            
            // Ajout de validation en temps réel
            const form = document.getElementById('editTaskForm');
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.checkValidity()) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    }
                });
            });
        } else {
            alert(data.message || 'Erreur lors du chargement des détails de la tâche');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert("Erreur lors de la communication avec le serveur");
    });
}

// Fonction pour sauvegarder les modifications
function sauvegarderModification() {
    const form = document.getElementById('editTaskForm');
    
    // Vérifier la validité du formulaire
    if (!form.checkValidity()) {
        // Ajouter une animation de secousse au formulaire
        form.classList.add('shake-animation');
        setTimeout(() => form.classList.remove('shake-animation'), 500);
        
        // Ajouter des classes d'invalidation pour le retour visuel
        Array.from(form.elements).forEach(input => {
            if (input.required && !input.checkValidity()) {
                input.classList.add('is-invalid');
            }
        });
        
        return false;
    }
    
    const formData = new FormData(form);
    formData.append('action', 'modifier_tache');
    
    // Désactiver le bouton pendant le traitement
    const saveButton = document.querySelector('#editTaskModal .btn-save');
    const originalText = saveButton.innerHTML;
    saveButton.disabled = true;
    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Traitement...';
    
    // Ajouter un effet de chargement au modal
    document.querySelector('.futuristic-modal').classList.add('is-processing');
    
    // Envoyer la requête AJAX
    fetch('ajax_handlers/tache_commentaires.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur réseau ou serveur');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Ajouter animation de succès
            saveButton.innerHTML = '<i class="fas fa-check me-2"></i>Succès!';
            saveButton.classList.add('success-animation');
            
            // Délai avant de fermer le modal pour montrer l'animation
            setTimeout(() => {
                // Fermer le modal
                const editModal = bootstrap.Modal.getInstance(document.getElementById('editTaskModal'));
                editModal.hide();
                
                // Recharger la page pour afficher les changements
                window.location.reload();
            }, 1000);
        } else {
            // Réactiver le bouton
            saveButton.disabled = false;
            saveButton.innerHTML = originalText;
            document.querySelector('.futuristic-modal').classList.remove('is-processing');
            
            // Afficher un message d'erreur avec animation
            const modalBody = document.querySelector('#editTaskModal .modal-body');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger error-animation mt-3';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${data.message || "Erreur lors de la modification de la tâche"}`;
            modalBody.appendChild(errorDiv);
            
            // Supprimer le message après 5 secondes
            setTimeout(() => {
                errorDiv.classList.add('fade-out');
                setTimeout(() => errorDiv.remove(), 500);
            }, 5000);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        
        // Réactiver le bouton
        saveButton.disabled = false;
        saveButton.innerHTML = originalText;
        document.querySelector('.futuristic-modal').classList.remove('is-processing');
        
        // Afficher un message d'erreur
        const modalBody = document.querySelector('#editTaskModal .modal-body');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger error-animation mt-3';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>Erreur lors de la communication avec le serveur`;
        modalBody.appendChild(errorDiv);
        
        // Supprimer le message après 5 secondes
        setTimeout(() => {
            errorDiv.classList.add('fade-out');
            setTimeout(() => errorDiv.remove(), 500);
        }, 5000);
    });
}

// Ajouter des particules en arrière-plan du modal pour un effet science-fiction
document.addEventListener('DOMContentLoaded', function() {
    // Créer un conteneur pour les particules
    const particlesContainer = document.createElement('div');
    particlesContainer.classList.add('particles-container');
    
    // Ajouter au modal quand il est ouvert
    document.getElementById('editTaskModal').addEventListener('shown.bs.modal', function() {
        const modalContent = document.querySelector('.futuristic-modal');
        if (!modalContent.querySelector('.particles-container')) {
            modalContent.appendChild(particlesContainer);
            createParticles();
        }
    });
    
    function createParticles() {
        const container = document.querySelector('.particles-container');
        if (!container) return;
        
        // Créer des particules
        const particleCount = 15;
        for (let i = 0; i < particleCount; i++) {
            setTimeout(() => {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Position aléatoire
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                particle.style.left = `${posX}%`;
                particle.style.bottom = `${posY}%`;
                
                // Taille aléatoire
                const size = 2 + Math.random() * 3;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Couleur aléatoire
                const colors = ['rgba(67, 97, 238, 0.5)', 'rgba(138, 43, 226, 0.5)', 'rgba(61, 218, 254, 0.5)'];
                const color = colors[Math.floor(Math.random() * colors.length)];
                particle.style.background = color;
                
                // Animation
                const duration = 3 + Math.random() * 5;
                particle.style.animation = `float-up ${duration}s linear infinite`;
                
                // Délai pour le démarrage de l'animation
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                container.appendChild(particle);
            }, i * 200);
        }
    }
});

// Fonction pour améliorer l'expérience utilisateur lors de la sélection des options
document.addEventListener('DOMContentLoaded', function() {
    // Appliquer des couleurs visuelles aux selects quand ils changent
    const prioriteSelect = document.getElementById('edit_priorite');
    const statutSelect = document.getElementById('edit_statut');
    
    if (prioriteSelect) {
        prioriteSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.color) {
                this.style.borderColor = selectedOption.dataset.color;
                this.style.boxShadow = `0 0 10px ${selectedOption.dataset.color}80`; // 50% d'opacité
            }
        });
    }
    
    if (statutSelect) {
        statutSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.color) {
                this.style.borderColor = selectedOption.dataset.color;
                this.style.boxShadow = `0 0 10px ${selectedOption.dataset.color}80`; // 50% d'opacité
            }
        });
    }
});
</script> 

<!-- Ajouter un nouveau modal pour afficher la description complète -->
<div class="modal fade" id="descriptionModal" tabindex="-1" aria-labelledby="descriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="descriptionModalLabel">Description de la tâche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <h5 id="description-title" class="fw-bold mb-3"></h5>
                <div id="description-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour éditer une tâche -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 futuristic-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="editTaskModalLabel">
                    <i class="fas fa-robot me-2 pulse-icon"></i>Modifier la tâche
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="editTaskForm" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" id="edit_task_id" name="id" value="">
                    <div class="row">
                        <div class="col-md-8 mb-4">
                            <div class="input-group-futuristic">
                                <label for="edit_titre" class="form-label">Titre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control futuristic-input" id="edit_titre" name="titre" required>
                                <span class="highlight-bar"></span>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="input-group-futuristic">
                                <label for="edit_employe_id" class="form-label">Assigner à</label>
                                <select class="form-select futuristic-select" id="edit_employe_id" name="employe_id">
                                    <option value="">Non assigné</option>
                                    <?php foreach ($utilisateurs as $utilisateur): ?>
                                        <option value="<?php echo $utilisateur['id']; ?>">
                                            <?php echo htmlspecialchars($utilisateur['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="highlight-bar"></span>
                            </div>
                        </div>
                        
                        <div class="col-12 mb-4">
                            <div class="input-group-futuristic">
                                <label for="edit_description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control futuristic-textarea" id="edit_description" name="description" rows="4" required></textarea>
                                <span class="highlight-bar"></span>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="input-group-futuristic">
                                <label for="edit_priorite" class="form-label">Priorité <span class="text-danger">*</span></label>
                                <select class="form-select futuristic-select" id="edit_priorite" name="priorite" required>
                                    <option value="">Sélectionner</option>
                                    <option value="basse" data-color="#00c853">Basse</option>
                                    <option value="moyenne" data-color="#ffc107">Moyenne</option>
                                    <option value="haute" data-color="#f44336">Haute</option>
                                </select>
                                <span class="highlight-bar"></span>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="input-group-futuristic">
                                <label for="edit_statut" class="form-label">Statut <span class="text-danger">*</span></label>
                                <select class="form-select futuristic-select" id="edit_statut" name="statut" required>
                                    <option value="">Sélectionner</option>
                                    <option value="a_faire" data-color="#3d5afe">À faire</option>
                                    <option value="en_cours" data-color="#7e57c2">En cours</option>
                                    <option value="termine" data-color="#00c853">Terminé</option>
                                </select>
                                <span class="highlight-bar"></span>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="input-group-futuristic">
                                <label for="edit_date_limite" class="form-label">Date d'échéance</label>
                                <input type="date" class="form-control futuristic-input" id="edit_date_limite" name="date_limite">
                                <span class="highlight-bar"></span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-save" onclick="sauvegarderModification()">
                    <i class="fas fa-save me-2"></i>Enregistrer
                    <span class="btn-save-overlay"></span>
                </button>
            </div>
        </div>
    </div>
</div>