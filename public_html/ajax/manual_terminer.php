<?php
// Traitement manuel de la demande de fin de réparation
require_once('../config/database.php');
require_once('../includes/functions.php');

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    set_message("Vous devez être connecté pour effectuer cette action.", "danger");
    header('Location: ../index.php');
    exit;
}

// Récupérer les données du formulaire
$reparation_id = isset($_POST['reparation_id']) ? (int)$_POST['reparation_id'] : 0;
$nouveau_statut = isset($_POST['nouveau_statut']) ? $_POST['nouveau_statut'] : '';

// Vérifier que les données nécessaires sont présentes
if (empty($reparation_id) || empty($nouveau_statut)) {
    set_message("Données manquantes pour terminer la réparation.", "danger");
    header('Location: ../index.php?page=statut_rapide&id=' . $reparation_id);
    exit;
}

// Variables utilisateur
$user_id = $_SESSION['user_id'];
$employe_id = $user_id;

try {
    // Vérifier si l'attribution existe
    $stmt = $pdo->prepare("
        SELECT ra.id, ra.statut_avant, ra.est_principal, r.statut 
        FROM reparation_attributions ra 
        JOIN reparations r ON ra.reparation_id = r.id 
        WHERE ra.reparation_id = ? AND ra.employe_id = ? AND ra.date_fin IS NULL
    ");
    $stmt->execute([$reparation_id, $employe_id]);
    $attribution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attribution) {
        set_message("Vous ne travaillez pas actuellement sur cette réparation", "danger");
        header('Location: ../index.php?page=statut_rapide&id=' . $reparation_id);
        exit;
    }
    
    $statut_avant = $attribution['statut'];
    $statut_apres = $nouveau_statut;
    
    // Valider que le statut est valide
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM statuts WHERE code = ?");
    $stmt->execute([$statut_apres]);
    $statut_valide = ($stmt->fetchColumn() > 0);
    
    // Si le statut n'est pas valide, utiliser un statut par défaut
    if (!$statut_valide) {
        $statut_apres = 'reparation_effectue';
        error_log("Statut invalide fourni: " . $nouveau_statut . ". Utilisation du statut par défaut: " . $statut_apres);
    }
    
    // Mettre fin à l'attribution
    $stmt = $pdo->prepare("UPDATE reparation_attributions SET date_fin = NOW(), statut_apres = ? WHERE id = ?");
    $result = $stmt->execute([$statut_apres, $attribution['id']]);
    
    if ($result) {
        // Si c'était le principal, mettre à jour le statut de la réparation
        if ($attribution['est_principal'] == 1) {
            $stmt = $pdo->prepare("UPDATE reparations SET statut = ? WHERE id = ?");
            $stmt->execute([$statut_apres, $reparation_id]);
            
            // Mise à jour de l'utilisateur pour indiquer qu'il n'est plus occupé
            $stmt = $pdo->prepare("UPDATE users SET techbusy = 0, active_repair_id = NULL WHERE id = ?");
            $stmt->execute([$employe_id]);
        }
        
        // Enregistrer l'action dans les logs
        $stmt = $pdo->prepare("INSERT INTO reparation_logs (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $reparation_id, 
            $employe_id, 
            'terminer', 
            $statut_avant, 
            $statut_apres, 
            'Réparation terminée' . ($attribution['est_principal'] ? ' en tant que principal' : ' en tant qu\'assistant')
        ]);
        
        set_message("Réparation terminée avec succès!", "success");
    } else {
        set_message("Erreur lors de la fin de la réparation", "danger");
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la fin de la réparation: " . $e->getMessage(), "danger");
    error_log("Erreur lors de la fin de la réparation: " . $e->getMessage());
}

// Rediriger vers la page statut_rapide
header('Location: ../index.php?page=statut_rapide&id=' . $reparation_id);
exit; 