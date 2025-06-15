<?php
// Vérifier si l'ID de la réparation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_message("ID réparation non spécifié.", "danger");
    redirect("reparations");
}

$reparation_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Vérifier si l'utilisateur a déjà une réparation active
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("
        SELECT active_repair_id FROM users WHERE id = ? AND active_repair_id IS NOT NULL
    ");
    $stmt->execute([$user_id]);
    $active_repair_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si l'utilisateur a déjà une réparation active
    if ($active_repair_result && $active_repair_result['active_repair_id']) {
        $active_repair_id = $active_repair_result['active_repair_id'];
        
        // Récupérer les détails de la réparation active
        $stmt = $shop_pdo->prepare("
            SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, s.nom as statut_nom
            FROM reparations r
            JOIN clients c ON r.client_id = c.id
            LEFT JOIN statuts s ON r.statut = s.code
            WHERE r.id = ?
        ");
        $stmt->execute([$active_repair_id]);
        $active_repair = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si la réparation existe et n'est pas celle qu'on tente de démarrer
        if ($active_repair && $active_repair_id != $reparation_id) {
            set_message("Vous avez déjà une réparation active (#" . $active_repair_id . "). Vous devez d'abord la terminer avant d'en démarrer une nouvelle.", "warning");
            redirect("details_reparation", ['id' => $active_repair_id]);
        }
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la vérification des réparations actives: " . $e->getMessage());
}

// Récupérer les informations de la réparation
try {
    $stmt = $shop_pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.id as client_id
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        set_message("Réparation non trouvée.", "danger");
        redirect("reparations");
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des informations de la réparation: " . $e->getMessage(), "danger");
    redirect("reparations");
}

// Vérifier si l'utilisateur a déjà des réparations en cours
$reparations_en_cours = [];
$client_id = $reparation['client_id'];

try {
    // Récupérer les réparations en cours pour ce client (non terminées, non annulées)
    $stmt = $shop_pdo->prepare("
        SELECT r.id, r.type_appareil, r.modele, r.statut, r.date_reception
        FROM reparations r
        WHERE r.client_id = ? 
        AND r.id != ? 
        AND r.statut NOT IN ('restitue', 'annule', 'termine')
        ORDER BY r.date_reception DESC
    ");
    $stmt->execute([$client_id, $reparation_id]);
    $reparations_en_cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la vérification des réparations en cours: " . $e->getMessage(), "danger");
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Si l'utilisateur veut démarrer la réparation
    if (isset($_POST['demarrer'])) {
        try {
            // Vérifier si l'utilisateur a déjà une réparation active
            $stmt = $shop_pdo->prepare("
                SELECT active_repair_id FROM users WHERE id = ? AND active_repair_id IS NOT NULL AND active_repair_id != ?
            ");
            $stmt->execute([$user_id, $reparation_id]);
            $active_repair_result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($active_repair_result && $active_repair_result['active_repair_id']) {
                set_message("Vous avez déjà une réparation active (#" . $active_repair_result['active_repair_id'] . "). Veuillez d'abord la terminer.", "warning");
                redirect("details_reparation", ['id' => $active_repair_result['active_repair_id']]);
            }
            
            // Déterminer le nouveau statut en fonction du statut actuel
            $new_status = 'en_cours';
            if ($reparation['statut'] === 'nouveau_diagnostique') {
                $new_status = 'en_cours_diagnostique';
            } elseif ($reparation['statut'] === 'nouvelle_intervention') {
                $new_status = 'en_cours_intervention';
            }
            
            // Mettre à jour le statut de la réparation et assigner l'employé
            $stmt = $shop_pdo->prepare("
                UPDATE reparations
                SET statut = ?, 
                    employe_id = ?,
                    date_modification = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $user_id, $reparation_id]);
            
            // Mettre à jour les informations de l'utilisateur
            $stmt = $shop_pdo->prepare("
                UPDATE users
                SET active_repair_id = ?, 
                    techbusy = 1
                WHERE id = ?
            ");
            $stmt->execute([$reparation_id, $user_id]);
            
            // Enregistrer dans les logs
            $stmt = $shop_pdo->prepare("
                INSERT INTO reparation_logs 
                (reparation_id, employe_id, action_type, details, date_action)
                VALUES (?, ?, 'attribution', ?, NOW())
            ");
            $stmt->execute([$reparation_id, $user_id, "Réparation démarrée et assignée à l'employé"]);
            
            set_message("Réparation démarrée avec succès et assignée à votre compte!", "success");
            redirect("details_reparation", ['id' => $reparation_id]);
        } catch (PDOException $e) {
            set_message("Erreur lors du démarrage de la réparation: " . $e->getMessage(), "danger");
        }
    }
}

// Formater la date d'entrée
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-12">
            <!-- En-tête -->
            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Démarrer la réparation #<?php echo $reparation_id; ?></h4>
                    <a href="index.php?page=reparations" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </div>
            
            <!-- Informations sur la réparation -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informations sur la réparation</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Client</h6>
                            <p><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></p>
                            <p><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($reparation['client_telephone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Appareil</h6>
                            <p><?php echo htmlspecialchars($reparation['type_appareil'] . ' - ' . $reparation['modele']); ?></p>
                            <p><i class="fas fa-calendar me-2"></i>Reçu le <?php echo $date_reception; ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="fw-bold">Problème signalé</h6>
                            <p><?php echo htmlspecialchars($reparation['description_probleme']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="fw-bold">Statut actuel</h6>
                            <div class="d-inline-block px-3 py-2 rounded" style="background-color: #f0f0f0;">
                                <?php echo htmlspecialchars($reparation['statut']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($reparations_en_cours)): ?>
            <!-- Réparations en cours -->
            <div class="card mb-3 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Attention: Réparations en cours pour ce client</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <p>Ce client a déjà des réparations en cours. Veuillez vérifier si elles doivent être mises à jour avant de démarrer une nouvelle réparation.</p>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Appareil</th>
                                    <th>Modèle</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reparations_en_cours as $rep): ?>
                                <tr>
                                    <td><?php echo $rep['id']; ?></td>
                                    <td><?php echo htmlspecialchars($rep['type_appareil']); ?></td>
                                    <td><?php echo htmlspecialchars($rep['modele']); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($rep['statut']); ?></span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($rep['date_reception'])); ?></td>
                                    <td>
                                        <a href="index.php?page=statut_rapide&id=<?php echo $rep['id']; ?>" class="btn btn-sm btn-primary">
                                            Mettre à jour
                                        </a>
                                        <a href="index.php?page=details_reparation&id=<?php echo $rep['id']; ?>" class="btn btn-sm btn-secondary">
                                            Détails
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Action -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Souhaitez-vous démarrer cette réparation maintenant?</h5>
                            <p class="text-muted mb-0">Le statut sera mis à jour et la réparation vous sera assignée</p>
                        </div>
                        <div>
                            <form method="POST" action="">
                                <input type="hidden" name="demarrer" value="1">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-play me-2"></i>Démarrer la réparation
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }
    
    .card-header {
        border-top-left-radius: 15px !important;
        border-top-right-radius: 15px !important;
    }
    
    .btn {
        font-weight: 500;
        padding: 0.6rem 1.25rem;
        border-radius: 10px;
    }
    
    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }
    
    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }
</style> 

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner le bouton de démarrage
    const startButton = document.querySelector('.btn-success');
    
    if (startButton) {
        // Ajouter un gestionnaire d'événements au clic
        startButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Créer un formulaire invisible
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // Ajouter le champ caché pour le démarrage
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'demarrer';
            hiddenInput.value = '1';
            
            // Ajouter le champ au formulaire
            form.appendChild(hiddenInput);
            
            // Ajouter le formulaire à la page
            document.body.appendChild(form);
            
            // Soumettre le formulaire
            form.submit();
        });
    }
});
</script> 