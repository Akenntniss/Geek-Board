<?php
require_once __DIR__ . '/../config/database.php';
// Vérifier si l'utilisateur est connecté
$shop_pdo = getShopDBConnection();
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

// Récupérer l'article
$stmt = $shop_pdo->prepare("SELECT a.*, c.name as category_name 
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

// Format de date pour l'impression
$date_updated = date('d/m/Y à H:i', strtotime($article['updated_at']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - Impression</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .print-header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .print-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .print-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .print-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .print-category {
            font-size: 14px;
            background-color: #f5f5f5;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .print-content {
            margin-top: 20px;
        }
        
        .print-content img {
            max-width: 100%;
            height: auto;
        }
        
        .print-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print();" style="padding: 8px 16px; cursor: pointer;">
            Imprimer <i class="fas fa-print"></i>
        </button>
    </div>
    
    <div class="print-header">
        <div class="print-logo">
            <h2>MD Geek - Base de Connaissances</h2>
        </div>
        <div class="print-title"><?php echo htmlspecialchars($article['title']); ?></div>
        <div class="print-meta">
            Dernière mise à jour: <?php echo $date_updated; ?> | 
            Catégorie: <span class="print-category"><?php echo htmlspecialchars($article['category_name']); ?></span>
        </div>
    </div>
    
    <div class="print-content">
        <?php echo $article['content']; ?>
    </div>
    
    <div class="print-footer">
        Document imprimé depuis la Base de Connaissances MD Geek le <?php echo date('d/m/Y à H:i'); ?>.
        <br>
        Ce document est réservé à un usage interne.
    </div>
    
    <script>
        // Auto-imprimer après chargement complet
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
<?php
// Pour éviter d'inclure le footer et le header du site
exit();
?> 