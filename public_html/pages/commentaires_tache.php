<?php
// Vérification de l'ID de la tâche
if (!isset($_GET['id'])) {
    set_message("ID de tâche non spécifié", "error");
    redirect("taches");
}

$tache_id = (int)$_GET['id'];

// Récupération des informations de la tâche
try {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               u.full_name as employe_nom,
               c.full_name as createur_nom
        FROM taches t 
        LEFT JOIN users u ON t.employe_id = u.id 
        LEFT JOIN users c ON t.created_by = c.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$tache_id]);
    $tache = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tache) {
        set_message("Tâche non trouvée", "error");
        redirect("taches");
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération de la tâche: " . $e->getMessage(), "error");
    redirect("taches");
}

// Traitement de l'ajout d'un commentaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $commentaire = cleanInput($_POST['commentaire']);
    
    if (empty($commentaire)) {
        $errors[] = "Le commentaire ne peut pas être vide.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO commentaires_tache (tache_id, user_id, commentaire) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$tache_id, $_SESSION['user_id'], $commentaire]);
            
            set_message("Commentaire ajouté avec succès!", "success");
            redirect("commentaires_tache", ["id" => $tache_id]);
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'ajout du commentaire: " . $e->getMessage();
        }
    }
}

// Récupération des commentaires
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name as user_nom
        FROM commentaires_tache c
        JOIN users u ON c.user_id = u.id
        WHERE c.tache_id = ?
        ORDER BY c.date_creation DESC
    ");
    $stmt->execute([$tache_id]);
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des commentaires: " . $e->getMessage(), "error");
    $commentaires = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-3 mb-md-0">Détails de la Tâche</h1>
    <a href="index.php?page=taches" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Retour
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($tache['titre']); ?></h5>
                <p class="card-text"><?php echo nl2br(htmlspecialchars($tache['description'])); ?></p>
                
                <div class="row mt-3">
                    <div class="col-md-4">
                        <strong>Priorité:</strong>
                        <span class="badge bg-<?php 
                            echo $tache['priorite'] == 'haute' ? 'danger' : 
                                ($tache['priorite'] == 'moyenne' ? 'warning' : 'success'); 
                        ?>">
                            <?php echo ucfirst($tache['priorite']); ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Statut:</strong>
                        <span class="badge bg-<?php 
                            echo $tache['statut'] == 'termine' ? 'success' : 
                                ($tache['statut'] == 'en_cours' ? 'primary' : 'secondary'); 
                        ?>">
                            <?php echo $tache['statut'] == 'termine' ? 'Terminé' : 
                                ($tache['statut'] == 'en_cours' ? 'En cours' : 'À faire'); ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Date limite:</strong>
                        <?php if ($tache['date_limite']): ?>
                            <?php echo date('d/m/Y', strtotime($tache['date_limite'])); ?>
                        <?php else: ?>
                            <span class="text-muted">Non définie</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <strong>Assigné à:</strong>
                        <?php if ($tache['employe_nom']): ?>
                            <?php echo htmlspecialchars($tache['employe_nom']); ?>
                        <?php else: ?>
                            <span class="text-muted">Non assigné</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Créé par:</strong>
                        <?php echo htmlspecialchars($tache['createur_nom']); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Commentaires</h5>
                
                <?php if (empty($commentaires)): ?>
                    <p class="text-muted">Aucun commentaire pour le moment.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($commentaires as $commentaire): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($commentaire['user_nom']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($commentaire['date_creation'])); ?>
                                    </small>
                                </div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($commentaire['commentaire'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Ajouter un commentaire</h5>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="index.php?page=commentaires_tache&id=<?php echo $tache_id; ?>">
                    <div class="mb-3">
                        <label for="commentaire" class="form-label">Commentaire</label>
                        <textarea class="form-control" id="commentaire" name="commentaire" rows="4" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div> 