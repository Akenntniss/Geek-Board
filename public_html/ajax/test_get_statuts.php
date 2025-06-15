<?php
// Fichier de test pour get_statuts_by_category.php
// Accédez à ce fichier via: /ajax/test_get_statuts.php?category_id=5

// Démarrer la session au début
session_start();

// Afficher les erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test de get_statuts_by_category.php</h2>";

// Simuler une session
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 4; // Valeur par défaut pour les tests
}

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 5;

echo "<p><strong>Paramètres de test:</strong></p>";
echo "<ul>";
echo "<li>Category ID: $category_id</li>";
echo "<li>Shop ID: " . ($_SESSION['shop_id'] ?? 'Non défini') . "</li>";
echo "</ul>";

echo "<p><strong>Test de l'appel direct:</strong></p>";

// Inclure les fichiers nécessaires
try {
    $config_path = realpath(__DIR__ . '/../config/database.php');
    $functions_path = realpath(__DIR__ . '/../includes/functions.php');
    
    if (!file_exists($config_path) || !file_exists($functions_path)) {
        throw new Exception('Fichiers de configuration introuvables.');
    }

    require_once $config_path;
    require_once $functions_path;

    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        throw new Exception('Impossible de se connecter à la base de données du magasin');
    }

    echo "<p style='color: green;'>✓ Connexion à la base de données réussie</p>";

    // Test de récupération des statuts
    $stmt = $shop_pdo->prepare("
        SELECT id, nom, code, est_actif, ordre
        FROM statuts
        WHERE categorie_id = ? AND est_actif = 1
        ORDER BY ordre ASC
    ");
    $stmt->execute([$category_id]);
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Statuts trouvés (" . count($statuts) . "):</strong></p>";
    echo "<pre>" . print_r($statuts, true) . "</pre>";
    
    // Test de récupération de la catégorie
    $stmt = $shop_pdo->prepare("
        SELECT nom, code, couleur
        FROM statut_categories
        WHERE id = ?
    ");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Catégorie trouvée:</strong></p>";
    echo "<pre>" . print_r($category, true) . "</pre>";
    
    if (!empty($statuts) && $category) {
        echo "<p style='color: green;'>✓ Test réussi - Données récupérées correctement</p>";
        
        $result = [
            'success' => true,
            'category' => $category,
            'statuts' => $statuts
        ];
        
        echo "<p><strong>JSON qui serait retourné:</strong></p>";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color: red;'>✗ Aucune donnée trouvée</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Erreur PDO: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><strong>Test de l'appel AJAX:</strong></p>";
echo "<button onclick='testAjaxCall()'>Tester l'appel AJAX</button>";
echo "<div id='ajax-result'></div>";

?>

<script>
function testAjaxCall() {
    const categoryId = <?php echo $category_id; ?>;
    const resultDiv = document.getElementById('ajax-result');
    
    resultDiv.innerHTML = '<p>Test en cours...</p>';
    
    fetch(`get_statuts_by_category.php?category_id=${categoryId}`)
        .then(response => {
            console.log('Statut de la réponse:', response.status);
            console.log('Headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            console.log('Content-Type:', contentType);
            
            return response.text().then(text => {
                console.log('Réponse brute:', text);
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    throw new Error('Réponse JSON invalide: ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            resultDiv.innerHTML = `
                <p style="color: green;">✓ Appel AJAX réussi</p>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <p style="color: red;">✗ Erreur AJAX: ${error.message}</p>
            `;
            console.error('Erreur:', error);
        });
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
</style> 