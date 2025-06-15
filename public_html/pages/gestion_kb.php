<?php
// Page de gestion des catégories et tags de la base de connaissances
$page_title = "Gestion de la Base de Connaissances";
require_once 'includes/header.php';

// Vérifier que l'utilisateur est connecté et a les droits suffisants
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    set_message("Vous n'avez pas les droits nécessaires pour accéder à cette page.", "danger");
    redirect('base_connaissances');
}

// Récupération des catégories
function get_kb_categories() {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "SELECT c.*, COUNT(a.id) as article_count 
                  FROM kb_categories c
                  LEFT JOIN kb_articles a ON c.id = a.category_id
                  GROUP BY c.id
                  ORDER BY c.name ASC";
        $stmt = $shop_pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des catégories KB: " . $e->getMessage());
        return [];
    }
}

// Récupération des tags
function get_kb_tags() {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "SELECT t.*, COUNT(at.article_id) as article_count 
                  FROM kb_tags t
                  LEFT JOIN kb_article_tags at ON t.id = at.tag_id
                  GROUP BY t.id
                  ORDER BY t.name ASC";
        $stmt = $shop_pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tags KB: " . $e->getMessage());
        return [];
    }
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_pdo = getShopDBConnection();
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Ajouter une catégorie
    if ($action === 'add_category') {
        $name = cleanInput($_POST['category_name']);
        $icon = cleanInput($_POST['category_icon']);
        
        if (empty($name)) {
            set_message("Le nom de la catégorie est requis.", "danger");
        } else {
            try {
                $query = "INSERT INTO kb_categories (name, icon, created_at) VALUES (?, ?, NOW())";
                $stmt = $shop_pdo->prepare($query);
                $stmt->execute([$name, $icon]);
                
                set_message("La catégorie a été ajoutée avec succès.", "success");
                redirect('gestion_kb');
            } catch (PDOException $e) {
                error_log("Erreur lors de l'ajout de la catégorie: " . $e->getMessage());
                set_message("Une erreur est survenue lors de l'ajout de la catégorie.", "danger");
            }
        }
    }
    
    // Modifier une catégorie
    elseif ($action === 'edit_category') {
        $category_id = intval($_POST['category_id']);
        $name = cleanInput($_POST['category_name']);
        $icon = cleanInput($_POST['category_icon']);
        
        if (empty($name)) {
            set_message("Le nom de la catégorie est requis.", "danger");
        } else {
            try {
                $query = "UPDATE kb_categories SET name = ?, icon = ? WHERE id = ?";
                $stmt = $shop_pdo->prepare($query);
                $stmt->execute([$name, $icon, $category_id]);
                
                set_message("La catégorie a été mise à jour avec succès.", "success");
                redirect('gestion_kb');
            } catch (PDOException $e) {
                error_log("Erreur lors de la mise à jour de la catégorie: " . $e->getMessage());
                set_message("Une erreur est survenue lors de la mise à jour de la catégorie.", "danger");
            }
        }
    }
    
    // Supprimer une catégorie
    elseif ($action === 'delete_category') {
        $category_id = intval($_POST['category_id']);
        
        try {
            // Vérifier s'il y a des articles associés à cette catégorie
            $query = "SELECT COUNT(*) as count FROM kb_articles WHERE category_id = ?";
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$category_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                set_message("Impossible de supprimer cette catégorie car elle contient des articles. Veuillez d'abord déplacer ou supprimer ces articles.", "danger");
            } else {
                $query = "DELETE FROM kb_categories WHERE id = ?";
                $stmt = $shop_pdo->prepare($query);
                $stmt->execute([$category_id]);
                
                set_message("La catégorie a été supprimée avec succès.", "success");
            }
            
            redirect('gestion_kb');
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la catégorie: " . $e->getMessage());
            set_message("Une erreur est survenue lors de la suppression de la catégorie.", "danger");
        }
    }
    
    // Ajouter un tag
    elseif ($action === 'add_tag') {
        $name = cleanInput($_POST['tag_name']);
        
        if (empty($name)) {
            set_message("Le nom du tag est requis.", "danger");
        } else {
            try {
                $query = "INSERT INTO kb_tags (name, created_at) VALUES (?, NOW())";
                $stmt = $shop_pdo->prepare($query);
                $stmt->execute([$name]);
                
                set_message("Le tag a été ajouté avec succès.", "success");
                redirect('gestion_kb');
            } catch (PDOException $e) {
                error_log("Erreur lors de l'ajout du tag: " . $e->getMessage());
                set_message("Une erreur est survenue lors de l'ajout du tag.", "danger");
            }
        }
    }
    
    // Modifier un tag
    elseif ($action === 'edit_tag') {
        $tag_id = intval($_POST['tag_id']);
        $name = cleanInput($_POST['tag_name']);
        
        if (empty($name)) {
            set_message("Le nom du tag est requis.", "danger");
        } else {
            try {
                $query = "UPDATE kb_tags SET name = ? WHERE id = ?";
                $stmt = $shop_pdo->prepare($query);
                $stmt->execute([$name, $tag_id]);
                
                set_message("Le tag a été mis à jour avec succès.", "success");
                redirect('gestion_kb');
            } catch (PDOException $e) {
                error_log("Erreur lors de la mise à jour du tag: " . $e->getMessage());
                set_message("Une erreur est survenue lors de la mise à jour du tag.", "danger");
            }
        }
    }
    
    // Supprimer un tag
    elseif ($action === 'delete_tag') {
        $tag_id = intval($_POST['tag_id']);
        
        try {
            // Commencer une transaction
            $shop_pdo->beginTransaction();
            
            // Supprimer d'abord les associations avec les articles
            $query = "DELETE FROM kb_article_tags WHERE tag_id = ?";
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$tag_id]);
            
            // Puis supprimer le tag
            $query = "DELETE FROM kb_tags WHERE id = ?";
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$tag_id]);
            
            // Valider la transaction
            $shop_pdo->commit();
            
            set_message("Le tag a été supprimé avec succès.", "success");
            redirect('gestion_kb');
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $shop_pdo->rollBack();
            error_log("Erreur lors de la suppression du tag: " . $e->getMessage());
            set_message("Une erreur est survenue lors de la suppression du tag.", "danger");
        }
    }
}

