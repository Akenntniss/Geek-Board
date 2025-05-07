<?php
/**
 * Script AJAX pour télécharger une photo pour une réparation
 */

// Définir le type de contenu comme JSON dès le début
header('Content-Type: application/json');

// Fonction pour envoyer une réponse JSON et terminer le script
function send_json_response($success, $message, $data = []) {
    $response = array_merge(['success' => $success, 'message' => $message], $data);
    echo json_encode($response);
    exit;
}

try {
    // Inclure les fichiers nécessaires
    require_once '../config/config.php';
    require_once '../config/database.php';
    
    // Vérifier la méthode de requête
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_response(false, 'Méthode non autorisée');
    }
    
    // Récupérer et valider les données
    $repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
    $photo = isset($_POST['photo']) ? $_POST['photo'] : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Récupérer l'ID du magasin, soit de POST, soit de GET
    $shop_id = isset($_POST['shop_id']) ? intval($_POST['shop_id']) : null;
    if ($shop_id === null) {
        $shop_id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : null;
    }
    
    error_log("Upload photo - POST data: " . print_r($_POST, true));
    error_log("Upload photo - GET data: " . print_r($_GET, true));
    error_log("Upload photo - shop_id: " . ($shop_id ?? 'null'));
    
    if ($repair_id <= 0) {
        send_json_response(false, 'ID de réparation invalide');
    }
    
    if (empty($photo)) {
        send_json_response(false, 'Photo manquante');
    }
    
    // Vérifier le format de la photo (base64)
    if (!preg_match('/^data:image\/(\w+);base64,/', $photo, $matches)) {
        send_json_response(false, 'Format de photo invalide');
    }
    
    // Extraire le type d'image et les données
    $image_type = $matches[1];
    $base64_data = substr($photo, strpos($photo, ',') + 1);
    $decoded_data = base64_decode($base64_data);
    
    if ($decoded_data === false) {
        send_json_response(false, 'Impossible de décoder l\'image');
    }
    
    // Créer le dossier d'upload si nécessaire
    $upload_dir = '../assets/images/reparations/' . $repair_id;
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            send_json_response(false, 'Impossible de créer le dossier d\'upload');
        }
    }
    
    // Générer un nom de fichier unique
    $filename = 'photo_' . time() . '_' . uniqid() . '.' . $image_type;
    $file_path = $upload_dir . '/' . $filename;
    
    // Enregistrer le fichier
    if (file_put_contents($file_path, $decoded_data) === false) {
        send_json_response(false, 'Erreur lors de l\'enregistrement de l\'image');
    }
    
    // Chemin relatif pour la base de données
    $db_path = 'assets/images/reparations/' . $repair_id . '/' . $filename;
    
    // Démarrer la session si ce n'est pas déjà fait
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Si shop_id n'est pas défini dans la requête, essayer de le récupérer de la session
    if ($shop_id === null) {
        $shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 1;
    }
    
    // Utiliser la connexion à la base de données du magasin
    $db = getShopDBConnection();
    
    if (!$db) {
        error_log("Impossible d'obtenir la connexion à la base de données du magasin dans upload_repair_photo.php");
        send_json_response(false, "Erreur de connexion à la base de données");
    }
    
    // Vérifier si la table photos_reparation existe
    $check_table = $db->query("SHOW TABLES LIKE 'photos_reparation'");
    if ($check_table->rowCount() === 0) {
        error_log('La table photos_reparation n\'existe pas');
        
        // Vérifier si c'est photos_reparations avec un 's'
        $check_table_s = $db->query("SHOW TABLES LIKE 'photos_reparations'");
        if ($check_table_s->rowCount() > 0) {
            error_log('La table photos_reparations (avec un s) existe');
            // Utiliser cette table à la place
            $table_name = 'photos_reparations';
        } else {
            error_log('Ni photos_reparation ni photos_reparations n\'existent');
            send_json_response(false, 'Table de photos introuvable');
        }
    } else {
        $table_name = 'photos_reparation';
    }
    
    // Vérifier la structure de la table
    $columns = $db->query("SHOW COLUMNS FROM $table_name");
    $column_names = array();
    while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
        $column_names[] = $col['Field'];
    }
    error_log('Colonnes de la table ' . $table_name . ': ' . implode(', ', $column_names));
    
    // Vérifier si la réparation existe dans cette base de données
    $check_repair = $db->prepare("SELECT id FROM reparations WHERE id = ?");
    $check_repair->execute([$repair_id]);
    if (!$check_repair->fetch()) {
        error_log("La réparation avec ID $repair_id n'existe pas dans cette base de données");
        send_json_response(false, "Réparation non trouvée dans ce magasin");
        exit;
    }
    
    // Insérer la photo dans la base de données
    $date_column = in_array('date_upload', $column_names) ? 'date_upload' : (in_array('date_creation', $column_names) ? 'date_creation' : null);
    
    if ($date_column) {
        $stmt = $db->prepare("INSERT INTO $table_name (reparation_id, url, description, $date_column) VALUES (:reparation_id, :url, :description, NOW())");
    } else {
        $stmt = $db->prepare("INSERT INTO $table_name (reparation_id, url, description) VALUES (:reparation_id, :url, :description)");
    }
    
    $stmt->bindParam(':reparation_id', $repair_id, PDO::PARAM_INT);
    $stmt->bindParam(':url', $db_path, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $success = $stmt->execute();
    
    if ($success) {
        $photo_id = $db->lastInsertId();
        error_log("Photo ajoutée avec succès. ID: $photo_id, URL: $db_path, Description: $description");
        
        // Enregistrer l'action dans les logs
        $employe_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $log_message = "Photo ajoutée: " . ($description ? $description : 'Sans description');
        
        try {
            $log_stmt = $db->prepare('INSERT INTO logs_reparations (reparation_id, employe_id, action, date_action) VALUES (:reparation_id, :employe_id, :action, NOW())');
            $log_stmt->bindParam(':reparation_id', $repair_id, PDO::PARAM_INT);
            $log_stmt->bindParam(':employe_id', $employe_id, PDO::PARAM_INT);
            $log_stmt->bindParam(':action', $log_message, PDO::PARAM_STR);
            $log_stmt->execute();
        } catch (Exception $e) {
            // Si erreur lors de l'enregistrement du log, on continue quand même
            error_log('Erreur lors de l\'enregistrement du log: ' . $e->getMessage());
        }
        
        send_json_response(true, 'Photo ajoutée avec succès', ['photo_path' => $db_path]);
    } else {
        send_json_response(false, 'Erreur lors de l\'ajout de la photo dans la base de données');
    }
} catch (Exception $e) {
    // Capturer toutes les exceptions et erreurs
    error_log('Erreur dans upload_repair_photo.php: ' . $e->getMessage());
    send_json_response(false, 'Erreur: ' . $e->getMessage());
} 