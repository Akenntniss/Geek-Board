<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

// Activer la journalisation des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Vérifier l'ID du client
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

if ($client_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID client invalide']);
    exit;
}

try {
    // Vérifier la connexion à la base de données
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }
    
    // Créer un client simulé pour débogage
    $client = [
        'id' => $client_id,
        'nom' => 'Dupont',
        'prenom' => 'Jean',
        'email' => 'jean.dupont@example.com',
        'telephone' => '0606060606',
        'adresse' => '123 Rue de la République, 75001 Paris',
        'date_creation' => date('Y-m-d H:i:s')
    ];
    
    // Créer un historique simulé
    $formatted_historique = [];
    $statuts = ['En cours', 'Terminé', 'En attente', 'Livré'];
    $appareils = ['Smartphone', 'Tablette', 'Ordinateur portable', 'Montre connectée'];
    $modeles = ['iPhone 12', 'Samsung Galaxy S21', 'iPad Pro', 'MacBook Air'];
    
    for ($i = 1; $i <= 3; $i++) {
        $statut = $statuts[array_rand($statuts)];
        $formatted_historique[] = [
            'id' => $i,
            'type_appareil' => $appareils[array_rand($appareils)],
            'modele' => $modeles[array_rand($modeles)],
            'probleme' => 'Exemple de problème ' . $i,
            'statut' => $statut,
            'statusColor' => ($statut == 'Terminé' ? 'success' : ($statut == 'En cours' ? 'primary' : 'warning')),
            'date_creation' => date('d/m/Y', strtotime('-' . $i . ' days'))
        ];
    }
    
    // Retourner les informations
    echo json_encode([
        'success' => true,
        'client' => $client,
        'historique' => $formatted_historique
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
} 