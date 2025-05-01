<?php
// Récupération des données pour les graphiques
$reparations_par_statut = get_reparations_par_statut();
$reparations_par_periode = get_reparations_par_periode($periode, $nb_periodes);
$reparations_par_type_appareil = get_reparations_par_type_appareil();
$chiffre_affaires_par_periode = get_chiffre_affaires_par_periode($periode, $nb_periodes);

// Préparation des données pour les graphiques
$labels_periode = [];
$data_nouvelles = [];
$data_terminees = [];
$labels_ca = [];
$data_ca = [];

foreach ($reparations_par_periode as $data) {
    $labels_periode[] = $data['periode'];
    $data_nouvelles[] = $data['nouvelles'];
    $data_terminees[] = $data['terminees'];
}

foreach ($chiffre_affaires_par_periode as $data) {
    $labels_ca[] = $data['periode'];
    $data_ca[] = $data['chiffre_affaires'];
}

// Préparation des données pour le graphique des types d'appareils
$labels_types = [];
$data_types = [];
foreach ($reparations_par_type_appareil as $type) {
    $labels_types[] = $type['type_appareil'];
    $data_types[] = $type['nombre'];
}

// Préparation des données pour le graphique des statuts
$categories_statut = [];
$data_statuts = [];
$colors_statuts = [];

foreach ($reparations_par_statut as $categorie => $data) {
    $categories_statut[] = $categorie;
    $data_statuts[] = $data['total'];
    $colors_statuts[] = $data['couleur'] ?? '#4361ee';
}
?>

<div class="row">
    <!-- Tendances des réparations -->
    <div class="col-xl-6 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Évolution des réparations</h5>
            <div class="chart-container">
                <canvas id="reparationsChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Tendances du chiffre d'affaires -->
    <div class="col-xl-6 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Évolution du chiffre d'affaires</h5>
            <div class="chart-container">
                <canvas id="caChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Répartition par type d'appareil -->
    <div class="col-xl-6 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Répartition par type d'appareil</h5>
            <div class="chart-container">
                <canvas id="typeAppareilChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Répartition par statut -->
    <div class="col-xl-6 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Répartition par statut</h5>
            <div class="chart-container">
                <canvas id="statutChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique d'évolution des réparations
    const ctxRep = document.getElementById('reparationsChart').getContext('2d');
    new Chart(ctxRep, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels_periode) ?>,
            datasets: [
                {
                    label: 'Nouvelles réparations',
                    data: <?= json_encode($data_nouvelles) ?>,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Réparations terminées',
                    data: <?= json_encode($data_terminees) ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Graphique d'évolution du chiffre d'affaires
    const ctxCA = document.getElementById('caChart').getContext('2d');
    new Chart(ctxCA, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels_ca) ?>,
            datasets: [
                {
                    label: 'Chiffre d\'affaires',
                    data: <?= json_encode($data_ca) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toLocaleString() + ' €';
                        }
                    }
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' €';
                        }
                    }
                }
            }
        }
    });
    
    // Graphique des types d'appareils
    const ctxType = document.getElementById('typeAppareilChart').getContext('2d');
    new Chart(ctxType, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($labels_types) ?>,
            datasets: [
                {
                    data: <?= json_encode($data_types) ?>,
                    backgroundColor: generateColors(<?= count($labels_types) ?>),
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    
    // Graphique des statuts
    const ctxStatut = document.getElementById('statutChart').getContext('2d');
    new Chart(ctxStatut, {
        type: 'polarArea',
        data: {
            labels: <?= json_encode($categories_statut) ?>,
            datasets: [
                {
                    data: <?= json_encode($data_statuts) ?>,
                    backgroundColor: <?= json_encode($colors_statuts) ?>,
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            },
            scales: {
                r: {
                    ticks: {
                        backdropColor: 'transparent',
                        precision: 0
                    }
                }
            }
        }
    });
});
</script> 