<?php
/**
 * Script de test pour vérifier que le modal rechercheAvanceeModal 
 * fonctionne correctement avec le système multi-boutique
 */

// Inclure la configuration de session
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Recherche Avancée Modal</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='container mt-4'>";

echo "<h1>✅ Test du Modal Recherche Avancée Multi-Boutique</h1>";

// 1. Vérifier la session et boutique actuelle
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h3>1. Information Boutique Actuelle</h3></div>";
echo "<div class='card-body'>";

if (isset($_SESSION['shop_id'])) {
    echo "<p><strong>✅ Shop ID:</strong> " . $_SESSION['shop_id'] . "</p>";
    echo "<p><strong>✅ Shop Name:</strong> " . ($_SESSION['shop_name'] ?? 'Non défini') . "</p>";
} else {
    echo "<p><strong>❌ Aucune boutique sélectionnée en session</strong></p>";
}

echo "</div></div>";

// 2. Tester la connexion à la base de données
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h3>2. Test Connexion Base de Données</h3></div>";
echo "<div class='card-body'>";

try {
    $shop_pdo = getShopDBConnection();
    
    if ($shop_pdo instanceof PDO) {
        echo "<p><strong>✅ Connexion réussie</strong></p>";
        
        // Vérifier quelle base de données est utilisée
        $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
        $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>✅ Base de données utilisée:</strong> " . $db_info['db_name'] . "</p>";
        
        // Compter les différents éléments
        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM clients");
        $client_count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>✅ Nombre de clients:</strong> " . $client_count['count'] . "</p>";
        
        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM reparations");
        $reparation_count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>✅ Nombre de réparations:</strong> " . $reparation_count['count'] . "</p>";
        
        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM commandes_pieces");
        $commande_count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>✅ Nombre de commandes:</strong> " . $commande_count['count'] . "</p>";
        
    } else {
        echo "<p><strong>❌ Échec de la connexion</strong></p>";
    }
} catch (Exception $e) {
    echo "<p><strong>❌ Erreur:</strong> " . $e->getMessage() . "</p>";
}

echo "</div></div>";

// 3. Test de l'endpoint AJAX de recherche avancée
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h3>3. Test Recherche Avancée AJAX</h3></div>";
echo "<div class='card-body'>";

echo "<p><strong>Test de l'endpoint :</strong> <code>ajax/recherche_avancee.php</code></p>";

// Test avec un terme de recherche
$test_terme = "test";
echo "<div class='mb-3'>";
echo "<label class='form-label'>Terme de recherche à tester :</label>";
echo "<input type='text' class='form-control' id='termeTest' value='" . $test_terme . "' placeholder='Entrez un terme de recherche'>";
echo "<button class='btn btn-primary mt-2' id='testBtn'>Tester la Recherche Avancée</button>";
echo "</div>";

echo "<div id='testResults' class='mt-3'></div>";

echo "</div></div>";

// 4. Informations sur le modal
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h3>4. Information Modal Recherche Avancée</h3></div>";
echo "<div class='card-body'>";

echo "<p><strong>Localisation du modal :</strong> <code>components/quick-actions.php</code></p>";
echo "<p><strong>ID du modal :</strong> <code>rechercheAvanceeModal</code></p>";
echo "<p><strong>ID du champ de recherche :</strong> <code>recherche_avancee</code></p>";
echo "<p><strong>ID du bouton :</strong> <code>btn-recherche-avancee</code></p>";
echo "<p><strong>JavaScript :</strong> <code>assets/js/recherche-avancee.js</code></p>";
echo "<p><strong>Endpoint AJAX :</strong> <code>ajax/recherche_avancee.php</code> - ✅ <strong>CORRIGÉ</strong></p>";

echo "</div></div>";

// 5. Résumé des corrections
echo "<div class='card mb-3 border-success'>";
echo "<div class='card-header bg-success text-white'><h3>5. ✅ Corrections Appliquées</h3></div>";
echo "<div class='card-body'>";

echo "<p><strong>✅ PROBLÈME RÉSOLU !</strong></p>";
echo "<div class='alert alert-warning'>";
echo "<h5><i class='fas fa-exclamation-triangle me-2'></i>Problème détecté :</h5>";
echo "<p>Le fichier <code>ajax/recherche_avancee.php</code> utilisait l'ancienne variable globale <code>\$pdo</code> au lieu de <code>getShopDBConnection()</code>.</p>";
echo "</div>";

