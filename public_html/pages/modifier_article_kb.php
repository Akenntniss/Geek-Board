<?php
// Page de modification d'un article de la base de connaissances
$page_title = "Modifier un article de la Base de Connaissances";
require_once 'includes/header.php';

// Vérifier que l'utilisateur est connecté et a les droits suffisants
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    set_message("Vous n'avez pas les droits nécessaires pour accéder à cette page.", "danger");
    redirect('base_connaissances');
}

// Vérifier si un ID d'article est spécifié
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_message("L'article demandé n'existe pas.", "danger");
    redirect('base_connaissances');
}

$article_id = intval($_GET['id']);

// Récupérer l'article spécifié
function get_kb_article($id) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "
            SELECT a.*, c.name as category_name 
            FROM kb_articles a
            LEFT JOIN kb_categories c ON a.category_id = c.id
            WHERE a.id = ?
        ";
        $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de l'article KB: " . $e->getMessage());
        return false;
    }
}

// Récupération des catégories
function get_kb_categories() {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "SELECT * FROM kb_categories ORDER BY name ASC";
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

// Récupération des tags d'un article
function get_article_tags($article_id) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "
            SELECT tag_id 
            FROM kb_article_tags 
            WHERE article_id = ?
        ";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Extraire uniquement les IDs des tags
        $tag_ids = [];
        foreach ($results as $result) {
            $tag_ids[] = $result['tag_id'];
        }
        
        return $tag_ids;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tags de l'article: " . $e->getMessage());
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

// Récupérer l'article
$article = get_kb_article($article_id);

// Si l'article n'existe pas, rediriger vers la liste des articles
if (!$article) {
    set_message("L'article demandé n'existe pas.", "danger");
    redirect('base_connaissances');
}

// Récupérer les catégories et les tags
$categories = get_kb_categories();
$tags = get_kb_tags();
$article_tags = get_article_tags($article_id);

// Traitement du formulaire de modification d'article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_article') {
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
    
    // Si aucune erreur, modifier l'article
    if (empty($errors)) {
        try {
            // Début de la transaction
            $shop_pdo->beginTransaction();
            
            // Mettre à jour l'article
            $query = "UPDATE kb_articles SET title = ?, content = ?, category_id = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$title, $content, $category_id, $article_id]);
            
            // Supprimer tous les tags existants pour cet article
            $query = "DELETE FROM kb_article_tags WHERE article_id = ?";
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$article_id]);
            
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
            
            set_message("L'article a été mis à jour avec succès.", "success");
            redirect('article_kb', ['id' => $article_id]);
            
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $shop_pdo->rollBack();
            error_log("Erreur lors de la modification de l'article: " . $e->getMessage());
            set_message("Une erreur est survenue lors de la modification de l'article. Veuillez réessayer.", "danger");
        }
    }
}

// Traitement de la suppression d'article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_article') {
    try {
        // Début de la transaction
        $shop_pdo->beginTransaction();
        
        // Supprimer d'abord les associations avec les tags
        $query = "DELETE FROM kb_article_tags WHERE article_id = ?";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id]);
        
        // Supprimer les évaluations de l'article
        $query = "DELETE FROM kb_article_ratings WHERE article_id = ?";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id]);
        
        // Enfin, supprimer l'article
        $query = "DELETE FROM kb_articles WHERE id = ?";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id]);
        
        // Valider la transaction
        $shop_pdo->commit();
        
        set_message("L'article a été supprimé avec succès.", "success");
        redirect('base_connaissances');
        
    } catch (PDOException $e) {
        // Annuler la transaction en cas d'erreur
        $shop_pdo->rollBack();
        error_log("Erreur lors de la suppression de l'article: " . $e->getMessage());
        set_message("Une erreur est survenue lors de la suppression de l'article. Veuillez réessayer.", "danger");
    }
}
?>

