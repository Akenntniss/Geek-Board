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
    $stmt = $pdo->prepare("
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
    $new_task_id = $pdo->lastInsertId();
    
    // Essayer d'ajouter un commentaire automatique si la table existe
    try {
        // Vérifier si la table commentaires_tache existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'commentaires_tache'");
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            $comment = "Tâche créée via le formulaire rapide";
            
            $stmt = $pdo->prepare("
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
        'task_id' => $new_task_id
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