<?php
// Vérifier le rôle de l'utilisateur
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user['role'] === 'admin') {
        // Interface administrateur
        ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Gestion des Congés</h1>
            <div>
                <a href="index.php?page=conges_calendrier" class="btn btn-primary me-2">
                    <i class="fas fa-calendar-alt me-2"></i>Gérer le Calendrier
                </a>
                <a href="index.php?page=conges_imposer" class="btn btn-primary">
                    <i class="fas fa-user-clock me-2"></i>Imposer des Congés
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Statistiques -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Vue d'ensemble</h5>
                        <div class="row">
                            <?php
                            // Récupérer les statistiques
                            try {
                                // Nombre total de jours de congés pris
                                $stmt = $shop_pdo->query("
                                    SELECT COALESCE(SUM(nb_jours), 0) as total_jours
                                    FROM conges_demandes 
                                    WHERE statut = 'approuve'
                                ");
                                $total_jours = $stmt->fetch()['total_jours'] ?? 0;

                                // Nombre de demandes en attente
                                $stmt = $shop_pdo->query("
                                    SELECT COUNT(*) as nb_attente
                                    FROM conges_demandes 
                                    WHERE statut = 'en_attente'
                                ");
                                $nb_attente = $stmt->fetch()['nb_attente'] ?? 0;

                                // Moyenne des jours restants
                                $stmt = $shop_pdo->query("
                                    SELECT COALESCE(AVG(solde_actuel), 0) as moyenne_solde
                                    FROM conges_solde
                                ");
                                $moyenne_solde = $stmt->fetch()['moyenne_solde'] ?? 0;
                            } catch (PDOException $e) {
                                error_log("Erreur lors de la récupération des statistiques : " . $e->getMessage());
                            }
                            ?>
                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center">
                                    <h3 class="text-primary mb-0"><?php echo number_format($total_jours, 1); ?></h3>
                                    <p class="text-muted mb-0">Jours de congés pris</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center">
                                    <h3 class="text-warning mb-0"><?php echo $nb_attente; ?></h3>
                                    <p class="text-muted mb-0">Demandes en attente</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center">
                                    <h3 class="text-success mb-0"><?php echo number_format($moyenne_solde, 1); ?></h3>
                                    <p class="text-muted mb-0">Moyenne jours restants</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des demandes en attente -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Demandes en attente</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $stmt = $shop_pdo->query("
                                SELECT cd.*, u.full_name, cs.solde_actuel
                                FROM conges_demandes cd
                                JOIN users u ON cd.user_id = u.id
                                LEFT JOIN conges_solde cs ON cd.user_id = cs.user_id
                                WHERE cd.statut = 'en_attente'
                                ORDER BY cd.created_at DESC
                            ");
                            $demandes = $stmt->fetchAll();
                        } catch (PDOException $e) {
                            error_log("Erreur lors de la récupération des demandes : " . $e->getMessage());
                            $demandes = [];
                        }

                        if (empty($demandes)): ?>
                            <p class="text-muted text-center mb-0">Aucune demande en attente</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Employé</th>
                                            <th>Période</th>
                                            <th>Jours</th>
                                            <th>Solde actuel</th>
                                            <th>Date demande</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($demandes as $demande): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($demande['full_name']); ?></td>
                                                <td>
                                                    Du <?php echo date('d/m/Y', strtotime($demande['date_debut'])); ?>
                                                    au <?php echo date('d/m/Y', strtotime($demande['date_fin'])); ?>
                                                </td>
                                                <td><?php echo $demande['nb_jours']; ?> jours</td>
                                                <td><?php echo number_format($demande['solde_actuel'], 1); ?> jours</td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($demande['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-success"
                                                                onclick="approuverConges(<?php echo $demande['id']; ?>)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger"
                                                                onclick="refuserConges(<?php echo $demande['id']; ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
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

            <!-- Calendrier des congés -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Calendrier des congés</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $stmt = $shop_pdo->query("
                                SELECT cd.*, u.full_name
                                FROM conges_demandes cd
                                JOIN users u ON cd.user_id = u.id
                                WHERE cd.statut = 'approuve'
                                  AND cd.date_debut >= CURRENT_DATE
                                ORDER BY cd.date_debut ASC
                                LIMIT 10
                            ");
                            $conges_approuves = $stmt->fetchAll();
                        } catch (PDOException $e) {
                            error_log("Erreur lors de la récupération des congés approuvés : " . $e->getMessage());
                            $conges_approuves = [];
                        }

                        if (empty($conges_approuves)): ?>
                            <p class="text-muted text-center mb-0">Aucun congé à venir</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employé</th>
                                            <th>Début</th>
                                            <th>Fin</th>
                                            <th>Durée</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($conges_approuves as $conge): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($conge['full_name']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($conge['date_debut'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($conge['date_fin'])); ?></td>
                                                <td><?php echo $conge['nb_jours']; ?> jours</td>
                                                <td>
                                                    <?php if ($conge['type'] === 'impose'): ?>
                                                        <span class="badge bg-info">Imposé</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Normal</span>
                                                    <?php endif; ?>
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
        function approuverConges(id) {
            if (confirm('Êtes-vous sûr de vouloir approuver cette demande de congés ?')) {
                // Envoyer la requête AJAX pour approuver
                fetch('api/conges_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'approuver',
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur : ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue');
                });
            }
        }

        function refuserConges(id) {
            if (confirm('Êtes-vous sûr de vouloir refuser cette demande de congés ?')) {
                // Envoyer la requête AJAX pour refuser
                fetch('api/conges_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'refuser',
                        id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur : ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue');
                });
            }
        }
        </script>
        <?php
    } else {
        // Rediriger vers l'interface employé
        redirect('conges_employe');
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la vérification du rôle : " . $e->getMessage());
    set_message("Une erreur est survenue", "error");
    redirect('accueil');
}
?> 