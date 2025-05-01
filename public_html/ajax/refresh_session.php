<?php
// Définir l'en-tête JSON avant tout
header('Content-Type: application/json');

// Force le démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    // Session ID explicite (via cookie ou URL)
    if (isset($_COOKIE['PHPSESSID'])) {
        session_id($_COOKIE['PHPSESSID']);
    } elseif (isset($_GET['sid'])) {
        session_id($_GET['sid']);
    }
    
    // Configuration de la session
    ini_set('session.use_only_cookies', 0);
    ini_set('session.use_cookies', 1);
    ini_set('session.use_trans_sid', 1);
    ini_set('session.cache_limiter', 'private');
    
    // Augmenter la durée de la session
    ini_set('session.gc_maxlifetime', 86400); // 24 heures
    
    session_start();
}

// Inclusion des fichiers nécessaires
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Log des informations de session
error_log("Session refresh - ID: " . session_id());
error_log("Session refresh - Contenu: " . print_r($_SESSION, true));

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Rafraîchir la session en mettant à jour le timestamp
    $_SESSION['last_activity'] = time();
    
    // Vérifier que l'utilisateur existe toujours en base de données
    try {
        $stmt = $pdo->prepare("SELECT id, nom, prenom FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Mise à jour des données de session si nécessaire
            $_SESSION['user_name'] = $user['nom'] . ' ' . $user['prenom'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Session rafraîchie avec succès',
                'session_id' => session_id(),
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['nom'] . ' ' . $user['prenom']
                ]
            ]);
        } else {
            // L'utilisateur n'existe plus, invalider la session
            session_unset();
            session_destroy();
            
            echo json_encode([
                'success' => false,
                'message' => 'Session invalide - utilisateur non trouvé'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Erreur SQL lors du rafraîchissement de session: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'Erreur technique lors du rafraîchissement de session'
        ]);
    }
} else {
    // Utilisateur non connecté
    echo json_encode([
        'success' => false,
        'message' => 'Session expirée ou utilisateur non connecté',
        'session_data' => !empty($_SESSION)
    ]);
} 