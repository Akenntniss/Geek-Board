<?php
// Vérification des droits d'accès
if ($_SESSION['user_role'] !== 'admin') {
    redirect('conges_employe');
}

// Récupération des employés
try {
    $stmt = $pdo->query("
        SELECT u.id, u.full_name, cs.solde_actuel
        FROM users u
        LEFT JOIN conges_solde cs ON u.id = cs.user_id
        WHERE u.role = 'technicien'
        ORDER BY u.full_name
    ");
    $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des employés: " . $e->getMessage(), "error");
    $employes = [];
}

// Traitement de l'ajout de congés imposés
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_ids = isset($_POST['user_ids']) ? $_POST['user_ids'] : [];
    $date_debut = cleanInput($_POST['date_debut']);
    $date_fin = cleanInput($_POST['date_fin']);
    $commentaire = cleanInput($_POST['commentaire']);
    
    $errors = [];
    
    if (empty($user_ids)) {
        $errors[] = "Veuillez sélectionner au moins un employé.";
    }
    
    if (empty($date_debut) || empty($date_fin)) {
        $errors[] = "Les dates de début et de fin sont obligatoires.";
    }
    
    if (empty($errors)) {
        try {
            // Calcul du nombre de jours ouvrés entre les deux dates
            $debut = new DateTime($date_debut);
            $fin = new DateTime($date_fin);
            $nb_jours = 0;
            $interval = new DateInterval('P1D');
            $periode = new DatePeriod($debut, $interval, $fin->modify('+1 day'));
            
            foreach ($periode as $date) {
                if ($date->format('N') < 6) { // Lundi à Vendredi
                    $nb_jours++;
                }
            }
            
            // Insertion des congés imposés pour chaque employé
            $stmt = $pdo->prepare("
                INSERT INTO conges_demandes (
                    user_id, date_debut, date_fin, nb_jours, 
                    statut, type, commentaire, created_by
                ) VALUES (?, ?, ?, ?, 'approuve', 'impose', ?, ?)
            ");
            
            foreach ($user_ids as $user_id) {
                $stmt->execute([
                    $user_id,
                    $date_debut,
                    $date_fin,
                    $nb_jours,
                    $commentaire,
                    $_SESSION['user_id']
                ]);
                
                // Mise à jour du solde de congés
                $stmt2 = $pdo->prepare("
                    UPDATE conges_solde 
                    SET solde_actuel = solde_actuel - ? 
                    WHERE user_id = ?
                ");
                $stmt2->execute([$nb_jours, $user_id]);
            }
            
            set_message("Les congés ont été imposés avec succès!", "success");
            redirect('conges');
        } catch (PDOException $e) {
            set_message("Erreur lors de l'ajout des congés: " . $e->getMessage(), "error");
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Imposer des Congés</h1>
    <a href="index.php?page=conges" class="btn btn-secondary">
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
        
        <form method="POST" id="imposer-form">
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Les congés imposés seront automatiquement approuvés et déduits du solde des employés sélectionnés.
                    </div>
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Sélectionner les employés</label>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="select-all">
                                        </div>
                                    </th>
                                    <th>Employé</th>
                                    <th>Solde actuel</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employes as $employe): ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input employe-checkbox" 
                                                       name="user_ids[]" value="<?php echo $employe['id']; ?>">
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($employe['full_name']); ?></td>
                                        <td><?php echo number_format($employe['solde_actuel'] ?? 0, 2); ?> jours</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="date_debut" class="form-label">Date de début</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="date_fin" class="form-label">Date de fin</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" required>
                </div>
                
                <div class="col-md-12 mb-3">
                    <label for="commentaire" class="form-label">Commentaire</label>
                    <textarea class="form-control" id="commentaire" name="commentaire" rows="3" 
                              placeholder="Raison des congés imposés..."></textarea>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Imposer les congés
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la sélection de tous les employés
    const selectAll = document.getElementById('select-all');
    const employeCheckboxes = document.getElementsByClassName('employe-checkbox');
    
    selectAll.addEventListener('change', function() {
        Array.from(employeCheckboxes).forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
    });
    
    // Validation des dates
    const dateDebut = document.getElementById('date_debut');
    const dateFin = document.getElementById('date_fin');
    
    dateDebut.addEventListener('change', validateDates);
    dateFin.addEventListener('change', validateDates);
    
    function validateDates() {
        const debut = new Date(dateDebut.value);
        const fin = new Date(dateFin.value);
        
        if (debut > fin) {
            dateFin.value = dateDebut.value;
        }
        
        // Définir la date minimale à aujourd'hui
        const aujourd_hui = new Date().toISOString().split('T')[0];
        if (dateDebut.value < aujourd_hui) {
            dateDebut.value = aujourd_hui;
        }
        if (dateFin.value < aujourd_hui) {
            dateFin.value = aujourd_hui;
        }
    }
    
    // Confirmation avant envoi
    document.getElementById('imposer-form').addEventListener('submit', function(e) {
        const employesSelectionnes = Array.from(employeCheckboxes).filter(cb => cb.checked).length;
        
        if (employesSelectionnes === 0) {
            e.preventDefault();
            alert('Veuillez sélectionner au moins un employé.');
            return;
        }
        
        if (!confirm(`Êtes-vous sûr de vouloir imposer ces congés à ${employesSelectionnes} employé(s) ?`)) {
            e.preventDefault();
        }
    });
});
</script> 