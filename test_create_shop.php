<?php
// Script de test pour valider la création d'un magasin
echo "🧪 Test de création d'un magasin...\n\n";

// Simuler les données POST d'un nouveau magasin
$test_shop_name = "Test Magasin Automatique";
$test_subdomain = "test-auto-" . date('His'); // Sous-domaine unique avec timestamp

echo "📋 Données du test :\n";
echo "- Nom : $test_shop_name\n";
echo "- Sous-domaine : $test_subdomain\n";
echo "- URL de test : https://mdgeek.top/superadmin/create_shop.php\n\n";

// Créer les données POST
$post_data = [
    'shop_name' => $test_shop_name,
    'subdomain' => $test_subdomain
];

// Convertir en format URL-encoded
$post_string = http_build_query($post_data);

echo "🚀 Envoi de la requête de création...\n";

// Configuration cURL pour envoyer la requête POST
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://mdgeek.top/superadmin/create_shop.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_USERAGENT, 'Test-Script/1.0');

// Headers pour simuler un navigateur
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 Résultat de la requête :\n";
echo "- Code HTTP : $http_code\n";

if ($error) {
    echo "- Erreur cURL : $error\n";
} else {
    echo "- Taille de la réponse : " . strlen($response) . " octets\n";
    
    // Analyser la réponse pour voir si la création a réussi
    if (strpos($response, 'Magasin créé avec succès') !== false) {
        echo "✅ SUCCESS : Le magasin semble avoir été créé avec succès !\n";
        
        if (strpos($response, 'Mapping automatique mis à jour') !== false) {
            echo "✅ SUCCESS : Le mapping automatique a été mis à jour !\n";
        } else if (strpos($response, 'Le mapping automatique n\'a pas pu être mis à jour') !== false) {
            echo "⚠️  WARNING : Le mapping automatique n'a pas pu être mis à jour\n";
        }
    } else if (strpos($response, 'Erreur') !== false) {
        echo "❌ ERROR : Une erreur s'est produite lors de la création\n";
        
        // Extraire les messages d'erreur
        if (preg_match('/<div class="alert alert-danger">(.*?)<\/div>/s', $response, $matches)) {
            echo "Détails de l'erreur : " . strip_tags($matches[1]) . "\n";
        }
    }
}

echo "\n🔍 Vérification du mapping dans login_auto.php...\n";
echo "Recherche du sous-domaine '$test_subdomain' dans le fichier...\n";
?> 