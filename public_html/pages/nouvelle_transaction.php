<?php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/includes/auth_check.php';
require_once $root_path . '/includes/db_connect.php';

if (!isset($_GET['partenaire_id'])) {
    header('Location: partenaires.php');
    exit;
}

$partenaire_id = intval($_GET['partenaire_id']);

// Récupération des informations du partenaire
$stmt = $conn->prepare("SELECT p.*, sp.solde_actuel 
                       FROM partenaires p 
                       LEFT JOIN soldes_partenaires sp ON p.id = sp.partenaire_id 
                       WHERE p.id = ?");
$stmt->bind_param('i', $partenaire_id);
$stmt->execute();
$result = $stmt->get_result();
$partenaire = $result->fetch_assoc();

if (!$partenaire) {
    header('Location: partenaires.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Transaction - <?php echo htmlspecialchars($partenaire['nom'] . ' ' . $partenaire['prenom']); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include $root_path . '/includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h1>Nouvelle Transaction</h1>
                <h4>Partenaire : <?php echo htmlspecialchars($partenaire['nom'] . ' ' . $partenaire['prenom']); ?></h4>
                <p>Solde actuel : <span class="<?php echo $partenaire['solde_actuel'] < 0 ? 'text-danger' : 'text-success'; ?>">
                    <?php echo number_format($partenaire['solde_actuel'], 2); ?> €
                </span></p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form id="transactionForm">
                    <input type="hidden" name="partenaire_id" value="<?php echo $partenaire_id; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Type de transaction</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="typeDebit" value="debit" checked>
                            <label class="form-check-label" for="typeDebit">
                                Débit (le partenaire nous doit de l'argent)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="typeCredit" value="credit">
                            <label class="form-check-label" for="typeCredit">
                                Crédit (nous devons de l'argent au partenaire)
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="montant" class="form-label">Montant (€)</label>
                        <input type="number" step="0.01" class="form-control" id="montant" name="montant" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="reference_piece" class="form-label">Référence de la pièce/service</label>
                        <input type="text" class="form-control" id="reference_piece" name="reference_piece">
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="partenaires.php" class="btn btn-secondary">Retour</a>
                        <button type="submit" class="btn btn-primary">Enregistrer la transaction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../ajax_handlers/save_transaction.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transaction enregistrée avec succès');
                    window.location.href = 'transactions_partenaire.php?id=' + <?php echo $partenaire_id; ?>;
                } else {
                    alert(data.message || 'Erreur lors de l\'enregistrement de la transaction');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue');
            });
        });
    </script>
</body>
</html> 