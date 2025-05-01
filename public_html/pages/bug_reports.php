<?php
// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit();
}

// Vérifier si la connexion à la base de données est déjà établie
if (!isset($conn) || $conn === null) {
    // La connexion n'est pas établie, on la crée ici
    $host = 'srv931.hstgr.io';
    $db_name = 'u139954273_Vscodetest';
    $username = 'u139954273_Vscodetest';
    $password = 'Maman01#';
    
    $conn = new mysqli($host, $username, $password, $db_name);
    
    if ($conn->connect_error) {
        die("Erreur de connexion à la base de données: " . $conn->connect_error);
    }
}

// Récupération des signalements de bugs depuis la base de données
$query = "SELECT br.*, u.nom, u.prenom 
          FROM bug_reports br
          LEFT JOIN users u ON br.user_id = u.id 
          ORDER BY br.date_creation DESC";
$result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-bug text-danger me-2"></i>
                            Signalements de bugs
                        </h5>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <div class="mb-4">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary active filter-btn" data-filter="all">Tous</button>
                            <button type="button" class="btn btn-outline-warning filter-btn" data-filter="nouveau">Nouveaux</button>
                            <button type="button" class="btn btn-outline-info filter-btn" data-filter="en_cours">En cours</button>
                            <button type="button" class="btn btn-outline-success filter-btn" data-filter="resolu">Résolus</button>
                            <button type="button" class="btn btn-outline-secondary filter-btn" data-filter="ferme">Fermés</button>
                        </div>
                    </div>

                    <!-- Tableau des bugs -->
                    <div class="table-responsive">
                        <table class="table table-hover border-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Utilisateur</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr class="bug-row" data-status="<?php echo $row['status']; ?>">
                                            <td>#<?php echo $row['id']; ?></td>
                                            <td>
                                                <?php if ($row['nom'] && $row['prenom']): ?>
                                                    <?php echo htmlspecialchars($row['prenom'] . ' ' . $row['nom']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Utilisateur inconnu</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-truncate d-inline-block" style="max-width: 300px;">
                                                    <?php echo htmlspecialchars($row['description']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($row['date_creation'])); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_text = '';
                                                
                                                switch($row['status']) {
                                                    case 'nouveau':
                                                        $status_class = 'badge bg-warning';
                                                        $status_text = 'Nouveau';
                                                        break;
                                                    case 'en_cours':
                                                        $status_class = 'badge bg-info';
                                                        $status_text = 'En cours';
                                                        break;
                                                    case 'resolu':
                                                        $status_class = 'badge bg-success';
                                                        $status_text = 'Résolu';
                                                        break;
                                                    case 'ferme':
                                                        $status_class = 'badge bg-secondary';
                                                        $status_text = 'Fermé';
                                                        break;
                                                }
                                                ?>
                                                <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary view-bug" data-id="<?php echo $row['id']; ?>" data-description="<?php echo htmlspecialchars($row['description']); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary update-status" data-id="<?php echo $row['id']; ?>" data-status="<?php echo $row['status']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                                <p>Aucun signalement de bug pour le moment.</p>
                                            </div>
                                        </td>
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

<!-- Modal Voir Bug -->
<div class="modal fade" id="viewBugModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title">
                    <i class="fas fa-bug me-2 text-danger"></i>
                    Détails du bug
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p id="bug-description" class="mb-0"></p>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modifier Statut -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Modifier le statut
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="updateStatusForm">
                    <input type="hidden" id="bug-id" name="bug_id">
                    <div class="mb-3">
                        <label class="form-label">Statut actuel</label>
                        <span id="current-status" class="badge d-block p-2 text-start"></span>
                    </div>
                    <div class="mb-3">
                        <label for="new-status" class="form-label">Nouveau statut</label>
                        <select class="form-select" id="new-status" name="status">
                            <option value="nouveau">Nouveau</option>
                            <option value="en_cours">En cours</option>
                            <option value="resolu">Résolu</option>
                            <option value="ferme">Fermé</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="save-status">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtrage des bugs
    const filterButtons = document.querySelectorAll('.filter-btn');
    const bugRows = document.querySelectorAll('.bug-row');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Mise à jour des boutons actifs
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filtrage des lignes
            const filter = this.dataset.filter;
            
            bugRows.forEach(row => {
                if (filter === 'all' || row.dataset.status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    // Modal Voir Bug
    const viewButtons = document.querySelectorAll('.view-bug');
    if (viewButtons.length > 0 && typeof bootstrap !== 'undefined') {
        const viewBugModal = new bootstrap.Modal(document.getElementById('viewBugModal'));
        
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const description = this.dataset.description;
                document.getElementById('bug-description').textContent = description;
                viewBugModal.show();
            });
        });
    }
    
    // Modal Modifier Statut
    const updateButtons = document.querySelectorAll('.update-status');
    if (updateButtons.length > 0 && typeof bootstrap !== 'undefined') {
        const updateStatusModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
        
        updateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bugId = this.dataset.id;
                const status = this.dataset.status;
                
                document.getElementById('bug-id').value = bugId;
                
                const currentStatusElement = document.getElementById('current-status');
                currentStatusElement.textContent = '';
                currentStatusElement.className = 'badge d-block p-2 text-start';
                
                // Définir la classe et le texte en fonction du statut
                switch(status) {
                    case 'nouveau':
                        currentStatusElement.textContent = 'Nouveau';
                        currentStatusElement.classList.add('bg-warning');
                        break;
                    case 'en_cours':
                        currentStatusElement.textContent = 'En cours';
                        currentStatusElement.classList.add('bg-info');
                        break;
                    case 'resolu':
                        currentStatusElement.textContent = 'Résolu';
                        currentStatusElement.classList.add('bg-success');
                        break;
                    case 'ferme':
                        currentStatusElement.textContent = 'Fermé';
                        currentStatusElement.classList.add('bg-secondary');
                        break;
                }
                
                // Sélectionner le statut actuel dans le select
                document.getElementById('new-status').value = status;
                
                updateStatusModal.show();
            });
        });
        
        // Sauvegarder le nouveau statut
        document.getElementById('save-status').addEventListener('click', function() {
            const bugId = document.getElementById('bug-id').value;
            const newStatus = document.getElementById('new-status').value;
            
            // Requête AJAX pour mettre à jour le statut
            fetch('/ajax/update_bug_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `bug_id=${bugId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer le modal
                    updateStatusModal.hide();
                    
                    // Recharger la page pour refléter les changements
                    location.reload();
                } else {
                    // Afficher un message d'erreur
                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.message || 'Une erreur est survenue');
                    } else {
                        alert(data.message || 'Une erreur est survenue');
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Une erreur est survenue lors de la mise à jour du statut');
                } else {
                    alert('Une erreur est survenue lors de la mise à jour du statut');
                }
            });
        });
    }
});
</script> 