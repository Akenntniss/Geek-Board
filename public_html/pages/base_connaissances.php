<?php
// Page principale de la base de connaissances
$page_title = "Base de Connaissances";
require_once 'includes/header.php';

// Récupération de la catégorie sélectionnée (si présente)
$categorie_id = isset($_GET['categorie']) ? intval($_GET['categorie']) : 0;

// Récupération du terme de recherche (si présent)
$recherche = isset($_GET['recherche']) ? cleanInput($_GET['recherche']) : '';

// Récupération des catégories
function get_kb_categories() {
    global $pdo;
    try {
        $query = "SELECT * FROM kb_categories ORDER BY name ASC";
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des catégories KB: " . $e->getMessage());
        return [];
    }
}

// Récupération des articles
function get_kb_articles($categorie_id = 0, $recherche = '', $limit = 50) {
    global $pdo;
    try {
        $params = [];
        $where_clauses = [];
        
        // Si une catégorie est spécifiée
        if ($categorie_id > 0) {
            $where_clauses[] = "a.category_id = ?";
            $params[] = $categorie_id;
        }
        
        // Si un terme de recherche est spécifié
        if (!empty($recherche)) {
            $where_clauses[] = "(a.title LIKE ? OR a.content LIKE ?)";
            $params[] = "%$recherche%";
            $params[] = "%$recherche%";
        }
        
        // Construction de la clause WHERE
        $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        
        $query = "
            SELECT a.*, c.name as category_name, c.icon as category_icon,
                   COUNT(r.id) as rating_count,
                   SUM(CASE WHEN r.is_helpful = 1 THEN 1 ELSE 0 END) as helpful_count
            FROM kb_articles a
            LEFT JOIN kb_categories c ON a.category_id = c.id
            LEFT JOIN kb_article_ratings r ON a.id = r.article_id
            $where_sql
            GROUP BY a.id
            ORDER BY a.title ASC
            LIMIT ?
        ";
        
        $params[] = $limit;
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des articles KB: " . $e->getMessage());
        return [];
    }
}

// Récupération des tags d'un article
function get_article_tags($article_id) {
    global $pdo;
    try {
        $query = "
            SELECT t.* 
            FROM kb_tags t
            JOIN kb_article_tags at ON t.id = at.tag_id
            WHERE at.article_id = ?
            ORDER BY t.name ASC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$article_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des tags: " . $e->getMessage());
        return [];
    }
}

// Récupération des catégories et des articles
$categories = get_kb_categories();
$articles = get_kb_articles($categorie_id, $recherche);
?>

