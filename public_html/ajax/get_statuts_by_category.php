<?php
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

    // Récupérer l'ID de catégorie
    $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    
    if ($category_id <= 0) {
        throw new Exception('ID de catégorie invalide');
    }

    // Récupérer les statuts de cette catégorie
    $stmt = $pdo->prepare("
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
    $stmt = $pdo->prepare("
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
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 