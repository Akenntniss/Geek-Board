<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!is_logged_in()) {
    redirect("login");
}

// Vérifier si l'ID de la réparation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_message("ID réparation non spécifié.", "danger");
    redirect("reparations");
}

$reparation_id = (int)$_GET['id'];

// Si un statut est soumis dans le formulaire
if (isset($_POST['statut'])) {
    try {
        // Récupérer le statut actuel
        $stmt = $pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
        $stmt->execute([$reparation_id]);
        $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reparation) {
            set_message("Réparation non trouvée.", "danger");
            redirect("reparations");
        }
        
        $statut_avant = $reparation['statut'];
        $nouveau_statut = $_POST['statut'];
        
        // Obtenir l'ID du statut et la catégorie
        $stmt = $pdo->prepare("SELECT id, categorie_id FROM statuts WHERE code = ?");
        $stmt->execute([$nouveau_statut]);
        $statut_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$statut_info) {
            set_message("Statut invalide.", "danger");
            redirect("update_status.php?id=" . $reparation_id);
        }
        
        $statut_id = $statut_info['id'];
        $statut_categorie = $statut_info['categorie_id'];
        
        // Mettre à jour le statut
        $stmt = $pdo->prepare("
            UPDATE reparations 
            SET statut = ?, 
                statut_id = ?, 
                statut_categorie = ?, 
                employe_id = ?,
                date_modification = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $nouveau_statut,
            $statut_id,
            $statut_categorie,
            $_SESSION['user_id'],
            $reparation_id
        ]);
        
        if ($result) {
            // Enregistrer le log
            $stmt = $pdo->prepare("
                INSERT INTO reparation_logs 
                (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) 
                VALUES (?, ?, 'changement_statut', ?, ?, 'Changement de statut')
            ");
            
            $stmt->execute([
                $reparation_id,
                $_SESSION['user_id'],
                $statut_avant,
                $nouveau_statut
            ]);
            
            set_message("Le statut de la réparation a été mis à jour avec succès.", "success");
            redirect("reparations");
        } else {
            set_message("Erreur lors de la mise à jour du statut.", "danger");
        }
    } catch (PDOException $e) {
        set_message("Erreur lors de la mise à jour du statut: " . $e->getMessage(), "danger");
        error_log("Erreur SQL: " . $e->getMessage());
    }
}

// Récupérer les informations de la réparation
try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               c.nom as client_nom, 
               c.prenom as client_prenom,
               s.nom as statut_nom,
               sc.couleur as statut_couleur
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        LEFT JOIN statuts s ON r.statut = s.code
        LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        set_message("Réparation non trouvée.", "danger");
        redirect("reparations");
    }
    
    // Récupérer tous les statuts
    $stmt = $pdo->query("
        SELECT s.*, sc.nom as categorie_nom, sc.couleur
        FROM statuts s
        JOIN statut_categories sc ON s.categorie_id = sc.id
        ORDER BY s.categorie_id, s.ordre
    ");
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des informations: " . $e->getMessage(), "danger");
    redirect("reparations");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour du statut - Réparation #<?php echo $reparation_id; ?></title>
    <?php include '../includes/head.php'; ?>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Modifier le statut de la réparation #<?php echo $reparation_id; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Client:</strong> <?php echo htmlspecialchars($reparation['client_prenom'] . ' ' . $reparation['client_nom']); ?><br>
                            <strong>Appareil:</strong> <?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['marque'] . ' ' . $reparation['modele']); ?><br>
                            <strong>Statut actuel:</strong> 
                            <span class="badge bg-<?php echo $reparation['statut_couleur'] ?? 'primary'; ?>">
                                <?php echo htmlspecialchars($reparation['statut_nom'] ?? $reparation['statut']); ?>
                            </span>
                        </div>
                        
                        <form method="POST" class="mt-4">
                            <div class="mb-4">
                                <label for="statut" class="form-label">Nouveau statut</label>
                                <select name="statut" id="statut" class="form-select form-select-lg" required>
                                    <option value="">Sélectionner un statut</option>
                                    <?php
                                    $current_category = null;
                                    foreach ($statuts as $statut) {
                                        if ($current_category !== $statut['categorie_nom']) {
                                            if ($current_category !== null) {
                                                echo '</optgroup>';
                                            }
                                            echo '<optgroup label="' . htmlspecialchars($statut['categorie_nom']) . '">';
                                            $current_category = $statut['categorie_nom'];
                                        }
                                        
                                        echo '<option value="' . htmlspecialchars($statut['code']) . '" 
                                                data-color="' . htmlspecialchars($statut['couleur']) . '"
                                                ' . ($reparation['statut'] === $statut['code'] ? 'selected' : '') . '>
                                                ' . htmlspecialchars($statut['nom']) . '
                                            </option>';
                                    }
                                    if ($current_category !== null) {
                                        echo '</optgroup>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="demarrer_reparation.php?id=<?php echo $reparation_id; ?>" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </a>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save me-2"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statutSelect = document.getElementById('statut');
            
            // Mettre à jour la couleur du select en fonction du statut sélectionné
            statutSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const color = selectedOption.getAttribute('data-color');
                
                // Réinitialiser les classes
                this.className = 'form-select form-select-lg';
                
                // Ajouter la classe de couleur si disponible
                if (color) {
                    this.classList.add('border-' + color);
                    this.classList.add('bg-' + color + '-subtle');
                }
            });
            
            // Déclencher l'événement au chargement
            statutSelect.dispatchEvent(new Event('change'));
        });
    </script>
</body>
</html> 