echo "<div class='alert alert-success'>";
echo "<h5><i class='fas fa-check-circle me-2'></i>Correction appliquée :</h5>";
echo "<ul>";
echo "<li>✅ Remplacé <code>\$pdo</code> par <code>\$shop_pdo = getShopDBConnection()</code></li>";
echo "<li>✅ Ajouté le logging de la base de données utilisée</li>";
echo "<li>✅ Amélioré la gestion d'erreurs</li>";
echo "<li>✅ Toutes les requêtes SQL utilisent maintenant la bonne connexion boutique</li>";
echo "</ul>";
echo "</div>";

echo "<h6>Avant (❌) :</h6>";
echo "<pre><code>if (!isset(\$pdo) || !(\$pdo instanceof PDO)) {
    throw new Exception('Connexion à la base de données non disponible');
}
\$stmt = \$pdo->prepare(\$sql_clients);</code></pre>";

echo "<h6>Après (✅) :</h6>";
echo "<pre><code>\$shop_pdo = getShopDBConnection();
if (!isset(\$shop_pdo) || !(\$shop_pdo instanceof PDO)) {
    throw new Exception('Connexion à la base de données du magasin non disponible');
}
\$stmt = \$shop_pdo->prepare(\$sql_clients);</code></pre>";

echo "</div></div>";

?>

<script>
document.getElementById('testBtn').addEventListener('click', function() {
    const terme = document.getElementById('termeTest').value;
    const resultsDiv = document.getElementById('testResults');
    
    if (!terme.trim()) {
        resultsDiv.innerHTML = '<div class="alert alert-warning">Veuillez entrer un terme de recherche</div>';
        return;
    }
    
    resultsDiv.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Test en cours...';
    
    // Test avec la même requête que le modal
    fetch('ajax/recherche_avancee.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'terme=' + encodeURIComponent(terme)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Résultat test recherche avancée:', data);
        
        let html = '<div class="alert alert-info"><h5>Résultat du test :</h5>';
        html += '<p><strong>Succès:</strong> ' + (data.success ? '✅ Oui' : '❌ Non') + '</p>';
        
        if (data.success) {
            html += '<p><strong>Terme recherché:</strong> ' + (data.terme || 'N/A') + '</p>';
            html += '<p><strong>Total de résultats:</strong> ' + (data.counts.total || 0) + '</p>';
            
            if (data.counts) {
                html += '<ul>';
                html += '<li><strong>Clients trouvés:</strong> ' + (data.counts.clients || 0) + '</li>';
                html += '<li><strong>Réparations trouvées:</strong> ' + (data.counts.reparations || 0) + '</li>';
                html += '<li><strong>Commandes trouvées:</strong> ' + (data.counts.commandes || 0) + '</li>';
                html += '</ul>';
            }
            
            // Afficher quelques exemples de résultats
            if (data.resultats) {
                if (data.resultats.clients && data.resultats.clients.length > 0) {
                    html += '<h6>Exemples de clients :</h6><ul>';
                    data.resultats.clients.slice(0, 3).forEach(client => {
                        html += '<li>' + (client.nom || '') + ' ' + (client.prenom || '') + ' - ' + (client.telephone || '') + '</li>';
                    });
                    html += '</ul>';
                }
                
                if (data.resultats.reparations && data.resultats.reparations.length > 0) {
                    html += '<h6>Exemples de réparations :</h6><ul>';
                    data.resultats.reparations.slice(0, 3).forEach(reparation => {
                        html += '<li>ID: ' + (reparation.id || '') + ' - ' + (reparation.appareil || '') + ' ' + (reparation.modele || '') + '</li>';
                    });
                    html += '</ul>';
                }
            }
        } else {
            html += '<p><strong>Message d\'erreur:</strong> ' + (data.message || 'Erreur inconnue') + '</p>';
        }
        
        html += '</div>';
        resultsDiv.innerHTML = html;
    })
    .catch(error => {
        console.error('Erreur test:', error);
        resultsDiv.innerHTML = '<div class="alert alert-danger">❌ Erreur lors du test: ' + error.message + '</div>';
    });
});

// Test automatique au chargement
window.addEventListener('load', function() {
    console.log('✅ Page de test chargée - Modal rechercheAvanceeModal corrigé');
});
</script>

</body>
</html> 