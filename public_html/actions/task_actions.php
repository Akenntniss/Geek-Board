<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action.']);
    exit();
}

// Configuration de l'affichage des erreurs (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le chemin de base
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Inclure les fichiers de configuration et de connexion à la base de données
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Vérifier si c'est une requête AJAX POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Action par défaut
    $action = isset($_POST['action']) ? cleanInput($_POST['action']) : '';
    
    // Traiter l'action
    switch ($action) {
        case 'update_task_status':
            updateTaskStatus();
            break;
            
        default:
            // Action non reconnue
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Action non reconnue.']);
            exit();
    }
} else {
    // Méthode non autorisée
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

/**
 * Met à jour le statut d'une tâche
 */
function updateTaskStatus() {
    global $pdo;
    
    // Récupérer et nettoyer les données
    $task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $new_status = isset($_POST['new_status']) ? cleanInput($_POST['new_status']) : '';
    
    // Valider les données
    if ($task_id <= 0 || empty($new_status)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Données invalides.']);
        exit();
    }
    
    // Vérifier que le nouveau statut est valide
    $valid_statuses = ['a_faire', 'en_cours', 'termine'];
    if (!in_array($new_status, $valid_statuses)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Statut invalide.']);
        exit();
    }
    
    try {
        // Vérifier si la tâche existe
        $stmt = $pdo->prepare("SELECT id, statut FROM taches WHERE id = ?");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Tâche non trouvée.']);
            exit();
        }
        
        // Si le statut est déjà celui demandé, on ne fait rien
        if ($task['statut'] === $new_status) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Le statut de la tâche est déjà à jour.']);
            exit();
        }
        
        // Mettre à jour le statut
        $stmt = $pdo->prepare("UPDATE taches SET statut = ?, date_modification = NOW() WHERE id = ?");
        $result = $stmt->execute([$new_status, $task_id]);
        
        if ($result) {
            // Déterminer le message à afficher en fonction du nouveau statut
            $status_message = '';
            switch ($new_status) {
                case 'en_cours':
                    $status_message = 'La tâche a été démarrée avec succès.';
                    break;
                case 'termine':
                    $status_message = 'La tâche a été marquée comme terminée.';
                    break;
                default:
                    $status_message = 'Le statut de la tâche a été mis à jour.';
            }
            
            // Journaliser l'action
            $log_message = "Statut de la tâche #$task_id mis à jour vers '$new_status' par l'utilisateur #" . $_SESSION['user_id'];
            error_log($log_message);
            
            // Réponse au client
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $status_message]);
            exit();
        } else {
            // Erreur lors de la mise à jour
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du statut.']);
            exit();
        }
    } catch (PDOException $e) {
        // Erreur de base de données
        error_log("Erreur lors de la mise à jour du statut de la tâche: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour.']);
        exit();
    }
} 