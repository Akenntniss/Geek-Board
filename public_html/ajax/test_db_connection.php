<?php
/**
 * Script de test pour vérifier la connexion à la base de données
 * À utiliser pour le débogage uniquement
 */

// Désactiver l'affichage des erreurs PHP (pour retourner uniquement du JSON)
ini_set('display_errors', 0);
error_reporting(0);

// Définir l'en-tête JSON avant tout
header('Content-Type: application/json');

try {
    // Tenter de charger le fichier de configuration
    if (!file_exists('../config/database.php')) {
        throw new Exception("Le fichier de configuration de la base de données est introuvable");
    }
    
    // Charger les constantes de la base de données
    require_once '../config/database.php';
    
    // Vérifier si les constantes requises sont définies
    $required_constants = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    $missing_constants = [];
    
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            $missing_constants[] = $constant;
        }
    }
    
    if (!empty($missing_constants)) {
        throw new Exception("Constantes manquantes: " . implode(', ', $missing_constants));
    }
    
    // Tenter la connexion à la base de données
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Vérifier si la table bug_reports existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'bug_reports'");
    if ($stmt->rowCount() === 0) {
        throw new Exception("La table bug_reports n'existe pas dans la base de données");
    }
    
    // Vérifier la structure de la table
    $stmt = $pdo->query("DESCRIBE bug_reports");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = ['id', 'user_id', 'description', 'page_url', 'user_agent', 'priorite', 'status', 'date_creation'];
    $missing_columns = [];
    
    foreach ($required_columns as $column) {
        if (!in_array($column, $columns)) {
            $missing_columns[] = $column;
        }
    }
    
    if (!empty($missing_columns)) {
        throw new Exception("Colonnes manquantes dans la table bug_reports: " . implode(', ', $missing_columns));
    }
    
    // Tout est bon
    echo json_encode([
        'success' => true,
        'message' => 'Connexion à la base de données établie avec succès',
        'database' => [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'tables_checked' => ['bug_reports'],
            'columns' => $columns
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
} 