<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Traiter les différentes actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Ajouter un nouveau produit
    if ($action === 'ajouter_produit') {
        try {
            // Vérifier que tous les champs requis sont présents
            $required_fields = ['reference', 'nom', 'prix_achat', 'prix_vente', 'quantite', 'seuil_alerte'];
            foreach ($required_fields as $field) {
                if (!isset($_POST[$field]) || $_POST[$field] === '') {
                    set_message("Le champ {$field} est requis.", 'warning');
                    redirect('inventaire');
                    exit;
                }
            }
            
            // Vérifier si la référence existe déjà
            $stmt = $pdo->prepare("SELECT id FROM produits WHERE reference = ?");
            $stmt->execute([$_POST['reference']]);
            if ($stmt->rowCount() > 0) {
                set_message("Un produit avec cette référence existe déjà.", 'warning');
                redirect('inventaire');
                exit;
            }
            
            // Préparation des données
            $data = [
                'reference' => $_POST['reference'],
                'nom' => $_POST['nom'],
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
                'prix_achat' => (float)$_POST['prix_achat'],
                'prix_vente' => (float)$_POST['prix_vente'],
                'quantite' => (int)$_POST['quantite'],
                'seuil_alerte' => (int)$_POST['seuil_alerte'],
                'status' => isset($_POST['is_temporaire']) && $_POST['is_temporaire'] == '1' ? 'temporaire' : 'normal',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Insertion dans la base de données
            $sql = "INSERT INTO produits (reference, nom, description, prix_achat, prix_vente, quantite, seuil_alerte, status, created_at, updated_at) 
                    VALUES (:reference, :nom, :description, :prix_achat, :prix_vente, :quantite, :seuil_alerte, :status, :created_at, :updated_at)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            
            // Enregistrer le mouvement de stock initial si quantité > 0
            if ($data['quantite'] > 0) {
                $produit_id = $pdo->lastInsertId();
                $motif = 'Stock initial';
                if ($data['status'] === 'temporaire') {
                    $motif .= ' (Produit temporaire)';
                }
                $sql = "INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, motif, user_id, date_mouvement) 
                        VALUES (?, 'entree', ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$produit_id, $data['quantite'], $motif, $_SESSION['user_id']]);
            }
            
            set_message("Le produit a été ajouté avec succès.", 'success');
        } catch (PDOException $e) {
            set_message("Erreur lors de l'ajout du produit: " . $e->getMessage(), 'danger');
        }
        
        redirect('inventaire');
    }
    
    // Modifier un produit existant
    if ($action === 'modifier_produit') {
        try {
            // Vérifier que tous les champs requis sont présents
            if (!isset($_POST['produit_id']) || !is_numeric($_POST['produit_id'])) {
                set_message("ID du produit invalide.", 'warning');
                redirect('inventaire');
                exit;
            }
            
            $required_fields = ['reference', 'nom', 'prix_achat', 'prix_vente', 'seuil_alerte'];
            foreach ($required_fields as $field) {
                if (!isset($_POST[$field]) || $_POST[$field] === '') {
                    set_message("Le champ {$field} est requis.", 'warning');
                    redirect('inventaire');
                    exit;
                }
            }
            
            // Vérifier que la référence n'est pas déjà utilisée par un autre produit
            $stmt = $pdo->prepare("SELECT id FROM produits WHERE reference = ? AND id != ?");
            $stmt->execute([$_POST['reference'], $_POST['produit_id']]);
            if ($stmt->rowCount() > 0) {
                set_message("Un autre produit utilise déjà cette référence.", 'warning');
                redirect('inventaire');
                exit;
            }
            
            // Préparation des données
            $data = [
                'reference' => $_POST['reference'],
                'nom' => $_POST['nom'],
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
                'prix_achat' => (float)$_POST['prix_achat'],
                'prix_vente' => (float)$_POST['prix_vente'],
                'seuil_alerte' => (int)$_POST['seuil_alerte'],
                'status' => isset($_POST['is_temporaire']) && $_POST['is_temporaire'] == '1' ? 'temporaire' : 'normal',
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => (int)$_POST['produit_id']
            ];
            
            // Mise à jour dans la base de données
            $sql = "UPDATE produits SET 
                    reference = :reference,
                    nom = :nom, 
                    description = :description,
                    prix_achat = :prix_achat,
                    prix_vente = :prix_vente,
                    seuil_alerte = :seuil_alerte,
                    status = :status,
                    updated_at = :updated_at
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            
            set_message("Le produit a été modifié avec succès.", 'success');
        } catch (PDOException $e) {
            set_message("Erreur lors de la modification du produit: " . $e->getMessage(), 'danger');
        }
        
        redirect('inventaire');
    }
    
    // Supprimer un produit
    if ($action === 'supprimer_produit') {
        try {
            if (!isset($_POST['produit_id']) || !is_numeric($_POST['produit_id'])) {
                set_message("ID du produit invalide.", 'warning');
                redirect('inventaire');
                exit;
            }
            
            $produit_id = (int)$_POST['produit_id'];
            
            // Supprimer les mouvements de stock associés
            $stmt = $pdo->prepare("DELETE FROM mouvements_stock WHERE produit_id = ?");
            $stmt->execute([$produit_id]);
            
            // Supprimer le produit
            $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
            $stmt->execute([$produit_id]);
            
            set_message("Le produit a été supprimé avec succès.", 'success');
        } catch (PDOException $e) {
            set_message("Erreur lors de la suppression du produit: " . $e->getMessage(), 'danger');
        }
        
        redirect('inventaire');
    }
    
    // Enregistrer un mouvement de stock (entrée ou sortie)
    if ($action === 'mouvement_stock') {
        try {
            // Vérifier que tous les champs requis sont présents
            if (!isset($_POST['produit_id']) || !is_numeric($_POST['produit_id'])) {
                set_message("ID du produit invalide.", 'warning');
                redirect('inventaire');
                exit;
            }
            
            if (!isset($_POST['type_mouvement']) || !in_array($_POST['type_mouvement'], ['entree', 'sortie'])) {
                set_message("Type de mouvement invalide.", 'warning');
                redirect('inventaire');
                exit;
            }
            
            if (!isset($_POST['quantite']) || !is_numeric($_POST['quantite']) || $_POST['quantite'] <= 0) {
                set_message("La quantité doit être un nombre positif.", 'warning');
                redirect('inventaire');
                exit;
            }
            
            if (!isset($_POST['motif']) || trim($_POST['motif']) === '') {
                set_message("Le motif est requis.", 'warning');
                redirect('inventaire');
                exit;
            }
            
            $produit_id = (int)$_POST['produit_id'];
            $type = $_POST['type_mouvement'];
            $quantite = (int)$_POST['quantite'];
            $motif = trim($_POST['motif']);
            
            // Vérifier le stock disponible si c'est une sortie
            if ($type === 'sortie') {
                $stmt = $pdo->prepare("SELECT quantite FROM produits WHERE id = ?");
                $stmt->execute([$produit_id]);
                $produit = $stmt->fetch();
                
                if ($produit['quantite'] < $quantite) {
                    set_message("Stock insuffisant. Il ne reste que {$produit['quantite']} unité(s).", 'warning');
                    redirect('inventaire');
                    exit;
                }
            }
            
            // Commencer une transaction
            $pdo->beginTransaction();
            
            // Enregistrer le mouvement de stock
            $sql = "INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, motif, user_id, date_mouvement) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$produit_id, $type, $quantite, $motif, $_SESSION['user_id']]);
            
            // Mettre à jour le stock du produit
            if ($type === 'entree') {
                $sql = "UPDATE produits SET quantite = quantite + ? WHERE id = ?";
            } else {
                $sql = "UPDATE produits SET quantite = quantite - ? WHERE id = ?";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$quantite, $produit_id]);
            
            // Valider la transaction
            $pdo->commit();
            
            set_message("Le mouvement de stock a été enregistré avec succès.", 'success');
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $pdo->rollBack();
            set_message("Erreur lors de l'enregistrement du mouvement: " . $e->getMessage(), 'danger');
        }
        
        redirect('inventaire');
    }
}

// Rediriger vers la page d'inventaire si aucune action valide
redirect('inventaire');
?> 