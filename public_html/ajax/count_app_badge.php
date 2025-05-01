<?php
// Désactiver l'affichage des erreurs pour éviter de corrompre la sortie JSON
ini_set('display_errors', 0);
error_reporting(0);

// Initialiser la session
session_start();

// S'assurer que la réponse est toujours en JSON
header('Content-Type: application/json');

try {
    // Vérifier le chemin d'inclusion correct
    $config_file = '../config/database.php';
    
    // Vérifier si le fichier existe
    if (!file_exists($config_file)) {
        throw new Exception("Fichier de configuration non trouvé: $config_file");
    }
    
    // Inclure la configuration et les fonctions
    include_once $config_file;

    // Vérifier que les variables de connexion sont définies
    if (!isset($host) || !isset($dbname) || !isset($username) || !isset($password)) {
        throw new Exception("Paramètres de connexion à la base de données manquants");
    }

    // Connexion à la base de données
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        throw new Exception("Erreur de connexion à la base de données: " . $e->getMessage());
    }

    // Initialiser le compteur
    $notificationCount = 0;
    $notifications = [
        'commandes_en_attente' => 0,
        'commandes_urgent' => 0,
        'reparations_nouveau_diagnostique' => 0,
        'reparations_nouvelle_intervention' => 0,
        'reparations_nouvelle_commande' => 0
    ];

    // 1. Compter les commandes avec statut 'en_attente'
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes_pieces WHERE statut = 'en_attente'");
    $stmt->execute();
    $notifications['commandes_en_attente'] = (int)$stmt->fetchColumn();
    $notificationCount += $notifications['commandes_en_attente'];

    // 2. Compter les commandes avec statut 'urgent'
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes_pieces WHERE statut = 'urgent'");
    $stmt->execute();
    $notifications['commandes_urgent'] = (int)$stmt->fetchColumn();
    $notificationCount += $notifications['commandes_urgent'];

    // 3. Compter les réparations avec statut 'nouveau_diagnostique'
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reparations WHERE statut = 'nouveau_diagnostique'");
    $stmt->execute();
    $notifications['reparations_nouveau_diagnostique'] = (int)$stmt->fetchColumn();
    $notificationCount += $notifications['reparations_nouveau_diagnostique'];

    // 4. Compter les réparations avec statut 'nouvelle_intervention'
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reparations WHERE statut = 'nouvelle_intervention'");
    $stmt->execute();
    $notifications['reparations_nouvelle_intervention'] = (int)$stmt->fetchColumn();
    $notificationCount += $notifications['reparations_nouvelle_intervention'];

    // 5. Compter les réparations avec statut 'nouvelle_commande'
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reparations WHERE statut = 'nouvelle_commande'");
    $stmt->execute();
    $notifications['reparations_nouvelle_commande'] = (int)$stmt->fetchColumn();
    $notificationCount += $notifications['reparations_nouvelle_commande'];

    // Renvoyer le résultat
    echo json_encode([
        'success' => true,
        'count' => $notificationCount,
        'details' => $notifications
    ]);

} catch (Exception $e) {
    // En cas d'erreur, renvoyer un message d'erreur
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'count' => 0
    ]);
} 