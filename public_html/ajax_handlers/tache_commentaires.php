<?php
// Définir le chemin d'accès au cookie de session
$root_path = dirname(dirname($_SERVER['SCRIPT_NAME']));
if ($root_path == '/' || $root_path == '\\') {
    $root_path = '';
}
session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 30, // 30 jours
    'path' => $root_path,
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Initialisation de la session (si ce n'est pas déjà fait)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Création du dossier logs s'il n'existe pas
if (!is_dir('../logs')) {
    mkdir('../logs', 0755, true);
}

// Log de l'état de la session
$log_message = "TACHE_COMMENTAIRES - Vérification session - ";
$log_message .= "session_id: " . session_id() . ", ";
$log_message .= "user_id présent: " . (isset($_SESSION['user_id']) ? "OUI (".$_SESSION['user_id'].")" : "NON");
file_put_contents('../logs/sms_debug.log', $log_message . "\n", FILE_APPEND);

// Pour le développement, désactivons temporairement la vérification d'authentification
/*
// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non authentifié']);
    exit;
}
*/

// Paramètres de connexion à la base de données
$db_host = 'srv931.hstgr.io';
$db_name = 'u139954273_Vscodetest';
$db_user = 'u139954273_Vscodetest';
$db_pass = 'Maman01#';
$db_port = 3306;

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
    exit;
}

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer l'action à effectuer
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Loguer les données reçues
error_log('TACHE_COMMENTAIRES - Action: ' . $action);
error_log('TACHE_COMMENTAIRES - Données POST: ' . print_r($_POST, true));

// Traiter l'action demandée
switch ($action) {
    case 'modifier_tache':
        // Vérifier les données requises
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            error_log('TACHE_COMMENTAIRES - ID de tâche manquant');
            echo json_encode(['success' => false, 'message' => 'ID de tâche manquant']);
            exit;
        }

        $tache_id = (int)$_POST['id'];
        $titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $priorite = isset($_POST['priorite']) ? trim($_POST['priorite']) : 'moyenne';
        $statut = isset($_POST['statut']) ? trim($_POST['statut']) : 'a_faire';
        $date_limite = isset($_POST['date_limite']) && !empty($_POST['date_limite']) ? $_POST['date_limite'] : null;
        $employe_id = isset($_POST['employe_id']) && !empty($_POST['employe_id']) ? (int)$_POST['employe_id'] : null;

        // Validation des données
        if (empty($titre)) {
            echo json_encode(['success' => false, 'message' => 'Le titre est obligatoire']);
            exit;
        }

        try {
            // Mise à jour de la tâche
            $query = "
                UPDATE taches 
                SET titre = ?, description = ?, priorite = ?, statut = ?, 
                    date_limite = ?, employe_id = ?
                WHERE id = ?
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $titre,
                $description,
                $priorite,
                $statut,
                $date_limite,
                $employe_id,
                $tache_id
            ]);

            // Vérifier si la mise à jour a réussi
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Tâche modifiée avec succès']);
            } else {
                // Vérifier si la tâche existe
                $check = $pdo->prepare("SELECT id FROM taches WHERE id = ?");
                $check->execute([$tache_id]);
                if ($check->fetchColumn()) {
                    echo json_encode(['success' => true, 'message' => 'Aucune modification n\'a été apportée']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Tâche non trouvée']);
                }
            }
        } catch (PDOException $e) {
            error_log('TACHE_COMMENTAIRES - Erreur SQL: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification de la tâche: ' . $e->getMessage()]);
        }
        break;

    case 'changer_statut':
        // Vérifier les données requises
        if (!isset($_POST['tache_id']) || empty($_POST['tache_id']) || !isset($_POST['statut']) || empty($_POST['statut'])) {
            echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
            exit;
        }

        $tache_id = (int)$_POST['tache_id'];
        $statut = trim($_POST['statut']);

        // Valider le statut
        if (!in_array($statut, ['a_faire', 'en_cours', 'termine', 'annule'])) {
            echo json_encode(['success' => false, 'message' => 'Statut invalide']);
            exit;
        }

        try {
            // Mise à jour du statut
            $query = "UPDATE taches SET statut = ? WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$statut, $tache_id]);

            // Si le statut est "termine", mettre à jour la date de fin
            if ($statut === 'termine') {
                $query = "UPDATE taches SET date_fin = NOW() WHERE id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$tache_id]);
            }

            echo json_encode(['success' => true, 'message' => 'Statut modifié avec succès']);
        } catch (PDOException $e) {
            error_log('TACHE_COMMENTAIRES - Erreur SQL: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification du statut: ' . $e->getMessage()]);
        }
        break;

    case 'changer_priorite':
        // Vérifier les données requises
        if (!isset($_POST['tache_id']) || empty($_POST['tache_id']) || !isset($_POST['priorite']) || empty($_POST['priorite'])) {
            echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
            exit;
        }

        $tache_id = (int)$_POST['tache_id'];
        $priorite = trim($_POST['priorite']);

        // Valider la priorité
        if (!in_array($priorite, ['basse', 'moyenne', 'haute', 'urgente'])) {
            echo json_encode(['success' => false, 'message' => 'Priorité invalide']);
            exit;
        }

        try {
            // Mise à jour de la priorité
            $query = "UPDATE taches SET priorite = ? WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$priorite, $tache_id]);

            echo json_encode(['success' => true, 'message' => 'Priorité modifiée avec succès']);
        } catch (PDOException $e) {
            error_log('TACHE_COMMENTAIRES - Erreur SQL: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification de la priorité: ' . $e->getMessage()]);
        }
        break;

    case 'changer_employe':
        // Vérifier les données requises
        if (!isset($_POST['tache_id']) || empty($_POST['tache_id'])) {
            echo json_encode(['success' => false, 'message' => 'ID de tâche manquant']);
            exit;
        }

        $tache_id = (int)$_POST['tache_id'];
        $employe_id = isset($_POST['employe_id']) && !empty($_POST['employe_id']) ? (int)$_POST['employe_id'] : null;

        try {
            // Mise à jour de l'employé assigné
            $query = "UPDATE taches SET employe_id = ? WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$employe_id, $tache_id]);

            echo json_encode(['success' => true, 'message' => 'Assignation modifiée avec succès']);
        } catch (PDOException $e) {
            error_log('TACHE_COMMENTAIRES - Erreur SQL: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification de l\'assignation: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        break;
}

// Assurer que tout est envoyé et fermer la connexion
header('Content-Type: application/json');
exit;
?> 