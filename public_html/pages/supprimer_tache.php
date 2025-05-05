<?php
// Vérification de l'ID de la tâche
if (!isset($_GET['id'])) {
    set_message("ID de tâche manquant.", "error");
    redirect("taches");
}

$tache_id = (int)$_GET['id'];

// Obtenir la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Vérification de l'existence de la tâche
try {
    $stmt = $shop_pdo->prepare("SELECT id FROM taches WHERE id = ?");
    $stmt->execute([$tache_id]);
    if (!$stmt->fetch()) {
        set_message("Tâche non trouvée.", "error");
        redirect("taches");
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la vérification de la tâche: " . $e->getMessage(), "error");
    redirect("taches");
}

// Suppression de la tâche
try {
    // Suppression des commentaires associés
    $stmt = $shop_pdo->prepare("DELETE FROM commentaires_tache WHERE tache_id = ?");
    $stmt->execute([$tache_id]);
    
    // Suppression de la tâche
    $stmt = $shop_pdo->prepare("DELETE FROM taches WHERE id = ?");
    $stmt->execute([$tache_id]);
    
    set_message("Tâche supprimée avec succès!", "success");
} catch (PDOException $e) {
    set_message("Erreur lors de la suppression de la tâche: " . $e->getMessage(), "error");
}

redirect("taches"); 