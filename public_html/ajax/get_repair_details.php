<?php
// Désactiver l'affichage des erreurs PHP pour la production
// mais les logger pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("Démarrage de get_repair_details.php");

// S'assurer que nous envoyons du JSON
header('Content-Type: application/json');

require_once('../config/database.php');

// Vérifier si la connexion à la base de données est établie
if (!isset($pdo) || $pdo === null) {
    error_log("Erreur: Connexion à la base de données non établie dans get_repair_details.php");
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    error_log("Erreur: ID de réparation non fourni dans get_repair_details.php");
    echo json_encode([
        'success' => false,
        'error' => 'ID de réparation non fourni'
    ]);
    exit;
}

$repair_id = (int)$_GET['id'];
error_log("Récupération des détails pour la réparation ID: $repair_id");

try {
    // Requête améliorée pour inclure le nom et la couleur du statut
    $sql = "
        SELECT 
            r.*, 
            c.nom as client_nom, 
            c.prenom as client_prenom, 
            c.telephone as client_telephone, 
            c.email as client_email,
            s.nom as statut_nom,       -- Récupérer le nom du statut depuis la table statuts
            sc.couleur as statut_couleur -- Récupérer la couleur depuis la table statut_categories
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        LEFT JOIN statuts s ON r.statut = s.code -- Joindre avec la table statuts sur le code
        LEFT JOIN statut_categories sc ON s.categorie_id = sc.id -- Joindre avec la table statut_categories pour la couleur
        WHERE r.id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    if (!$stmt) {
        $error = $pdo->errorInfo();
        error_log("Erreur de préparation SQL: " . json_encode($error));
        throw new PDOException("Erreur lors de la préparation de la requête: " . $error[2]);
    }
    
    $success = $stmt->execute([$repair_id]);
    if (!$success) {
        $error = $stmt->errorInfo();
        error_log("Erreur d'exécution SQL: " . json_encode($error));
        throw new PDOException("Erreur lors de l'exécution de la requête: " . $error[2]);
    }
    
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        error_log("Réparation non trouvée avec ID: $repair_id");
        echo json_encode([
            'success' => false,
            'error' => 'Réparation non trouvée'
        ]);
        exit;
    }
    
    error_log("Détails de réparation récupérés avec succès pour ID: $repair_id");
    
    // Formater les dates
    $repair['date_reception'] = $repair['date_reception'] ? date('d/m/Y H:i', strtotime($repair['date_reception'])) : null;
    $repair['date_debut'] = $repair['date_debut'] ? date('d/m/Y H:i', strtotime($repair['date_debut'])) : null;
    $repair['date_fin'] = $repair['date_fin'] ? date('d/m/Y H:i', strtotime($repair['date_fin'])) : null;
    $repair['date_modification'] = $repair['date_modification'] ? date('d/m/Y H:i', strtotime($repair['date_modification'])) : null;
    
    // Formater le prix
    if (isset($repair['prix_reparation'])) {
        $repair['prix_reparation_formatte'] = number_format((float)$repair['prix_reparation'], 2, ',', ' ');
    }
    
    // Tenter de récupérer les photos mais continuer même en cas d'échec
    $photos = [];
    try {
        $photos_sql = "SELECT * FROM photos_reparation WHERE reparation_id = ? ORDER BY date_upload DESC";
        error_log("Exécution de la requête SQL: $photos_sql avec ID: $repair_id");
        
        $photos_stmt = $pdo->prepare($photos_sql);
        $photos_stmt->execute([$repair_id]);
        $photos = $photos_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Photos récupérées: " . count($photos));
        if (count($photos) > 0) {
            foreach ($photos as $index => $photo) {
                error_log("Photo #$index - ID: {$photo['id']}, URL: {$photo['url']}");
            }
        } else {
            error_log("Aucune photo trouvée pour la réparation ID: $repair_id");
            
            // Essayer avec une autre requête si aucune photo n'est trouvée
            $alt_photos_sql = "SHOW TABLES LIKE 'photos_reparation%'";
            $alt_stmt = $pdo->query($alt_photos_sql);
            $tables = $alt_stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Tables similaires trouvées: " . implode(', ', $tables));
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des photos: " . $e->getMessage());
    }
    
    // Préparer les données de réponse
    $response = [
        'success' => true,
        'repair' => $repair,
        'photos' => $photos
    ];
    
    echo json_encode($response);
    error_log("Réponse JSON envoyée avec succès");
    
} catch (PDOException $e) {
    error_log("Erreur PDO dans get_repair_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des détails: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Erreur inattendue dans get_repair_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur inattendue est survenue: ' . $e->getMessage()
    ]);
} 