<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/includes/auth_check.php';
require_once $root_path . '/includes/db_connect.php';

// Récupération des partenaires
$query = "SELECT p.*, sp.solde_actuel 
          FROM partenaires p 
          LEFT JOIN soldes_partenaires sp ON p.id = sp.partenaire_id 
          WHERE p.statut = 'actif'
          ORDER BY p.nom, p.prenom";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Partenaires</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include $root_path . '/includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h1>Gestion des Partenaires</h1>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPartenaireModal">
                    Nouveau Partenaire
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Société</th>
                        <th>Téléphone</th>
                        <th>Solde Actuel</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nom']); ?></td>
                        <td><?php echo htmlspecialchars($row['prenom']); ?></td>
                        <td><?php echo htmlspecialchars($row['societe']); ?></td>
                        <td><?php echo htmlspecialchars($row['telephone']); ?></td>
                        <td class="<?php echo $row['solde_actuel'] < 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo number_format($row['solde_actuel'], 2); ?> €
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="showTransactions(<?php echo $row['id']; ?>)">
                                Voir Transactions
                            </button>
                            <button class="btn btn-sm btn-success" onclick="addTransaction(<?php echo $row['id']; ?>)">
                                Nouvelle Transaction
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Nouveau Partenaire -->
    <div class="modal fade" id="addPartenaireModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau Partenaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addPartenaireForm">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required>
                        </div>
                        <div class="mb-3">
                            <label for="societe" class="form-label">Société</label>
                            <input type="text" class="form-control" id="societe" name="societe">
                        </div>
                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="savePartenaire()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showTransactions(partenaireId) {
            window.location.href = `transactions_partenaire.php?id=${partenaireId}`;
        }

        function addTransaction(partenaireId) {
            window.location.href = `nouvelle_transaction.php?partenaire_id=${partenaireId}`;
        }

        function savePartenaire() {
            const formData = new FormData(document.getElementById('addPartenaireForm'));
            
            fetch('../ajax_handlers/save_partenaire.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erreur lors de l\'enregistrement du partenaire');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            });
        }
    </script>
</body>
</html> 