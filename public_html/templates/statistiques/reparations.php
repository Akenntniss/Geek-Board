<?php
// Récupération des données pour les graphiques
$reparations_par_statut = get_reparations_par_statut();
$reparations_par_type_appareil = get_reparations_par_type_appareil();
$temps_moyen_reparation = get_temps_moyen_reparation();
$reparations_par_marque = get_reparations_par_marque();
$reparations_par_periode = get_reparations_par_periode($periode, $nb_periodes);

// Préparation des données pour le graphique des temps moyens
$labels_temps = [];
$data_temps = [];
$nb_reparations = [];

foreach ($temps_moyen_reparation as $data) {
    $labels_temps[] = $data['type_appareil'];
    $data_temps[] = round($data['temps_moyen_jours'], 1);
    $nb_reparations[] = $data['nombre_reparations'];
}

// Préparation des données pour le graphique des marques
$labels_marques = [];
$data_marques = [];

foreach ($reparations_par_marque as $data) {
    $labels_marques[] = $data['marque'];
    $data_marques[] = $data['nombre'];
}

// Préparation des données pour le graphique des périodes
$labels_periode = [];
$data_nouvelles = [];
$data_terminees = [];

foreach ($reparations_par_periode as $data) {
    $labels_periode[] = $data['periode'];
    $data_nouvelles[] = $data['nouvelles'];
    $data_terminees[] = $data['terminees'];
}
?>

<div class="row">
    <!-- Statistiques par statut -->
    <div class="col-xl-6 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Répartition par statut</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Catégorie</th>
                            <th>Statut</th>
                            <th class="text-end">Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reparations_par_statut as $categorie => $data): ?>
                            <!-- Ligne de la catégorie -->
                            <tr class="table-light">
                                <td colspan="2">
                                    <strong><?= htmlspecialchars($categorie) ?></strong>
                                </td>
                                <td class="text-end">
                                    <span class="badge rounded-pill" style="background-color: <?= $data['couleur'] ?>">
                                        <?= $data['total'] ?>
                                    </span>
                                </td>
                            </tr>
                            <!-- Lignes des statuts -->
                            <?php foreach ($data['statuts'] as $statut): ?>
                                <tr>
                                    <td></td>
                                    <td><?= htmlspecialchars($statut['nom']) ?></td>
                                    <td class="text-end"><?= $statut['nombre'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Temps moyen de réparation -->
    <div class="col-xl-6 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Temps moyen de réparation (jours)</h5>
            <div class="chart-container">
                <canvas id="tempsReparationChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Types d'appareils -->
    <div class="col-xl-6 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Réparations par type d'appareil</h5>
            <div class="chart-container">
                <canvas id="typeAppareilChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Réparations par marque -->
    <div class="col-xl-6 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Top 10 marques réparées</h5>
            <div class="chart-container">
                <canvas id="marqueChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Évolution des réparations -->
    <div class="col-12 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Évolution des réparations par <?= $periode ?></h5>
            <div class="chart-container">
                <canvas id="evolutionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique du temps moyen de réparation
    const ctxTemps = document.getElementById('tempsReparationChart').getContext('2d');
    new Chart(ctxTemps, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels_temps) ?>,
            datasets: [
                {
                    label: 'Jours moyens',
                    data: <?= json_encode($data_temps) ?>,
                    backgroundColor: 'rgba(67, 97, 238, 0.7)',
                    borderWidth: 1,
                    borderRadius: 5,
                    borderColor: '#4361ee',
                    yAxisID: 'y'
                },
                {
                    label: 'Nombre de réparations',
                    data: <?= json_encode($nb_reparations) ?>,
                    type: 'line',
                    borderColor: '#f72585',
                    borderWidth: 2,
                    pointBackgroundColor: '#f72585',
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Jours moyens'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    title: {
                        display: true,
                        text: 'Nombre de réparations'
                    }
                }
            }
        }
    });
    
    // Graphique des types d'appareils
    const ctxType = document.getElementById('typeAppareilChart').getContext('2d');
    new Chart(ctxType, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($reparations_par_type_appareil, 'type_appareil')) ?>,
            datasets: [
                {
                    data: <?= json_encode(array_column($reparations_par_type_appareil, 'nombre')) ?>,
                    backgroundColor: generateColors(<?= count($reparations_par_type_appareil) ?>),
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15
                    }
                }
            }
        }
    });
    
    // Graphique des marques
    const ctxMarque = document.getElementById('marqueChart').getContext('2d');
    new Chart(ctxMarque, {
        type: 'horizontalBar',
        data: {
            labels: <?= json_encode($labels_marques) ?>,
            datasets: [
                {
                    label: 'Nombre de réparations',
                    data: <?= json_encode($data_marques) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 5
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Graphique d'évolution des réparations
    const ctxEvol = document.getElementById('evolutionChart').getContext('2d');
    new Chart(ctxEvol, {
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
});
</script> 