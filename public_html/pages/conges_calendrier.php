<?php
// Débogage - Afficher les données POST
error_log('POST data: ' . print_r($_POST, true));

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('index');
}

// Définition des mois
$mois = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];

// Récupérer les paramètres de vue et de période
$vue = isset($_GET['vue']) ? $_GET['vue'] : 'mois';
$annee_selectionnee = isset($_GET['annee']) ? (int)$_GET['annee'] : (int)date('Y');
$mois_selectionne = isset($_GET['mois']) ? (int)$_GET['mois'] : (int)date('n');
$trimestre_selectionne = isset($_GET['trimestre']) ? (int)$_GET['trimestre'] : (int)ceil($mois_selectionne / 3);

// Calculer les périodes selon la vue
switch ($vue) {
    case 'annee':
        $debut_periode = sprintf('%04d-01-01', $annee_selectionnee);
        $fin_periode = sprintf('%04d-12-31', $annee_selectionnee);
        $mois_afficher = range(1, 12);
        break;
    case 'trimestre':
        $premier_mois = ($trimestre_selectionne - 1) * 3 + 1;
        $dernier_mois = $premier_mois + 2;
        $debut_periode = sprintf('%04d-%02d-01', $annee_selectionnee, $premier_mois);
        $fin_periode = sprintf('%04d-%02d-%02d', $annee_selectionnee, $dernier_mois, date('t', strtotime(sprintf('%04d-%02d-01', $annee_selectionnee, $dernier_mois))));
        $mois_afficher = range($premier_mois, $dernier_mois);
        break;
    default: // mois
        $debut_periode = sprintf('%04d-%02d-01', $annee_selectionnee, $mois_selectionne);
        $fin_periode = sprintf('%04d-%02d-%02d', $annee_selectionnee, $mois_selectionne, date('t', strtotime($debut_periode)));
        $mois_afficher = [$mois_selectionne];
        break;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $shop_pdo->beginTransaction();

        // Supprimer les anciennes entrées
        $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("DELETE FROM conges_jours_disponibles WHERE date BETWEEN ? AND ?");
        $stmt->execute([$debut_periode, $fin_periode]);

        // Insérer les nouveaux jours
        if (isset($_POST['disponible']) && is_array($_POST['disponible'])) {
            $stmt = $shop_pdo->prepare("INSERT INTO conges_jours_disponibles (date, disponible, created_by) VALUES (?, 1, ?)");
            foreach ($_POST['disponible'] as $date => $value) {
                if (strtotime($date) >= strtotime($debut_periode) && strtotime($date) <= strtotime($fin_periode)) {
                    $stmt->execute([$date, $_SESSION['user_id']]);
                }
            }
        }

        $shop_pdo->commit();
        set_message("Les jours disponibles ont été mis à jour avec succès.");
    } catch (PDOException $e) {
        $shop_pdo->rollBack();
        set_message("Erreur lors de la mise à jour : " . $e->getMessage(), 'danger');
    }
}

// Récupérer les jours disponibles
try {
    $stmt = $shop_pdo->prepare("SELECT date FROM conges_jours_disponibles WHERE date BETWEEN ? AND ? AND disponible = 1");
    $stmt->execute([$debut_periode, $fin_periode]);
    $jours_disponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $jours_disponibles = [];
    set_message("Erreur lors de la récupération des jours disponibles.", 'danger');
}

