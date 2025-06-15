<?php
// Script pour initialiser la table des employés avec un employé par défaut si elle est vide
require_once('../config/database.php');

try {
    // Vérifier si la table est vide
    $stmt = $shop_pdo->query("SELECT COUNT(*) FROM employes");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // La table est vide, créer un employé par défaut
        $stmt = $shop_pdo->prepare("
            INSERT INTO employes 
            (nom, prenom, email, telephone, date_embauche, statut, created_at, updated_at) 
            VALUES 
            (?, ?, ?, ?, CURRENT_DATE(), 'actif', NOW(), NOW())
        ");
        
        // Insérer un administrateur par défaut
        $stmt->execute([
            'Admin', 
            'Système', 
            'admin@votredomaine.com',
            '0000000000'
        ]);
        
        // Insérer un technicien par défaut
        $stmt->execute([
            'Technicien', 
            'Système', 
            'tech@votredomaine.com',
            '0000000000'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Employés par défaut créés avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'La table des employés contient déjà des données'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'initialisation des employés: ' . $e->getMessage()
    ]);
} 