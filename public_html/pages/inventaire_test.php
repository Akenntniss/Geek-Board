<?php
// Inclure la configuration et les fonctions
require_once 'config/database.php';

$shop_pdo = getShopDBConnection();
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Traiter le formulaire s'il est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Formulaire de test soumis: " . print_r($_POST, true));
    
    try {
        $produit_id = (int)$_POST['produit_id'];
        $fournisseur_id = !empty($_POST['fournisseur_id']) ? (int)$_POST['fournisseur_id'] : null;
        
        // Mise à jour du fournisseur uniquement
        $sql = "UPDATE produits SET fournisseur_id = ? WHERE id = ?";
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([$fournisseur_id, $produit_id]);
        
        // Vérifier si la mise à jour a fonctionné
        $stmt = $shop_pdo->prepare("SELECT fournisseur_id FROM produits WHERE id = ?");
        $stmt->execute([$produit_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Après mise à jour, fournisseur_id = " . var_export($result['fournisseur_id'], true));
        
        set_message("Test de mise à jour effectué. Nouveau fournisseur_id: " . var_export($result['fournisseur_id'], true), 'success');
    } catch (PDOException $e) {
        error_log("Erreur lors du test: " . $e->getMessage());
        set_message("Erreur: " . $e->getMessage(), 'danger');
    }
}

// Récupérer la liste des produits
$stmt = $shop_pdo->query("SELECT id, reference, nom FROM produits ORDER BY nom LIMIT 10");
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la liste des fournisseurs
$stmt = $shop_pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
$fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de mise à jour fournisseur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4">Test de mise à jour fournisseur</h1>
        
        <?php echo display_message(); ?>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="produit_id" class="form-label">Produit</label>
                        <select class="form-select" id="produit_id" name="produit_id" required>
                            <option value="">Sélectionner un produit</option>
                            <?php foreach ($produits as $produit): ?>
                                <option value="<?php echo $produit['id']; ?>">
                                    <?php echo htmlspecialchars($produit['reference'] . ' - ' . $produit['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fournisseur_id" class="form-label">Fournisseur</label>
                        <select class="form-select" id="fournisseur_id" name="fournisseur_id">
                            <option value="">Aucun fournisseur</option>
                            <?php foreach ($fournisseurs as $fournisseur): ?>
                                <option value="<?php echo $fournisseur['id']; ?>">
                                    <?php echo htmlspecialchars($fournisseur['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Mettre à jour le fournisseur</button>
                </form>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="index.php?page=inventaire" class="btn btn-secondary">Retour à l'inventaire</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Récupérer les informations du produit sélectionné
        document.getElementById('produit_id').addEventListener('change', function() {
            const produitId = this.value;
            if (produitId) {
                fetch('ajax/get_produit.php?id=' + produitId)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Produit sélectionné:', data);
                        // Définir le fournisseur s'il existe
                        if (data.fournisseur_id) {
                            document.getElementById('fournisseur_id').value = data.fournisseur_id;
                            console.log('Fournisseur_id défini:', data.fournisseur_id);
                        } else {
                            document.getElementById('fournisseur_id').value = '';
                            console.log('Aucun fournisseur associé');
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        });
    </script>
</body>
</html> 