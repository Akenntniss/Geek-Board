<?php
// Initialisation de la session (si ce n'est pas déjà fait)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
    exit;
}

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Inclusion des fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérification de l'action
$action = isset($_POST['action']) ? cleanInput($_POST['action']) : '';

if ($action === 'ajouter_commentaire') {
    // Vérification des paramètres requis
    if (!isset($_POST['tache_id']) || !isset($_POST['commentaire']) || empty($_POST['commentaire'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
        exit;
    }
    
    // Nettoyage et validation des données
    $tache_id = (int)$_POST['tache_id'];
    $commentaire = cleanInput($_POST['commentaire']);
    $user_id = $_SESSION['user_id'];
    
    try {
        // Vérifier que la tâche existe
        $stmt = $pdo->prepare("SELECT id FROM taches WHERE id = ?");
        $stmt->execute([$tache_id]);
        if (!$stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Tâche non trouvée']);
            exit;
        }
        
        // Insertion du commentaire
        $stmt = $pdo->prepare("
            INSERT INTO commentaires_tache (tache_id, user_id, commentaire, date_creation) 
            VALUES (?, ?, ?, NOW())
        ");
        $result = $stmt->execute([$tache_id, $user_id, $commentaire]);
        
        if ($result) {
            // Récupérer le commentaire ajouté avec les informations de l'utilisateur
            $commentaire_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("
                SELECT c.*, u.full_name as user_nom
                FROM commentaires_tache c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$commentaire_id]);
            $nouveau_commentaire = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Commentaire ajouté avec succès',
                'commentaire' => $nouveau_commentaire
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Échec de l\'ajout du commentaire']);
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de l\'ajout du commentaire: ' . $e->getMessage()
        ]);
        
        // Journaliser l'erreur
        error_log('Erreur dans tache_commentaires.php: ' . $e->getMessage());
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
}
?> 