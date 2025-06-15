<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Récupérer les données JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Log des données reçues
error_log("Données JSON reçues pour l'ajout de commandes multiples: " . print_r($data, true));

// Vérifier si les données sont valides
if (!isset($data['pieces']) || !is_array($data['pieces']) || empty($data['pieces'])) {
    error_log("Aucune pièce trouvée dans les données");
    echo json_encode(['success' => false, 'message' => 'Aucune pièce trouvée dans les données']);
    exit;
}

// Compteur pour les commandes traitées avec succès
$success_count = 0;
$errors = [];

try {
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    // Préparer la requête SQL
    $stmt = $shop_pdo->prepare("
        INSERT INTO commandes_pieces (
            reference, client_id, fournisseur_id, reparation_id, 
            nom_piece, code_barre, quantite, prix_estime, 
            statut, date_creation
        ) VALUES (
            :reference, :client_id, :fournisseur_id, :reparation_id, 
            :nom_piece, :code_barre, :quantite, :prix_estime, 
            :statut, NOW()
        )
    ");
    
    // Traiter chaque pièce
    foreach ($data['pieces'] as $piece) {
        // Validation basique
        if (empty($piece['client_id']) || empty($piece['fournisseur_id']) || empty($piece['nom_piece']) || 
            !isset($piece['quantite']) || !isset($piece['prix_estime'])) {
            $errors[] = "Données incomplètes pour une pièce: " . json_encode($piece);
            continue;
        }
        
        // Générer une référence unique
        $reference = 'CMD-' . date('Ymd') . '-' . uniqid();
        
        // Préparer les données
        $insert_data = [
            'reference' => $reference,
            'client_id' => $piece['client_id'],
            'fournisseur_id' => $piece['fournisseur_id'],
            'reparation_id' => !empty($piece['reparation_id']) ? $piece['reparation_id'] : null,
            'nom_piece' => trim($piece['nom_piece']),
            'code_barre' => !empty($piece['code_barre']) ? trim($piece['code_barre']) : null,
            'quantite' => floatval($piece['quantite']),
            'prix_estime' => floatval($piece['prix_estime']),
            'statut' => !empty($piece['statut']) ? $piece['statut'] : 'en_attente'
        ];
        
        // Log des données à insérer
        error_log("Commande à insérer : " . print_r($insert_data, true));
        
        // Exécuter la requête
        $result = $stmt->execute($insert_data);
        
        if ($result) {
            $success_count++;
            error_log("Commande ajoutée avec succès. ID: " . $shop_pdo->lastInsertId());
        } else {
            $errors[] = "Erreur lors de l'insertion d'une commande: " . json_encode($piece);
        }
    }
    
    // Si des erreurs ont été rencontrées, annuler la transaction
    if (!empty($errors)) {
        $shop_pdo->rollBack();
        error_log("Transaction annulée. Erreurs: " . print_r($errors, true));
        echo json_encode([
            'success' => false, 
            'message' => 'Des erreurs sont survenues lors de l\'ajout des commandes. Aucune commande n\'a été enregistrée.', 
            'errors' => $errors
        ]);
        exit;
    }
    
    // Tout s'est bien passé, valider la transaction
    $shop_pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => $success_count . ' commande(s) ajoutée(s) avec succès'
    ]);
} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    
    error_log("Erreur PDO lors de l'ajout des commandes : " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de l\'ajout des commandes: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if ($shop_pdo->inTransaction()) {
        $shop_pdo->rollBack();
    }
    
    error_log("Erreur générale lors de l'ajout des commandes : " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de l\'ajout des commandes: ' . $e->getMessage()
    ]);
} 