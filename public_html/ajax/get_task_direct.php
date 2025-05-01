<?php
// Désactiver l'affichage d'erreurs pour éviter de corrompre le JSON
error_reporting(0);
ini_set('display_errors', 0);

// Définir les en-têtes pour éviter le cache et spécifier le type de contenu
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Fonction pour nettoyer les entrées
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Créer la connexion à la base de données directement
try {
    $db_pdo = new PDO(
        "mysql:host=srv931.hstgr.io;port=3306;dbname=u139954273_Vscodetest;charset=utf8mb4",
        "u139954273_Vscodetest",
        "Maman01#",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Vérifier si l'ID de la tâche est fourni
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de tâche non spécifié'
        ]);
        exit;
    }
    
    $tache_id = (int)$_GET['id'];
    
    // Récupérer les informations de la tâche
    $stmt = $db_pdo->prepare("SELECT * FROM taches WHERE id = ?");
    $stmt->execute([$tache_id]);
    $tache = $stmt->fetch();
    
    // Vérifier si la tâche existe
    if (!$tache) {
        echo json_encode([
            'success' => false,
            'message' => 'Tâche non trouvée'
        ]);
        exit;
    }
    
    // Récupérer les informations de l'employé si nécessaire
    if (!empty($tache['employe_id'])) {
        $stmt = $db_pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$tache['employe_id']]);
        $employe = $stmt->fetch();
        
        if ($employe) {
            $tache['employe_nom'] = $employe['full_name'];
        }
    }
    
    // Récupérer le créateur si nécessaire
    if (!empty($tache['created_by'])) {
        $stmt = $db_pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$tache['created_by']]);
        $createur = $stmt->fetch();
        
        if ($createur) {
            $tache['createur_nom'] = $createur['full_name'];
        }
    }
    
    // Formater les données
    if (isset($tache['date_limite']) && $tache['date_limite']) {
        $tache['date_limite'] = date('Y-m-d', strtotime($tache['date_limite']));
    }
    
    // Retourner les données en JSON
    echo json_encode([
        'success' => true,
        'message' => 'Tâche récupérée avec succès',
        'task' => $tache
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération de la tâche: ' . $e->getMessage()
    ]);
} 