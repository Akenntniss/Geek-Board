<?php
// Fichier autonome pour démarrer une réparation
header("Content-Type: application/json");

// Activer le logging pour le débogage
error_log("start_repair.php appelé à " . date('Y-m-d H:i:s'));
file_put_contents("../logs/repair_start.log", "Requête reçue: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents("../logs/repair_start.log", "POST data: " . file_get_contents("php://input") . "\n", FILE_APPEND);

// Inclure la configuration de la base de données
require_once("../config/database.php");
require_once("../includes/functions.php");

// Démarrer la session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Récupérer les données POST
$data = json_decode(file_get_contents("php://input"), true);
$reparation_id = isset($data["reparation_id"]) ? intval($data["reparation_id"]) : 0;
$user_id = isset($data["user_id"]) ? intval($data["user_id"]) : 0;

// Log des données reçues
file_put_contents("../logs/repair_start.log", "Données décodées: reparation_id=$reparation_id, user_id=$user_id\n", FILE_APPEND);

// Vérifier que les données nécessaires sont présentes
if (empty($reparation_id) || empty($user_id)) {
    echo json_encode([
        "success" => false,
        "message" => "Données manquantes (ID réparation ou utilisateur)"
    ]);
    exit;
}

try {
    // Vérifier si la réparation existe
    $stmt = $shop_pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        echo json_encode([
            "success" => false,
            "message" => "Réparation non trouvée"
        ]);
        exit;
    }
    
    $statut_avant = $reparation["statut"];
    file_put_contents("../logs/repair_start.log", "Statut avant: $statut_avant\n", FILE_APPEND);
    
    // Déterminer le nouveau statut
    $statut_apres = "en_cours_intervention";
    if ($statut_avant === "nouveau_diagnostique") {
        $statut_apres = "en_cours_diagnostique";
    }
    
    // Vérifier si l'employé a déjà commencé cette réparation
    $stmt = $shop_pdo->prepare("SELECT id FROM reparation_attributions WHERE reparation_id = ? AND employe_id = ? AND date_fin IS NULL");
    $stmt->execute([$reparation_id, $user_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Vous travaillez déjà sur cette réparation"
        ]);
        exit;
    }
    
    // Mise à jour du statut de la réparation
    $stmt = $shop_pdo->prepare("UPDATE reparations SET statut = ?, employe_id = ? WHERE id = ?");
    $stmt->execute([$statut_apres, $user_id, $reparation_id]);
    
    // Mise à jour de l'utilisateur
    $stmt = $shop_pdo->prepare("UPDATE users SET techbusy = 1, active_repair_id = ? WHERE id = ?");
    $stmt->execute([$reparation_id, $user_id]);
    
    // Créer l'attribution
    $stmt = $shop_pdo->prepare("INSERT INTO reparation_attributions (reparation_id, employe_id, statut_avant, est_principal) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$reparation_id, $user_id, $statut_avant, 1]);
    
    if ($result) {
        // Enregistrer l'action dans les logs
        $stmt = $shop_pdo->prepare("INSERT INTO reparation_logs (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $reparation_id, 
            $user_id, 
            "demarrage", 
            $statut_avant, 
            $statut_apres, 
            "Réparation démarrée en tant que principal via start_repair.php"
        ]);
        
        file_put_contents("../logs/repair_start.log", "Réparation démarrée avec succès: $reparation_id pour user $user_id\n", FILE_APPEND);
        
        echo json_encode([
            "success" => true,
            "message" => "Réparation démarrée avec succès",
            "new_status" => $statut_apres
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Erreur lors du démarrage de la réparation"
        ]);
    }
} catch (PDOException $e) {
    file_put_contents("../logs/repair_start.log", "Erreur PDO: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        "success" => false,
        "message" => "Erreur de base de données: " . $e->getMessage()
    ]);
} 