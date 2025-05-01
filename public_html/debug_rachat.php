<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer ou récupérer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la configuration de la base de données
require_once __DIR__ . '/config/database.php';

// Vérifier les droits d'accès (optionnel, mais recommandé)
if (!isset($_SESSION['user_id'])) {
    echo "<p>Non autorisé. Veuillez vous connecter.</p>";
    exit;
}

echo "<h1>Débogage des Rachats d'Appareils</h1>";

// Fonction pour vérifier si une table existe
function tableExists($tableName, $pdo) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $result->rowCount() > 0;
    } catch (PDOException $e) {
        echo "<p>Erreur lors de la vérification de la table: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Vérifier l'existence de la table
$tableRachat = 'rachat_appareils';
if (!tableExists($tableRachat, $pdo)) {
    echo "<p>La table '$tableRachat' n'existe pas dans la base de données.</p>";
    
    // Afficher les tables disponibles
    echo "<h2>Tables existantes :</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    exit;
}

// Requête pour récupérer tous les rachats
try {
    $query = "SELECT 
        r.id, 
        r.date_rachat, 
        r.type_appareil, 
        r.modele, 
        r.prix,
        r.client_id,
        c.nom,
        c.prenom
    FROM 
        $tableRachat r
    LEFT JOIN 
        clients c ON r.client_id = c.id
    ORDER BY 
        r.id DESC
    LIMIT 20";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $rachats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rachats) > 0) {
        echo "<h2>Derniers rachats (20 max)</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>
            <th>ID</th>
            <th>Date</th>
            <th>Client</th>
            <th>Type d'appareil</th>
            <th>Modèle</th>
            <th>Prix</th>
            <th>Actions</th>
        </tr>";
        
        foreach ($rachats as $rachat) {
            echo "<tr>";
            echo "<td>" . $rachat['id'] . "</td>";
            echo "<td>" . $rachat['date_rachat'] . "</td>";
            echo "<td>" . $rachat['prenom'] . " " . $rachat['nom'] . " (ID: " . $rachat['client_id'] . ")</td>";
            echo "<td>" . $rachat['type_appareil'] . "</td>";
            echo "<td>" . $rachat['modele'] . "</td>";
            echo "<td>" . $rachat['prix'] . " €</td>";
            echo "<td>
                <a href='ajax/export_attestation.php?id=" . $rachat['id'] . "' target='_blank'>Test Export</a> | 
                <a href='javascript:void(0)' onclick='testJS(" . $rachat['id'] . ")'>Test JS</a>
            </td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucun rachat trouvé dans la base de données.</p>";
    }
    
    // Vérifier l'existence du client lié
    if (count($rachats) > 0) {
        $rachatId = $rachats[0]['id'];
        $clientId = $rachats[0]['client_id'];
        
        echo "<h2>Vérification du client pour le rachat ID $rachatId</h2>";
        
        $stmtClient = $pdo->prepare("SELECT id, nom, prenom FROM clients WHERE id = ?");
        $stmtClient->execute([$clientId]);
        $client = $stmtClient->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            echo "<p>Client trouvé : ID " . $client['id'] . " - " . $client['prenom'] . " " . $client['nom'] . "</p>";
        } else {
            echo "<p>Client non trouvé pour l'ID $clientId</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p>Erreur lors de la récupération des rachats: " . $e->getMessage() . "</p>";
}
?>

<script>
function testJS(id) {
    alert('Test pour le rachat ID ' + id);
    
    // Simuler l'appel de la fonction exportAttestation
    fetch('/ajax/export_attestation.php?id=' + id, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        // Vérifier le type de réponse
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }
        return response.text().then(text => {
            throw new Error('La réponse n\'est pas au format JSON: ' + text);
        });
    })
    .then(data => {
        if (data.error) {
            alert('Erreur: ' + data.error);
        } else {
            alert('Succès! Attestation générée pour le rachat ID ' + id);
            console.log(data);
        }
    })
    .catch(error => {
        alert('Erreur lors du test: ' + error.message);
    });
}
</script> 