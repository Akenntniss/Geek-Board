<?php
// Inclure la configuration et les fonctions avant tout output
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Configuration des headers pour les requêtes AJAX
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Gérer les requêtes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier que la connexion à la base de données est établie
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit;
}

// Récupérer les données JSON de la requête
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Validation des données
    if (!isset($data['produit_id']) || !is_numeric($data['produit_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de produit invalide']);
        exit;
    }
    
    $produit_id = (int)$data['produit_id'];
    
    // Traitement du fournisseur_id
    $fournisseur_id = null; // Par défaut NULL
    if (isset($data['fournisseur_id']) && $data['fournisseur_id'] !== '' && $data['fournisseur_id'] !== null) {
        $fournisseur_id = (int)$data['fournisseur_id'];
    }
    
    // Log des données reçues
    error_log("AJAX: Mise à jour du fournisseur pour produit #$produit_id");
    error_log("AJAX: fournisseur_id = " . var_export($fournisseur_id, true));
    error_log("AJAX: Données complètes: " . print_r($data, true));
    
    // Mettre à jour le fournisseur_id
    $stmt = $pdo->prepare("UPDATE produits SET fournisseur_id = ? WHERE id = ?");
    $stmt->execute([$fournisseur_id, $produit_id]);
    
    // Vérifier le nombre de lignes affectées
    $rowCount = $stmt->rowCount();
    error_log("AJAX: Nombre de lignes affectées: $rowCount");
    
    if ($rowCount > 0) {
        // Récupérer les nouvelles données du produit
        $stmt = $pdo->prepare("SELECT p.*, f.nom as fournisseur_nom FROM produits p LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id WHERE p.id = ?");
        $stmt->execute([$produit_id]);
        $produit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Fournisseur mis à jour avec succès',
            'produit' => $produit
        ]);
    } else {
        // Si aucune ligne n'a été affectée, c'est peut-être parce que la valeur est déjà celle qu'on veut définir
        $stmt = $pdo->prepare("SELECT fournisseur_id FROM produits WHERE id = ?");
        $stmt->execute([$produit_id]);
        $currentValue = $stmt->fetchColumn();
        
        // Convertir null en NULL pour affichage
        $currentValueStr = $currentValue === null ? 'NULL' : $currentValue;
        $newValueStr = $fournisseur_id === null ? 'NULL' : $fournisseur_id;
        
        error_log("AJAX: Valeur actuelle: $currentValueStr, Nouvelle valeur: $newValueStr");
        
        if (($currentValue === null && $fournisseur_id === null) || $currentValue == $fournisseur_id) {
            echo json_encode([
                'success' => true,
                'message' => 'Aucune modification nécessaire, la valeur est déjà ' . $newValueStr
            ]);
        } else {
            // Forcer la mise à jour même si MySQL pense qu'il n'y a pas de changement
            $stmt = $pdo->prepare("UPDATE produits SET fournisseur_id = ? WHERE id = ?");
            if ($fournisseur_id === null) {
                $stmt->bindValue(1, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(1, $fournisseur_id, PDO::PARAM_INT);
            }
            $stmt->bindValue(2, $produit_id, PDO::PARAM_INT);
            $stmt->execute();
            
            error_log("AJAX: Deuxième tentative, lignes affectées: " . $stmt->rowCount());
            
            echo json_encode([
                'success' => true,
                'message' => 'Fournisseur mis à jour (deuxième tentative)',
                'old_value' => $currentValueStr,
                'new_value' => $newValueStr
            ]);
        }
    }
} catch (PDOException $e) {
    error_log("AJAX Erreur: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?> 