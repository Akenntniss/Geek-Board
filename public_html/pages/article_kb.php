<?php
// Page d'affichage d'un article de la base de connaissances
$page_title = "Article Base de Connaissances";
require_once 'includes/header.php';

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
            SELECT a.*, c.name as category_name, c.icon as category_icon 
            FROM kb_articles a
            LEFT JOIN kb_categories c ON a.category_id = c.id
            WHERE a.id = ?
        ";
        $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare($query);
        $stmt->execute([$id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Incrémenter le compteur de vues si l'article existe
        if ($article) {
            $update = "UPDATE kb_articles SET views = views + 1 WHERE id = ?";
            $stmt = $shop_pdo->prepare($update);
            $stmt->execute([$id]);
        }
        
        return $article;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de l'article KB: " . $e->getMessage());
        return false;
    }
}

// Récupération des tags d'un article
function get_article_tags($article_id) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "
            SELECT t.* 
            FROM kb_tags t
            JOIN kb_article_tags at ON t.id = at.tag_id
            WHERE at.article_id = ?
            ORDER BY t.name ASC
        ";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tags: " . $e->getMessage());
        return [];
    }
}

// Vérifier si l'utilisateur a déjà évalué cet article
function has_user_rated_article($article_id, $user_id) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "SELECT id FROM kb_article_ratings WHERE article_id = ? AND user_id = ?";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification de l'évaluation: " . $e->getMessage());
        return false;
    }
}

// Récupération des statistiques d'évaluation
function get_rating_stats($article_id) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "
            SELECT COUNT(*) as total_ratings,
                   SUM(CASE WHEN is_helpful = 1 THEN 1 ELSE 0 END) as helpful_count
            FROM kb_article_ratings
            WHERE article_id = ?
        ";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculer le pourcentage d'utilité
        if ($stats['total_ratings'] > 0) {
            $stats['helpful_percent'] = round(($stats['helpful_count'] / $stats['total_ratings']) * 100);
        } else {
            $stats['helpful_percent'] = 0;
        }
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques d'évaluation: " . $e->getMessage());
        return ['total_ratings' => 0, 'helpful_count' => 0, 'helpful_percent' => 0];
    }
}

