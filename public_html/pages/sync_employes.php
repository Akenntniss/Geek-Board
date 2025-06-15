<?php
// Inclure la configuration de la base de données
require_once('config/database.php');

// Afficher les erreurs pendant le développement
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Début de la synchronisation utilisateurs/employés...\n";

try {
    // Récupérer tous les utilisateurs
    $stmt = $pdo->query("SELECT id, nom, prenom, email FROM utilisateurs");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Nombre d'utilisateurs trouvés: " . count($users) . "\n";
    
    // Pour chaque utilisateur, vérifier s'il existe un employé correspondant
    foreach ($users as $user) {
        // Vérifier si un employé avec cet email existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM employes WHERE email = ?");
        $stmt->execute([$user['email']]);
        $exists = (int)$stmt->fetchColumn();
        
        if ($exists > 0) {
            echo "L'employé avec l'email {$user['email']} existe déjà.\n";
            continue;
        }
        
        // Créer l'employé avec les mêmes données que l'utilisateur
        $stmt = $pdo->prepare("
            INSERT INTO employes (nom, prenom, email, statut)
            VALUES (?, ?, ?, 'actif')
        ");
        
        $result = $stmt->execute([
            $user['nom'], 
            $user['prenom'], 
            $user['email']
        ]);
        
        if ($result) {
            echo "Employé créé avec succès pour l'utilisateur {$user['prenom']} {$user['nom']} ({$user['email']}).\n";
        } else {
            echo "Erreur lors de la création de l'employé pour {$user['email']}.\n";
        }
    }
    
    echo "Synchronisation terminée.\n";
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
    exit;
} 