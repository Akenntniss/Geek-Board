<?php
/**
 * Script AJAX pour mettre à jour les informations d'un client
 * Appelé depuis le modal de modification client de la recherche universelle
 */

// Configuration d'en-tête pour réponse JSON
header('Content-Type: application/json; charset=utf-8');

// Démarrer la session
session_start();

try {
    // Inclusion des fichiers de configuration
    require_once '../config/session_config.php';
    require_once '../includes/functions.php';
    
    // Vérifier que l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Session expirée ou utilisateur non connecté');
    }
    
    // Récupérer la connexion à la base de données
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données');
    }
    
    // Vérifier que c'est une requête POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    
    // Récupérer et valider les données
    $client_id = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
    $nom = trim(filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING));
    $prenom = trim(filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING));
    $telephone = trim(filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $adresse = trim(filter_input(INPUT_POST, 'adresse', FILTER_SANITIZE_STRING));
    $code_postal = trim(filter_input(INPUT_POST, 'code_postal', FILTER_SANITIZE_STRING));
    $ville = trim(filter_input(INPUT_POST, 'ville', FILTER_SANITIZE_STRING));
    $notes = trim(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING));
    
    // Validation des champs obligatoires
    if (!$client_id || $client_id <= 0) {
        throw new Exception('ID client invalide');
    }
    
    if (empty($nom)) {
        throw new Exception('Le nom est obligatoire');
    }
    
    if (empty($prenom)) {
        throw new Exception('Le prénom est obligatoire');
    }
    
    if (empty($telephone)) {
        throw new Exception('Le téléphone est obligatoire');
    }
    
    // Validation de l'email si fourni
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Format d\'email invalide');
    }
    
    // Log de débogage
    error_log("📝 Mise à jour client ID: $client_id");
    error_log("📝 Nouvelles données: nom=$nom, prenom=$prenom, telephone=$telephone");
    
    // Vérifier que le client existe
    $stmt = $shop_pdo->prepare("SELECT id FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Client introuvable');
    }
    
    // Préparer la requête de mise à jour
    $sql = "UPDATE clients SET 
            nom = ?, 
            prenom = ?, 
            telephone = ?, 
            email = ?, 
            adresse = ?, 
            code_postal = ?, 
            ville = ?, 
            notes = ?, 
            date_modification = NOW() 
            WHERE id = ?";
    
    $stmt = $shop_pdo->prepare($sql);
    $result = $stmt->execute([
        $nom,
        $prenom,
        $telephone,
        $email ?: null,
        $adresse ?: null,
        $code_postal ?: null,
        $ville ?: null,
        $notes ?: null,
        $client_id
    ]);
    
    if (!$result) {
        throw new Exception('Erreur lors de la mise à jour en base de données');
    }
    
    // Vérifier qu'au moins une ligne a été affectée
    $rows_affected = $stmt->rowCount();
    error_log("📝 Lignes affectées: $rows_affected");
    
    // Log d'activité pour traçabilité
    $activity_message = "Modification client: $prenom $nom (ID: $client_id)";
    error_log("✅ $activity_message");
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Fiche client mise à jour avec succès',
        'client_id' => $client_id,
        'rows_affected' => $rows_affected,
        'data' => [
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,
            'email' => $email,
            'adresse' => $adresse,
            'code_postal' => $code_postal,
            'ville' => $ville,
            'notes' => $notes
        ]
    ]);
    
} catch (Exception $e) {
    // Log de l'erreur
    error_log("❌ Erreur update_client.php: " . $e->getMessage());
    
    // Réponse d'erreur
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (PDOException $e) {
    // Erreur de base de données
    error_log("❌ Erreur PDO update_client.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage(),
        'error_code' => 'DB_ERROR'
    ]);
}
?> 