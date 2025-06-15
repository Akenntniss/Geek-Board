<?php
require_once __DIR__ . '/../config/database.php';
$root_path = $_SERVER['DOCUMENT_ROOT'];
$shop_pdo = getShopDBConnection();
require_once $root_path . '/includes/auth_check.php';
require_once $root_path . '/includes/db_connect.php';

if (!isset($_GET['id'])) {
    header('Location: partenaires.php');
    exit;
}

$partenaire_id = intval($_GET['id']);

// Récupération des informations du partenaire
$stmt = $shop_pdo->prepare("SELECT p.*, sp.solde_actuel 
                       FROM partenaires p 
                       LEFT JOIN soldes_partenaires sp ON p.id = sp.partenaire_id 
                       WHERE p.id = ?");
// MySQLi code - needs manual conversion
$stmt->execute();
$result = $stmt;
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partenaire) {
    header('Location: partenaires.php');
    exit;
}

// Récupération des transactions
$stmt = $shop_pdo->prepare("SELECT t.*, u.full_name as created_by_name 
                       FROM transactions_partenaires t
                       LEFT JOIN users u ON t.created_by = u.id
                       WHERE t.partenaire_id = ?
                       ORDER BY t.date_transaction DESC");
// MySQLi code - needs manual conversion
$stmt->execute();
$transactions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - <?php echo htmlspecialchars($partenaire['nom'] . ' ' . $partenaire['prenom']); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include $root_path . '/includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h1>Historique des Transactions</h1>
                <h4>Partenaire : <?php echo htmlspecialchars($partenaire['nom'] . ' ' . $partenaire['prenom']); ?></h4>
                <p>Solde actuel : <span class="<?php echo $partenaire['solde_actuel'] < 0 ? 'text-danger' : 'text-success'; ?>">
                    <?php echo number_format($partenaire['solde_actuel'], 2); ?> €
                </span></p>
            </div>
            <div class="col-auto">
                <a href="nouvelle_transaction.php?partenaire_id=<?php echo $partenaire_id; ?>" class="btn btn-primary">
                    Nouvelle Transaction
                </a>
                <a href="partenaires.php" class="btn btn-secondary">
                    Retour
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Description</th>
                        <th>Référence</th>
                        <th>Créé par</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($transaction = $transactions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($transaction['date_transaction'])); ?></td>
                        <td>
                            <span class="badge <?php echo $transaction['type'] === 'debit' ? 'bg-danger' : 'bg-success'; ?>">
                                <?php echo $transaction['type'] === 'debit' ? 'Débit' : 'Crédit'; ?>
                            </span>
                        </td>
                        <td class="<?php echo $transaction['type'] === 'debit' ? 'text-danger' : 'text-success'; ?>">
                            <?php echo number_format($transaction['montant'], 2); ?> €
                        </td>
                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['reference_piece']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['created_by_name']); ?></td>
                        <td>
                            <span class="badge <?php 
                                switch($transaction['statut']) {
                                    case 'validee':
                                        echo 'bg-success';
                                        break;
                                    case 'en_attente':
                                        echo 'bg-warning';
                                        break;
                                    case 'annulee':
                                        echo 'bg-danger';
                                        break;
                                }
                            ?>">
                                <?php echo ucfirst($transaction['statut']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 