<?php
require_once __DIR__ . '/../config/database.php';

// S'assurer que la variable $shop_pdo est disponible
global $shop_pdo;

// Vérifier si la connexion à la base de données est établie
if (!isset($shop_pdo) || !($shop_pdo instanceof PDO)) {
    header('Content-Type: application/json');
    echo json_encode([
        'results' => [],
        'pagination' => ['more' => false],
        'error' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'results' => [],
        'pagination' => ['more' => false]
    ]);
    exit;
}

// Récupérer les paramètres de recherche
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Debug - Vérifier la connexion à la base de données
    error_log("Recherche produits - Terme: " . $search);
    
    // Version simplifiée - Récupérer tous les produits si $search est vide
    if (empty($search)) {
        $stmt = $shop_pdo->prepare("
            SELECT 
                id,
                nom as text,
                reference as code,
                prix_vente as price,
                quantite as quantity
            FROM produits
            WHERE quantite > 0
            ORDER BY nom ASC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $count_stmt = $shop_pdo->prepare("SELECT COUNT(*) as total FROM produits WHERE quantite > 0");
        $count_stmt->execute();
    } else {
        // Requête pour compter le nombre total de résultats
        $count_stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as total
            FROM produits
            WHERE (nom LIKE :search OR reference LIKE :search)
            AND quantite > 0
        ");
        $search_param = "%{$search}%";
        $count_stmt->execute(['search' => $search_param]);
        
        // Requête pour récupérer les produits
        $stmt = $shop_pdo->prepare("
            SELECT 
                id,
                nom as text,
                reference as code,
                prix_vente as price,
                quantite as quantity
            FROM produits
            WHERE (nom LIKE :search OR reference LIKE :search)
            AND quantite > 0
            ORDER BY nom ASC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':search', $search_param, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    error_log("Recherche produits - Nombre de résultats: " . $total);

    $results = [];
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Formater le prix pour l'affichage
        $product['price'] = number_format($product['price'], 2, ',', ' ');
        
        // Formater le texte affiché
        $product['text'] = $product['text'] . ' (' . $product['code'] . ')';
        
        $results[] = $product;
    }

    // Préparer la réponse au format attendu par Select2
    $response = [
        'results' => $results,
        'pagination' => [
            'more' => ($total > ($page * $per_page))
        ]
    ];

} catch (PDOException $e) {
    error_log("Erreur lors de la recherche de produits: " . $e->getMessage());
    $response = [
        'results' => [],
        'pagination' => ['more' => false],
        'error' => "Une erreur est survenue lors de la recherche: " . $e->getMessage()
    ];
}

// Envoyer la réponse
header('Content-Type: application/json');
echo json_encode($response); 