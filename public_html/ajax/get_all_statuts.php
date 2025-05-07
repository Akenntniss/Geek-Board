<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Démarrer la session pour récupérer l'ID du magasin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer l'ID du magasin depuis les paramètres GET
$shop_id_from_request = $_GET['shop_id'] ?? null;
if ($shop_id_from_request) {
    $_SESSION['shop_id'] = $shop_id_from_request;
    error_log("ID du magasin récupéré depuis la requête: $shop_id_from_request");
}

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable.');
    }

    // Inclure les fichiers nécessaires
    require_once $config_path;
    
    // Utiliser la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    // Vérifier que la connexion à la base de données est établie
    if (!isset($shop_pdo) || $shop_pdo === null) {
        error_log("Erreur: Connexion à la base de données non établie dans get_all_statuts.php");
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Vérifier quelle base de données nous utilisons
    try {
        $db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
        $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Base de données connectée dans get_all_statuts.php: " . ($db_info['current_db'] ?? 'Inconnue'));
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de la base: " . $e->getMessage());
    }

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
    
    $stmt = $shop_pdo->query($sql);
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
    error_log("Erreur dans get_all_statuts.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 