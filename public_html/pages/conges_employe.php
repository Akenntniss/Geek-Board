<?php
// Récupération du solde de congés
try {
    $stmt = $pdo->prepare("
        SELECT solde_actuel, date_derniere_maj
        FROM conges_solde
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $solde = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération du solde: " . $e->getMessage(), "error");
    $solde = ['solde_actuel' => 0];
}

// Récupération des jours disponibles
try {
    $stmt = $pdo->prepare("
        SELECT date_jour, statut
        FROM conges_jours_disponibles
        WHERE date_jour >= CURRENT_DATE
        ORDER BY date_jour ASC
    ");
    $stmt->execute();
    $jours_disponibles = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des jours disponibles: " . $e->getMessage(), "error");
    $jours_disponibles = [];
}

// Récupération des demandes de congés
try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM conges_demandes
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des demandes: " . $e->getMessage(), "error");
    $demandes = [];
}

// Traitement de la demande de congés
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_debut = cleanInput($_POST['date_debut']);
    $date_fin = cleanInput($_POST['date_fin']);
    $commentaire = cleanInput($_POST['commentaire']);
    
    $errors = [];
    
    if (empty($date_debut) || empty($date_fin)) {
        $errors[] = "Les dates de début et de fin sont obligatoires.";
    }
    
    if (empty($errors)) {
        try {
            // Vérification des jours disponibles
            $debut = new DateTime($date_debut);
            $fin = new DateTime($date_fin);
            $nb_jours = 0;
            $interval = new DateInterval('P1D');
            $periode = new DatePeriod($debut, $interval, $fin->modify('+1 day'));
            
            foreach ($periode as $date) {
                if ($date->format('N') < 6) { // Lundi à Vendredi
                    $date_str = $date->format('Y-m-d');
                    if (!isset($jours_disponibles[$date_str]) || 
                        $jours_disponibles[$date_str] !== 'disponible') {
                        $errors[] = "Le jour " . date('d/m/Y', strtotime($date_str)) . " n'est pas disponible.";
                    }
                    $nb_jours++;
                }
            }
            
            // Vérification du solde
            if ($nb_jours > $solde['solde_actuel']) {
                $errors[] = "Votre solde de congés est insuffisant.";
            }
            
            if (empty($errors)) {
                $stmt = $pdo->prepare("
                    INSERT INTO conges_demandes (
                        user_id, date_debut, date_fin, nb_jours,
                        commentaire, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $date_debut,
                    $date_fin,
                    $nb_jours,
                    $commentaire,
                    $_SESSION['user_id']
                ]);
                
                set_message("Votre demande de congés a été enregistrée!", "success");
                redirect('conges_employe');
            }
        } catch (PDOException $e) {
            set_message("Erreur lors de l'enregistrement de la demande: " . $e->getMessage(), "error");
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Mes Congés</h1>
</div>

<div class="row">
    <!-- Colonne de gauche -->
    <div class="col-lg-4">
        <!-- Carte du solde -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar-check fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="card-title mb-0">Mon Solde de Congés</h5>
                    </div>
                </div>
                <div class="text-center">
                    <h1 class="display-4 mb-0 text-primary"><?php echo number_format($solde['solde_actuel'] ?? 0, 1); ?></h1>
                    <p class="text-muted mb-2">jours disponibles</p>
                    <?php if (isset($solde['date_derniere_maj'])): ?>
                        <small class="text-muted">
                            Mise à jour le <?php echo date('d/m/Y', strtotime($solde['date_derniere_maj'])); ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Formulaire de demande -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-paper-plane fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="card-title mb-0">Nouvelle Demande</h5>
                    </div>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="demande-form">
                    <div class="mb-3">
                        <label for="date_debut" class="form-label">Date de début</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="date_fin" class="form-label">Date de fin</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="commentaire" class="form-label">Commentaire (optionnel)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-comment"></i></span>
                            <textarea class="form-control" id="commentaire" name="commentaire" rows="2" placeholder="Ajoutez un commentaire si nécessaire"></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer la demande
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Colonne de droite -->
    <div class="col-lg-8">
        <!-- Calendrier des disponibilités -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="card-title mb-0">Calendrier des Disponibilités</h5>
                        <small class="text-muted">Vert : disponible, Rouge : indisponible, Gris : weekend/passé</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $mois_actuels = [];
                    $date = new DateTime();
                    $fin_annee = new DateTime('last day of december this year');
                    
                    while ($date <= $fin_annee) {
                        $mois = $date->format('Y-m');
                        if (!in_array($mois, $mois_actuels)) {
                            $mois_actuels[] = $mois;
                        }
                        $date->modify('+1 day');
                    }
                    
                    // Afficher seulement les 3 prochains mois
                    $mois_actuels = array_slice($mois_actuels, 0, 3);
                    ?>
                    
                    <?php foreach ($mois_actuels as $mois): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-light py-2">
                                    <h6 class="card-title mb-0 text-center">
                                        <?php 
                                        $date = new DateTime($mois . '-01');
                                        echo format_mois_annee($date->getTimestamp()); 
                                        ?>
                                    </h6>
                                </div>
                                <div class="card-body p-2">
                                    <table class="table table-bordered table-sm mb-0">
                                        <thead>
                                            <tr class="text-center">
                                                <th>Lu</th>
                                                <th>Ma</th>
                                                <th>Me</th>
                                                <th>Je</th>
                                                <th>Ve</th>
                                                <th>Sa</th>
                                                <th>Di</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $premier_jour = new DateTime($mois . '-01');
                                            $dernier_jour = new DateTime($mois . '-' . $premier_jour->format('t'));
                                            
                                            $jour_semaine = $premier_jour->format('N') - 1;
                                            if ($jour_semaine > 0) {
                                                echo '<tr><td colspan="' . $jour_semaine . '"></td>';
                                            }
                                            
                                            $jour_courant = clone $premier_jour;
                                            while ($jour_courant <= $dernier_jour) {
                                                if ($jour_courant->format('N') == 1 && $jour_courant != $premier_jour) {
                                                    echo '<tr>';
                                                }
                                                
                                                $date_str = $jour_courant->format('Y-m-d');
                                                $est_disponible = isset($jours_disponibles[$date_str]) && 
                                                                $jours_disponibles[$date_str] === 'disponible';
                                                $est_weekend = in_array($jour_courant->format('N'), [6, 7]);
                                                $est_passe = $jour_courant < new DateTime();
                                                
                                                $classe = '';
                                                if ($est_weekend || $est_passe) {
                                                    $classe = 'text-muted';
                                                } elseif ($est_disponible) {
                                                    $classe = 'text-success';
                                                } else {
                                                    $classe = 'text-danger';
                                                }
                                                
                                                echo '<td class="text-center ' . $classe . '">';
                                                echo $jour_courant->format('d');
                                                echo '</td>';
                                                
                                                if ($jour_courant->format('N') == 7) {
                                                    echo '</tr>';
                                                }
                                                
                                                $jour_courant->modify('+1 day');
                                            }
                                            
                                            $jour_semaine = $dernier_jour->format('N');
                                            if ($jour_semaine < 7) {
                                                echo '<td colspan="' . (7 - $jour_semaine) . '"></td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Bouton pour voir plus de mois -->
                <div class="text-center mt-2">
                    <button type="button" class="btn btn-outline-primary" id="voir-plus-mois">
                        <i class="fas fa-calendar-plus me-2"></i>Voir plus de mois
                    </button>
                </div>
            </div>
        </div>

        <!-- Historique des demandes -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-history fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="card-title mb-0">Historique des Demandes</h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($demandes)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Aucune demande de congés</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Période</th>
                                    <th>Durée</th>
                                    <th>Statut</th>
                                    <th>Type</th>
                                    <th>Date demande</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($demandes as $demande): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-calendar-day text-primary me-2"></i>
                                            Du <?php echo date('d/m/Y', strtotime($demande['date_debut'])); ?>
                                            <br>
                                            <i class="fas fa-calendar-day text-primary me-2"></i>
                                            au <?php echo date('d/m/Y', strtotime($demande['date_fin'])); ?>
                                        </td>
                                        <td><?php echo $demande['nb_jours']; ?> jours</td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $demande['statut'] == 'approuve' ? 'success' : 
                                                    ($demande['statut'] == 'refuse' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($demande['statut']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($demande['type'] === 'impose'): ?>
                                                <span class="badge bg-info">Imposé</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Normal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-clock text-muted me-2"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($demande['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateDebut = document.getElementById('date_debut');
    const dateFin = document.getElementById('date_fin');
    
    // Définir la date minimale à aujourd'hui
    const aujourd_hui = new Date().toISOString().split('T')[0];
    dateDebut.min = aujourd_hui;
    dateFin.min = aujourd_hui;
    
    // Validation des dates
    dateDebut.addEventListener('change', function() {
        if (dateFin.value && dateDebut.value > dateFin.value) {
            dateFin.value = dateDebut.value;
        }
        dateFin.min = dateDebut.value;
    });
    
    dateFin.addEventListener('change', function() {
        if (dateDebut.value && dateFin.value < dateDebut.value) {
            dateDebut.value = dateFin.value;
        }
    });
    
    // Confirmation avant envoi
    document.getElementById('demande-form').addEventListener('submit', function(e) {
        if (!confirm('Êtes-vous sûr de vouloir envoyer cette demande de congés ?')) {
            e.preventDefault();
        }
    });

    // Gestion du bouton "Voir plus de mois"
    document.getElementById('voir-plus-mois').addEventListener('click', function() {
        // TODO: Implémenter l'affichage de plus de mois
        alert('Fonctionnalité à venir : affichage de plus de mois');
    });
});
</script>

<style>
/* Styles pour les cartes */
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-5px);
}

/* Styles pour les badges */
.badge {
    font-weight: 500;
    padding: 0.5em 0.8em;
}

/* Styles pour les icônes dans le tableau */
.table i {
    width: 16px;
}

/* Styles pour le calendrier */
.table-bordered {
    border-color: #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border-color: #dee2e6;
}

.text-success {
    color: #28a745 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.text-muted {
    color: #6c757d !important;
}

/* Styles pour les inputs */
.input-group-text {
    background-color: #f8f9fa;
    border-right: none;
}

.input-group .form-control {
    border-left: none;
}

.input-group .form-control:focus {
    border-color: #dee2e6;
    box-shadow: none;
}

/* Animation pour les icônes */
.fa-2x {
    transition: transform 0.3s ease-in-out;
}

.card:hover .fa-2x {
    transform: scale(1.1);
}
</style> 