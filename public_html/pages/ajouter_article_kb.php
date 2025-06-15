<?php
// Page d'ajout d'un article à la base de connaissances
$page_title = "Ajouter un article à la Base de Connaissances";
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
        $query = "SELECT * FROM kb_categories ORDER BY name ASC";
        $shop_pdo = getShopDBConnection();
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
        $query = "SELECT * FROM kb_tags ORDER BY name ASC";
        $stmt = $shop_pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tags KB: " . $e->getMessage());
        return [];
    }
}

// Fonction pour créer un nouveau tag
function create_kb_tag($name) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "INSERT INTO kb_tags (name, created_at) VALUES (?, NOW())";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$name]);
        return $shop_pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erreur lors de la création du tag: " . $e->getMessage());
        return false;
    }
}

// Fonction pour vérifier si un tag existe et le créer s'il n'existe pas
function get_or_create_tag($tag_name) {
    $shop_pdo = getShopDBConnection();
    try {
        // Vérifier si le tag existe déjà
        $query = "SELECT id FROM kb_tags WHERE name = ?";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([trim($tag_name)]);
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tag) {
            return $tag['id'];
        } else {
            // Créer le tag s'il n'existe pas
            return create_kb_tag(trim($tag_name));
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération/création du tag: " . $e->getMessage());
        return false;
    }
}

// Récupérer les catégories et les tags
$categories = get_kb_categories();
$tags = get_kb_tags();

// Traitement du formulaire d'ajout d'article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_article') {
    $title = cleanInput($_POST['title']);
    $content = $_POST['content']; // Ne pas nettoyer le contenu qui peut contenir du HTML formaté
    $category_id = intval($_POST['category_id']);
    $tag_ids = isset($_POST['tag_ids']) ? $_POST['tag_ids'] : [];
    $new_tags = isset($_POST['new_tags']) ? $_POST['new_tags'] : '';
    
    // Validation basique
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Le titre de l'article est requis.";
    }
    
    if (empty($content)) {
        $errors[] = "Le contenu de l'article est requis.";
    }
    
    if ($category_id <= 0) {
        $errors[] = "Veuillez sélectionner une catégorie.";
    }
    
    // Si aucune erreur, ajouter l'article
    if (empty($errors)) {
        try {
            // Début de la transaction
            $shop_pdo->beginTransaction();
            
            // Insérer l'article
            $query = "INSERT INTO kb_articles (title, content, category_id, created_at, updated_at, views) 
                      VALUES (?, ?, ?, NOW(), NOW(), 0)";
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$title, $content, $category_id]);
            $article_id = $shop_pdo->lastInsertId();
            
            // Ajouter les tags existants sélectionnés
            if (!empty($tag_ids)) {
                $values = [];
                $placeholders = [];
                
                foreach ($tag_ids as $tag_id) {
                    $placeholders[] = "(?, ?)";
                    $values[] = $article_id;
                    $values[] = intval($tag_id);
                }
                
                $query = "INSERT INTO kb_article_tags (article_id, tag_id) VALUES " . implode(', ', $placeholders);
                $stmt = $shop_pdo->prepare($query);
                $stmt->execute($values);
            }
            
            // Traiter les nouveaux tags
            if (!empty($new_tags)) {
                $tag_names = explode(',', $new_tags);
                
                foreach ($tag_names as $tag_name) {
                    $tag_name = trim($tag_name);
                    if (!empty($tag_name)) {
                        $tag_id = get_or_create_tag($tag_name);
                        
                        if ($tag_id) {
                            // Ajouter l'association entre l'article et le tag
                            $query = "INSERT INTO kb_article_tags (article_id, tag_id) VALUES (?, ?)";
                            $stmt = $shop_pdo->prepare($query);
                            $stmt->execute([$article_id, $tag_id]);
                        }
                    }
                }
            }
            
            // Valider la transaction
            $shop_pdo->commit();
            
            set_message("L'article a été ajouté avec succès à la base de connaissances.", "success");
            redirect('article_kb', ['id' => $article_id]);
            
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $shop_pdo->rollBack();
            error_log("Erreur lors de l'ajout de l'article: " . $e->getMessage());
            set_message("Une erreur est survenue lors de l'ajout de l'article. Veuillez réessayer.", "danger");
        }
    }
}
?>

<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i> Ajouter un article à la Base de Connaissances
                    </h5>
                    <a href="index.php?page=base_connaissances" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                    </a>
                </div>
                <div class="card-body">
                    <!-- Affichage des erreurs -->
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Formulaire d'ajout d'article -->
                    <form action="index.php?page=ajouter_article_kb" method="POST" id="add-article-form">
                        <input type="hidden" name="action" value="add_article">
                        
                        <div class="row mb-3">
                            <div class="col-md-9">
                                <!-- Titre de l'article -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">Titre de l'article <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                                </div>
                                
                                <!-- Contenu de l'article -->
                                <div class="mb-3">
                                    <label for="content" class="form-label">Contenu de l'article <span class="text-danger">*</span></label>
                                    <textarea class="form-control rich-editor" id="content" name="content" rows="15" required><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></textarea>
                                    <div class="form-text">Utilisez l'éditeur pour mettre en forme votre contenu. Vous pouvez ajouter des images, des liens, des tableaux, etc.</div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <!-- Catégorie -->
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Catégorie <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Sélectionner une catégorie</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Tags existants -->
                                <div class="mb-3">
                                    <label class="form-label">Tags existants</label>
                                    <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                        <?php if (empty($tags)): ?>
                                        <div class="text-muted small">Aucun tag existant.</div>
                                        <?php else: ?>
                                            <?php foreach ($tags as $tag): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="tag_ids[]" 
                                                       value="<?= $tag['id'] ?>" id="tag-<?= $tag['id'] ?>"
                                                       <?= (isset($_POST['tag_ids']) && in_array($tag['id'], $_POST['tag_ids'])) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="tag-<?= $tag['id'] ?>">
                                                    <?= htmlspecialchars($tag['name']) ?>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Nouveaux tags -->
                                <div class="mb-3">
                                    <label for="new_tags" class="form-label">Nouveaux tags</label>
                                    <input type="text" class="form-control" id="new_tags" name="new_tags" 
                                           placeholder="tag1, tag2, tag3" 
                                           value="<?= isset($_POST['new_tags']) ? htmlspecialchars($_POST['new_tags']) : '' ?>">
                                    <div class="form-text">Séparez les tags par des virgules.</div>
                                </div>
                                
                                <!-- Boutons d'action -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Enregistrer l'article
                                    </button>
                                    <a href="index.php?page=base_connaissances" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Annuler
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inclure TinyMCE pour l'éditeur de texte riche -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.5/tinymce.min.js" integrity="sha512-TBhJOcYyaYvx+W7AaQZBnPVpbJX9LZvgidy1jWV9W78vUCKsK8/UODri3nkkjbWQXNKK+1dz/yLMrtdoJ+brQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser TinyMCE
    tinymce.init({
        selector: '.rich-editor',
        height: 500,
        menubar: true,
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'
        ],
        toolbar: 'undo redo | formatselect | ' +
        'bold italic backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | help',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial; font-size: 16px; }',
        language: 'fr_FR',
        entity_encoding: 'raw',
        forced_root_block: 'p',
        remove_linebreaks: false,
        convert_newlines_to_brs: true,
        remove_trailing_brs: false
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 