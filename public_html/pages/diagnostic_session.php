<?php
/**
 * Page de diagnostic pour les problèmes de session et shop_id
 * Accessible via index.php?page=diagnostic_session
 */

echo '<div class="container mt-4">';
echo '<h1>🔍 Diagnostic de Session - GeekBoard</h1>';

// Afficher l'état de la session
echo '<div class="card mb-4">';
echo '<div class="card-header"><h3>📊 État de la Session</h3></div>';
echo '<div class="card-body">';
echo '<pre>';
echo 'Session Status: ' . session_status() . ' (2 = active)' . "\n";
echo 'Session ID: ' . session_id() . "\n";
echo 'User ID: ' . ($_SESSION['user_id'] ?? '❌ NON DÉFINI') . "\n";
echo 'Shop ID: ' . ($_SESSION['shop_id'] ?? '❌ NON DÉFINI') . "\n";
echo 'Shop Name: ' . ($_SESSION['shop_name'] ?? '❌ NON DÉFINI') . "\n";
echo 'Username: ' . ($_SESSION['username'] ?? '❌ NON DÉFINI') . "\n";
echo 'Role: ' . ($_SESSION['role'] ?? '❌ NON DÉFINI') . "\n";
echo '</pre>';
echo '</div>';
echo '</div>';

// Test de connexion à la base principale
echo '<div class="card mb-4">';
echo '<div class="card-header"><h3>🗄️ Test Base de Données Principale</h3></div>';
echo '<div class="card-body">';
try {
    $pdo_main = getMainDBConnection();
    echo '<p class="text-success">✅ Connexion à la base principale: OK</p>';
    
    // Lister les magasins disponibles
    $stmt = $pdo_main->query("SELECT id, name, active FROM shops ORDER BY name");
    $shops = $stmt->fetchAll();
    
    echo '<h5>Magasins disponibles:</h5>';
    echo '<ul>';
    foreach ($shops as $shop) {
        $status = $shop['active'] ? '✅ Actif' : '❌ Inactif';
        echo '<li>ID: ' . $shop['id'] . ' - ' . $shop['name'] . ' (' . $status . ')</li>';
    }
    echo '</ul>';
    
} catch (Exception $e) {
    echo '<p class="text-danger">❌ Erreur connexion base principale: ' . $e->getMessage() . '</p>';
}
echo '</div>';
echo '</div>';

// Test de connexion à la base du magasin si shop_id est défini
if (isset($_SESSION['shop_id'])) {
    echo '<div class="card mb-4">';
    echo '<div class="card-header"><h3>🏪 Test Base de Données Magasin</h3></div>';
    echo '<div class="card-body">';
    try {
        $shop_pdo = getShopDBConnection();
        echo '<p class="text-success">✅ Connexion à la base du magasin: OK</p>';
        
        // Tester une requête simple
        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM fournisseurs");
        $result = $stmt->fetch();
        echo '<p>Nombre de fournisseurs: <strong>' . $result['count'] . '</strong></p>';
        
        // Lister quelques fournisseurs
        $stmt = $shop_pdo->query("SELECT id, nom FROM fournisseurs LIMIT 5");
        $fournisseurs = $stmt->fetchAll();
        
        if (count($fournisseurs) > 0) {
            echo '<h5>Exemples de fournisseurs:</h5>';
            echo '<ul>';
            foreach ($fournisseurs as $fournisseur) {
                echo '<li>ID: ' . $fournisseur['id'] . ' - ' . $fournisseur['nom'] . '</li>';
            }
            echo '</ul>';
        }
        
    } catch (Exception $e) {
        echo '<p class="text-danger">❌ Erreur connexion base magasin: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    echo '</div>';
} else {
    echo '<div class="card mb-4">';
    echo '<div class="card-header"><h3>🏪 Base de Données Magasin</h3></div>';
    echo '<div class="card-body">';
    echo '<p class="text-warning">⚠️ Impossible de tester: shop_id non défini en session</p>';
    echo '</div>';
    echo '</div>';
}

// Test de l'API get_fournisseurs
echo '<div class="card mb-4">';
echo '<div class="card-header"><h3>🔧 Test API get_fournisseurs</h3></div>';
echo '<div class="card-body">';
echo '<p>Test de l\'API qui pose problème:</p>';
echo '<button class="btn btn-primary" onclick="testGetFournisseurs()">Tester get_fournisseurs.php</button>';
echo '<div id="api-result" class="mt-3"></div>';
echo '</div>';
echo '</div>';

// Actions recommandées
echo '<div class="card mb-4">';
echo '<div class="card-header"><h3>🚀 Actions Recommandées</h3></div>';
echo '<div class="card-body">';

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">';
    echo '<h5>❌ Problème: Utilisateur non connecté</h5>';
    echo '<p>Vous devez vous connecter pour accéder aux fonctionnalités.</p>';
    echo '<a href="pages/login.php" class="btn btn-primary">Se connecter</a>';
    echo '</div>';
} elseif (!isset($_SESSION['shop_id'])) {
    echo '<div class="alert alert-warning">';
    echo '<h5>⚠️ Problème: Magasin non sélectionné</h5>';
    echo '<p>Vous êtes connecté mais aucun magasin n\'est sélectionné.</p>';
    echo '<a href="pages/login.php" class="btn btn-warning">Sélectionner un magasin</a>';
    echo '</div>';
} else {
    echo '<div class="alert alert-success">';
    echo '<h5>✅ Session OK</h5>';
    echo '<p>Vous êtes connecté et un magasin est sélectionné.</p>';
    echo '<a href="index.php?page=commandes_pieces" class="btn btn-success">Aller aux commandes</a>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

echo '</div>'; // Fermer container

// JavaScript pour tester l'API
echo '<script>
function testGetFournisseurs() {
    const resultDiv = document.getElementById("api-result");
    resultDiv.innerHTML = "<p>Test en cours...</p>";
    
    fetch("ajax/get_fournisseurs.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h6>✅ API fonctionne !</h6>
                        <p>Nombre de fournisseurs: ${data.fournisseurs.length}</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>❌ Erreur API</h6>
                        <p>${data.message}</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h6>❌ Erreur réseau</h6>
                    <p>${error.message}</p>
                </div>
            `;
        });
}
</script>';
?> 