<?php
// Vérifier que functions.php est bien inclus
if (!function_exists('redirect')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'ajouter_retour':
            ajouterRetour();
            break;
            
        case 'modifier_retour':
            modifierRetour();
            break;
            
        case 'ajouter_colis':
            ajouterColis();
            break;
            
        case 'modifier_colis':
            modifierColis();
            break;
    }
}

// Fonction pour ajouter un retour
function ajouterRetour() {
    $shop_pdo = getShopDBConnection();
    
    try {
        // Vérifier les données requises
        if (!isset($_POST['produit_id']) || !isset($_POST['date_limite'])) {
            throw new Exception("Données incomplètes");
        }
        
        $produit_id = (int)$_POST['produit_id'];
        $date_limite = $_POST['date_limite'];
        $notes = $_POST['notes'] ?? '';
        
        // Vérifier si le produit existe et est en statut temporaire
        $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT id, status FROM stock WHERE id = ?");
        $stmt->execute([$produit_id]);
        $produit = $stmt->fetch();
        
        if (!$produit) {
            throw new Exception("Produit non trouvé");
        }
        
        if ($produit['status'] !== 'temporaire') {
            throw new Exception("Le produit n'est pas en statut temporaire");
        }
        
        // Commencer une transaction
        $shop_pdo->beginTransaction();
        
        // Créer le retour
        $stmt = $shop_pdo->prepare("
            INSERT INTO retours (produit_id, date_limite, notes)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$produit_id, $date_limite, $notes]);
        
        // Mettre à jour le statut du produit
        $stmt = $shop_pdo->prepare("UPDATE stock SET status = 'a_retourner' WHERE id = ?");
        $stmt->execute([$produit_id]);
        
        $shop_pdo->commit();
        set_message("Retour ajouté avec succès", 'success');
        
    } catch (Exception $e) {
        if ($shop_pdo->inTransaction()) {
            $shop_pdo->rollBack();
        }
        set_message("Erreur lors de l'ajout du retour: " . $e->getMessage(), 'danger');
    }
    
    redirect('retours');
}

// Fonction pour modifier un retour
function modifierRetour() {
    $shop_pdo = getShopDBConnection();
    
    try {
        // Vérifier les données requises
        if (!isset($_POST['retour_id']) || !isset($_POST['statut'])) {
            throw new Exception("Données incomplètes");
        }
        
        $retour_id = (int)$_POST['retour_id'];
        $statut = $_POST['statut'];
        $numero_suivi = $_POST['numero_suivi'] ?? null;
        $montant_rembourse = !empty($_POST['montant_rembourse']) ? (float)$_POST['montant_rembourse'] : null;
        $montant_rembourse_client = !empty($_POST['montant_rembourse_client']) ? (float)$_POST['montant_rembourse_client'] : null;
        $notes = $_POST['notes'] ?? '';
        
        // Vérifier si le retour existe
        $stmt = $shop_pdo->prepare("SELECT id, produit_id FROM retours WHERE id = ?");
        $stmt->execute([$retour_id]);
        $retour = $stmt->fetch();
        
        if (!$retour) {
            throw new Exception("Retour non trouvé");
        }
        
        // Commencer une transaction
        $shop_pdo->beginTransaction();
        
        // Mettre à jour le retour
        $stmt = $shop_pdo->prepare("
            UPDATE retours 
            SET statut = ?, 
                numero_suivi = ?, 
                montant_rembourse = ?, 
                montant_rembourse_client = ?, 
                notes = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $statut,
            $numero_suivi,
            $montant_rembourse,
            $montant_rembourse_client,
            $notes,
            $retour_id
        ]);
        
        // Si le retour est terminé, mettre à jour le statut du produit
        if ($statut === 'termine') {
            $stmt = $shop_pdo->prepare("UPDATE stock SET status = 'normal' WHERE id = ?");
            $stmt->execute([$retour['produit_id']]);
        }
        
        $shop_pdo->commit();
        set_message("Retour modifié avec succès", 'success');
        
    } catch (Exception $e) {
        if ($shop_pdo->inTransaction()) {
            $shop_pdo->rollBack();
        }
        set_message("Erreur lors de la modification du retour: " . $e->getMessage(), 'danger');
    }
    
    redirect('retours');
}

// Fonction pour ajouter un colis
function ajouterColis() {
    $shop_pdo = getShopDBConnection();
    
    try {
        // Vérifier les données requises
        if (!isset($_POST['numero_suivi'])) {
            throw new Exception("Numéro de suivi requis");
        }
        
        $numero_suivi = $_POST['numero_suivi'];
        $notes = $_POST['notes'] ?? '';
        
        // Créer le colis
        $stmt = $shop_pdo->prepare("
            INSERT INTO colis_retour (numero_suivi, notes)
            VALUES (?, ?)
        ");
        $stmt->execute([$numero_suivi, $notes]);
        
        set_message("Colis ajouté avec succès", 'success');
        
    } catch (Exception $e) {
        set_message("Erreur lors de l'ajout du colis: " . $e->getMessage(), 'danger');
    }
    
    redirect('retours');
}

// Fonction pour modifier un colis
function modifierColis() {
    $shop_pdo = getShopDBConnection();
    
    try {
        // Vérifier les données requises
        if (!isset($_POST['colis_id']) || !isset($_POST['statut'])) {
            throw new Exception("Données incomplètes");
        }
        
        $colis_id = (int)$_POST['colis_id'];
        $statut = $_POST['statut'];
        $notes = $_POST['notes'] ?? '';
        
        // Vérifier si le colis existe
        $stmt = $shop_pdo->prepare("SELECT id FROM colis_retour WHERE id = ?");
        $stmt->execute([$colis_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Colis non trouvé");
        }
        
        // Commencer une transaction
        $shop_pdo->beginTransaction();
        
        // Mettre à jour le colis
        $stmt = $shop_pdo->prepare("
            UPDATE colis_retour 
            SET statut = ?, 
                notes = ?,
                date_expedition = CASE 
                    WHEN ? = 'en_expedition' AND statut != 'en_expedition' 
                    THEN NOW() 
                    ELSE date_expedition 
                END
            WHERE id = ?
        ");
        $stmt->execute([$statut, $notes, $statut, $colis_id]);
        
        // Si le colis est livré, mettre à jour les retours associés
        if ($statut === 'livre') {
            $stmt = $shop_pdo->prepare("
                UPDATE retours 
                SET statut = 'a_verifier' 
                WHERE colis_id = ? AND statut = 'expedie'
            ");
            $stmt->execute([$colis_id]);
        }
        
        $shop_pdo->commit();
        set_message("Colis modifié avec succès", 'success');
        
    } catch (Exception $e) {
        if ($shop_pdo->inTransaction()) {
            $shop_pdo->rollBack();
        }
        set_message("Erreur lors de la modification du colis: " . $e->getMessage(), 'danger');
    }
    
    redirect('retours');
} 