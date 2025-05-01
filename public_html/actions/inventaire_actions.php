<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'ajouter_produit':
            ajouterProduit();
            break;
            
        case 'modifier_produit':
            modifierProduit();
            break;
            
        case 'supprimer_produit':
            supprimerProduit();
            break;
            
        case 'mouvement_stock':
            mouvementStock();
            break;
            
        case 'ajouter_categorie':
            ajouterCategorie();
            break;
            
        case 'modifier_categorie':
            modifierCategorie();
            break;
            
        case 'supprimer_categorie':
            supprimerCategorie();
            break;
            
        case 'ajouter_fournisseur':
            ajouterFournisseur();
            break;
            
        case 'modifier_fournisseur':
            modifierFournisseur();
            break;
            
        case 'supprimer_fournisseur':
            supprimerFournisseur();
            break;
            
        case 'creer_commande':
            creerCommande();
            break;
            
        case 'marquer_livree':
            marquerCommandeLivree();
            break;
            
        case 'annuler_commande':
            annulerCommande();
            break;
    }
}

// Fonctions de gestion des produits
function ajouterProduit() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO produits (reference, nom, description, prix_achat, prix_vente, quantite, seuil_alerte)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['reference'],
            $_POST['nom'],
            $_POST['description'],
            $_POST['prix_achat'],
            $_POST['prix_vente'],
            $_POST['quantite'],
            $_POST['seuil_alerte']
        ]);
        
        set_message("Produit ajouté avec succès.", 'success');
    } catch (PDOException $e) {
        set_message("Erreur lors de l'ajout du produit: " . $e->getMessage(), 'danger');
    }
    
    redirect('inventaire');
}

function modifierProduit() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE produits 
            SET reference = ?, nom = ?, description = ?, prix_achat = ?, 
                prix_vente = ?, seuil_alerte = ?, categorie_id = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['reference'],
            $_POST['nom'],
            $_POST['description'],
            $_POST['prix_achat'],
            $_POST['prix_vente'],
            $_POST['seuil_alerte'],
            $_POST['categorie_id'] ?? null,
            $_POST['produit_id']
        ]);
        
        set_message("Produit modifié avec succès.", 'success');
    } catch (PDOException $e) {
        set_message("Erreur lors de la modification du produit: " . $e->getMessage(), 'danger');
    }
    
    redirect('inventaire');
}

function supprimerProduit() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
        $stmt->execute([$_POST['produit_id']]);
        
        set_message("Produit supprimé avec succès.", 'success');
    } catch (PDOException $e) {
        set_message("Erreur lors de la suppression du produit: " . $e->getMessage(), 'danger');
    }
    
    redirect('inventaire');
}

function mouvementStock() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Récupérer le produit
        $stmt = $pdo->prepare("SELECT quantite FROM produits WHERE id = ?");
        $stmt->execute([$_POST['produit_id']]);
        $produit = $stmt->fetch();
        
        if (!$produit) {
            throw new Exception("Produit non trouvé.");
        }
        
        // Calculer la nouvelle quantité
        $quantite = intval($_POST['quantite']);
        $nouvelle_quantite = $produit['quantite'];
        
        if ($_POST['type_mouvement'] === 'entree') {
            $nouvelle_quantite += $quantite;
        } else {
            $nouvelle_quantite -= $quantite;
            if ($nouvelle_quantite < 0) {
                throw new Exception("Stock insuffisant.");
            }
        }
        
        // Mettre à jour le stock
        $stmt = $pdo->prepare("UPDATE produits SET quantite = ? WHERE id = ?");
        $stmt->execute([$nouvelle_quantite, $_POST['produit_id']]);
        
        // Enregistrer le mouvement
        $stmt = $pdo->prepare("
            INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, motif, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['produit_id'],
            $_POST['type_mouvement'],
            $quantite,
            $_POST['motif'],
            $_SESSION['user_id']
        ]);
        
        $pdo->commit();
        set_message("Mouvement de stock enregistré avec succès.", 'success');
    } catch (Exception $e) {
        $pdo->rollBack();
        set_message("Erreur lors du mouvement de stock: " . $e->getMessage(), 'danger');
    }
    
    redirect('inventaire');
}

