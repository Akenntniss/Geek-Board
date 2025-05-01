<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Récupérer l'année et le trimestre sélectionnés
$annee_selectionnee = isset($_GET['annee']) ? (int)$_GET['annee'] : (int)date('Y');
$trimestre_selectionne = isset($_GET['trimestre']) ? (int)$_GET['trimestre'] : (int)ceil(date('n') / 3);

// Calculer les mois du trimestre
$premier_mois = ($trimestre_selectionne - 1) * 3 + 1;
$dernier_mois = $premier_mois + 2;

// Récupérer les jours disponibles
try {
    $debut_periode = sprintf('%04d-%02d-01', $annee_selectionnee, $premier_mois);
    $fin_periode = sprintf('%04d-%02d-%02d', $annee_selectionnee, $dernier_mois, date('t', strtotime(sprintf('%04d-%02d-01', $annee_selectionnee, $dernier_mois))));
    
    $stmt = $pdo->prepare("SELECT date FROM conges_jours_disponibles WHERE date BETWEEN ? AND ? AND disponible = 1");
    $stmt->execute([$debut_periode, $fin_periode]);
    $jours_disponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Convertir en tableau associatif pour une recherche plus rapide
    $jours_disponibles = array_flip($jours_disponibles);
} catch (PDOException $e) {
    set_message('Erreur lors de la récupération des jours disponibles', 'danger');
    $jours_disponibles = [];
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">Calendrier des Congés Disponibles</h1>
            <p class="text-muted mb-0">Trimestre <?php echo $trimestre_selectionne; ?> de l'année <?php echo $annee_selectionnee; ?></p>
        </div>
        <div>
            <a href="index.php?page=conges" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>

    <?php echo display_message(); ?>

    <!-- Navigation des années et trimestres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <label class="me-2 fw-bold">Année :</label>
                    <div class="btn-group">
                        <?php
                        $annee_courante = (int)date('Y');
                        for ($annee = $annee_courante; $annee <= $annee_courante + 5; $annee++) {
                            $active = $annee === $annee_selectionnee ? 'active' : '';
                            echo '<a href="index.php?page=conges_disponibles&annee=' . $annee . '&trimestre=' . $trimestre_selectionne . '" ';
                            echo 'class="btn btn-outline-primary ' . $active . '">' . $annee . '</a>';
                        }
                        ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Plus...
                            </button>
                            <ul class="dropdown-menu">
                                <?php
                                for ($annee = $annee_courante + 6; $annee <= 2040; $annee++) {
                                    echo '<li><a class="dropdown-item" href="index.php?page=conges_disponibles&annee=' . $annee . '&trimestre=' . $trimestre_selectionne . '">' . $annee . '</a></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="me-2 fw-bold">Trimestre :</label>
                    <div class="btn-group">
                        <?php
                        $trimestres = [
                            1 => ['label' => 'Janvier - Mars', 'icon' => 'snowflake'],
                            2 => ['label' => 'Avril - Juin', 'icon' => 'sun'],
                            3 => ['label' => 'Juillet - Septembre', 'icon' => 'umbrella-beach'],
                            4 => ['label' => 'Octobre - Décembre', 'icon' => 'leaf']
                        ];
                        
                        foreach ($trimestres as $num => $info) {
                            $active = $num === $trimestre_selectionne ? 'active' : '';
                            echo '<a href="index.php?page=conges_disponibles&annee=' . $annee_selectionnee . '&trimestre=' . $num . '" ';
                            echo 'class="btn btn-outline-primary ' . $active . '">';
                            echo '<i class="fas fa-' . $info['icon'] . ' me-2"></i>' . $info['label'];
                            echo '</a>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Légende -->
    <div class="alert alert-info mb-4">
        <div class="d-flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle fa-2x"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h5 class="alert-heading">Légende</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-success text-white px-3 py-1 rounded">25</div>
                            <span class="ms-2">Jour disponible</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-light px-3 py-1 rounded">25</div>
                            <span class="ms-2">Jour passé ou weekend</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="text-black-50 px-3 py-1">25</div>
                            <span class="ms-2">Jour hors mois</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendriers -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row">
                <?php
                for ($mois = $premier_mois; $mois <= $dernier_mois; $mois++) {
                    $date = new DateTime(sprintf('%04d-%02d-01', $annee_selectionnee, $mois));
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light py-2">
                                <h6 class="card-title mb-0 text-center">
                                    <?php echo format_mois_annee($date->getTimestamp()); ?>
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
                                            <th class="text-muted">Sa</th>
                                            <th class="text-muted">Di</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $premier_jour = clone $date;
                                        $dernier_jour = new DateTime($date->format('Y-m-t'));
                                        $jour_courant = clone $premier_jour;
                                        $jour_courant->modify('-' . ($premier_jour->format('N') - 1) . ' days');
                                        
                                        while ($jour_courant <= $dernier_jour) {
                                            echo '<tr>';
                                            for ($i = 0; $i < 7; $i++) {
                                                $date_str = $jour_courant->format('Y-m-d');
                                                $est_mois_courant = $jour_courant->format('m') === $date->format('m');
                                                $est_weekend = $jour_courant->format('N') >= 6;
                                                $est_passe = $jour_courant < new DateTime('today');
                                                $est_disponible = isset($jours_disponibles[$date_str]);
                                                
                                                $classe = '';
                                                if ($est_weekend) $classe .= ' text-muted';
                                                if ($est_passe) $classe .= ' bg-light';
                                                if (!$est_mois_courant) $classe .= ' text-black-50';
                                                if ($est_disponible && !$est_weekend && !$est_passe && $est_mois_courant) {
                                                    $classe = ' bg-success text-white';
                                                }
                                                
                                                echo '<td class="text-center p-2' . $classe . '">';
                                                echo '<small>' . $jour_courant->format('d') . '</small>';
                                                echo '</td>';
                                                $jour_courant->modify('+1 day');
                                            }
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php 
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour les cartes */
.card {
    transition: transform 0.2s ease-in-out;
    border: none;
}

.card:hover {
    transform: translateY(-5px);
}

/* Styles pour le calendrier */
.table-bordered {
    border-color: #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border-color: #dee2e6;
}

/* Style pour les jours passés */
.bg-light {
    background-color: #f8f9fa !important;
}

/* Style pour le message d'info */
.alert-info {
    background-color: #e8f4fd;
    border-color: #b8e7fc;
    color: #0c5460;
}

/* Styles pour les boutons de navigation */
.btn-group {
    border-radius: 0.25rem;
    overflow: hidden;
}

.btn-group .btn {
    border-radius: 0;
    margin: 0;
    border-right: none;
    transition: all 0.2s ease-in-out;
}

.btn-group .btn:last-child {
    border-right: 1px solid;
}

.btn-group .btn.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
    font-weight: bold;
}

.btn-group .btn:hover:not(.active) {
    background-color: #e9ecef;
    border-color: #dee2e6;
}

/* Style pour le dropdown */
.dropdown-menu {
    max-height: 300px;
    overflow-y: auto;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.3s ease-out;
}

/* Responsive design */
@media (max-width: 768px) {
    .btn-group {
        flex-wrap: wrap;
    }
    
    .btn-group .btn {
        flex: 1 0 auto;
        border-right: 1px solid;
        margin-bottom: 0.5rem;
    }
}
</style> 