// Récupérer les jours pris en congés
try {
    $stmt = $shop_pdo->prepare("
        SELECT DISTINCT date 
        FROM conges 
        WHERE date BETWEEN ? AND ? 
        AND statut = 'approuve'
    ");
    $stmt->execute([$debut_periode, $fin_periode]);
    $jours_pris = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $jours_pris = [];
    set_message("Erreur lors de la récupération des jours pris.", 'danger');
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="display-6 mb-1">Gestion du Calendrier des Congés</h1>
            <p class="text-muted mb-0">Configurez les jours disponibles pour les congés</p>
        </div>
        <a href="index.php?page=conges" class="btn btn-outline-primary btn-lg">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
    </div>

    <?php echo display_message(); ?>

    <!-- Navigation et options -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <div class="col-md-4">
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn <?php echo $vue === 'mois' ? 'btn-primary' : 'btn-outline-primary'; ?>" onclick="changerVue('mois')">
                            <i class="fas fa-calendar-alt me-2"></i>Mensuel
                        </button>
                        <button type="button" class="btn <?php echo $vue === 'trimestre' ? 'btn-primary' : 'btn-outline-primary'; ?>" onclick="changerVue('trimestre')">
                            <i class="fas fa-calendar-week me-2"></i>Trimestriel
                        </button>
                        <button type="button" class="btn <?php echo $vue === 'annee' ? 'btn-primary' : 'btn-outline-primary'; ?>" onclick="changerVue('annee')">
                            <i class="fas fa-calendar me-2"></i>Annuel
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center justify-content-center">
                        <button type="button" class="btn btn-outline-primary btn-lg me-3" onclick="changerPeriode('prev')">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="text-center">
                            <h3 class="mb-0 fw-bold">
                                <?php
                                if ($vue === 'mois') {
                                    echo $mois[$mois_selectionne] . ' ' . $annee_selectionnee;
                                } elseif ($vue === 'trimestre') {
                                    echo $trimestre_selectionne . 'ème trimestre ' . $annee_selectionnee;
                                } else {
                                    echo $annee_selectionnee;
                                }
                                ?>
                            </h3>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-lg ms-3" onclick="changerPeriode('next')">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select form-select-lg" id="annee" onchange="changerPeriode()">
                        <?php
                        $annee_courante = (int)date('Y');
                        for ($annee = $annee_courante; $annee <= 2040; $annee++) {
                            $selected = $annee === $annee_selectionnee ? 'selected' : '';
                            echo "<option value=\"$annee\" $selected>$annee</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <form id="calendar-form" method="POST">
        <div class="row g-4">
            <?php
            foreach ($mois_afficher as $mois) {
                $date = new DateTime(sprintf('%04d-%02d-01', $annee_selectionnee, $mois));
                $dernier_jour = new DateTime($date->format('Y-m-t'));
                ?>
                <div class="col-12 <?php echo count($mois_afficher) > 1 ? 'col-md-4' : ''; ?>">
                    <div class="card border-0 shadow-sm h-100 calendar-card">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold"><?php echo format_mois_annee($date->getTimestamp()); ?></h5>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="selectionnerTout('<?php echo $date->format('Y-m'); ?>')">
                                        <i class="fas fa-check-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deselectionnerTout('<?php echo $date->format('Y-m'); ?>')">
                                        <i class="fas fa-square"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center">Lu</th>
                                        <th class="text-center">Ma</th>
                                        <th class="text-center">Me</th>
                                        <th class="text-center">Je</th>
                                        <th class="text-center">Ve</th>
                                        <th class="text-center text-muted">Sa</th>
                                        <th class="text-center text-muted">Di</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $jour_courant = clone $date;
                                    $jour_courant->modify('first day of this month');
                                    $jour_courant->modify('-' . ($jour_courant->format('N') - 1) . ' days');

                                    while ($jour_courant <= $dernier_jour) {
                                        echo '<tr>';
                                        for ($i = 0; $i < 7; $i++) {
                                            $est_mois_courant = $jour_courant->format('m') === $date->format('m');
                                            $est_passe = $jour_courant < new DateTime('today');
                                            $date_str = $jour_courant->format('Y-m-d');
                                            $est_disponible = in_array($date_str, $jours_disponibles);
                                            $est_pris = in_array($date_str, $jours_pris);

                                            $classe = 'text-center';
                                            if (!$est_mois_courant) {
                                                $classe .= ' text-muted bg-light';
                                            } else if ($est_pris) {
                                                $classe .= ' bg-danger-subtle text-danger';
                                            } else if ($est_disponible) {
                                                $classe .= ' bg-success-subtle text-success';
                                            }
                                            if ($est_passe) $classe .= ' opacity-50';

                                            echo '<td class="' . $classe . '">';
                                            if ($est_mois_courant && !$est_passe) {
                                                $checked = $est_disponible ? 'checked' : '';
                                                $disabled = $est_pris ? 'disabled' : '';
                                                echo '<div class="calendar-cell" onclick="toggleJour(this, \'' . $date_str . '\')" ' . 
                                                     ($est_pris ? 'data-disabled="true"' : '') . ' ' . 
                                                     ($est_disponible ? 'data-checked="true"' : '') . '>';
                                                echo '<input type="checkbox" class="d-none" name="disponible[' . $date_str . ']" value="1" ' . 
                                                     $checked . ' ' . $disabled . '>';
                                                echo '<span class="day-number">' . $jour_courant->format('d') . '</span>';
                                                echo '</div>';
                                            } else {
                                                echo '<span class="day-number">' . $jour_courant->format('d') . '</span>';
                                            }
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
            <?php } ?>
        </div>

        <div class="position-fixed bottom-0 end-0 m-4">
            <button type="submit" class="btn btn-primary btn-lg shadow-lg save-button">
                <i class="fas fa-save me-2"></i>Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<script>
function changerVue(nouvelleVue) {
    const annee = document.getElementById('annee').value;
    let url = `index.php?page=conges_calendrier&vue=${nouvelleVue}&annee=${annee}`;
    
    // Si on passe en vue mensuelle, on garde le mois actuel
    if (nouvelleVue === 'mois') {
        url += `&mois=${<?php echo $mois_selectionne; ?>}`;
    }
    // Si on passe en vue trimestrielle, on calcule le trimestre actuel
    else if (nouvelleVue === 'trimestre') {
        const trimestre = Math.ceil(<?php echo $mois_selectionne; ?> / 3);
        url += `&trimestre=${trimestre}`;
    }
    
    window.location.href = url;
}

function changerPeriode(direction) {
    const vue = '<?php echo $vue; ?>';
    const annee = document.getElementById('annee').value;
    let url = `index.php?page=conges_calendrier&vue=${vue}&annee=${annee}`;
    
    if (vue === 'mois') {
        let mois = <?php echo $mois_selectionne; ?>;
        if (direction === 'prev') {
            mois = mois > 1 ? mois - 1 : 12;
        } else {
            mois = mois < 12 ? mois + 1 : 1;
        }
        url += `&mois=${mois}`;
    } 
    else if (vue === 'trimestre') {
        let trimestre = <?php echo $trimestre_selectionne; ?>;
        if (direction === 'prev') {
            trimestre = trimestre > 1 ? trimestre - 1 : 4;
        } else {
            trimestre = trimestre < 4 ? trimestre + 1 : 1;
        }
        url += `&trimestre=${trimestre}`;
    } 
    else if (vue === 'annee') {
        let annee = <?php echo $annee_selectionnee; ?>;
        if (direction === 'prev') {
            annee--;
        } else {
            annee++;
        }
        url = `index.php?page=conges_calendrier&vue=${vue}&annee=${annee}`;
    }
    
    window.location.href = url;
}

function selectionnerTout(mois) {
    const checkboxes = document.querySelectorAll(`input[name^="disponible"][value="1"]`);
    checkboxes.forEach(checkbox => {
        const date = checkbox.name.match(/\[(.*?)\]/)[1];
        if (date.startsWith(mois)) {
            checkbox.checked = true;
        }
    });
}

function deselectionnerTout(mois) {
    const checkboxes = document.querySelectorAll(`input[name^="disponible"][value="1"]`);
    checkboxes.forEach(checkbox => {
        const date = checkbox.name.match(/\[(.*?)\]/)[1];
        if (date.startsWith(mois)) {
            checkbox.checked = false;
        }
    });
}

function toggleJour(element, date) {
    if (element.dataset.disabled === 'true') return;
    
    const checkbox = element.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
    
    // Animation de l'effet ripple
    const ripple = document.createElement('div');
    ripple.className = 'ripple';
    element.appendChild(ripple);
    
    // Mettre à jour les classes pour le style
    const cell = element.closest('td');
    if (checkbox.checked) {
        element.dataset.checked = 'true';
        cell.classList.add('bg-success-subtle', 'text-success');
        cell.classList.remove('bg-white');
    } else {
        element.removeAttribute('data-checked');
        cell.classList.remove('bg-success-subtle', 'text-success');
        cell.classList.add('bg-white');
    }
    
    // Supprimer l'effet ripple après l'animation
    setTimeout(() => ripple.remove(), 1000);
}

document.getElementById('calendar-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const checkboxes = this.querySelectorAll('input[type="checkbox"]');
    const totalCheckboxes = checkboxes.length;
    const checkedCheckboxes = Array.from(checkboxes).filter(cb => cb.checked).length;
    
    if (confirm(`Voulez-vous vraiment enregistrer ces modifications ?\n\n${checkedCheckboxes} jours seront disponibles\n${totalCheckboxes - checkedCheckboxes} jours seront indisponibles`)) {
        this.submit();
    }
});

// Amélioration de l'animation des cartes
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.calendar-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 150);
    });
});
</script>

