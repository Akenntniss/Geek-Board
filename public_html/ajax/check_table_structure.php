<?php
// Script pour vérifier la structure de la table commandes_pieces
header('Content-Type: text/plain; charset=utf-8');

echo "=== VÉRIFICATION STRUCTURE TABLE COMMANDES_PIECES ===\n\n";

try {
    // Connexion à la base de données
    $host = '191.96.63.103';
    $port = '3306';
    $dbname = 'u139954273_pscannes';
    $username = 'u139954273_pscannes';
    $password = 'Merguez01#';
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // Vérifier la structure de la table commandes_pieces
    echo "📋 STRUCTURE DE LA TABLE 'commandes_pieces':\n";
    echo str_repeat("-", 80) . "\n";
    
    $stmt = $pdo->query("DESCRIBE commandes_pieces");
    $columns = $stmt->fetchAll();
    
    if (empty($columns)) {
        echo "❌ La table 'commandes_pieces' n'existe pas ou est vide\n";
    } else {
        printf("%-20s %-15s %-8s %-8s %-15s %-10s\n", 
               "COLONNE", "TYPE", "NULL", "KEY", "DEFAULT", "EXTRA");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($columns as $column) {
            printf("%-20s %-15s %-8s %-8s %-15s %-10s\n",
                   $column['Field'],
                   $column['Type'],
                   $column['Null'],
                   $column['Key'],
                   $column['Default'] ?? 'NULL',
                   $column['Extra']
            );
        }
    }
    
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "📊 NOMBRE TOTAL DE COLONNES: " . count($columns) . "\n\n";
    
    // Vérifier si certaines colonnes spécifiques existent
    $requiredColumns = ['id', 'client_id', 'fournisseur_id', 'nom_piece', 'code_barre', 
                       'prix_estime', 'quantite', 'statut', 'reparation_id', 'date_creation', 'user_id'];
    
    echo "🔍 VÉRIFICATION DES COLONNES REQUISES:\n";
    echo str_repeat("-", 50) . "\n";
    
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $col) {
        $exists = in_array($col, $existingColumns);
        echo sprintf("%-20s %s\n", $col, $exists ? "✅ EXISTE" : "❌ MANQUANTE");
    }
    
    echo "\n" . str_repeat("-", 50) . "\n";
    
    // Afficher quelques exemples de données
    echo "📄 EXEMPLES DE DONNÉES (5 premières lignes):\n";
    echo str_repeat("-", 80) . "\n";
    
    $stmt = $pdo->query("SELECT * FROM commandes_pieces LIMIT 5");
    $examples = $stmt->fetchAll();
    
    if (empty($examples)) {
        echo "ℹ️  Aucune donnée dans la table\n";
    } else {
        // Afficher les en-têtes
        $headers = array_keys($examples[0]);
        echo implode(" | ", array_map(function($h) { return str_pad($h, 12); }, $headers)) . "\n";
        echo str_repeat("-", count($headers) * 15) . "\n";
        
        // Afficher les données
        foreach ($examples as $row) {
            echo implode(" | ", array_map(function($v) { 
                return str_pad(substr($v ?? 'NULL', 0, 12), 12); 
            }, $row)) . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN VÉRIFICATION ===\n";
?> 