<?php
// Activer temporairement l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Créer un fichier de log
$logFile = __DIR__ . '/debug.log';
file_put_contents($logFile, "--- Nouvelle requête ---\n", FILE_APPEND);
file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($logFile, "Date: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

try {
    // Vérifier les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    $functions_path = realpath(__DIR__ . '/../includes/functions.php');
    
    file_put_contents($logFile, "Config path: " . $config_path . "\n", FILE_APPEND);
    file_put_contents($logFile, "Functions path: " . $functions_path . "\n", FILE_APPEND);
    file_put_contents($logFile, "Current directory: " . __DIR__ . "\n", FILE_APPEND);

    if (!file_exists($config_path) || !file_exists($functions_path)) {
        throw new Exception('Fichiers de configuration introuvables. Chemins tentés : ' . $config_path . ' et ' . $functions_path);
    }

    require_once $config_path;
    require_once $functions_path;

    // Vérifier si la requête est en POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Vérifier si l'ID de la réparation est fourni
    if (!isset($_POST['reparation_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de la réparation non fourni'
        ]);
        exit;
    }

    $reparation_id = intval($_POST['reparation_id']);

    // Préparer les données à mettre à jour
    $data = [];
    
    // Avant de traiter les données, vérifier la structure de la table
    try {
        $schema_stmt = $shop_pdo->prepare("DESCRIBE reparations");
        $schema_stmt->execute();
        $columns = $schema_stmt->fetchAll(PDO::FETCH_COLUMN);
        file_put_contents($logFile, "Colonnes de la table reparations: " . print_r($columns, true) . "\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents($logFile, "Erreur lors de la récupération du schéma: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    // Liste des champs possibles dans le formulaire avec mapping vers les colonnes de la BD
    // Format: 'nom_formulaire' => 'nom_colonne_bd'
    $field_mapping = [
        'type_appareil' => 'type_appareil',
        'marque' => 'marque',
        'modele' => 'modele',
        'description_probleme' => 'description_probleme',
        'notes_techniques' => 'notes_techniques',
        'prix_reparation' => 'prix_reparation',
        'date_fin_prevue' => 'date_fin_prevue',
        'mot_de_passe' => 'mot_de_passe'
    ];
    
    // Gérer les numéros de série (ils sont stockés dans les notes_techniques)
    if (isset($_POST['numero_serie']) && $_POST['numero_serie'] !== '') {
        // Si notes_techniques existe déjà, l'ajouter
        if (isset($_POST['notes_techniques']) && $_POST['notes_techniques'] !== '') {
            $data['notes_techniques'] = "Numéro de série: " . $_POST['numero_serie'] . "\n\n" . $_POST['notes_techniques'];
        } else {
            $data['notes_techniques'] = "Numéro de série: " . $_POST['numero_serie'];
        }
    }
    
    // Ajouter les champs présents dans la requête POST avec le mapping correct
    foreach ($field_mapping as $form_field => $db_field) {
        if (isset($_POST[$form_field]) && $_POST[$form_field] !== '' && $form_field !== 'notes_techniques') {
            $data[$db_field] = $_POST[$form_field];
        }
    }
    
    // Gérer le cas spécial du statut (dans le formulaire, il s'appelle "status")
    if (isset($_POST['status']) && $_POST['status'] !== '') {
        // Convertir le statut en format enum accepté par la table
        switch ($_POST['status']) {
            case 'en_attente':
                $data['statut'] = 'En attente';
                $data['statut_categorie'] = 3; // Catégorie "en attente"
                break;
            case 'en_cours':
                $data['statut'] = 'En cours';
                $data['statut_categorie'] = 2; // Catégorie "en cours"
                break;
            case 'livree':
                $data['statut'] = 'Livré'; 
                $data['statut_categorie'] = 5; // Catégorie "terminé"
                break;
            case 'archive':
                $data['statut'] = 'archive';
                $data['statut_categorie'] = 5; // Catégorie "terminé"
                break;
            default:
                $data['statut'] = $_POST['status'];
                // Laisser statut_categorie tel quel
                break;
        }
    }
    
    // Si aucune donnée à mettre à jour, retourner une erreur
    if (empty($data)) {
        echo json_encode([
            'success' => false,
            'message' => 'Aucune donnée à mettre à jour'
        ]);
        exit;
    }
    
    // Construire la requête SQL
    $sql = "UPDATE reparations SET ";
    $params = [];
    
    foreach ($data as $key => $value) {
        $sql .= "$key = ?, ";
        $params[] = $value;
    }
    
    // Supprimer la dernière virgule et ajouter la condition WHERE
    $sql = rtrim($sql, ", ") . " WHERE id = ?";
    $params[] = $reparation_id;
    
    file_put_contents($logFile, "SQL: " . $sql . "\n", FILE_APPEND);
    file_put_contents($logFile, "Params: " . print_r($params, true) . "\n", FILE_APPEND);
    
    // Exécuter la requête
    $stmt = $shop_pdo->prepare($sql);
    $success = $stmt->execute($params);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Réparation mise à jour avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour de la réparation'
        ]);
    }

} catch (Exception $e) {
    // Log l'erreur pour le débogage
    $error_message = "Erreur dans update_reparation.php: " . $e->getMessage();
    error_log($error_message);
    file_put_contents($logFile, "Exception: " . $error_message . "\n", FILE_APPEND);
    
    // Renvoyer une réponse JSON d'erreur
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 