<?php
// 🔧 Configuration de session et sécurité
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 📋 Vérifier la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

// 📊 Récupérer les données
$reparation_id = $_POST['reparation_id'] ?? '';
$statut_id = $_POST['statut_id'] ?? '';

// ✅ Validation des données
if (empty($reparation_id) || empty($statut_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de réparation et ID de statut requis'
    ]);
    exit;
}

// 🔢 Validation des IDs (doivent être numériques)
if (!is_numeric($reparation_id) || !is_numeric($statut_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'IDs invalides'
    ]);
    exit;
}

try {
    // 🗄️ Utiliser la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('❌ Erreur de connexion à la base de données du magasin');
    }
    
    // 🔍 Vérifier que la réparation existe
    $checkRepairSQL = "SELECT id, statut FROM reparations WHERE id = ?";
    $checkRepairStmt = $shop_pdo->prepare($checkRepairSQL);
    $checkRepairStmt->execute([$reparation_id]);
    $reparation = $checkRepairStmt->fetch(PDO::FETCH_ASSOC);

    if (!$reparation) {
        echo json_encode([
            'success' => false,
            'message' => 'Réparation non trouvée'
        ]);
        exit;
    }
    
    // 🔍 Vérifier que le statut existe et récupérer son nom
    $checkStatutSQL = "SELECT id, nom, code FROM statuts WHERE id = ? AND est_actif = 1";
    $checkStatutStmt = $shop_pdo->prepare($checkStatutSQL);
    $checkStatutStmt->execute([$statut_id]);
    $statut = $checkStatutStmt->fetch(PDO::FETCH_ASSOC);

    if (!$statut) {
        echo json_encode([
            'success' => false,
            'message' => 'Statut non trouvé ou inactif'
        ]);
        exit;
    }
    
    // 📝 Commencer une transaction
    $shop_pdo->beginTransaction();
    
    try {
        // 🔄 Mettre à jour le statut de la réparation
        $updateSQL = "UPDATE reparations SET statut = ?, date_modification = NOW() WHERE id = ?";
        $updateStmt = $shop_pdo->prepare($updateSQL);
        $updateResult = $updateStmt->execute([$statut['nom'], $reparation_id]);

        if (!$updateResult) {
            throw new Exception('Erreur lors de la mise à jour du statut');
        }
        
        // 📝 Enregistrer le changement dans les logs si nécessaire (optionnel)
        try {
        if (isset($_SESSION['user_id'])) {
                // Vérifier d'abord si la table existe
                $tableCheckSQL = "SHOW TABLES LIKE 'reparation_logs'";
                $tableCheckStmt = $shop_pdo->prepare($tableCheckSQL);
                $tableCheckStmt->execute();
                $tableExists = $tableCheckStmt->fetch();
                
                if ($tableExists) {
                    // Vérifier la structure de la table
                    $columnCheckSQL = "SHOW COLUMNS FROM reparation_logs LIKE 'user_id'";
                    $columnCheckStmt = $shop_pdo->prepare($columnCheckSQL);
                    $columnCheckStmt->execute();
                    $columnExists = $columnCheckStmt->fetch();
                    
                    if ($columnExists) {
            $logSQL = "INSERT INTO reparation_logs (reparation_id, user_id, action_type, statut_avant, statut_apres, details, date_action) 
                       VALUES (?, ?, 'changement_statut', ?, ?, 'Changement de statut via modal', NOW())";
            $logStmt = $shop_pdo->prepare($logSQL);
            $logStmt->execute([
                $reparation_id,
                $_SESSION['user_id'],
                $reparation['statut'],
                $statut['nom']
            ]);
                    } else {
                        // Log simple sans user_id si la colonne n'existe pas
                        $logSQL = "INSERT INTO reparation_logs (reparation_id, action_type, statut_avant, statut_apres, details, date_action) 
                                   VALUES (?, 'changement_statut', ?, ?, 'Changement de statut via modal', NOW())";
                        $logStmt = $shop_pdo->prepare($logSQL);
                        $logStmt->execute([
                            $reparation_id,
                            $reparation['statut'],
                            $statut['nom']
                        ]);
                    }
                } else {
                    // La table n'existe pas, on passe le logging
                    error_log("ℹ️ Table reparation_logs non trouvée, passage du logging");
                }
            }
        } catch (PDOException $logException) {
            // Erreur de logging, mais on continue (le logging n'est pas critique)
            error_log("⚠️ Erreur lors du logging (non critique): " . $logException->getMessage());
        }
        
        // ✅ Valider la transaction
        $shop_pdo->commit();
        
        // 📝 Log pour debug
        error_log("✅ Statut mis à jour pour réparation #$reparation_id - Ancien: '{$reparation['statut']}' → Nouveau: '{$statut['nom']}'");
        
        // 🚀 Réponse de succès
        echo json_encode([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'data' => [
                'reparation_id' => $reparation_id,
                'ancien_statut' => $reparation['statut'],
                'nouveau_statut' => $statut['nom'],
                'statut_code' => $statut['code'],
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        // ❌ Annuler la transaction en cas d'erreur
        $shop_pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("❌ Erreur PDO dans update_statut_reparation.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données',
        'error' => $e->getMessage()
    ]);

} catch (Exception $e) {
    error_log("❌ Erreur générale dans update_statut_reparation.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour du statut',
        'error' => $e->getMessage()
    ]);
}
?> 