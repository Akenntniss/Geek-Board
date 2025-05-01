<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et nettoyer les données
$nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$email = trim($_POST['email'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');

// Validation des données
if (empty($nom) || empty($prenom)) {
    echo json_encode(['success' => false, 'message' => 'Nom et prénom requis']);
    exit;
}

if (empty($telephone)) {
    echo json_encode(['success' => false, 'message' => 'Numéro de téléphone requis']);
    exit;
}

try {
    // Préparer la requête d'insertion
    $sql = "INSERT INTO clients (nom, prenom, telephone, email, adresse, date_creation) 
            VALUES (:nom, :prenom, :telephone, :email, :adresse, NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    // Exécuter la requête
    $success = $stmt->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':telephone' => $telephone,
        ':email' => $email,
        ':adresse' => $adresse
    ]);
    
    if ($success) {
        // Récupérer l'ID du client créé
        $client_id = $pdo->lastInsertId();
        
        // Retourner les données du client créé
        echo json_encode([
            'success' => true,
            'message' => 'Client créé avec succès',
            'client' => [
                'id' => $client_id,
                'nom' => $nom,
                'prenom' => $prenom,
                'telephone' => $telephone,
                'email' => $email,
                'adresse' => $adresse
            ]
        ]);
    } else {
        throw new Exception('Erreur lors de la création du client');
    }
} catch (PDOException $e) {
    error_log('Erreur SQL: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la création du client'
    ]);
} catch (Exception $e) {
    error_log('Erreur: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 