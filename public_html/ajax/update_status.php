<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Définir le header Content-Type pour renvoyer du JSON
header('Content-Type: application/json');

// Log de débogage
error_log('Démarrage de update_status.php');

// Inclure les fichiers de configuration et de fonctions
require_once('../config/database.php');
require_once('../includes/functions.php');

// Récupérer les paramètres
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$statut = isset($_POST['statut']) ? $_POST['statut'] : '';

// Log des paramètres reçus
error_log("Paramètres reçus: id=$id, statut=$statut");

// Vérifier que les paramètres sont valides
if ($id <= 0 || empty($statut)) {
    error_log("Erreur: ID de commande ou statut manquant");
    echo json_encode([
        'success' => false,
        'message' => 'ID de commande ou statut manquant'
    ]);
    exit;
}

// Vérifier que le statut est valide
$statuts_valides = ['en_attente', 'commande', 'recue', 'termine', 'annulee', 'urgent', 'utilise', 'a_retourner'];
if (!in_array($statut, $statuts_valides)) {
    error_log("Erreur: Statut invalide: $statut");
    echo json_encode([
        'success' => false,
        'message' => 'Statut invalide'
    ]);
    exit;
}

try {
    // Mettre à jour le statut
    $sql = "UPDATE commandes_pieces SET statut = :statut WHERE id = :id";
    error_log("Requête SQL: $sql avec statut=$statut et id=$id");
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $success = $stmt->execute();
    
    error_log("Exécution de la requête: " . ($success ? "réussie" : "échouée"));
    
    // Vérifier si la mise à jour a affecté une ligne
    if ($stmt->rowCount() > 0) {
        error_log("Commande $id mise à jour avec le statut $statut");
        
        // Récupérer les détails mis à jour de la commande
        $sql_fetch = "SELECT cp.*, c.nom as client_nom, c.prenom as client_prenom 
                      FROM commandes_pieces cp 
                      LEFT JOIN clients c ON cp.client_id = c.id 
                      WHERE cp.id = :id";
        
        $stmt_fetch = $shop_pdo->prepare($sql_fetch);
        $stmt_fetch->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $commande = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'commande' => $commande
        ]);
    } else {
        error_log("Aucune commande trouvée avec l'ID $id");
        echo json_encode([
            'success' => false,
            'message' => 'Commande non trouvée'
        ]);
    }
} catch (PDOException $e) {
    // Logger l'erreur
    error_log('Erreur dans update_status.php: ' . $e->getMessage());
    
    // Renvoyer une réponse d'erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour du statut: ' . $e->getMessage()
    ]);
} 