<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-book me-2"></i> Base de Connaissances
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Barre de recherche -->
                    <div class="row mb-4">
                        <div class="col-md-6 offset-md-3">
                            <form action="index.php" method="GET" class="mb-0">
                                <input type="hidden" name="page" value="base_connaissances">
                                <?php if ($categorie_id > 0): ?>
                                <input type="hidden" name="categorie" value="<?= $categorie_id ?>">
                                <?php endif; ?>
                                <div class="input-group">
                                    <input type="text" name="recherche" class="form-control" 
                                           placeholder="Rechercher dans la base de connaissances..." 
                                           value="<?= htmlspecialchars($recherche) ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Rechercher
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Sidebar (Catégories) -->
                        <div class="col-md-3">
                            <div class="card border-light mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Catégories</h5>
                                </div>
                                <div class="list-group list-group-flush">
                                    <a href="index.php?page=base_connaissances<?= !empty($recherche) ? '&recherche='.urlencode($recherche) : '' ?>" 
                                       class="list-group-item list-group-item-action <?= $categorie_id === 0 ? 'active' : '' ?>">
                                        <i class="fas fa-folder me-2"></i> Toutes les catégories
                                    </a>
                                    
                                    <?php foreach ($categories as $categorie): ?>
                                    <a href="index.php?page=base_connaissances&categorie=<?= $categorie['id'] ?><?= !empty($recherche) ? '&recherche='.urlencode($recherche) : '' ?>" 
                                       class="list-group-item list-group-item-action <?= $categorie_id === (int)$categorie['id'] ? 'active' : '' ?>">
                                        <i class="<?= htmlspecialchars($categorie['icon']) ?> me-2"></i> 
                                        <?= htmlspecialchars($categorie['name']) ?>
                                    </a>
                                    <?php endforeach; ?>
                                    
                                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')): ?>
                                    <a href="index.php?page=gestion_kb" class="list-group-item list-group-item-action text-primary">
                                        <i class="fas fa-cog me-2"></i> Gérer les catégories
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')): ?>
                            <div class="d-grid">
                                <a href="index.php?page=ajouter_article_kb" class="btn btn-success">
                                    <i class="fas fa-plus-circle me-2"></i> Nouvel Article
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Liste d'articles -->
                        <div class="col-md-9">
                            <?php if (!empty($recherche)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-search me-2"></i>
                                Résultats de recherche pour : <strong><?= htmlspecialchars($recherche) ?></strong>
                                <a href="index.php?page=base_connaissances<?= $categorie_id > 0 ? '&categorie='.$categorie_id : '' ?>" class="float-end">
                                    <i class="fas fa-times"></i> Effacer la recherche
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (empty($articles)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Aucun article trouvé dans la base de connaissances.
                                <?php if (!empty($recherche)): ?>
                                <div class="mt-2">
                                    Essayez avec d'autres termes de recherche ou
                                    <a href="index.php?page=base_connaissances<?= $categorie_id > 0 ? '&categorie='.$categorie_id : '' ?>">
                                        consultez tous les articles
                                    </a>.
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                                
                            <div class="row">
                                <?php foreach ($articles as $article): 
                                    // Récupérer les tags pour cet article
                                    $tags = get_article_tags($article['id']);
                                    
                                    // Calculer le taux d'utilité si des évaluations existent
                                    $utilite = 0;
                                    if ($article['rating_count'] > 0) {
                                        $utilite = round(($article['helpful_count'] / $article['rating_count']) * 100);
                                    }
                                ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 border-light hover-shadow">
                                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="<?= htmlspecialchars($article['category_icon']) ?> me-2 text-primary"></i>
                                                <span class="text-muted small"><?= htmlspecialchars($article['category_name']) ?></span>
                                            </span>
                                            <?php if ($article['rating_count'] > 0): ?>
                                            <span class="badge bg-<?= $utilite >= 70 ? 'success' : ($utilite >= 40 ? 'warning' : 'danger') ?>" 
                                                  title="<?= $article['helpful_count'] ?> sur <?= $article['rating_count'] ?> utilisateurs ont trouvé cet article utile">
                                                <i class="fas fa-thumbs-up me-1"></i> <?= $utilite ?>%
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="index.php?page=article_kb&id=<?= $article['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($article['title']) ?>
                                                </a>
                                            </h5>
                                            <p class="card-text text-muted">
                                                <?= nl2br(htmlspecialchars(mb_substr(strip_tags($article['content']), 0, 150))) ?>...
                                            </p>
                                            
                                            <?php if (!empty($tags)): ?>
                                            <div class="mt-2">
                                                <?php foreach ($tags as $tag): ?>
                                                <a href="index.php?page=base_connaissances&recherche=<?= urlencode($tag['name']) ?>" 
                                                   class="badge bg-light text-dark text-decoration-none me-1">
                                                    <i class="fas fa-tag me-1 text-secondary"></i>
                                                    <?= htmlspecialchars($tag['name']) ?>
                                                </a>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-white">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-eye me-1"></i> <?= $article['views'] ?> vues
                                                </small>
                                                <small class="text-muted">
                                                    Mis à jour le <?= date('d/m/Y', strtotime($article['updated_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles spécifiques -->
<style>
.hover-shadow:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: box-shadow 0.3s ease-in-out;
}
</style>

<?php require_once 'includes/footer.php'; ?> 