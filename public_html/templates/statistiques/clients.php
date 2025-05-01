<?php
// Récupération des données pour les statistiques clients
$stats_clients = get_statistiques_clients($nb_periodes);

// Préparation des données pour le graphique des nouveaux clients
$labels_nouveaux = [];
$data_nouveaux = [];

foreach ($stats_clients['nouveaux_clients'] as $data) {
    $labels_nouveaux[] = $data['mois'];
    $data_nouveaux[] = $data['nouveaux_clients'];
}
?>

<div class="row">
    <!-- Statistiques générales -->
    <div class="col-xl-4 mb-4">
        <div class="stats-card p-4">
            <h5 class="card-title mb-4">Vue d'ensemble clients</h5>
            
            <div class="d-flex align-items-center mb-3">
                <div class="stats-icon me-3">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="stat-value"><?= number_format($stats_clients['total_clients']) ?></div>
                    <div class="stat-title">Clients totaux</div>
                </div>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="stats-icon me-3">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div>
                    <div class="stat-value"><?= number_format($stats_clients['panier_moyen'], 2, ',', ' ') ?>€</div>
                    <div class="stat-title">Panier moyen</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Évolution du nombre de clients -->
    <div class="col-xl-8 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Évolution du nombre de nouveaux clients</h5>
            <div class="chart-container">
                <canvas id="nouveauxClientsChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Clients fidèles -->
    <div class="col-12 mb-4">
        <div class="stats-card p-3">
            <h5 class="card-title mb-3">Top 10 clients fidèles</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Téléphone</th>
                            <th class="text-center">Réparations</th>
                            <th class="text-center">Dernière réparation</th>
                            <th class="text-end">Montant total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats_clients['clients_fideles'] as $client): ?>
                            <tr>
                                <td>
                                    <a href="index.php?page=details_client&id=<?= $client['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($client['telephone']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill"><?= $client['nombre_reparations'] ?></span>
                                </td>
                                <td class="text-center">
                                    <?= date('d/m/Y', strtotime($client['derniere_reparation'])) ?>
                                </td>
                                <td class="text-end fw-bold"><?= number_format($client['montant_total'], 2, ',', ' ') ?>€</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique d'évolution des nouveaux clients
    const ctxClients = document.getElementById('nouveauxClientsChart').getContext('2d');
    new Chart(ctxClients, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels_nouveaux) ?>,
            datasets: [
                {
                    label: 'Nouveaux clients',
                    data: <?= json_encode($data_nouveaux) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: '#4bc0c0',
                    borderWidth: 1,
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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