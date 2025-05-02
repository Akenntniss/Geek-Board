<?php
// Script pour supprimer les données de test
// Connexion à la base de données
$host = 'srv931.hstgr.io';
$port = '3306';
$dbname = 'u139954273_Vscodetest';
$username = 'u139954273_Vscodetest';
$password = 'Maman01#';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie.<br>";
    
    // Début de la transaction
    $pdo->beginTransaction();
    
    // 1. Nettoyage des logs (journal_actions)
    $stmt = $pdo->prepare("TRUNCATE TABLE journal_actions");
    $stmt->execute();
    echo "Table 'journal_actions' vidée avec succès.<br>";
    
    // 2. Nettoyage des réparations
    $stmt = $pdo->prepare("DELETE FROM reparations");
    $stmt->execute();
    echo "Table 'reparations' vidée avec succès.<br>";
    
    // 3. Nettoyage des logs de réparation
    $stmt = $pdo->prepare("DELETE FROM reparation_logs");
    $stmt->execute();
    echo "Table 'reparation_logs' vidée avec succès.<br>";
    
    // 4. Nettoyage des clients
    $stmt = $pdo->prepare("DELETE FROM clients");
    $stmt->execute();
    echo "Table 'clients' vidée avec succès.<br>";
    
    // 5. Nettoyage des commandes
    // Commandes fournisseurs
    $stmt = $pdo->prepare("DELETE FROM commandes_fournisseurs");
    $stmt->execute();
    echo "Table 'commandes_fournisseurs' vidée avec succès.<br>";
    
    // Lignes de commande fournisseur
    $stmt = $pdo->prepare("DELETE FROM lignes_commande_fournisseur");
    $stmt->execute();
    echo "Table 'lignes_commande_fournisseur' vidée avec succès.<br>";
    
    // Commandes pièces
    $stmt = $pdo->prepare("DELETE FROM commandes_pieces");
    $stmt->execute();
    echo "Table 'commandes_pieces' vidée avec succès.<br>";
    
    // 6. Nettoyage des logs SMS
    $stmt = $pdo->prepare("DELETE FROM sms_logs");
    $stmt->execute();
    echo "Table 'sms_logs' vidée avec succès.<br>";
    
    // 7. Nettoyage des logs de réparation SMS
    $stmt = $pdo->prepare("DELETE FROM reparation_sms");
    $stmt->execute();
    echo "Table 'reparation_sms' vidée avec succès.<br>";
    
    // 8. Nettoyage de l'historique
    // Historique des soldes
    $stmt = $pdo->prepare("DELETE FROM historique_soldes");
    $stmt->execute();
    echo "Table 'historique_soldes' vidée avec succès.<br>";
    
    // Historique du stock
    $stmt = $pdo->prepare("DELETE FROM stock_history");
    $stmt->execute();
    echo "Table 'stock_history' vidée avec succès.<br>";
    
    // 9. Nettoyage des photos de réparation
    $stmt = $pdo->prepare("DELETE FROM photos_reparation");
    $stmt->execute();
    echo "Table 'photos_reparation' vidée avec succès.<br>";
    
    // Validation de la transaction
    $pdo->commit();
    
    echo "<br><strong>Toutes les données de test ont été supprimées avec succès.</strong>";
    
} catch (PDOException $e) {
    // En cas d'erreur, annulation de la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Erreur: " . $e->getMessage();
}
?> 