<?php
// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Activer la journalisation des erreurs
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Définir l'en-tête JSON avant tout
header('Content-Type: application/json');

// Inclusion du fichier de configuration et functions
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'ID de la commande est fourni
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de la commande non fourni'
    ]);
    exit;
}

$commande_id = intval($_GET['id']);

try {
    // Récupérer les informations de la commande
    $stmt = $pdo->prepare("
        SELECT c.*, f.nom as fournisseur_nom, cl.nom as client_nom, cl.prenom as client_prenom
        FROM commandes_pieces c
        LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id
        LEFT JOIN clients cl ON c.client_id = cl.id
        WHERE c.id = ?
    ");
    $stmt->execute([$commande_id]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($commande) {
        echo json_encode([
            'success' => true,
            'commande' => $commande
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Commande non trouvée'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des informations de la commande: ' . $e->getMessage()
    ]);
} 