<?php
// Démarrage de la session uniquement si aucune session n'est active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Activation des journaux d'erreurs
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Vérification du dossier de logs
if (!file_exists('../logs')) {
    mkdir('../logs', 0755, true);
}

// Inclusion des fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';

// Journalisation de la requête
error_log('Requête reçue dans update_commande_field.php');

// Vérification de la connexion à la base de données
if (!isset($pdo) || $pdo === null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    error_log('Erreur: connexion à la base de données non établie dans update_commande_field.php');
    exit;
}

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    error_log('Erreur: méthode HTTP non autorisée dans update_commande_field.php');
    exit;
}

// Vérification des données requises
if (!isset($_POST['action']) || $_POST['action'] !== 'update_field' || 
    !isset($_POST['commande_id']) || !isset($_POST['field_name']) || !isset($_POST['field_value'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données manquantes ou invalides']);
    error_log('Erreur: données manquantes dans update_commande_field.php');
    exit;
}

// Récupération des données
$commande_id = intval($_POST['commande_id']);
$field_name = $_POST['field_name'];
$field_value = $_POST['field_value'];

// Validation du nom du champ (sécurité)
$allowed_fields = ['nom_piece', 'prix_estime', 'fournisseur_id'];
if (!in_array($field_name, $allowed_fields)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Champ non autorisé']);
    error_log("Erreur: tentative de modification d'un champ non autorisé: $field_name");
    exit;
}

// Préparation des valeurs spécifiques selon le champ
if ($field_name === 'prix_estime') {
    // S'assurer que le prix est un nombre valide
    $field_value = floatval(str_replace(',', '.', $field_value));
    if ($field_value < 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Le prix ne peut pas être négatif']);
        exit;
    }
} elseif ($field_name === 'fournisseur_id') {
    // Vérifier que le fournisseur existe
    try {
        $stmt = $pdo->prepare("SELECT id FROM fournisseurs WHERE id = ?");
        $stmt->execute([$field_value]);
        if ($stmt->rowCount() === 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Fournisseur invalide']);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification du fournisseur: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la vérification du fournisseur']);
        exit;
    }
} elseif ($field_name === 'nom_piece') {
    // Vérifier que le nom de la pièce n'est pas vide
    if (trim($field_value) === '') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Le nom de la pièce ne peut pas être vide']);
        exit;
    }
}

// Mise à jour de la commande
try {
    // Log des données reçues
    error_log("Mise à jour de la commande: ID=$commande_id, Champ=$field_name, Valeur=$field_value");
    
    // Construction de la requête SQL
    $sql = "UPDATE commandes_pieces SET $field_name = :value, date_modification = NOW() WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    // Exécution de la requête
    $result = $stmt->execute([
        ':value' => $field_value,
        ':id' => $commande_id
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Enregistrement de l'historique de modification
        try {
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
            $field_label = [
                'nom_piece' => 'Nom de la pièce',
                'prix_estime' => 'Prix estimé',
                'fournisseur_id' => 'Fournisseur'
            ][$field_name] ?? $field_name;
            
            // Vérifier si la table historique_modifications existe
            $check_table = $pdo->query("SHOW TABLES LIKE 'historique_modifications'");
            if ($check_table->rowCount() > 0) {
                $stmt_history = $pdo->prepare("
                    INSERT INTO historique_modifications 
                    (commande_id, user_id, field_name, old_value, new_value, date_creation) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                // Récupérer l'ancienne valeur pour l'historique
                $stmt_old = $pdo->prepare("SELECT $field_name FROM commandes_pieces WHERE id = ?");
                $stmt_old->execute([$commande_id]);
                $old_value = $stmt_old->fetchColumn();
                
                $stmt_history->execute([$commande_id, $user_id, $field_label, $old_value, $field_value]);
            }
        } catch (PDOException $e) {
            // Ignorer les erreurs d'historique - elles ne doivent pas bloquer la mise à jour
            error_log("Erreur lors de l'enregistrement de l'historique: " . $e->getMessage());
        }
        
        // Réponse réussie
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Mise à jour effectuée avec succès',
            'field' => $field_name,
            'value' => $field_value
        ]);
    } else {
        // Aucune ligne mise à jour
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Aucune modification effectuée. La valeur est peut-être identique ou la commande n\'existe pas.'
        ]);
    }
} catch (PDOException $e) {
    // Erreur de base de données
    error_log("Erreur SQL lors de la mise à jour: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
} 