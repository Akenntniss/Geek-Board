<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

// Vérifier si l'ID de l'article est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?page=knowledge_base");
    exit;
}

$article_id = intval($_GET['id']);

// Connexion à la base de données
require_once 'includes/db.php';

// Mettre à jour le compteur de vues
$stmt = $db->prepare("UPDATE kb_articles SET views = views + 1 WHERE id = ?");
$stmt->execute([$article_id]);

// Récupérer l'article
$stmt = $db->prepare("SELECT a.*, c.name as category_name, c.icon as category_icon 
                      FROM kb_articles a 
                      LEFT JOIN kb_categories c ON a.category_id = c.id 
                      WHERE a.id = ?");
$stmt->execute([$article_id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

// Si l'article n'existe pas, rediriger vers la liste
if (!$article) {
    header("Location: index.php?page=knowledge_base");
    exit;
}

// Récupérer les articles liés de la même catégorie
$stmt = $db->prepare("SELECT id, title, updated_at 
                      FROM kb_articles 
                      WHERE category_id = ? AND id != ? 
                      ORDER BY updated_at DESC 
                      LIMIT 5");
$stmt->execute([$article['category_id'], $article_id]);
$related_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les tags de l'article
$stmt = $db->prepare("SELECT t.id, t.name 
                      FROM kb_tags t 
                      JOIN kb_article_tags at ON t.id = at.tag_id 
                      WHERE at.article_id = ?");
$stmt->execute([$article_id]);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <div class="row">
        <!-- Contenu principal de l'article -->
        <div class="col-lg-9">
            <div class="kb-article-page">
                <!-- Fil d'Ariane -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="index.php?page=knowledge_base">Base de connaissances</a></li>
                        <li class="breadcrumb-item"><a href="index.php?page=knowledge_base&category_id=<?php echo $article['category_id']; ?>"><?php echo htmlspecialchars($article['category_name']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($article['title']); ?></li>
                    </ol>
                </nav>

                <!-- Carte principale de l'article -->
                <div class="card kb-article-card">
                    <div class="card-header">
                        <div class="article-meta d-flex justify-content-between align-items-center">
                            <div class="category-tag">
                                <span class="badge bg-light text-dark">
                                    <i class="<?php echo !empty($article['category_icon']) ? $article['category_icon'] : 'fas fa-folder'; ?> me-1"></i>
                                    <?php echo htmlspecialchars($article['category_name']); ?>
                                </span>
                            </div>
                            <div class="article-stats">
                                <span class="text-muted">
                                    <i class="fas fa-eye me-1"></i> <?php echo $article['views']; ?> vues
                                </span>
                                <span class="text-muted ms-3">
                                    <i class="fas fa-clock me-1"></i> Mis à jour le <?php echo date('d/m/Y', strtotime($article['updated_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <h1 class="article-title mt-3"><?php echo htmlspecialchars($article['title']); ?></h1>
                    </div>
                    <div class="card-body kb-article-content">
                        <?php echo $article['content']; ?>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="article-tags">
                                <?php if (!empty($tags)): ?>
                                    <?php foreach ($tags as $tag): ?>
                                        <a href="index.php?page=knowledge_base&tag=<?php echo $tag['id']; ?>" class="badge bg-secondary text-decoration-none me-1">
                                            <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($tag['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="article-actions mt-2 mt-sm-0">
                                <a href="index.php?page=kb_print&id=<?php echo $article_id; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <i class="fas fa-print me-1"></i> Imprimer
                                </a>
                                <?php if ($_SESSION['is_admin']): ?>
                                <a href="index.php?page=kb_edit_article&id=<?php echo $article_id; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section de feedback -->
                <div class="card mt-4 kb-feedback">
                    <div class="card-body text-center">
                        <h5 class="card-title">Cet article vous a-t-il été utile ?</h5>
                        <div class="btn-group mt-3" role="group" aria-label="Évaluation de l'article">
                            <button type="button" class="btn btn-outline-success btn-feedback" data-value="yes">
                                <i class="fas fa-thumbs-up me-2"></i>Oui
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-feedback" data-value="no">
                                <i class="fas fa-thumbs-down me-2"></i>Non
                            </button>
                        </div>
                        <div id="feedback-message" class="mt-3 d-none alert alert-success">
                            Merci pour votre retour !
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar avec articles liés et navigation -->
        <div class="col-lg-3 mt-4 mt-lg-0">
            <!-- Articles liés -->
            <div class="card kb-related-articles mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Articles liés</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (!empty($related_articles)): ?>
                            <?php foreach ($related_articles as $related): ?>
                                <a href="index.php?page=kb_article&id=<?php echo $related['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <p class="mb-1"><?php echo htmlspecialchars($related['title']); ?></p>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i> <?php echo date('d/m/Y', strtotime($related['updated_at'])); ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item">
                                <p class="mb-0 text-muted">Aucun article lié trouvé</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card kb-quick-actions">
                <div class="card-header">
                    <h5 class="mb-0">Actions rapides</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="index.php?page=knowledge_base" class="list-group-item list-group-item-action">
                            <i class="fas fa-home me-2"></i> Accueil base de connaissances
                        </a>
                        <a href="index.php?page=knowledge_base&category_id=<?php echo $article['category_id']; ?>" class="list-group-item list-group-item-action">
                            <i class="fas fa-folder me-2"></i> Parcourir la catégorie
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="window.print(); return false;">
                            <i class="fas fa-print me-2"></i> Imprimer cet article
                        </a>
                        <?php if ($_SESSION['is_admin']): ?>
                        <a href="index.php?page=kb_add_article" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle me-2"></i> Ajouter un article
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour la page d'article */
.content-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px 15px;
}

.kb-article-card {
    border-radius: 0.75rem;
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 2rem;
}

.kb-article-card .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.article-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0;
}

.kb-article-content {
    font-size: 1.05rem;
    line-height: 1.7;
    color: #343a40;
    padding: 2rem;
}

.kb-article-content h2 {
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    font-size: 1.5rem;
    font-weight: 600;
    color: #212529;
}

.kb-article-content h3 {
    margin-top: 1.25rem;
    margin-bottom: 0.75rem;
    font-size: 1.3rem;
    font-weight: 600;
    color: #343a40;
}

.kb-article-content p {
    margin-bottom: 1.25rem;
}

.kb-article-content ul, 
.kb-article-content ol {
    margin-bottom: 1.25rem;
    padding-left: 1.5rem;
}

.kb-article-content li {
    margin-bottom: 0.5rem;
}

.kb-article-content img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1rem 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.kb-article-content code {
    background-color: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    color: #e83e8c;
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

.kb-article-content pre {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-bottom: 1.25rem;
    border: 1px solid rgba(0,0,0,0.1);
}

.kb-article-content blockquote {
    padding: 1rem;
    margin-bottom: 1.25rem;
    border-left: 4px solid #4361ee;
    background-color: rgba(67, 97, 238, 0.05);
    color: #495057;
}

.category-tag .badge {
    font-weight: 500;
    padding: 0.5rem 0.75rem;
}

.article-tags .badge {
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.5rem;
}

.kb-related-articles .list-group-item {
    padding: 0.75rem 1rem;
}

.kb-related-articles .list-group-item p {
    font-size: 0.9rem;
    line-height: 1.4;
    margin-bottom: 0.25rem;
}

.kb-feedback {
    transition: all 0.3s ease;
}

.btn-feedback {
    transition: all 0.3s ease;
    padding: 0.5rem 1.5rem;
}

.btn-feedback:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .kb-article-card .card-header {
        padding: 1rem;
    }
    
    .article-title {
        font-size: 1.5rem;
    }
    
    .kb-article-content {
        padding: 1.5rem;
        font-size: 1rem;
    }
    
    .article-stats {
        display: flex;
        flex-direction: column;
    }
    
    .article-stats span:last-child {
        margin-left: 0 !important;
        margin-top: 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du feedback
    const feedbackButtons = document.querySelectorAll('.btn-feedback');
    const feedbackMessage = document.getElementById('feedback-message');
    
    feedbackButtons.forEach(button => {
        button.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            const articleId = <?php echo $article_id; ?>;
            
            // Ici, vous pouvez ajouter le code pour envoyer le feedback via AJAX
            // Simulation pour l'exemple
            setTimeout(() => {
                feedbackMessage.classList.remove('d-none');
                feedbackButtons.forEach(btn => {
                    btn.disabled = true;
                    if (btn === this) {
                        btn.classList.remove('btn-outline-' + (value === 'yes' ? 'success' : 'danger'));
                        btn.classList.add('btn-' + (value === 'yes' ? 'success' : 'danger'));
                    }
                });
                
                // Masquer le message après quelques secondes
                setTimeout(() => {
                    feedbackMessage.classList.add('d-none');
                }, 3000);
            }, 500);
        });
    });
});
</script> 