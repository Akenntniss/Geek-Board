<?php
// Démarrer la session au début
session_start();

// Désactiver l'affichage des erreurs pour éviter de casser le JSON
ini_set('display_errors', 0);
error_reporting(0);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    $functions_path = realpath(__DIR__ . '/../includes/functions.php');
    
    if (!file_exists($config_path) || !file_exists($functions_path)) {
        throw new Exception('Fichiers de configuration introuvables.');
    }

    // Inclure les fichiers nécessaires
    require_once $config_path;
    require_once $functions_path;

    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin');
    }

    // Récupérer l'ID de catégorie depuis les paramètres GET
    $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    
    // Récupérer l'ID du magasin depuis les paramètres GET ou la session
    $shop_id = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : ($_SESSION['shop_id'] ?? null);
    
    if ($category_id <= 0) {
        throw new Exception('ID de catégorie invalide');
    }
    
    if (!$shop_id) {
        throw new Exception('ID du magasin non trouvé');
    }

    // Récupérer les statuts de cette catégorie
    $stmt = $shop_pdo->prepare("
        SELECT id, nom, code, est_actif, ordre
        FROM statuts
        WHERE categorie_id = ? AND est_actif = 1
        ORDER BY ordre ASC
    ");
    $stmt->execute([$category_id]);
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($statuts)) {
        throw new Exception('Aucun statut trouvé pour cette catégorie');
    }
    
    // Récupérer les informations de la catégorie
    $stmt = $shop_pdo->prepare("
        SELECT nom, code, couleur
        FROM statut_categories
        WHERE id = ?
    ");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        throw new Exception('Catégorie introuvable');
    }
    
    // Renvoyer les résultats
    echo json_encode([
        'success' => true,
        'category' => $category,
        'statuts' => $statuts
    ]);

} catch (Exception $e) {
    // Logger l'erreur sans l'afficher
    error_log("Erreur dans get_statuts_by_category.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    // Logger l'erreur de base de données
    error_log("Erreur PDO dans get_statuts_by_category.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données'
    ]);
}
?> 