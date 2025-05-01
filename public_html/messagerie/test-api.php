<?php
// Fichier de test pour l'API SMS
require_once '../includes/functions.php';
require_once '../database.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// En-têtes pour le format texte
header('Content-Type: text/plain');

echo "=== Test de l'API SMS ===\n\n";

// Paramètres du test
$recipient = '+33600000000'; // Remplacez par un numéro de test valide
$message = 'Ceci est un message de test envoyé le ' . date('d/m/Y H:i:s');

echo "Envoi à: $recipient\n";
echo "Message: $message\n\n";

// Test 1: Vérification des variables et constantes
echo "=== Configuration ===\n";
echo "URL de l'API: https://api.sms-gate.app:443/api/v1/messages\n";
echo "Nom d'utilisateur: -GCB75\n";
echo "Mot de passe: " . (defined('PASSWORD') ? "Défini" : "Non défini") . "\n\n";

// Test 2: Connexion à la base de données
echo "=== Vérification de la base de données ===\n";
if (isset($conn) && $conn instanceof PDO) {
    echo "Connexion à la base de données: OK\n";
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'sms_logs'");
        $table_exists = $stmt->rowCount() > 0;
        echo "Table 'sms_logs': " . ($table_exists ? "Existe" : "N'existe pas") . "\n\n";
    } catch (PDOException $e) {
        echo "Erreur lors de la vérification de la table: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "Connexion à la base de données: ÉCHEC\n\n";
}

// Test 3: Vérification des extensions PHP
echo "=== Vérification des extensions PHP ===\n";
echo "Extension cURL: " . (function_exists('curl_init') ? "Installée" : "Non installée") . "\n";
echo "Extension JSON: " . (function_exists('json_encode') ? "Installée" : "Non installée") . "\n";
echo "Extension PDO: " . (class_exists('PDO') ? "Installée" : "Non installée") . "\n\n";

// Test 4: Test direct de l'API avec cURL
echo "=== Test direct avec cURL ===\n";
$url = 'https://api.sms-gate.app:443/api/v1/messages';
$username = '-GCB75';
$password = 'wkbteo4zox0p4q';
$data = [
    'phone' => $recipient,
    'message' => $message
];

// Initialisation de cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Récupérer les informations d'erreur CURL dans un fichier temporaire
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Exécution de la requête
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Vérification des erreurs
if ($response === false) {
    $curl_error = curl_error($ch);
    echo "Erreur cURL: $curl_error\n";
    
    // Récupérer les informations de débogage
    rewind($verbose);
    $verbose_log = stream_get_contents($verbose);
    echo "Informations détaillées:\n$verbose_log\n";
} else {
    echo "Code de statut: $status\n";
    echo "Réponse: $response\n";
}

curl_close($ch);
fclose($verbose);

// Test 5: Utilisation de la fonction send_sms
echo "\n=== Test avec la fonction send_sms ===\n";
$result = send_sms($recipient, $message);

echo "Résultat: " . ($result['success'] ? "Succès" : "Échec") . "\n";
echo "Message: " . $result['message'] . "\n";

if (!$result['success'] && isset($result['error'])) {
    echo "Erreur détaillée: " . $result['error'] . "\n";
}

echo "\n=== Fin des tests ===\n";
?> 