// Récupération des articles associés (même catégorie)
function get_related_articles($article_id, $category_id, $limit = 5) {
    $shop_pdo = getShopDBConnection();
    try {
        $query = "
            SELECT id, title, views
            FROM kb_articles 
            WHERE id != ? AND category_id = ?
            ORDER BY views DESC
            LIMIT ?
        ";
        $stmt = $shop_pdo->prepare($query);
        $stmt->execute([$article_id, $category_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des articles associés: " . $e->getMessage());
        return [];
    }
}

// Traitement de l'évaluation
if (isset($_POST['action']) && $_POST['action'] === 'rate_article' && isset($_SESSION['user_id'])) {
    $is_helpful = isset($_POST['is_helpful']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    
    try {
        // Vérifier si l'utilisateur a déjà évalué cet article
        if (!has_user_rated_article($article_id, $user_id)) {
            $query = "INSERT INTO kb_article_ratings (article_id, user_id, is_helpful, rated_at) VALUES (?, ?, ?, NOW())";
            $stmt = $shop_pdo->prepare($query);
            $stmt->execute([$article_id, $user_id, $is_helpful]);
            
            set_message("Merci pour votre évaluation !", "success");
        } else {
            set_message("Vous avez déjà évalué cet article.", "warning");
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de l'enregistrement de l'évaluation: " . $e->getMessage());
        set_message("Une erreur est survenue lors de l'enregistrement de votre évaluation.", "danger");
    }
    
    // Rediriger pour éviter le rechargement du formulaire
    redirect('article_kb', ['id' => $article_id]);
}

// Récupérer l'article
$article = get_kb_article($article_id);

// Si l'article n'existe pas, rediriger vers la liste des articles
if (!$article) {
    set_message("L'article demandé n'existe pas.", "danger");
    redirect('base_connaissances');
}

// Récupérer les tags de l'article
$tags = get_article_tags($article_id);

// Récupérer les statistiques d'évaluation
$rating_stats = get_rating_stats($article_id);

// Récupérer les articles associés
$related_articles = get_related_articles($article_id, $article['category_id']);

// Déterminer si l'utilisateur a déjà évalué cet article
$user_has_rated = isset($_SESSION['user_id']) ? has_user_rated_article($article_id, $_SESSION['user_id']) : false;

// Mettre à jour le titre de la page avec le titre de l'article
$page_title = $article['title'] . " | Base de Connaissances";
?>

<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0 rounded-lg">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-book me-2"></i> Base de Connaissances
                    </h5>
                    <a href="index.php?page=base_connaissances" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                    </a>
                </div>
                <div class="card-body px-4 py-4">
                    <div class="row">
                        <!-- Contenu principal -->
                        <div class="col-md-9">
                            <!-- Fil d'Ariane -->
                            <nav aria-label="breadcrumb" class="mb-4 d-flex align-items-center text-muted small">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="index.php?page=accueil" class="text-decoration-none"><i class="fas fa-home"></i></a></li>
                                    <li class="breadcrumb-item"><a href="index.php?page=base_connaissances" class="text-decoration-none">Base de Connaissances</a></li>
                                    <li class="breadcrumb-item"><a href="index.php?page=base_connaissances&categorie=<?= $article['category_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($article['category_name']) ?></a></li>
                                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($article['title']) ?></li>
                                </ol>
                            </nav>
                            
                            <!-- Affichage du message -->
                            <?= display_message() ?>
                            
                            <!-- Titre et informations de l'article -->
                            <div class="mb-4">
                                <h1 class="display-6 fw-bold mb-3"><?= htmlspecialchars($article['title']) ?></h1>
                                
                                <div class="d-flex flex-wrap align-items-center text-muted mb-3 small">
                                    <span class="me-3 mb-2 d-flex align-items-center">
                                        <span class="badge rounded-pill bg-primary me-2">
                                            <i class="<?= htmlspecialchars($article['category_icon']) ?>"></i>
                                        </span>
                                        <?= htmlspecialchars($article['category_name']) ?>
                                    </span>
                                    <span class="me-3 mb-2 d-flex align-items-center">
                                        <span class="badge rounded-pill bg-secondary me-2">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                        <?= $article['views'] ?> vues
                                    </span>
                                    <span class="mb-2 d-flex align-items-center">
                                        <span class="badge rounded-pill bg-info me-2">
                                            <i class="fas fa-calendar-alt"></i>
                                        </span>
                                        Mis à jour le <?= date('d/m/Y', strtotime($article['updated_at'])) ?>
                                    </span>
                                    
                                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')): ?>
                                    <a href="index.php?page=modifier_article_kb&id=<?= $article_id ?>" class="btn btn-outline-primary btn-sm ms-auto">
                                        <i class="fas fa-edit me-1"></i> Modifier
                                    </a>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($tags)): ?>
                                <div class="mb-4">
                                    <?php foreach ($tags as $tag): ?>
                                    <a href="index.php?page=base_connaissances&recherche=<?= urlencode($tag['name']) ?>" 
                                       class="badge rounded-pill bg-light text-dark text-decoration-none me-1 p-2 mb-2 d-inline-block">
                                        <i class="fas fa-tag me-1 text-primary"></i>
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Contenu de l'article -->
                            <div class="card shadow-sm border-0 rounded-lg mb-4 overflow-hidden">
                                <div class="card-body content-article p-4">
                                    <?= nl2br(html_entity_decode($article['content'])) ?>
                                </div>
                            </div>
                            
                            <!-- Évaluation de l'article -->
                            <div class="card shadow-sm border-0 rounded-lg mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0 fw-bold">Cet article vous a-t-il été utile ?</h5>
                                </div>
                                <div class="card-body p-4">
                                    <?php if (!$user_has_rated && isset($_SESSION['user_id'])): ?>
                                    <form action="index.php?page=article_kb&id=<?= $article_id ?>" method="POST">
                                        <input type="hidden" name="action" value="rate_article">
                                        <div class="d-flex flex-wrap justify-content-center gap-3">
                                            <button type="submit" name="is_helpful" value="1" class="btn btn-success btn-lg px-4 py-2">
                                                <i class="fas fa-thumbs-up me-2"></i> Oui, cet article m'a aidé
                                            </button>
                                            <button type="submit" class="btn btn-danger btn-lg px-4 py-2">
                                                <i class="fas fa-thumbs-down me-2"></i> Non, je n'ai pas trouvé ce que je cherchais
                                            </button>
                                        </div>
                                    </form>
                                    <?php elseif ($user_has_rated): ?>
                                    <div class="alert alert-info mb-0 d-flex align-items-center">
                                        <div class="me-3 fs-3">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div>
                                            <strong>Merci pour votre évaluation !</strong><br>
                                            <span class="text-muted">Votre retour nous aide à améliorer notre base de connaissances.</span>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-warning mb-0 d-flex align-items-center">
                                        <div class="me-3 fs-3">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div>
                                            <strong>Connectez-vous pour évaluer cet article</strong><br>
                                            <span class="text-muted">Votre avis nous est précieux pour améliorer notre base de connaissances.</span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($rating_stats['total_ratings'] > 0): ?>
                                    <div class="text-center mt-4">
                                        <div class="progress mb-2" style="height: 10px;">
                                            <div class="progress-bar bg-<?= $rating_stats['helpful_percent'] >= 70 ? 'success' : ($rating_stats['helpful_percent'] >= 40 ? 'warning' : 'danger') ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= $rating_stats['helpful_percent'] ?>%;" 
                                                 aria-valuenow="<?= $rating_stats['helpful_percent'] ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                        <span class="badge bg-<?= $rating_stats['helpful_percent'] >= 70 ? 'success' : ($rating_stats['helpful_percent'] >= 40 ? 'warning' : 'danger') ?> p-2">
                                            <i class="fas fa-thumbs-up me-1"></i>
                                            <?= $rating_stats['helpful_percent'] ?>% des utilisateurs (<?= $rating_stats['helpful_count'] ?>/<?= $rating_stats['total_ratings'] ?>) ont trouvé cet article utile
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sidebar -->
                        <div class="col-md-3">
                            <!-- Articles associés -->
                            <div class="card shadow-sm border-0 rounded-lg mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0 fw-bold">Articles similaires</h5>
                                </div>
                                <?php if (empty($related_articles)): ?>
                                <div class="card-body p-4">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-search fa-3x mb-3"></i>
                                        <p>Aucun article similaire trouvé.</p>
                                    </div>
                                </div>
                                <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($related_articles as $related): ?>
                                    <li class="list-group-item border-0 px-4 py-3">
                                        <a href="index.php?page=article_kb&id=<?= $related['id'] ?>" class="text-decoration-none d-block">
                                            <div class="fw-bold text-primary mb-1"><?= htmlspecialchars($related['title']) ?></div>
                                            <div class="small text-muted">
                                                <i class="fas fa-eye me-1"></i> <?= $related['views'] ?> vues
                                            </div>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                                <div class="card-footer bg-white p-3">
                                    <a href="index.php?page=base_connaissances&categorie=<?= $article['category_id'] ?>" class="btn btn-outline-primary btn-sm d-block w-100">
                                        <i class="fas fa-list me-1"></i> Voir tous les articles de cette catégorie
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Retour à la liste -->
                            <div class="d-grid gap-2">
                                <a href="index.php?page=base_connaissances" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-1"></i> Retour aux articles
                                </a>
                                
                                <!-- Bouton d'impression -->
                                <button onclick="window.print()" class="btn btn-outline-secondary">
                                    <i class="fas fa-print me-1"></i> Imprimer cet article
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles spécifiques pour le contenu de l'article -->
<style>
/* Styles généraux pour l'article */
.card {
    transition: all 0.2s ease;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #4361ee 0%, #3a56e8 100%);
}

/* Styles pour le contenu de l'article */
.content-article {
    font-size: 1.05rem;
    line-height: 1.7;
    color: #333;
}

.content-article h1, .content-article h2, .content-article h3,
.content-article h4, .content-article h5, .content-article h6 {
    margin-top: 2rem;
    margin-bottom: 1.2rem;
    font-weight: 600;
    color: #333;
}

.content-article h1 { font-size: 2rem; }
.content-article h2 { font-size: 1.8rem; }
.content-article h3 { font-size: 1.5rem; }
.content-article h4 { font-size: 1.3rem; }
.content-article h5 { font-size: 1.1rem; }

.content-article p {
    margin-bottom: 1.5rem;
    white-space: pre-wrap;
}

.content-article br {
    display: block;
    margin-bottom: 0.5rem;
}

.content-article img {
    max-width: 100%;
    height: auto;
    margin: 1.5rem 0;
    border-radius: 0.5rem;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.content-article pre {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin: 1.5rem 0;
    overflow-x: auto;
    white-space: pre-wrap;
    box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05);
}

.content-article code {
    background-color: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-family: 'Courier New', monospace;
    color: #e83e8c;
}

.content-article table {
    width: 100%;
    margin-bottom: 1.5rem;
    border-collapse: collapse;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.content-article table th,
.content-article table td {
    padding: 0.75rem 1rem;
    border: 1px solid #e9ecef;
}

.content-article table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
}

.content-article table tr:hover {
    background-color: #f8f9fa;
}

.content-article ul, .content-article ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
}

.content-article li {
    margin-bottom: 0.75rem;
}

.content-article blockquote {
    border-left: 4px solid #4361ee;
    padding: 1rem 1.5rem;
    margin: 1.5rem 0;
    background-color: #f8f9fa;
    border-radius: 0 0.5rem 0.5rem 0;
    font-style: italic;
    color: #495057;
}

.content-article a {
    color: #4361ee;
    text-decoration: none;
    border-bottom: 1px dashed #4361ee;
    transition: all 0.2s ease;
}

.content-article a:hover {
    color: #3a56e8;
    border-bottom: 1px solid #3a56e8;
}

/* Mise en forme pour l'impression */
@media print {
    .container-fluid {
        width: 100%;
        max-width: none;
    }
    
    .col-md-9 {
        width: 100%;
        max-width: 100%;
        flex: 0 0 100%;
    }
    
    .col-md-3, .card-header, .breadcrumb, .btn, 
    .alert, form, .card-footer, nav {
        display: none !important;
    }
    
    .card, .card-body {
        border: none !important;
        box-shadow: none !important;
    }
    
    .content-article {
        font-size: 12pt;
        line-height: 1.5;
    }
    
    .content-article img {
        max-width: 500px;
        margin: 10px auto;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 