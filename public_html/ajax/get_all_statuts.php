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

    // Requête pour récupérer tous les statuts avec leurs catégories
    $sql = "
        SELECT 
            s.id, s.nom, s.code, s.est_actif, s.ordre,
            c.id as categorie_id, c.nom as categorie_nom, c.code as categorie_code, c.couleur
        FROM statuts s
        JOIN statut_categories c ON s.categorie_id = c.id
        WHERE s.est_actif = 1
        ORDER BY c.ordre, s.ordre
    ";
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organiser les statuts par catégorie
    $statuts_par_categorie = [];
    
    foreach ($results as $statut) {
        $categorie_code = $statut['categorie_code'];
        
        if (!isset($statuts_par_categorie[$categorie_code])) {
            $statuts_par_categorie[$categorie_code] = [
                'nom' => $statut['categorie_nom'],
                'couleur' => $statut['couleur'],
                'statuts' => []
            ];
        }
        
        $statuts_par_categorie[$categorie_code]['statuts'][] = [
            'id' => $statut['id'],
            'nom' => $statut['nom'],
            'code' => $statut['code']
        ];
    }
    
    // Renvoyer les résultats
    echo json_encode([
        'success' => true,
        'statuts' => $statuts_par_categorie
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 