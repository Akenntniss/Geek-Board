<?php
// Vérifier si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: index.php?page=knowledge_base");
    exit;
}

// Connexion à la base de données
require_once 'includes/db.php';

// Récupérer les catégories
$stmt = $db->prepare("SELECT * FROM kb_categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les tags
$stmt = $db->prepare("SELECT * FROM kb_tags ORDER BY name");
$stmt->execute();
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $category_id = intval($_POST['category_id'] ?? 0);
    $selected_tags = $_POST['tags'] ?? [];
    
    // Validation
    if (empty($title)) {
        $error = "Le titre est obligatoire";
    } elseif (empty($content)) {
        $error = "Le contenu est obligatoire";
    } elseif ($category_id <= 0) {
        $error = "Veuillez sélectionner une catégorie";
    } else {
        try {
            // Début de la transaction
            $db->beginTransaction();
            
            // Insérer l'article
            $stmt = $db->prepare("INSERT INTO kb_articles (title, content, category_id, created_at, updated_at, views) 
                                 VALUES (?, ?, ?, NOW(), NOW(), 0)");
            $stmt->execute([$title, $content, $category_id]);
            
            $article_id = $db->lastInsertId();
            
            // Ajouter les tags
            if (!empty($selected_tags)) {
                $stmt = $db->prepare("INSERT INTO kb_article_tags (article_id, tag_id) VALUES (?, ?)");
                foreach ($selected_tags as $tag_id) {
                    $stmt->execute([$article_id, $tag_id]);
                }
            }
            
            // Valider la transaction
            $db->commit();
            
            $success = "L'article a été ajouté avec succès";
            // Redirection vers l'article nouvellement créé
            header("Location: index.php?page=kb_article&id=$article_id&created=1");
            exit;
            
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $db->rollBack();
            $error = "Une erreur est survenue lors de l'ajout de l'article: " . $e->getMessage();
        }
    }
}
?>

<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <!-- Fil d'Ariane -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php?page=dashboard">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="index.php?page=knowledge_base">Base de connaissances</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Ajouter un article</li>
                </ol>
            </nav>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'ajout d'article -->
            <div class="card kb-editor-card">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Ajouter un nouvel article</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="kb-form">
                        <div class="row">
                            <!-- Colonne principale -->
                            <div class="col-lg-9">
                                <div class="mb-4">
                                    <label for="title" class="form-label">Titre de l'article <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                    <div class="form-text">Choisissez un titre clair et descriptif</div>
                                </div>

                                <div class="mb-4">
                                    <label for="content" class="form-label">Contenu <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="content" name="content" rows="12"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                    <div class="form-text">Utilisez l'éditeur pour formater votre article</div>
                                </div>
                            </div>

                            <!-- Sidebar pour les métadonnées -->
                            <div class="col-lg-3">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Publication</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Publier l'article
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?page=knowledge_base'">
                                                <i class="fas fa-times me-2"></i>Annuler
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Catégorie <span class="text-danger">*</span></h5>
                                    </div>
                                    <div class="card-body">
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Sélectionner une catégorie</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="mt-3">
                                            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#newCategoryModal">
                                                <i class="fas fa-plus-circle me-1"></i> Ajouter une nouvelle catégorie
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Tags</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <select class="form-select" id="tags" name="tags[]" multiple>
                                                <?php foreach ($tags as $tag): ?>
                                                    <option value="<?php echo $tag['id']; ?>" <?php echo (isset($_POST['tags']) && in_array($tag['id'], $_POST['tags'])) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($tag['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mt-3">
                                            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#newTagModal">
                                                <i class="fas fa-plus-circle me-1"></i> Ajouter un nouveau tag
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter une nouvelle catégorie -->
<div class="modal fade" id="newCategoryModal" tabindex="-1" aria-labelledby="newCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newCategoryModalLabel"><i class="fas fa-folder-plus me-2"></i>Ajouter une nouvelle catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="newCategoryForm">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Nom de la catégorie <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_icon" class="form-label">Icône (FontAwesome)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-icons"></i></span>
                            <input type="text" class="form-control" id="category_icon" name="category_icon" placeholder="fas fa-folder">
                        </div>
                        <div class="form-text">Exemple: fas fa-folder, fas fa-book, etc.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="saveCategoryBtn">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un nouveau tag -->
<div class="modal fade" id="newTagModal" tabindex="-1" aria-labelledby="newTagModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newTagModalLabel"><i class="fas fa-tag me-2"></i>Ajouter un nouveau tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="newTagForm">
                    <div class="mb-3">
                        <label for="tag_name" class="form-label">Nom du tag <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tag_name" name="tag_name" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="saveTagBtn">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour l'éditeur */
.content-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px 15px;
}

.kb-editor-card {
    border-radius: 0.75rem;
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 2rem;
}

.kb-editor-card .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.ck-editor__editable {
    min-height: 400px;
}

@media (max-width: 768px) {
    .kb-editor-card .card-header {
        padding: 1rem;
    }
}
</style>

<!-- Inclusion de l'éditeur CKEditor -->
<script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation de l'éditeur CKEditor
    ClassicEditor
        .create(document.querySelector('#content'), {
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'blockQuote', 'insertTable', 'mediaEmbed', 'undo', 'redo']
        })
        .catch(error => {
            console.error(error);
        });
    
    // Gestion de l'ajout d'une nouvelle catégorie
    document.getElementById('saveCategoryBtn').addEventListener('click', function() {
        const categoryName = document.getElementById('category_name').value;
        const categoryIcon = document.getElementById('category_icon').value;
        
        if (!categoryName) {
            alert('Le nom de la catégorie est obligatoire');
            return;
        }
        
        // Ici, vous pourriez ajouter du code AJAX pour sauvegarder la catégorie
        // Simulation pour l'exemple
        setTimeout(() => {
            // Ajouter la nouvelle catégorie à la liste
            const categorySelect = document.getElementById('category_id');
            const newOption = document.createElement('option');
            newOption.value = 'new_' + Date.now(); // ID temporaire
            newOption.text = categoryName;
            newOption.selected = true;
            categorySelect.add(newOption);
            
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('newCategoryModal'));
            modal.hide();
            
            // Message de succès
            alert('Catégorie ajoutée avec succès');
        }, 500);
    });
    
    // Gestion de l'ajout d'un nouveau tag
    document.getElementById('saveTagBtn').addEventListener('click', function() {
        const tagName = document.getElementById('tag_name').value;
        
        if (!tagName) {
            alert('Le nom du tag est obligatoire');
            return;
        }
        
        // Ici, vous pourriez ajouter du code AJAX pour sauvegarder le tag
        // Simulation pour l'exemple
        setTimeout(() => {
            // Ajouter le nouveau tag à la liste
            const tagSelect = document.getElementById('tags');
            const newOption = document.createElement('option');
            newOption.value = 'new_' + Date.now(); // ID temporaire
            newOption.text = tagName;
            newOption.selected = true;
            tagSelect.add(newOption);
            
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('newTagModal'));
            modal.hide();
            
            // Message de succès
            alert('Tag ajouté avec succès');
        }, 500);
    });
});
</script> 