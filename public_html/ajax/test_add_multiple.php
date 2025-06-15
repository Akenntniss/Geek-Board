<?php
// Script de test pour add_multiple_commandes.php
header('Content-Type: application/json');

// Données de test
$testData = [
    'client_id' => '508',
    'fournisseur_id' => '4',
    'statut' => 'en_attente',
    'pieces' => [
        [
            'nom_piece' => 'Test Pièce',
            'code_barre' => '123456789',
            'prix_estime' => '25.50',
            'quantite' => '1',
            'reparation_id' => ''
        ]
    ]
];

// Simuler la requête POST
$_SERVER['REQUEST_METHOD'] = 'POST';

// Simuler l'input JSON
$jsonData = json_encode($testData);
file_put_contents('php://temp', $jsonData);

echo "Test des données:\n";
echo json_encode($testData, JSON_PRETTY_PRINT);
echo "\n\nTentative d'inclusion du script...\n";

try {
    // Capturer la sortie du script
    ob_start();
    
    // Simuler l'input
    $GLOBALS['test_input'] = $jsonData;
    
    // Modifier temporairement file_get_contents pour notre test
    function file_get_contents_test($filename) {
        if ($filename === 'php://input') {
            return $GLOBALS['test_input'];
        }
        return file_get_contents($filename);
    }
    
    // Inclure le script (mais on ne peut pas facilement le tester comme ça)
    echo "Le script existe: " . (file_exists('add_multiple_commandes.php') ? 'OUI' : 'NON') . "\n";
    
    $output = ob_get_clean();
    echo $output;
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?> 