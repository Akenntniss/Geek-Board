<?php
// 🔧 Script de débogage pour les statuts de réparation
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('❌ Erreur de connexion à la base de données du magasin');
    }
    
    $debug_info = [
        'shop_id' => $_SESSION['shop_id'] ?? 'non défini',
        'database_connected' => true,
        'tables_check' => [],
        'statuts_data' => [],
        'actions_performed' => []
    ];
    
    // 📊 Vérifier si la table statuts existe
    $tables = $shop_pdo->query("SHOW TABLES LIKE 'statuts'")->fetchAll();
    $statuts_table_exists = count($tables) > 0;
    $debug_info['tables_check']['statuts_exists'] = $statuts_table_exists;
    
    if (!$statuts_table_exists) {
        // 🚀 Créer la table statuts si elle n'existe pas
        $create_table_sql = "
            CREATE TABLE IF NOT EXISTS statuts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                code VARCHAR(50) UNIQUE,
                couleur VARCHAR(7) DEFAULT '#007bff',
                est_actif TINYINT(1) DEFAULT 1,
                ordre INT DEFAULT 0,
                date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        
        $shop_pdo->exec($create_table_sql);
        $debug_info['actions_performed'][] = 'Table statuts créée';
        
        // 🎯 Insérer des statuts par défaut
        $statuts_defaut = [
            ['nom' => 'Nouvelle intervention', 'code' => 'nouvelle', 'couleur' => '#17a2b8', 'ordre' => 1],
            ['nom' => 'En cours de diagnostic', 'code' => 'diagnostic', 'couleur' => '#ffc107', 'ordre' => 2],
            ['nom' => 'Devis en attente', 'code' => 'devis_attente', 'couleur' => '#fd7e14', 'ordre' => 3],
            ['nom' => 'Devis validé', 'code' => 'devis_valide', 'couleur' => '#198754', 'ordre' => 4],
            ['nom' => 'Réparation en cours', 'code' => 'reparation', 'couleur' => '#0d6efd', 'ordre' => 5],
            ['nom' => 'Attente pièce', 'code' => 'attente_piece', 'couleur' => '#6f42c1', 'ordre' => 6],
            ['nom' => 'Réparation terminée', 'code' => 'terminee', 'couleur' => '#20c997', 'ordre' => 7],
            ['nom' => 'Prêt à récupérer', 'code' => 'pret', 'couleur' => '#28a745', 'ordre' => 8],
            ['nom' => 'Récupéré', 'code' => 'recupere', 'couleur' => '#6c757d', 'ordre' => 9],
            ['nom' => 'Irréparable', 'code' => 'irreparable', 'couleur' => '#dc3545', 'ordre' => 10],
            ['nom' => 'Annulé', 'code' => 'annule', 'couleur' => '#343a40', 'ordre' => 11]
        ];
        
        $insert_sql = "INSERT INTO statuts (nom, code, couleur, ordre, est_actif) VALUES (?, ?, ?, ?, 1)";
        $stmt = $shop_pdo->prepare($insert_sql);
        
        foreach ($statuts_defaut as $statut) {
            $stmt->execute([$statut['nom'], $statut['code'], $statut['couleur'], $statut['ordre']]);
        }
        
        $debug_info['actions_performed'][] = count($statuts_defaut) . ' statuts par défaut insérés';
    }
    
    // 📋 Récupérer tous les statuts existants
    $sql = "SELECT id, nom, code, couleur, est_actif, ordre FROM statuts ORDER BY ordre ASC, nom ASC";
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute();
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $debug_info['statuts_data'] = $statuts;
    $debug_info['statuts_count'] = count($statuts);
    
    // 📊 Récupérer quelques réparations pour voir les statuts utilisés
    $reparations_sql = "SELECT DISTINCT statut FROM reparations WHERE statut IS NOT NULL LIMIT 10";
    try {
        $reparations_stmt = $shop_pdo->prepare($reparations_sql);
        $reparations_stmt->execute();
        $statuts_utilises = $reparations_stmt->fetchAll(PDO::FETCH_COLUMN);
        $debug_info['statuts_utilises_reparations'] = $statuts_utilises;
    } catch (Exception $e) {
        $debug_info['statuts_utilises_reparations'] = 'Erreur: ' . $e->getMessage();
    }
    
    // ✅ Résultat final
    echo json_encode([
        'success' => true,
        'debug_info' => $debug_info,
        'message' => 'Diagnostic terminé avec succès'
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur PDO: ' . $e->getMessage(),
        'debug_info' => $debug_info ?? []
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur générale: ' . $e->getMessage(),
        'debug_info' => $debug_info ?? []
    ], JSON_PRETTY_PRINT);
}
?> 