// Fonctions de gestion des catégories
function ajouterCategorie() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)");
        $stmt->execute([$_POST['nom'], $_POST['description']]);
        
        set_message("Catégorie ajoutée avec succès.", 'success');
    } catch (PDOException $e) {
        set_message("Erreur lors de l'ajout de la catégorie: " . $e->getMessage(), 'danger');
    }
    
    redirect('categories');
}

function modifierCategorie() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE categories SET nom = ?, description = ? WHERE id = ?");
        $stmt->execute([$_POST['nom'], $_POST['description'], $_POST['categorie_id']]);
        
        set_message("Catégorie modifiée avec succès.", 'success');
    } catch (PDOException $e) {
        set_message("Erreur lors de la modification de la catégorie: " . $e->getMessage(), 'danger');
    }
    
    redirect('categories');
}

function supprimerCategorie() {
    global $pdo;
    
    try {
        // Vérifier si la catégorie a des produits
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id = ?");
        $stmt->execute([$_POST['categorie_id']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            throw new Exception("Impossible de supprimer une catégorie contenant des produits.");
        }
        
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$_POST['categorie_id']]);
        
        set_message("Catégorie supprimée avec succès.", 'success');
    } catch (Exception $e) {
        set_message("Erreur lors de la suppression de la catégorie: " . $e->getMessage(), 'danger');
    }
    
    redirect('categories');
}

// Fonctions de gestion des fournisseurs
function ajouterFournisseur() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO fournisseurs (nom, email, telephone, adresse, delai_livraison, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['nom'],
            $_POST['email'],
            $_POST['telephone'],
            $_POST['adresse'],
            $_POST['delai_livraison'],
            $_POST['notes']
        ]);
        
        set_message("Fournisseur ajouté avec succès.", 'success');
    } catch (PDOException $e) {
        set_message("Erreur lors de l'ajout du fournisseur: " . $e->getMessage(), 'danger');
    }
    
    redirect('fournisseurs');
}

function modifierFournisseur() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE fournisseurs 
            SET nom = ?, email = ?, telephone = ?, adresse = ?, delai_livraison = ?, notes = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['nom'],
            $_POST['email'],
            $_POST['telephone'],
            $_POST['adresse'],
            $_POST['delai_livraison'],
            $_POST['notes'],
            $_POST['fournisseur_id']
        ]);
        
        set_message("Fournisseur modifié avec succès.", 'success');
    } catch (PDOException $e) {
        set_message("Erreur lors de la modification du fournisseur: " . $e->getMessage(), 'danger');
    }
    
    redirect('fournisseurs');
}

