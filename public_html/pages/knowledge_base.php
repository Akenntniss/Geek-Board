<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

// Récupérer les catégories
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT * FROM kb_categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recherche d'articles
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Construction de la requête SQL
$sql = "SELECT a.*, c.name as category_name 
        FROM kb_articles a 
        LEFT JOIN kb_categories c ON a.category_id = c.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id > 0) {
    $sql .= " AND a.category_id = ?";
    $params[] = $category_id;
}

$sql .= " ORDER BY a.updated_at DESC";

$stmt = $shop_pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les articles les plus consultés
$stmt = $shop_pdo->prepare("SELECT * FROM kb_articles ORDER BY views DESC LIMIT 5");
$stmt->execute();
$most_viewed = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les articles récemment mis à jour
$stmt = $shop_pdo->prepare("SELECT * FROM kb_articles ORDER BY updated_at DESC LIMIT 5");
$stmt->execute();
$recent_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
    <!-- En-tête de la base de connaissances -->
    <div class="kb-header mb-4">
        <div class="search-container">
            <h1>Base de Connaissances</h1>
            <p class="text-muted">Trouvez rapidement des solutions, des guides et des réponses à vos questions</p>
            
            <form method="GET" action="index.php" class="mt-4">
                <input type="hidden" name="page" value="knowledge_base">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Rechercher des articles, guides, solutions..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Rechercher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar avec catégories -->
        <div class="col-lg-3 mb-4">
            <div class="card kb-categories">
                <div class="card-header">
                    <h5 class="mb-0">Catégories</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="index.php?page=knowledge_base" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $category_id == 0 ? 'active' : ''; ?>">
                            Toutes les catégories
                            <span class="badge bg-primary rounded-pill"><?php echo count($articles); ?></span>
                        </a>
                        <?php foreach ($categories as $category): ?>
                        <a href="index.php?page=knowledge_base&category_id=<?php echo $category['id']; ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                            <div>
                                <i class="<?php echo !empty($category['icon']) ? $category['icon'] : 'fas fa-folder'; ?> me-2"></i>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?php 
                                $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM kb_articles WHERE category_id = ?");
                                $stmt->execute([$category['id']]);
                                echo $stmt->fetchColumn();
                            ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Articles populaires -->
            <div class="card mt-4 kb-popular">
                <div class="card-header">
                    <h5 class="mb-0">Articles populaires</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($most_viewed as $article): ?>
                        <a href="index.php?page=kb_article&id=<?php echo $article['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($article['title']); ?></h6>
                                <small><i class="fas fa-eye"></i> <?php echo $article['views']; ?></small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des articles -->
        <div class="col-lg-9">
            <?php if (!empty($search) || $category_id > 0): ?>
                <div class="alert alert-info mb-4">
                    <h5 class="alert-heading"><i class="fas fa-filter me-2"></i>Résultats de la recherche</h5>
                    <?php if (!empty($search)): ?>
                        <p class="mb-0">Recherche pour: <strong><?php echo htmlspecialchars($search); ?></strong></p>
                    <?php endif; ?>
                    <?php if ($category_id > 0): 
                        $cat_name = "";
                        foreach ($categories as $cat) {
                            if ($cat['id'] == $category_id) {
                                $cat_name = $cat['name'];
                                break;
                            }
                        }
                    ?>
                        <p class="mb-0">Catégorie: <strong><?php echo htmlspecialchars($cat_name); ?></strong></p>
                    <?php endif; ?>
                    <div class="mt-2">
                        <a href="index.php?page=knowledge_base" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Effacer les filtres
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Affichage des articles -->
            <?php if (count($articles) > 0): ?>
                <div class="row">
                    <?php foreach ($articles as $article): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card kb-article h-100">
                            <div class="card-body">
                                <div class="kb-category">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-folder me-1"></i>
                                        <?php echo htmlspecialchars($article['category_name']); ?>
                                    </span>
                                </div>
                                <h5 class="card-title mt-2">
                                    <a href="index.php?page=kb_article&id=<?php echo $article['id']; ?>" class="kb-title">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </h5>
                                <p class="card-text">
                                    <?php 
                                        $content = strip_tags($article['content']);
                                        echo substr($content, 0, 120) . (strlen($content) > 120 ? '...' : ''); 
                                    ?>
                                </p>
                                <div class="kb-meta">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i> Mis à jour: <?php echo date('d/m/Y', strtotime($article['updated_at'])); ?>
                                    </small>
                                    <small class="text-muted ms-3">
                                        <i class="fas fa-eye me-1"></i> <?php echo $article['views']; ?> vues
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="index.php?page=kb_article&id=<?php echo $article['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    Lire l'article <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Aucun résultat trouvé</h5>
                    <p class="mb-0">Aucun article ne correspond à votre recherche. Essayez avec d'autres termes ou consultez toutes les catégories.</p>
                </div>
            <?php endif; ?>

            <?php if (empty($search) && $category_id == 0): ?>
                <!-- Articles récents -->
                <div class="recent-articles mt-4">
                    <h3 class="mb-3">Articles récemment mis à jour</h3>
                    <div class="row">
                        <?php foreach ($recent_articles as $article): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card kb-article h-100">
                                <div class="card-body">
                                    <div class="kb-category">
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-folder me-1"></i>
                                            <?php 
                                                $cat_name = "Non classé";
                                                foreach ($categories as $cat) {
                                                    if ($cat['id'] == $article['category_id']) {
                                                        $cat_name = $cat['name'];
                                                        break;
                                                    }
                                                }
                                                echo htmlspecialchars($cat_name); 
                                            ?>
                                        </span>
                                    </div>
                                    <h5 class="card-title mt-2">
                                        <a href="index.php?page=kb_article&id=<?php echo $article['id']; ?>" class="kb-title">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text">
                                        <?php 
                                            $content = strip_tags($article['content']);
                                            echo substr($content, 0, 120) . (strlen($content) > 120 ? '...' : ''); 
                                        ?>
                                    </p>
                                    <div class="kb-meta">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i> Mis à jour: <?php echo date('d/m/Y', strtotime($article['updated_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="index.php?page=kb_article&id=<?php echo $article['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        Lire l'article <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bouton Ajouter un article (pour les admins) -->
<div class="position-fixed bottom-0 end-0 m-4">
    <a href="index.php?page=kb_add_article" class="btn btn-primary rounded-circle p-3" data-bs-toggle="tooltip" data-bs-placement="left" title="Ajouter un article">
        <i class="fas fa-plus"></i>
    </a>
</div>

<style>
/* Styles pour la base de connaissances */
.content-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px 15px;
}

.kb-header {
    background: linear-gradient(135deg, #4361ee, #3f37c9);
    border-radius: 1rem;
    padding: 2.5rem;
    color: white;
    text-align: center;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(67, 97, 238, 0.15);
}

.kb-header h1 {
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.kb-header .input-group {
    max-width: 700px;
    margin: 0 auto;
}

.kb-header .form-control {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem 0 0 0.5rem;
    border: none;
    font-size: 1.1rem;
}

.kb-header .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0 0.5rem 0.5rem 0;
    font-size: 1.1rem;
}

.kb-categories .list-group-item {
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.kb-categories .list-group-item:hover {
    background-color: #f8f9fa;
}

.kb-categories .list-group-item.active {
    background-color: #4361ee;
    border-color: #4361ee;
}

.kb-categories .list-group-item i {
    width: 20px;
    text-align: center;
}

.kb-article {
    border-radius: 0.75rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border: 1px solid rgba(0,0,0,0.1);
}

.kb-article:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.kb-category {
    margin-bottom: 0.5rem;
}

.kb-category .badge {
    font-weight: 500;
    padding: 0.5rem 0.75rem;
}

.kb-title {
    color: #212529;
    text-decoration: none;
    transition: color 0.2s ease;
}

.kb-title:hover {
    color: #4361ee;
}

.kb-meta {
    margin-top: 1rem;
    color: #6c757d;
}

/* Articles populaires */
.kb-popular .list-group-item {
    padding: 0.75rem 1rem;
}

.kb-popular .list-group-item h6 {
    margin-bottom: 0;
    font-weight: 500;
}

.kb-popular .list-group-item:hover {
    background-color: #f8f9fa;
}

@media (max-width: 768px) {
    .kb-header {
        padding: 1.5rem;
    }
    
    .kb-header h1 {
        font-size: 1.75rem;
    }
    
    .kb-header .form-control,
    .kb-header .btn {
        padding: 0.5rem 1rem;
        font-size: 1rem;
    }
    
    .recent-articles h3 {
        font-size: 1.5rem;
    }
}
</style>

<script>
// Activer les tooltips Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script> 