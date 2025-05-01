<?php
/**
 * Script pour envoyer un devis par SMS et mettre à jour le statut d'une réparation
 */

// Désactiver l'affichage des erreurs PHP pour éviter de corrompre la sortie JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Définir l'en-tête JSON dès le début
header('Content-Type: application/json');

// Initialiser la réponse
$response = [
    'success' => false,
    'error' => null
];

// Vérifier si la requête est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Méthode non autorisée';
    echo json_encode($response);
    exit;
}

// Inclure les fichiers de configuration et de connexion à la base de données
require_once '../config/database.php';
require_once '../includes/functions.php';

// S'assurer que $pdo est disponible
if (!isset($pdo) || $pdo === null) {
    $response['error'] = 'Erreur de connexion à la base de données';
    echo json_encode($response);
    exit;
}

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    $response['error'] = 'Utilisateur non authentifié';
    echo json_encode($response);
    exit;
}

// Récupérer les données du formulaire
$repair_id = isset($_POST['repair_id']) ? intval($_POST['repair_id']) : 0;
$sms_template_id = isset($_POST['sms_template_id']) ? intval($_POST['sms_template_id']) : 0;
$new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';

// Vérifier que les données requises sont présentes
if ($repair_id <= 0 || $sms_template_id <= 0 || empty($new_status)) {
    $response['error'] = 'Données invalides';
    echo json_encode($response);
    exit;
}

try {
    // Début de la transaction
    $pdo->beginTransaction();
    
    // 1. Récupérer les informations de la réparation et du client
    $stmt = $pdo->prepare("
        SELECT r.*, c.telephone, c.nom, c.prenom, c.email 
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$repair_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        throw new Exception('Réparation non trouvée');
    }
    
    // 2. Récupérer le template SMS
    $stmt = $pdo->prepare("SELECT * FROM sms_templates WHERE id = ?");
    $stmt->execute([$sms_template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        throw new Exception('Template SMS non trouvé');
    }
    
    // 3. Récupérer le code du statut
    $stmt = $pdo->prepare("SELECT code FROM statuts WHERE code = ? OR id = ?");
    $stmt->execute([$new_status, $new_status]);
    $statut = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$statut) {
        throw new Exception('Statut invalide');
    }
    
    $statut_code = $statut['code'];
    
    // 4. Mettre à jour le statut de la réparation
    $stmt = $pdo->prepare("
        UPDATE reparations 
        SET statut = ?, date_modification = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$statut_code, $repair_id]);
    
    // 5. Préparer le message SMS
    $message = $template['contenu'];
    
    // Remplacer les variables dans le message
    $message = str_replace('{ID}', $repair_id, $message);
    $message = str_replace('{NOM}', $reparation['nom'], $message);
    $message = str_replace('{PRENOM}', $reparation['prenom'], $message);
    $message = str_replace('{APPAREIL}', $reparation['type_appareil'], $message);
    $message = str_replace('{MARQUE}', $reparation['marque'], $message);
    $message = str_replace('{MODELE}', $reparation['modele'], $message);
    $message = str_replace('{PRIX}', $reparation['prix'], $message);
    
    // 6. Envoyer le SMS
    // Utiliser la fonction send_sms définie dans functions.php
    $sms_success = false;
    $sms_error = null;
    
    try {
        if (function_exists('send_sms')) {
            $sms_result = send_sms($reparation['telephone'], $message);
            
            if (isset($sms_result['success']) && $sms_result['success'] === true) {
                $sms_success = true;
            } else {
                $sms_error = isset($sms_result['message']) ? $sms_result['message'] : 'Erreur inconnue';
                error_log("Erreur SMS: " . $sms_error);
            }
        } else {
            // Simuler l'envoi si la fonction n'existe pas (pour le développement)
            error_log("Simulation d'envoi de SMS à {$reparation['telephone']}: $message");
            $sms_success = true; // Considérer comme un succès en mode développement
        }
    } catch (Exception $sms_exception) {
        $sms_error = $sms_exception->getMessage();
        error_log("Exception lors de l'envoi SMS: " . $sms_error);
    }
    
    // Ne pas bloquer le processus si l'envoi SMS échoue, mais enregistrer l'erreur
    if (!$sms_success) {
        $response['sms_error'] = $sms_error;
    }
    
    // 7. Enregistrer l'historique du SMS
    $stmt = $pdo->prepare("
        INSERT INTO sms_history (client_id, reparation_id, telephone, message, template_id, date_envoi, statut)
        VALUES (?, ?, ?, ?, ?, NOW(), 'envoyé')
    ");
    $stmt->execute([
        $reparation['client_id'], 
        $repair_id, 
        $reparation['telephone'], 
        $message, 
        $sms_template_id
    ]);
    
    // 8. Enregistrer le changement de statut dans l'historique
    $stmt = $pdo->prepare("
        INSERT INTO historique_reparations (reparation_id, utilisateur_id, action, ancien_statut, nouveau_statut, date_action)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $repair_id,
        $_SESSION['user_id'],
        'changement_statut',
        $reparation['statut'],
        $statut_code
    ]);
    
    // Commit de la transaction
    $pdo->commit();
    
    // Réponse de succès
    $response['success'] = true;
    if ($sms_success) {
        $response['message'] = 'Devis envoyé avec succès et statut mis à jour';
    } else {
        $response['message'] = 'Statut mis à jour, mais l\'envoi du SMS a échoué: ' . $sms_error;
        // On considère quand même l'opération comme un succès car le statut a été mis à jour
    }
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Enregistrer l'erreur et la renvoyer
    error_log('Erreur dans send_devis.php: ' . $e->getMessage());
    $response['error'] = $e->getMessage();
}

// Renvoyer la réponse en JSON
echo json_encode($response); 