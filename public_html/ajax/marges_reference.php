<?php
// Inclure les fichiers nécessaires
require_once '../includes/db.php';
require_once '../includes/functions.php';

// En-têtes pour autoriser les requêtes AJAX
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Traitement des différentes actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Récupération des références de marges
    $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : '';
    
    if (empty($categorie) || !in_array($categorie, ['smartphone', 'tablet', 'computer'])) {
        echo json_encode(['success' => false, 'message' => 'Catégorie invalide']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM marges_reference WHERE categorie = ? ORDER BY type_reparation");
        $stmt->execute([$categorie]);
        $references = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'references' => $references]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'add_margin_reference') {
        // Ajouter une nouvelle référence
        $type_reparation = isset($_POST['type_reparation']) ? cleanInput($_POST['type_reparation']) : '';
        $categorie = isset($_POST['categorie']) ? $_POST['categorie'] : '';
        $prix_achat = isset($_POST['prix_achat']) ? (float)$_POST['prix_achat'] : 0;
        $marge_pourcentage = isset($_POST['marge_pourcentage']) ? (int)$_POST['marge_pourcentage'] : 0;
        
        // Validation des données
        if (empty($type_reparation) || empty($categorie) || $prix_achat < 0 || $marge_pourcentage < 0) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
            exit;
        }
        
        if (!in_array($categorie, ['smartphone', 'tablet', 'computer'])) {
            echo json_encode(['success' => false, 'message' => 'Catégorie invalide']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO marges_reference (type_reparation, categorie, prix_achat, marge_pourcentage) VALUES (?, ?, ?, ?)");
            $stmt->execute([$type_reparation, $categorie, $prix_achat, $marge_pourcentage]);
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout: ' . $e->getMessage()]);
        }
    } elseif ($action === 'delete_margin_reference') {
        // Supprimer une référence
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID invalide']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM marges_reference WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Référence non trouvée']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Action invalide']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
} 