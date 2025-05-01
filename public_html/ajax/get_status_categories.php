<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable.');
    }

    // Inclure les fichiers nécessaires
    require_once $config_path;

    // Récupérer toutes les catégories de statut
    $stmt = $pdo->query("
        SELECT id, nom, code, couleur, ordre
        FROM statut_categories
        ORDER BY ordre ASC
    ");
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($categories)) {
        throw new Exception('Aucune catégorie de statut trouvée');
    }
    
    // Renvoyer les résultats
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 