// Récupérer les catégories et les tags
$categories = get_kb_categories();
$tags = get_kb_tags();
?>

<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i> Gestion de la Base de Connaissances
                    </h5>
                    <a href="index.php?page=base_connaissances" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Retour à la base de connaissances
                    </a>
                </div>
                <div class="card-body">
                    <!-- Messages d'erreur ou de succès -->
                    <?= display_message() ?>
                    
                    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab" aria-controls="categories" aria-selected="true">
                                <i class="fas fa-folder me-1"></i> Catégories
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tags-tab" data-bs-toggle="tab" data-bs-target="#tags" type="button" role="tab" aria-controls="tags" aria-selected="false">
                                <i class="fas fa-tags me-1"></i> Tags
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="myTabContent">
                        <!-- Onglet Catégories -->
                        <div class="tab-pane fade show active" id="categories" role="tabpanel" aria-labelledby="categories-tab">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h4 class="mb-3">Ajouter une catégorie</h4>
                                    <form action="index.php?page=gestion_kb" method="POST" class="card p-3 border">
                                        <input type="hidden" name="action" value="add_category">
                                        
                                        <div class="mb-3">
                                            <label for="category_name" class="form-label">Nom de la catégorie</label>
                                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="category_icon" class="form-label">Icône (classe Font Awesome)</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-icons"></i></span>
                                                <input type="text" class="form-control" id="category_icon" name="category_icon" placeholder="fas fa-folder" value="fas fa-folder">
                                            </div>
                                            <div class="form-text">Exemple: fas fa-folder, fas fa-book, etc.</div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-plus-circle me-1"></i> Ajouter la catégorie
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="col-md-6">
                                    <h4 class="mb-3">Liste des catégories</h4>
                                    <?php if (empty($categories)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-1"></i> Aucune catégorie n'a été créée.
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nom</th>
                                                    <th>Icône</th>
                                                    <th>Articles</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($category['name']) ?></td>
                                                    <td>
                                                        <i class="<?= htmlspecialchars($category['icon']) ?>"></i> 
                                                        <span class="text-muted small"><?= htmlspecialchars($category['icon']) ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($category['article_count'] > 0): ?>
                                                        <a href="index.php?page=base_connaissances&categorie=<?= $category['id'] ?>" class="badge bg-primary text-decoration-none">
                                                            <?= $category['article_count'] ?> article(s)
                                                        </a>
                                                        <?php else: ?>
                                                        <span class="badge bg-secondary">0 article</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-primary edit-category-btn" 
                                                                data-bs-toggle="modal" data-bs-target="#editCategoryModal" 
                                                                data-category-id="<?= $category['id'] ?>" 
                                                                data-category-name="<?= htmlspecialchars($category['name']) ?>"
                                                                data-category-icon="<?= htmlspecialchars($category['icon']) ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <?php if ($category['article_count'] == 0): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-category-btn" 
                                                                data-bs-toggle="modal" data-bs-target="#deleteCategoryModal" 
                                                                data-category-id="<?= $category['id'] ?>" 
                                                                data-category-name="<?= htmlspecialchars($category['name']) ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Cette catégorie contient des articles et ne peut pas être supprimée.">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Onglet Tags -->
                        <div class="tab-pane fade" id="tags" role="tabpanel" aria-labelledby="tags-tab">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h4 class="mb-3">Ajouter un tag</h4>
                                    <form action="index.php?page=gestion_kb" method="POST" class="card p-3 border">
                                        <input type="hidden" name="action" value="add_tag">
                                        
                                        <div class="mb-3">
                                            <label for="tag_name" class="form-label">Nom du tag</label>
                                            <input type="text" class="form-control" id="tag_name" name="tag_name" required>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-plus-circle me-1"></i> Ajouter le tag
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="col-md-6">
                                    <h4 class="mb-3">Liste des tags</h4>
                                    <?php if (empty($tags)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-1"></i> Aucun tag n'a été créé.
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nom</th>
                                                    <th>Articles</th>
                                                    <th>Date de création</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($tags as $tag): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($tag['name']) ?></td>
                                                    <td>
                                                        <?php if ($tag['article_count'] > 0): ?>
                                                        <a href="index.php?page=base_connaissances&recherche=<?= urlencode($tag['name']) ?>" class="badge bg-primary text-decoration-none">
                                                            <?= $tag['article_count'] ?> article(s)
                                                        </a>
                                                        <?php else: ?>
                                                        <span class="badge bg-secondary">0 article</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= date('d/m/Y', strtotime($tag['created_at'])) ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-primary edit-tag-btn" 
                                                                data-bs-toggle="modal" data-bs-target="#editTagModal" 
                                                                data-tag-id="<?= $tag['id'] ?>" 
                                                                data-tag-name="<?= htmlspecialchars($tag['name']) ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-tag-btn" 
                                                                data-bs-toggle="modal" data-bs-target="#deleteTagModal" 
                                                                data-tag-id="<?= $tag['id'] ?>" 
                                                                data-tag-name="<?= htmlspecialchars($tag['name']) ?>"
                                                                data-article-count="<?= $tag['article_count'] ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modifier Catégorie -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=gestion_kb" method="POST">
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="category_id" id="edit_category_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Modifier une catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Nom de la catégorie</label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_category_icon" class="form-label">Icône (classe Font Awesome)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-icons"></i></span>
                            <input type="text" class="form-control" id="edit_category_icon" name="category_icon">
                        </div>
                        <div class="form-text">Exemple: fas fa-folder, fas fa-book, etc.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Supprimer Catégorie -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=gestion_kb" method="POST">
                <input type="hidden" name="action" value="delete_category">
                <input type="hidden" name="category_id" id="delete_category_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer la catégorie <strong id="delete_category_name"></strong> ?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Cette action est irréversible.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifier Tag -->
<div class="modal fade" id="editTagModal" tabindex="-1" aria-labelledby="editTagModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=gestion_kb" method="POST">
                <input type="hidden" name="action" value="edit_tag">
                <input type="hidden" name="tag_id" id="edit_tag_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editTagModalLabel">Modifier un tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_tag_name" class="form-label">Nom du tag</label>
                        <input type="text" class="form-control" id="edit_tag_name" name="tag_name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Supprimer Tag -->
<div class="modal fade" id="deleteTagModal" tabindex="-1" aria-labelledby="deleteTagModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=gestion_kb" method="POST">
                <input type="hidden" name="action" value="delete_tag">
                <input type="hidden" name="tag_id" id="delete_tag_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTagModalLabel">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le tag <strong id="delete_tag_name"></strong> ?</p>
                    <div id="tag_warning_container"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurer les modals pour éditer une catégorie
    document.querySelectorAll('.edit-category-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-category-id');
            const categoryName = this.getAttribute('data-category-name');
            const categoryIcon = this.getAttribute('data-category-icon');
            
            document.getElementById('edit_category_id').value = categoryId;
            document.getElementById('edit_category_name').value = categoryName;
            document.getElementById('edit_category_icon').value = categoryIcon;
        });
    });
    
    // Configurer les modals pour supprimer une catégorie
    document.querySelectorAll('.delete-category-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-category-id');
            const categoryName = this.getAttribute('data-category-name');
            
            document.getElementById('delete_category_id').value = categoryId;
            document.getElementById('delete_category_name').textContent = categoryName;
        });
    });
    
    // Configurer les modals pour éditer un tag
    document.querySelectorAll('.edit-tag-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const tagId = this.getAttribute('data-tag-id');
            const tagName = this.getAttribute('data-tag-name');
            
            document.getElementById('edit_tag_id').value = tagId;
            document.getElementById('edit_tag_name').value = tagName;
        });
    });
    
    // Configurer les modals pour supprimer un tag
    document.querySelectorAll('.delete-tag-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const tagId = this.getAttribute('data-tag-id');
            const tagName = this.getAttribute('data-tag-name');
            const articleCount = parseInt(this.getAttribute('data-article-count'));
            
            document.getElementById('delete_tag_id').value = tagId;
            document.getElementById('delete_tag_name').textContent = tagName;
            
            const warningContainer = document.getElementById('tag_warning_container');
            warningContainer.innerHTML = '';
            
            if (articleCount > 0) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-warning';
                alert.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>
                                   Ce tag est utilisé dans ${articleCount} article(s). La suppression retirera le tag de tous ces articles.`;
                warningContainer.appendChild(alert);
            }
            
            const alertDanger = document.createElement('div');
            alertDanger.className = 'alert alert-danger';
            alertDanger.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>
                                    Cette action est irréversible.`;
            warningContainer.appendChild(alertDanger);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 