function supprimerFournisseur() {
    global $pdo;
    
    try {
        // Vérifier si le fournisseur a des commandes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes_fournisseurs WHERE fournisseur_id = ?");
        $stmt->execute([$_POST['fournisseur_id']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            throw new Exception("Impossible de supprimer un fournisseur ayant des commandes.");
        }
        
        $stmt = $pdo->prepare("DELETE FROM fournisseurs WHERE id = ?");
        $stmt->execute([$_POST['fournisseur_id']]);
        
        set_message("Fournisseur supprimé avec succès.", 'success');
    } catch (Exception $e) {
        set_message("Erreur lors de la suppression du fournisseur: " . $e->getMessage(), 'danger');
    }
    
    redirect('fournisseurs');
}

// Fonction de création de commande
function creerCommande() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Créer la commande
        $stmt = $pdo->prepare("
            INSERT INTO commandes_fournisseurs (fournisseur_id, date_commande, date_livraison_prevue, notes, statut, created_by)
            VALUES (?, NOW(), ?, ?, 'en_cours', ?)
        ");
        
        $stmt->execute([
            $_POST['fournisseur_id'],
            $_POST['date_livraison'],
            $_POST['notes'],
            $_SESSION['user_id']
        ]);
        
        $commande_id = $pdo->lastInsertId();
        
        // Ajouter les lignes de commande
        $stmt = $pdo->prepare("
            INSERT INTO lignes_commande_fournisseur (commande_id, produit_id, quantite, prix_unitaire)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($_POST['produits'] as $produit_id => $quantite) {
            if ($quantite > 0) {
                // Récupérer le prix d'achat actuel du produit
                $stmt_prix = $pdo->prepare("SELECT prix_achat FROM produits WHERE id = ?");
                $stmt_prix->execute([$produit_id]);
                $prix_achat = $stmt_prix->fetchColumn();
                
                $stmt->execute([
                    $commande_id,
                    $produit_id,
                    $quantite,
                    $prix_achat
                ]);
            }
        }
        
        $pdo->commit();
        set_message("Commande créée avec succès.", 'success');
    } catch (Exception $e) {
        $pdo->rollBack();
        set_message("Erreur lors de la création de la commande: " . $e->getMessage(), 'danger');
    }
    
    redirect('fournisseurs');
}

// Fonctions de gestion des commandes
function marquerCommandeLivree() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Récupérer la commande
        $stmt = $pdo->prepare("
            SELECT cf.*, lcf.produit_id, lcf.quantite
            FROM commandes_fournisseurs cf 
            JOIN lignes_commande_fournisseur lcf ON cf.id = lcf.commande_id
            WHERE cf.id = ?
        ");
        $stmt->execute([$_POST['commande_id']]);
        $lignes = $stmt->fetchAll();
        
        if (empty($lignes)) {
            throw new Exception("Commande non trouvée.");
        }
        
        if ($lignes[0]['statut'] !== 'en_cours') {
            throw new Exception("Cette commande ne peut pas être marquée comme livrée.");
        }
        
        // Mettre à jour le stock pour chaque produit
        $stmt = $pdo->prepare("UPDATE produits SET quantite = quantite + ? WHERE id = ?");
        
        foreach ($lignes as $ligne) {
            $stmt->execute([$ligne['quantite'], $ligne['produit_id']]);
            
            // Enregistrer le mouvement de stock
            $stmt_mouvement = $pdo->prepare("
                INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, motif, created_by)
                VALUES (?, 'entree', ?, ?, ?)
            ");
            
            $stmt_mouvement->execute([
                $ligne['produit_id'],
                $ligne['quantite'],
                'Livraison commande #' . $ligne['id'],
                $_SESSION['user_id']
            ]);
        }
        
        // Marquer la commande comme livrée
        $stmt = $pdo->prepare("
            UPDATE commandes_fournisseurs 
            SET statut = 'livree', date_livraison = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$_POST['commande_id']]);
        
        $pdo->commit();
        set_message("Commande marquée comme livrée avec succès.", 'success');
    } catch (Exception $e) {
        $pdo->rollBack();
        set_message("Erreur lors de la livraison de la commande: " . $e->getMessage(), 'danger');
    }
    
    redirect('commandes');
}

function annulerCommande() {
    global $pdo;
    
    try {
        // Vérifier si la commande peut être annulée
        $stmt = $pdo->prepare("
            SELECT statut 
            FROM commandes_fournisseurs 
            WHERE id = ?
        ");
        $stmt->execute([$_POST['commande_id']]);
        $commande = $stmt->fetch();
        
        if (!$commande) {
            throw new Exception("Commande non trouvée.");
        }
        
        if ($commande['statut'] !== 'en_cours') {
            throw new Exception("Cette commande ne peut pas être annulée.");
        }
        
        // Marquer la commande comme annulée
        $stmt = $pdo->prepare("
            UPDATE commandes_fournisseurs 
            SET statut = 'annulee' 
            WHERE id = ?
        ");
        $stmt->execute([$_POST['commande_id']]);
        
        set_message("Commande annulée avec succès.", 'success');
    } catch (Exception $e) {
        set_message("Erreur lors de l'annulation de la commande: " . $e->getMessage(), 'danger');
    }
    
    redirect('commandes');
} 