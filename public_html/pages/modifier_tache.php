<?php
// Vérification de l'ID de la tâche
if (!isset($_GET['id'])) {
    set_message("ID de tâche manquant.", "error");
    redirect("taches");
}

$tache_id = (int)$_GET['id'];

// Récupération des employés actifs
try {
    $stmt = $pdo->query("SELECT id, full_name as nom, username as prenom FROM users WHERE role = 'technicien' ORDER BY full_name ASC");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des employés: " . $e->getMessage(), "error");
    $employes = [];
}

// Récupération des données de la tâche
try {
    $stmt = $pdo->prepare("
        SELECT t.*, e.nom as employe_nom, e.prenom as employe_prenom 
        FROM taches t 
        LEFT JOIN employes e ON t.employe_id = e.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$tache_id]);
    $tache = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tache) {
        set_message("Tâche non trouvée.", "error");
        redirect("taches");
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération de la tâche: " . $e->getMessage(), "error");
    redirect("taches");
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération et nettoyage des données
    $titre = clean_input($_POST['titre']);
    $description = clean_input($_POST['description']);
    $priorite = clean_input($_POST['priorite']);
    $statut = clean_input($_POST['statut']);
    $date_limite = clean_input($_POST['date_limite']);
    $employe_id = isset($_POST['employe_id']) ? (int)$_POST['employe_id'] : null;
    
    // Validation des données
    $errors = [];
    
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire.";
    }
    
    if (empty($description)) {
        $errors[] = "La description est obligatoire.";
    }
    
    if (empty($priorite)) {
        $errors[] = "La priorité est obligatoire.";
    }
    
    if (empty($statut)) {
        $errors[] = "Le statut est obligatoire.";
    }
    
    // Si pas d'erreurs, mise à jour de la tâche
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE taches 
                SET titre = ?, description = ?, priorite = ?, statut = ?, 
                    date_limite = ?, employe_id = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $titre, 
                $description, 
                $priorite, 
                $statut, 
                $date_limite ?: null, 
                $employe_id ?: null,
                $tache_id
            ]);
            
            set_message("Tâche modifiée avec succès!", "success");
            redirect("taches");
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la modification de la tâche: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-3 mb-md-0">Modifier la Tâche</h1>
    <a href="index.php?page=taches" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Retour
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=modifier_tache&id=<?php echo $tache_id; ?>">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="titre" class="form-label">Titre *</label>
                    <input type="text" class="form-control" id="titre" name="titre" required
                           value="<?php echo htmlspecialchars($tache['titre']); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="employe_id" class="form-label">Assigner à</label>
                    <select class="form-select" id="employe_id" name="employe_id">
                        <option value="">Non assigné</option>
                        <?php foreach ($employes as $employe): ?>
                            <option value="<?php echo $employe['id']; ?>" 
                                    <?php echo $tache['employe_id'] == $employe['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($employe['nom'] . ' ' . $employe['prenom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 mb-3">
                    <label for="description" class="form-label">Description *</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?php 
                        echo htmlspecialchars($tache['description']); 
                    ?></textarea>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="priorite" class="form-label">Priorité *</label>
                    <select class="form-select" id="priorite" name="priorite" required>
                        <option value="">Sélectionner une priorité</option>
                        <option value="basse" <?php echo $tache['priorite'] == 'basse' ? 'selected' : ''; ?>>Basse</option>
                        <option value="moyenne" <?php echo $tache['priorite'] == 'moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                        <option value="haute" <?php echo $tache['priorite'] == 'haute' ? 'selected' : ''; ?>>Haute</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="statut" class="form-label">Statut *</label>
                    <select class="form-select" id="statut" name="statut" required>
                        <option value="">Sélectionner un statut</option>
                        <option value="a_faire" <?php echo $tache['statut'] == 'a_faire' ? 'selected' : ''; ?>>À faire</option>
                        <option value="en_cours" <?php echo $tache['statut'] == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="termine" <?php echo $tache['statut'] == 'termine' ? 'selected' : ''; ?>>Terminé</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="date_limite" class="form-label">Date d'échéance</label>
                    <input type="date" class="form-control" id="date_limite" name="date_limite"
                           value="<?php echo $tache['date_limite'] ? date('Y-m-d', strtotime($tache['date_limite'])) : ''; ?>">
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
        </form>
    </div>
</div> 