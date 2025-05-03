<?php
/**
 * Gestionnaire AJAX pour l'ajout rapide de tâches
 * Ce script reçoit les données du formulaire modal d'ajout de tâche
 * et les enregistre dans la base de données.
 */

// Initialisation de la session si ce n'est pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
    exit;
}

// Inclusion des fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Vérification de l'ID du magasin en session
    if (!isset($_SESSION['shop_id'])) {
        // Journaliser l'erreur
        error_log("Erreur: Aucun magasin associé à l'utilisateur " . $_SESSION['user_id']);
        throw new Exception("Aucun magasin associé à votre compte. Veuillez contacter l'administrateur.");
    }
    
    // Récupérer l'ID du magasin
    $shop_id = $_SESSION['shop_id'];
    error_log("Utilisateur ID: " . $_SESSION['user_id'] . ", Magasin ID: " . $shop_id);
    
    // Obtenir la connexion à la base de données du magasin de l'utilisateur connecté
    $shop_pdo = getShopDBConnection();
    
    // Vérifier quelle base de données est utilisée
    $stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_db = $result['current_db'];
    error_log("Base de données utilisée pour l'insertion de la tâche rapide: " . $current_db);
    
    // Récupérer les informations du magasin depuis la base principale
    $main_pdo = getMainDBConnection();
    $stmt = $main_pdo->prepare("SELECT name, db_name FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop_info) {
        error_log("Magasin: " . $shop_info['name'] . ", Base attendue: " . $shop_info['db_name']);
        
        // Vérifier si la bonne base de données est utilisée
        if ($current_db !== $shop_info['db_name']) {
            error_log("ERREUR: Mauvaise base de données utilisée. Attendue: " . $shop_info['db_name'] . ", Utilisée: " . $current_db);
        }
    } else {
        error_log("ERREUR: Impossible de trouver les informations du magasin ID: " . $shop_id);
    }
    
    // Récupération et nettoyage des données
    $titre = isset($_POST['titre']) ? cleanInput($_POST['titre']) : '';
    $description = isset($_POST['description']) ? cleanInput($_POST['description']) : '';
    $priorite = isset($_POST['priorite']) ? cleanInput($_POST['priorite']) : '';
    $statut = isset($_POST['statut']) ? cleanInput($_POST['statut']) : '';
    $date_limite = isset($_POST['date_limite']) && !empty($_POST['date_limite']) ? cleanInput($_POST['date_limite']) : null;
    $employe_id = isset($_POST['employe_id']) && !empty($_POST['employe_id']) ? (int)$_POST['employe_id'] : null;
    
    // Validation des données
    $errors = [];
    
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire.";
    }
    
    if (empty($description)) {
        $errors[] = "La description est obligatoire.";
    }
    
    if (empty($priorite)) {
        $errors[] = "La priorité est obligatoire.";
    }
    
    if (empty($statut)) {
        $errors[] = "Le statut est obligatoire.";
    }
    
    // Si des erreurs sont trouvées, les renvoyer sous forme de réponse JSON
    if (!empty($errors)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }
    
    // Insertion de la tâche
    $stmt = $shop_pdo->prepare("
        INSERT INTO taches (titre, description, priorite, statut, date_limite, employe_id, created_by, date_creation) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $titre, 
        $description, 
        $priorite, 
        $statut, 
        $date_limite, 
        $employe_id,
        $_SESSION['user_id']
    ]);
    
    // Récupération de l'ID de la nouvelle tâche
    $new_task_id = $shop_pdo->lastInsertId();
    
    // Journaliser le succès
    error_log("Tâche rapide ID: " . $new_task_id . " créée avec succès dans la base: " . $current_db);
    
    // Essayer d'ajouter un commentaire automatique si la table existe
    try {
        // Vérifier si la table commentaires_tache existe
        $stmt = $shop_pdo->query("SHOW TABLES LIKE 'commentaires_tache'");
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            $comment = "Tâche créée via le formulaire rapide";
            
            $stmt = $shop_pdo->prepare("
                INSERT INTO commentaires_tache (tache_id, user_id, commentaire, date_creation) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $new_task_id,
                $_SESSION['user_id'],
                $comment
            ]);
        }
    } catch (Exception $commentError) {
        // Ignorer les erreurs de commentaire - ce n'est pas critique
        error_log("Avertissement: Impossible d'ajouter un commentaire à la tâche: " . $commentError->getMessage());
    }
    
    // Renvoyer une réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Tâche ajoutée avec succès',
        'task_id' => $new_task_id,
        'debug_info' => [
            'user_id' => $_SESSION['user_id'],
            'shop_id' => $shop_id,
            'database' => $current_db
        ]
    ]);
    
} catch (PDOException $e) {
    // Journaliser l'erreur
    error_log("Erreur lors de l'ajout rapide d'une tâche : " . $e->getMessage());
    
    // Renvoyer une réponse d'erreur
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => "Erreur lors de l'ajout de la tâche : " . $e->getMessage()
    ]);
}
?> 