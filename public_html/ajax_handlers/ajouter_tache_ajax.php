<?php
/**
 * Gestionnaire AJAX pour l'ajout d'une tâche depuis le modal
 */

// Initialisation de la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action.'
    ]);
    exit;
}

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée.'
    ]);
    exit;
}

// Récupération et nettoyage des données
$titre = cleanInput($_POST['titre'] ?? '');
$description = cleanInput($_POST['description'] ?? '');
$priorite = cleanInput($_POST['priorite'] ?? '');
$statut = cleanInput($_POST['statut'] ?? '');
$date_limite = cleanInput($_POST['date_limite'] ?? '');

// Vérification et conversion de employe_id
$employe_id_raw = $_POST['employe_id'] ?? '';
$employe_id = !empty($employe_id_raw) ? (int)$employe_id_raw : null;

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

// Si des erreurs sont présentes, renvoyer une réponse avec les erreurs
if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Des erreurs ont été détectées.',
        'errors' => $errors
    ]);
    exit;
}

// Si pas d'erreurs, insertion de la tâche
try {
    // Préparation des paramètres pour la requête
    $params = [
        $titre, 
        $description, 
        $priorite, 
        $statut, 
        !empty($date_limite) ? $date_limite : null, 
        $employe_id, 
        $_SESSION['user_id']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO taches (titre, description, priorite, statut, date_limite, employe_id, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute($params);
    
    // Récupérer l'ID de la tâche créée
    $task_id = $pdo->lastInsertId();
    
    // Renvoyer une réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Tâche ajoutée avec succès!',
        'task_id' => $task_id
    ]);
    
} catch (PDOException $e) {
    // Renvoyer une réponse d'erreur
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout de la tâche: ' . $e->getMessage()
    ]);
} 