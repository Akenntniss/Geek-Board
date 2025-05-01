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
if (!isset($_POST['commande_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de la commande non fourni'
    ]);
    exit;
}

$commande_id = intval($_POST['commande_id']);

try {
    // Préparer les données à mettre à jour
    $data = [
        'client_id' => $_POST['client_id'],
        'fournisseur_id' => $_POST['fournisseur_id'],
        'nom_piece' => $_POST['nom_piece'],
        'code_barre' => $_POST['code_barre'],
        'quantite' => $_POST['quantite'],
        'prix_estime' => $_POST['prix_estime'],
        'date_creation' => $_POST['date_creation'],
        'statut' => $_POST['statut']
    ];
    
    // Construire la requête SQL
    $sql = "UPDATE commandes_pieces SET ";
    $params = [];
    
    foreach ($data as $key => $value) {
        $sql .= "$key = ?, ";
        $params[] = $value;
    }
    
    // Supprimer la dernière virgule et ajouter la condition WHERE
    $sql = rtrim($sql, ", ") . " WHERE id = ?";
    $params[] = $commande_id;
    
    // Exécuter la requête
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($params);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Commande mise à jour avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour de la commande'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour de la commande: ' . $e->getMessage()
    ]);
} 