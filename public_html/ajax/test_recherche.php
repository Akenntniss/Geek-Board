<?php
// Script de diagnostic pour recherche_avancee.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuration du fichier de log spécifique
$log_file = __DIR__ . '/../logs/debug/test_recherche.log';
ini_set('error_log', $log_file);

error_log("=== DÉBUT TEST RECHERCHE ===");
error_log("Date: " . date('Y-m-d H:i:s'));

// Inclure la configuration de la base de données
// Utiliser le chemin absolu pour être sûr
require_once __DIR__ . '/../config/database.php';

// Vérifier si PDO est disponible
if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log("ERREUR: PDO non disponible");
    echo "Erreur: PDO non disponible";
    exit;
}

// Terme de test
$terme = 'test';
$terme_wildcard = "%$terme%";

error_log("Terme de recherche: $terme");
error_log("Terme avec wildcards: $terme_wildcard");

// Vérifier la structure de la table reparations
try {
    $reparationColumns = $pdo->query("DESCRIBE reparations")->fetchAll(PDO::FETCH_COLUMN);
    error_log("Colonnes de la table reparations: " . implode(", ", $reparationColumns));
    
    // Vérifier les données actuelles de la table
    $count = $pdo->query("SELECT COUNT(*) FROM reparations")->fetchColumn();
    error_log("Nombre de réparations dans la base: $count");
    
    if ($count > 0) {
        $sample = $pdo->query("SELECT * FROM reparations LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        error_log("Exemple de réparation: " . json_encode($sample));
    }
} catch (Exception $e) {
    error_log("ERREUR lors de la vérification de la structure: " . $e->getMessage());
}

// Test 1: Requête simple avec un seul paramètre
try {
    error_log("TEST 1: Requête simple avec un seul paramètre");
    $sql = "SELECT id FROM reparations WHERE id LIKE ?";
    error_log("SQL: $sql");
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$terme_wildcard]);
    $count = $stmt->rowCount();
    
    error_log("Résultat: $count réparations trouvées");
    error_log("TEST 1: OK");
} catch (Exception $e) {
    error_log("TEST 1 ERREUR: " . $e->getMessage());
}

// Test 2: Requête clients simple
try {
    error_log("TEST 2: Requête clients");
    $sql = "SELECT id, nom, prenom, telephone FROM clients WHERE nom LIKE ? OR prenom LIKE ? OR telephone LIKE ? LIMIT 5";
    error_log("SQL: $sql");
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$terme_wildcard, $terme_wildcard, $terme_wildcard]);
    $count = $stmt->rowCount();
    
    error_log("Résultat: $count clients trouvés");
    error_log("TEST 2: OK");
} catch (Exception $e) {
    error_log("TEST 2 ERREUR: " . $e->getMessage());
}

// Test 3: Requête réparations dynamique
try {
    error_log("TEST 3: Requête réparations dynamique");
    
    // Construire la requête en fonction des colonnes existantes
    $sql = "SELECT r.id, r.client_id, r.statut, r.date_reception, c.nom as client_nom, c.prenom as client_prenom FROM reparations r LEFT JOIN clients c ON r.client_id = c.id WHERE r.id LIKE ?";
    
    $params = [$terme_wildcard];
    
    // Ajouter des conditions selon les colonnes existantes
    if (in_array('type_appareil', $reparationColumns)) {
        $sql .= " OR r.type_appareil LIKE ?";
        $params[] = $terme_wildcard;
    }
    
    if (in_array('modele', $reparationColumns)) {
        $sql .= " OR r.modele LIKE ?";
        $params[] = $terme_wildcard;
    }
    
    if (in_array('description_probleme', $reparationColumns)) {
        $sql .= " OR r.description_probleme LIKE ?";
        $params[] = $terme_wildcard;
    }
    
    $sql .= " OR c.nom LIKE ? OR c.prenom LIKE ? LIMIT 10";
    $params[] = $terme_wildcard;
    $params[] = $terme_wildcard;
    
    error_log("SQL: $sql");
    error_log("Paramètres: " . count($params) . " - " . implode(", ", $params));
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $count = $stmt->rowCount();
    
    error_log("Résultat: $count réparations trouvées");
    error_log("TEST 3: OK");
} catch (Exception $e) {
    error_log("TEST 3 ERREUR: " . $e->getMessage());
}

// Test 4: Reconstruction de la requête problématique
try {
    error_log("TEST 4: Reconstruction de la requête problématique");
    
    $sql = "
        SELECT r.id, 
               r.client_id,
               " . (in_array('type_appareil', $reparationColumns) ? "r.type_appareil as appareil," : "'' as appareil,") . "
               " . (in_array('marque', $reparationColumns) ? "r.marque," : "'' as marque,") . "
               " . (in_array('modele', $reparationColumns) ? "r.modele," : "'' as modele,") . "
               " . (in_array('description_probleme', $reparationColumns) ? "r.description_probleme as probleme," : "'' as probleme,") . "
               r.date_reception, 
               r.statut,
               c.nom as client_nom, 
               c.prenom as client_prenom, 
               c.id as client_id
        FROM reparations r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE r.id LIKE ?";
    
    $params = [$terme_wildcard];
    
    // Ajouter les conditions de recherche en fonction des colonnes existantes
    if (in_array('type_appareil', $reparationColumns)) {
        $sql .= " OR r.type_appareil LIKE ?";
        $params[] = $terme_wildcard;
    }
    if (in_array('modele', $reparationColumns)) {
        $sql .= " OR r.modele LIKE ?";
        $params[] = $terme_wildcard;
    }
    if (in_array('description_probleme', $reparationColumns)) {
        $sql .= " OR r.description_probleme LIKE ?";
        $params[] = $terme_wildcard;
    }
    
    $sql .= " OR c.nom LIKE ? OR c.prenom LIKE ? ORDER BY r.date_reception DESC LIMIT 10";
    $params[] = $terme_wildcard;
    $params[] = $terme_wildcard;
    
    error_log("SQL finale: " . $sql);
    error_log("Nombre de paramètres: " . count($params));
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("TEST 4: OK - " . count($results) . " résultats");
    if (count($results) > 0) {
        error_log("Premier résultat: " . json_encode($results[0]));
    }
} catch (Exception $e) {
    error_log("TEST 4 ERREUR: " . $e->getMessage());
}

echo "Tests terminés. Consultez le fichier de log pour les résultats: $log_file";
?> 