<style>
:root {
    --primary-color: #0d6efd;
    --primary-light: rgba(13, 110, 253, 0.1);
    --success-color: #198754;
    --success-light: rgba(25, 135, 84, 0.1);
    --danger-color: #dc3545;
    --danger-light: rgba(220, 53, 69, 0.1);
    --border-radius: 0.5rem;
    --transition-speed: 0.3s;
    --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
}

/* Styles généraux */
.card {
    border: none;
    border-radius: var(--border-radius);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-sm);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

/* Navigation et boutons */
.btn-group .btn {
    padding: 0.5rem 1rem;
    border-width: 1px;
    font-weight: 500;
    font-size: 0.9rem;
    letter-spacing: 0.3px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-group {
    box-shadow: var(--shadow-sm);
    border-radius: var(--border-radius);
}

.btn-group .btn:first-child {
    border-top-left-radius: var(--border-radius);
    border-bottom-left-radius: var(--border-radius);
}

.btn-group .btn:last-child {
    border-top-right-radius: var(--border-radius);
    border-bottom-right-radius: var(--border-radius);
}

.btn-group .btn:hover {
    transform: translateY(-2px);
    z-index: 3;
}

.btn-group .btn.btn-primary {
    background: linear-gradient(45deg, var(--primary-color), #4d8bff);
    border: none;
    box-shadow: var(--shadow-sm);
}

/* Calendrier */
.calendar-cell {
    cursor: pointer;
    padding: 0.75rem;
    border-radius: var(--border-radius);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    min-height: 3rem;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.calendar-cell::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: var(--success-light);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.4s ease-out, height 0.4s ease-out;
    z-index: 0;
}

.calendar-cell:not([data-disabled="true"]):hover::before {
    width: 150%;
    height: 150%;
}

.calendar-cell[data-checked="true"] {
    background: var(--success-light);
    font-weight: 600;
}

.calendar-cell[data-checked="true"] .day-number {
    color: var(--success-color);
    transform: scale(1.1);
}

.calendar-cell[data-disabled="true"] {
    background: var(--danger-light);
    cursor: not-allowed;
}

.calendar-cell[data-disabled="true"] .day-number {
    color: var(--danger-color);
}

.day-number {
    font-size: 1.1em;
    position: relative;
    z-index: 1;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Table styles */
.table {
    border-collapse: separate;
    border-spacing: 0.25rem;
    width: 100%;
    table-layout: fixed;
}

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85em;
    letter-spacing: 0.5px;
    padding: 1rem 0.5rem;
    color: #6c757d;
}

.table td {
    border: none;
    padding: 0.25rem;
    vertical-align: middle;
    width: 14.28%;
}

/* Save button */
.save-button {
    padding: 1rem 2rem;
    border-radius: 2rem;
    background: linear-gradient(45deg, var(--primary-color), #4d8bff);
    border: none;
    font-weight: 500;
    letter-spacing: 0.5px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-md);
}

.save-button:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.calendar-card {
    animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

/* Media Queries */
@media (max-width: 768px) {
    .calendar-cell {
        padding: 0.5rem;
        min-height: 2.5rem;
    }
    
    .day-number {
        font-size: 1em;
    }
    
    .btn-group .btn {
        padding: 0.5rem 1rem;
        font-size: 0.9em;
    }
    
    .table th {
        font-size: 0.75em;
        padding: 0.75rem 0.25rem;
    }
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Transitions globales */
* {
    transition-property: background-color, border-color, color, transform, box-shadow;
    transition-duration: 0.3s;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style> 