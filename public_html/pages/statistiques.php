<?php
// Vérification des droits d'accès avant tout output
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit();
}

// Inclusions des fichiers nécessaires
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/statistiques_fonctions.php';

// Type de statistiques à afficher
$type_stats = isset($_GET['type']) ? $_GET['type'] : 'general';
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'mois';
$nb_periodes = isset($_GET['nb_periodes']) ? intval($_GET['nb_periodes']) : 12;

// Récupérer les statistiques générales pour les cartes en haut
$stats_generales = get_statistiques_generales();
?>

<!-- CSS pour les statistiques -->
<style>
    .stats-container {
        padding: 1.5rem;
    }
    .stats-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        height: 100%;
        overflow: hidden;
    }
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #4361ee;
    }
    .stat-title {
        color: #6c757d;
        font-weight: 500;
    }
    .stats-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: rgba(67, 97, 238, 0.1);
        color: #4361ee;
        font-size: 1.5rem;
    }
    .chart-container {
        min-height: 300px;
        position: relative;
    }
    .stats-nav .nav-link {
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        margin: 0.25rem;
        color: #495057;
        font-weight: 500;
    }
    .stats-nav .nav-link.active {
        background-color: #4361ee;
        color: white;
    }
    .stats-nav .nav-link:hover:not(.active) {
        background-color: rgba(67, 97, 238, 0.1);
    }
</style>

<div class="container-fluid py-4">
    <!-- En-tête et filtres -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-chart-line text-primary me-2"></i>
            Tableau de bord statistique
        </h1>
        
        <div class="d-flex">
            <!-- Sélecteur de période -->
            <div class="btn-group me-2">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    Période: <?= ucfirst($periode) ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item <?= $periode == 'jour' ? 'active' : '' ?>" href="?type=<?= $type_stats ?>&periode=jour">Jour</a></li>
                    <li><a class="dropdown-item <?= $periode == 'semaine' ? 'active' : '' ?>" href="?type=<?= $type_stats ?>&periode=semaine">Semaine</a></li>
                    <li><a class="dropdown-item <?= $periode == 'mois' ? 'active' : '' ?>" href="?type=<?= $type_stats ?>&periode=mois">Mois</a></li>
                    <li><a class="dropdown-item <?= $periode == 'annee' ? 'active' : '' ?>" href="?type=<?= $type_stats ?>&periode=annee">Année</a></li>
                </ul>
            </div>
            
            <!-- Sélecteur de nombre de périodes -->
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    Afficher: <?= $nb_periodes ?> périodes
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item <?= $nb_periodes == 6 ? 'active' : '' ?>" href="?type=<?= $type_stats ?>&periode=<?= $periode ?>&nb_periodes=6">6 périodes</a></li>
                    <li><a class="dropdown-item <?= $nb_periodes == 12 ? 'active' : '' ?>" href="?type=<?= $type_stats ?>&periode=<?= $periode ?>&nb_periodes=12">12 périodes</a></li>
                    <li><a class="dropdown-item <?= $nb_periodes == 24 ? 'active' : '' ?>" href="?type=<?= $type_stats ?>&periode=<?= $periode ?>&nb_periodes=24">24 périodes</a></li>
                    <li><a class="dropdown-item <?= $nb_periodes == 36 ? 'active' : '' ?>" href="?type=<?= $type_stats ?>&periode=<?= $periode ?>&nb_periodes=36">36 périodes</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Cartes des statistiques générales -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card p-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($stats_generales['total_reparations']) ?></div>
                        <div class="stat-title">Réparations totales</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card p-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($stats_generales['reparations_actives']) ?></div>
                        <div class="stat-title">Réparations actives</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card p-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($stats_generales['total_clients']) ?></div>
                        <div class="stat-title">Clients</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card p-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($stats_generales['commandes_en_cours']) ?></div>
                        <div class="stat-title">Commandes en cours</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card p-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($stats_generales['gardiennage_actif']) ?></div>
                        <div class="stat-title">Gardiennages actifs</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="stats-card p-4">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= number_format($stats_generales['ca_total'], 2, ',', ' ') ?>€</div>
                        <div class="stat-title">Chiffre d'affaires</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation entre les types de statistiques -->
    <ul class="nav nav-pills stats-nav mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $type_stats == 'general' ? 'active' : '' ?>" href="?type=general">
                <i class="fas fa-home me-1"></i> Général
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $type_stats == 'reparations' ? 'active' : '' ?>" href="?type=reparations">
                <i class="fas fa-tools me-1"></i> Réparations
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $type_stats == 'clients' ? 'active' : '' ?>" href="?type=clients">
                <i class="fas fa-users me-1"></i> Clients
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $type_stats == 'employes' ? 'active' : '' ?>" href="?type=employes">
                <i class="fas fa-user-tie me-1"></i> Employés
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $type_stats == 'finance' ? 'active' : '' ?>" href="?type=finance">
                <i class="fas fa-euro-sign me-1"></i> Finances
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $type_stats == 'stock' ? 'active' : '' ?>" href="?type=stock">
                <i class="fas fa-boxes me-1"></i> Stock
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $type_stats == 'taches' ? 'active' : '' ?>" href="?type=taches">
                <i class="fas fa-tasks me-1"></i> Tâches
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $type_stats == 'journal' ? 'active' : '' ?>" href="?type=journal">
                <i class="fas fa-history me-1"></i> Journal
            </a>
        </li>
    </ul>

    <!-- Conteneur principal des statistiques -->
    <div class="stats-container">
        <?php 
        // Inclusion du template correspondant au type de statistiques
        switch ($type_stats) {
            case 'reparations':
                include __DIR__ . '/../templates/statistiques/reparations.php';
                break;
            case 'clients':
                include __DIR__ . '/../templates/statistiques/clients.php';
                break;
            case 'employes':
                include __DIR__ . '/../templates/statistiques/employes.php';
                break;
            case 'finance':
                include __DIR__ . '/../templates/statistiques/finance.php';
                break;
            case 'stock':
                include __DIR__ . '/../templates/statistiques/stock.php';
                break;
            case 'taches':
                include __DIR__ . '/../templates/statistiques/taches.php';
                break;
            case 'journal':
                include __DIR__ . '/../templates/statistiques/journal.php';
                break;
            default:
                include __DIR__ . '/../templates/statistiques/general.php';
                break;
        }
        ?>
    </div>
</div>

<!-- Inclusion des scripts Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Fonction pour générer une couleur aléatoire
function getRandomColor() {
    const letters = '0123456789ABCDEF';
    let color = '#';
    for (let i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
}

// Fonction pour générer un tableau de couleurs
function generateColors(count) {
    const colors = [];
    const baseColors = [
        '#4361ee', '#3a0ca3', '#7209b7', '#f72585', '#4cc9f0',
        '#4895ef', '#560bad', '#f3722c', '#f8961e', '#90be6d'
    ];
    
    for (let i = 0; i < count; i++) {
        if (i < baseColors.length) {
            colors.push(baseColors[i]);
        } else {
            colors.push(getRandomColor());
        }
    }
    
    return colors;
}

// Initialisation des graphiques si présents
document.addEventListener('DOMContentLoaded', function() {
    // Chaque graphique doit avoir un ID unique et sera initialisé dans les templates inclus
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?> 