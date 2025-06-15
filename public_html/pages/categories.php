<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Récupérer les catégories
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("
        SELECT c.*, COUNT(p.id) as nb_produits 
        FROM categories c 
        LEFT JOIN produits p ON c.id = p.categorie_id 
        GROUP BY c.id 
        ORDER BY c.nom ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des catégories: " . $e->getMessage(), 'danger');
    $categories = [];
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="display-6 mb-1">Gestion des Catégories</h1>
            <p class="text-muted mb-0">Gérez les catégories de produits</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategorie">
                <i class="fas fa-plus me-2"></i>Nouvelle Catégorie
            </button>
        </div>
    </div>

    <?php echo display_message(); ?>

    <div class="row">
        <?php foreach ($categories as $categorie): ?>
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title d-flex justify-content-between align-items-center">
                        <?php echo htmlspecialchars($categorie['nom']); ?>
                        <div class="badge bg-primary"><?php echo $categorie['nb_produits']; ?></div>
                    </h5>
                    <p class="card-text text-muted">
                        <?php echo htmlspecialchars($categorie['description'] ?: 'Aucune description'); ?>
                    </p>
                </div>
                <div class="card-footer bg-light">
                    <div class="btn-group w-100">
                        <button type="button" class="btn btn-outline-primary" onclick="editerCategorie(<?php echo $categorie['id']; ?>)">
                            <i class="fas fa-edit me-2"></i>Modifier
                        </button>
                        <?php if ($categorie['nb_produits'] == 0): ?>
                        <button type="button" class="btn btn-outline-danger" onclick="supprimerCategorie(<?php echo $categorie['id']; ?>)">
                            <i class="fas fa-trash me-2"></i>Supprimer
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Ajout/Edition Catégorie -->
<div class="modal fade" id="modalCategorie" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCategorie" method="POST">
                    <input type="hidden" name="action" value="ajouter_categorie">
                    <input type="hidden" name="categorie_id" id="categorieId">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="formCategorie" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
function editerCategorie(categorieId) {
    // À implémenter : charger les données de la catégorie et ouvrir le modal
    alert('Fonctionnalité à implémenter');
}

function supprimerCategorie(categorieId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
        // À implémenter : supprimer la catégorie
        alert('Fonctionnalité à implémenter');
    }
}
</script>

<style>
.card {
    transition: transform 0.2s;
    border: none;
    box-shadow: var(--shadow-sm);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.card-title {
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.card-text {
    font-size: 0.9rem;
    height: 3em;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.card-footer {
    border-top: 1px solid rgba(0,0,0,0.1);
    padding: 0.75rem;
}

.btn-group .btn {
    flex: 1;
}
</style> 