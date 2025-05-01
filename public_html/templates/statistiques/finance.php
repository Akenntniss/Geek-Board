<?php
// Récupération des données pour les statistiques financières
$chiffre_affaires_par_periode = get_chiffre_affaires_par_periode($periode, $nb_periodes);

// Préparation des données pour le graphique
$labels_ca = [];
$data_ca = [];
$data_nb_reparations = [];

foreach ($chiffre_affaires_par_periode as $data) {
    $labels_ca[] = $data['periode'];
    $data_ca[] = $data['chiffre_affaires'];
    $data_nb_reparations[] = $data['nombre_reparations'];
}

// Calculer les totaux et moyennes
$total_ca = array_sum($data_ca);
$total_reparations = array_sum($data_nb_reparations);
$panier_moyen = $total_reparations > 0 ? $total_ca / $total_reparations : 0;

// Calculer la tendance (comparaison avec période précédente)
$nb_periodes_affichees = count($data_ca);
if ($nb_periodes_affichees >= 2) {
    $ca_periode_courante = array_sum(array_slice($data_ca, -($nb_periodes_affichees/2)));
    $ca_periode_precedente = array_sum(array_slice($data_ca, 0, $nb_periodes_affichees/2));
    
    if ($ca_periode_precedente > 0) {
        $variation_percentage = (($ca_periode_courante - $ca_periode_precedente) / $ca_periode_precedente) * 100;
    } else {
        $variation_percentage = 100; // Si période précédente à 0, on est à +100%
    }
} else {
    $variation_percentage = 0;
}
?>

<div class="row">
    <!-- Synthèse financière -->
    <div class="col-xl-4 mb-4">
        <div class="stats-card p-4">
            <h5 class="card-title mb-4">Synthèse financière</h5>
            
            <div class="d-flex align-items-center mb-3">
                <div class="stats-icon me-3">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div>
                    <div class="stat-value"><?= number_format($total_ca, 2, ',', ' ') ?>€</div>
                    <div class="stat-title">Chiffre d'affaires sur la période</div>
                </div>
            </div>
            
            <div class="d-flex align-items-center mb-3">
                <div class="stats-icon me-3">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <div class="stat-value"><?= number_format($panier_moyen, 2, ',', ' ') ?>€</div>
                    <div class="stat-title">Panier moyen</div>
                </div>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="stats-icon me-3">
                    <i class="fas fa-<?= $variation_percentage >= 0 ? 'arrow-up text-success' : 'arrow-down text-danger' ?>"></i>
                </div>
                <div>
                    <div class="stat-value <?= $variation_percentage >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= number_format(abs($variation_percentage), 1) ?>%
                    </div>
                    <div class="stat-title">
                        <?= $variation_percentage >= 0 ? 'Progression' : 'Baisse' ?> par rapport à la période précédente
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Évolution du chiffre d'affaires -->
    <div class="col-xl-8 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Évolution du chiffre d'affaires</h5>
            <div class="chart-container">
                <canvas id="caChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Répartition du CA par type d'appareil -->
    <div class="col-xl-6 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Répartition du CA par type d'appareil</h5>
            <div class="chart-container">
                <canvas id="caTypeAppareilChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Répartition du CA par mois -->
    <div class="col-xl-6 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Rapport réparations/CA</h5>
            <div class="chart-container">
                <canvas id="reparationsCAChart"></canvas>
            </div>
            <div class="text-center mt-3 text-muted small">
                Comparaison entre le nombre de réparations et le chiffre d'affaires généré
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique d'évolution du chiffre d'affaires
    const ctxCA = document.getElementById('caChart').getContext('2d');
    new Chart(ctxCA, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels_ca) ?>,
            datasets: [
                {
                    label: 'Chiffre d\'affaires',
                    data: <?= json_encode($data_ca) ?>,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.3,
                    fill: true,
                    borderWidth: 2
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
    
    // Graphique du rapport réparations/CA
    const ctxRCA = document.getElementById('reparationsCAChart').getContext('2d');
    new Chart(ctxRCA, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels_ca) ?>,
            datasets: [
                {
                    label: 'Chiffre d\'affaires (€)',
                    data: <?= json_encode($data_ca) ?>,
                    backgroundColor: 'rgba(67, 97, 238, 0.7)',
                    borderColor: '#4361ee',
                    borderWidth: 1,
                    borderRadius: 5,
                    yAxisID: 'y'
                },
                {
                    label: 'Nombre de réparations',
                    data: <?= json_encode($data_nb_reparations) ?>,
                    type: 'line',
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: false,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    mode: 'index',
                    callbacks: {
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return 'CA: ' + context.parsed.y.toLocaleString() + ' €';
                            } else {
                                return 'Réparations: ' + context.parsed.y;
                            }
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' €';
                        }
                    },
                    title: {
                        display: true,
                        text: 'Chiffre d\'affaires (€)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        precision: 0
                    },
                    title: {
                        display: true,
                        text: 'Nombre de réparations'
                    }
                }
            }
        }
    });
    
    // Graphique de répartition du CA par type d'appareil
    const ctxCaType = document.getElementById('caTypeAppareilChart').getContext('2d');
    
    // Récupération des données par type d'appareil
    // Note: Dans un cas réel, vous auriez une fonction PHP qui fournirait ces données
    // Ici, on simule ces données pour l'exemple
    const typeAppareils = ['Smartphone', 'Tablette', 'Ordinateur', 'Console', 'Autre'];
    const dataCaType = [45, 25, 20, 5, 5];
    
    new Chart(ctxCaType, {
        type: 'doughnut',
        data: {
            labels: typeAppareils,
            datasets: [
                {
                    data: dataCaType,
                    backgroundColor: [
                        '#4361ee',
                        '#3a0ca3',
                        '#7209b7',
                        '#f72585',
                        '#4cc9f0'
                    ],
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
});
</script> 