<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i> Modifier un article de la Base de Connaissances
                    </h5>
                    <div>
                        <button type="button" class="btn btn-outline-light btn-sm me-2" data-bs-toggle="modal" data-bs-target="#deleteArticleModal">
                            <i class="fas fa-trash me-1"></i> Supprimer
                        </button>
                        <a href="index.php?page=article_kb&id=<?= $article_id ?>" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Retour à l'article
                        </a>
                    </div>
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
                    
                    <!-- Formulaire de modification d'article -->
                    <form action="index.php?page=modifier_article_kb&id=<?= $article_id ?>" method="POST" id="edit-article-form">
                        <input type="hidden" name="action" value="edit_article">
                        
                        <div class="row mb-3">
                            <div class="col-md-9">
                                <!-- Titre de l'article -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">Titre de l'article <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           value="<?= htmlspecialchars($article['title']) ?>">
                                </div>
                                
                                <!-- Contenu de l'article -->
                                <div class="mb-3">
                                    <label for="content" class="form-label">Contenu de l'article <span class="text-danger">*</span></label>
                                    <textarea class="form-control rich-editor" id="content" name="content" rows="15" required><?= htmlspecialchars($article['content']) ?></textarea>
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
                                        <option value="<?= $category['id'] ?>" <?= ($article['category_id'] == $category['id']) ? 'selected' : '' ?>>
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
                                                       <?= in_array($tag['id'], $article_tags) ? 'checked' : '' ?>>
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
                                           placeholder="tag1, tag2, tag3">
                                    <div class="form-text">Séparez les tags par des virgules.</div>
                                </div>
                                
                                <!-- Informations sur l'article -->
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Informations</h6>
                                    </div>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Vues</span>
                                            <span class="badge bg-secondary"><?= $article['views'] ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Créé le</span>
                                            <span><?= date('d/m/Y', strtotime($article['created_at'])) ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Dernière mise à jour</span>
                                            <span><?= date('d/m/Y', strtotime($article['updated_at'])) ?></span>
                                        </li>
                                    </ul>
                                </div>
                                
                                <!-- Boutons d'action -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Enregistrer les modifications
                                    </button>
                                    <a href="index.php?page=article_kb&id=<?= $article_id ?>" class="btn btn-outline-secondary">
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

<!-- Modal Supprimer Article -->
<div class="modal fade" id="deleteArticleModal" tabindex="-1" aria-labelledby="deleteArticleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=modifier_article_kb&id=<?= $article_id ?>" method="POST">
                <input type="hidden" name="action" value="delete_article">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteArticleModalLabel">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer l'article <strong><?= htmlspecialchars($article['title']) ?></strong> ?</p>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Cette action est irréversible et supprimera également toutes les évaluations associées à cet article.
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

<!-- Inclusion de TinyMCE -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.5/tinymce.min.js" integrity="sha512-TBhJOcYyaYvx+W7AaQZBnPVpbJX9LZvgidy1jWV9W78vUCKsK8/UODri3nkkjbWQXNKK+1dz/yLMrtdoJ+brQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<!-- Initialisation de TinyMCE -->
<script>
    tinymce.init({
        selector: '.rich-editor',
        height: 500,
        menubar: 'file edit view insert format tools table help',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons',
            'codesample', 'hr', 'textcolor', 'paste', 'quickbars'
        ],
        toolbar1: 'formatselect | fontselect fontsizeselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify',
        toolbar2: 'bullist numlist | outdent indent | link image media table emoticons | removeformat code fullscreen help',
        toolbar_mode: 'sliding',
        toolbar_sticky: true,
        style_formats: [
            { title: 'Titres', items: [
                { title: 'Titre 1', format: 'h1' },
                { title: 'Titre 2', format: 'h2' },
                { title: 'Titre 3', format: 'h3' },
                { title: 'Titre 4', format: 'h4' },
                { title: 'Titre 5', format: 'h5' },
                { title: 'Titre 6', format: 'h6' }
            ]},
            { title: 'Blocs', items: [
                { title: 'Paragraphe', format: 'p' },
                { title: 'Citation', format: 'blockquote' },
                { title: 'Code', format: 'pre' }
            ]},
            { title: 'Conteneurs', items: [
                { title: 'Info', block: 'div', classes: 'alert alert-info', wrapper: true },
                { title: 'Succès', block: 'div', classes: 'alert alert-success', wrapper: true },
                { title: 'Attention', block: 'div', classes: 'alert alert-warning', wrapper: true },
                { title: 'Danger', block: 'div', classes: 'alert alert-danger', wrapper: true }
            ]}
        ],
        fontsize_formats: '8pt 10pt 12pt 14pt 16pt 18pt 20pt 22pt 24pt 26pt 28pt 36pt 48pt 72pt',
        font_family_formats: 'Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva;',
        content_style: `
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; font-size: 16px; line-height: 1.6; }
            .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
            .alert-info { color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb; }
            .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
            .alert-warning { color: #856404; background-color: #fff3cd; border-color: #ffeeba; }
            .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
            pre { background-color: #f8f9fa; padding: 15px; border-radius: 4px; }
            blockquote { border-left: 4px solid #6c757d; margin-left: 0; padding-left: 20px; font-style: italic; }
        `,
        images_upload_url: 'upload.php',
        automatic_uploads: true,
        image_title: true,
        file_picker_types: 'image',
        link_default_target: '_blank',
        link_title: true,
        link_assume_external_targets: true,
        paste_data_images: true,
        browser_spellcheck: true,
        contextmenu: 'link image table',
        quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote',
        entity_encoding: 'raw',
        forced_root_block: 'p',
        remove_linebreaks: false,
        convert_newlines_to_brs: true,
        remove_trailing_brs: false,
        language: 'fr_FR',
        branding: false,
        promotion: false
    });
</script>

<?php require_once 'includes/footer.php'; ?> 