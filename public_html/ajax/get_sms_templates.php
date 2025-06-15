<?php
// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Récupérer les chemins des fichiers includes
$config_path = realpath(__DIR__ . '/../config/database.php');
$functions_path = realpath(__DIR__ . '/../includes/functions.php');

if (!file_exists($config_path) || !file_exists($functions_path)) {
    echo json_encode([
        'success' => false,
        'error' => 'Fichiers de configuration introuvables.'
    ]);
    exit;
}

// Inclure les fichiers nécessaires
require_once $config_path;
require_once $functions_path;

try {
    // Obtenir la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    if (!$shop_pdo) {
        echo json_encode([
            'success' => false,
            'error' => 'Impossible de se connecter à la base de données du magasin.'
        ]);
        exit;
    }
    
    // Vérifier si la table sms_templates existe
    $checkTable = $shop_pdo->query("SHOW TABLES LIKE 'sms_templates'");
    if ($checkTable->rowCount() === 0) {
        echo json_encode([
            'success' => true,
            'templates' => [],
            'message' => 'Table sms_templates non trouvée'
        ]);
        exit;
    }
    
    // Récupérer tous les modèles de SMS actifs
    $stmt = $shop_pdo->query("
        SELECT id, nom, contenu
        FROM sms_templates 
        WHERE est_actif = 1
        ORDER BY nom ASC
    ");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si aucun modèle actif n'est trouvé, créer des modèles par défaut
    if (empty($templates)) {
        $defaultTemplates = [
            [
                'nom' => 'Réparation prête',
                'contenu' => 'Bonjour, votre réparation est terminée et prête à être récupérée. Merci.'
            ],
            [
                'nom' => 'Devis proposé',
                'contenu' => 'Bonjour, nous avons établi un devis pour votre réparation. Merci de nous contacter.'
            ],
            [
                'nom' => 'Diagnostic terminé',
                'contenu' => 'Bonjour, le diagnostic de votre appareil est terminé. Nous vous recontactons très prochainement.'
            ]
        ];
        
        // Insérer les modèles par défaut
        $insertStmt = $shop_pdo->prepare("INSERT INTO sms_templates (nom, contenu, est_actif) VALUES (?, ?, 1)");
        foreach ($defaultTemplates as $template) {
            $insertStmt->execute([$template['nom'], $template['contenu']]);
        }
        
        // Re-récupérer les modèles
        $stmt = $shop_pdo->query("
            SELECT id, nom, contenu
            FROM sms_templates 
            WHERE est_actif = 1
            ORDER BY nom ASC
        ");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Retourner les informations des modèles
    echo json_encode([
        'success' => true,
        'templates' => $templates
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur dans get_sms_templates.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception dans get_sms_templates.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur: ' . $e->getMessage()
    ]);
} 