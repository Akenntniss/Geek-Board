<?php
// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON avant tout
header('Content-Type: application/json');

// Démarrer la session
session_start();

// Inclure la configuration de la base de données
require_once dirname(__DIR__) . '/config/database.php';

// Vérifier que l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Accès non autorisé'
    ]);
    exit;
}

// Vérifier que l'ID est fourni
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID client invalide'
    ]);
    exit;
}

$client_id = (int)$_POST['id'];

try {
    // Vérifier si le client a des réparations
    $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM reparations WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de supprimer ce client car il a des réparations associées'
        ]);
        exit;
    }

    // Supprimer le client
    $stmt = $shop_pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Client supprimé avec succès'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression du client: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
} 