<?php
session_start();
require_once "../includes/functions.php";
require_once "../includes/db.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Vous devez être connecté pour effectuer cette action'
    ]);
    exit;
}

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée'
    ]);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

// Vérifier les données
if (!isset($data['id']) || !isset($data['archive'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Données incomplètes'
    ]);
    exit;
}

$id = (int) $data['id'];
$archive = (bool) $data['archive'];

// Mettre à jour le statut d'archive
try {
    $stmt = $pdo->prepare("UPDATE reparations SET archive = ?, date_modification = NOW() WHERE id = ?");
    $result = $stmt->execute([$archive ? 1 : 0, $id]);
    
    if ($result) {
        // Récupérer le statut de la réparation pour retourner le badge HTML
        $stmt = $pdo->prepare("
            SELECT r.statut, s.nom as statut_nom, s.couleur as statut_couleur
            FROM reparations r 
            JOIN statuts s ON r.statut = s.code 
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $reparation = $stmt->fetch();
        
        // Générer le badge HTML
        $statut_badge = '<span class="badge bg-' . $reparation['statut_couleur'] . '">' . htmlspecialchars($reparation['statut_nom']) . '</span>';
        
        echo json_encode([
            'success' => true,
            'archive' => $archive,
            'statut_badge' => $statut_badge
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la mise à jour de la réparation'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} 