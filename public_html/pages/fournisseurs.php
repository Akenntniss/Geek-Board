<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Récupérer la liste des fournisseurs
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query("SELECT * FROM fournisseurs ORDER BY nom");
    $fournisseurs = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des fournisseurs: " . $e->getMessage() . "</div>";
    $fournisseurs = [];
}

// Compter les fournisseurs
$total_fournisseurs = count($fournisseurs);
?>

<div class="content-wrapper">
    <!-- En-tête et actions principales -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="fas fa-truck text-primary me-2"></i>
            Gestion des Fournisseurs
        </h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajouterFournisseurModal">
            <i class="fas fa-plus me-1"></i> Ajouter un fournisseur
        </button>
    </div>

    <!-- Information sommaire -->
    <div class="stats-bar mb-3">
        <div class="stat-item">
            <i class="fas fa-truck text-primary"></i>
            <span class="stat-label">Total Fournisseurs</span>
            <span class="stat-value"><?php echo $total_fournisseurs; ?></span>
        </div>
    </div>

    <!-- Barre de recherche -->
    <div class="search-bar mb-3">
        <div class="input-group">
            <span class="input-group-text bg-light">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" class="form-control" id="searchSupplier" placeholder="Rechercher un fournisseur...">
        </div>
    </div>

    <?php echo display_message(); ?>

    <!-- Tableau principal -->
    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nom</th>
                            <th>URL</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($fournisseurs)): ?>
                            <?php foreach ($fournisseurs as $fournisseur): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary bg-opacity-10 text-primary me-3">
                                                <i class="fas fa-truck"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($fournisseur['nom']); ?></h6>
                                                <small class="text-muted">ID: <?php echo $fournisseur['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($fournisseur['url'])): ?>
                                            <a href="<?php echo htmlspecialchars($fournisseur['url']); ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt me-1"></i> Visiter
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Non spécifié</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger delete-supplier" 
                                                    data-id="<?php echo $fournisseur['id']; ?>"
                                                    data-nom="<?php echo htmlspecialchars($fournisseur['nom']); ?>"
                                                    data-bs-toggle="tooltip"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-box-open fa-3x mb-3"></i>
                                        <p class="mb-0">Aucun fournisseur enregistré</p>
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

<!-- Modal d'ajout de fournisseur -->
<div class="modal fade" id="ajouterFournisseurModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2 text-primary"></i>
                    Ajouter un fournisseur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ajouterFournisseurForm">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom du fournisseur</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-truck text-primary"></i>
                            </span>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="url" class="form-label">URL du site web</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-globe text-primary"></i>
                            </span>
                            <input type="url" class="form-control" id="url" name="url" placeholder="https://">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="button" class="btn btn-primary" id="saveSupplierBtn">
                    <i class="fas fa-save me-1"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteFournisseurModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-trash me-2 text-danger"></i>
                    Confirmer la suppression
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le fournisseur <strong id="deleteNomFournisseur"></strong> ?</p>
                <p class="text-danger mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Cette action est irréversible.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash me-1"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour la page fournisseurs */
.content-wrapper {
    padding: 1rem;
    max-width: 100%;
}

/* Style pour la barre de statistiques */
.stats-bar {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    background: #fff;
    padding: 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
}

.stat-item i {
    font-size: 1.5rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-right: 0.5rem;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
}

/* Style pour la barre de recherche */
.search-bar {
    background: #fff;
    padding: 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Style pour le tableau */
.card {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-bottom: 1rem;
}

.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85em;
    letter-spacing: 0.3px;
    color: #495057;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
}

/* Avatar circle */
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialisation des modals
    const addModal = new bootstrap.Modal(document.getElementById('ajouterFournisseurModal'));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteFournisseurModal'));
    
    // Recherche de fournisseurs
    document.getElementById('searchSupplier').addEventListener('input', function(e) {
        const searchText = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const nom = row.querySelector('td:first-child').textContent.toLowerCase();
            row.style.display = nom.includes(searchText) ? '' : 'none';
        });
    });
    
    // Gestionnaire pour l'ajout d'un fournisseur
    document.getElementById('saveSupplierBtn').addEventListener('click', async function() {
        const form = document.getElementById('ajouterFournisseurForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        
        try {
            const response = await fetch('../ajax/add_supplier.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                throw new Error(data.message || 'Erreur lors de l\'ajout du fournisseur');
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert(error.message);
        }
    });
    
    // Gestionnaire pour la suppression d'un fournisseur
    document.querySelectorAll('.delete-supplier').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nom = this.getAttribute('data-nom');
            
            document.getElementById('deleteNomFournisseur').textContent = nom;
            document.getElementById('confirmDelete').setAttribute('data-id', id);
            
            deleteModal.show();
        });
    });
    
    // Confirmer la suppression
    document.getElementById('confirmDelete').addEventListener('click', async function() {
        const id = this.getAttribute('data-id');
        
        try {
            const response = await fetch('../ajax/delete_supplier.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            });
            
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                throw new Error(data.message || 'Erreur lors de la suppression du fournisseur');
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert(error.message);
        }
    });
});
</script> 