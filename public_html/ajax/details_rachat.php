<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aucune restriction d'accès - tous les utilisateurs peuvent accéder à ces données
// Si vous souhaitez rétablir la restriction plus tard, décommentez le code ci-dessous
/*
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}
*/

// Vérifier l'ID du rachat
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'ID invalide']);
    exit;
}

try {
    // Obtenir la connexion à la base de données du magasin
    $pdo = getShopDBConnection();
    if ($pdo === null) {
        throw new Exception("La connexion à la base de données n'est pas disponible");
    }
    
    $stmt = $pdo->prepare("SELECT 
        r.id,
        r.date_rachat,
        r.type_appareil,
        r.modele,
        r.sin,
        r.prix,
        r.fonctionnel,
        r.photo_identite,
        r.photo_appareil,
        r.client_photo,
        r.signature,
        c.nom,
        c.prenom
    FROM rachat_appareils r
    JOIN clients c ON r.client_id = c.id
    WHERE r.id = ?");
    
    $stmt->execute([$_GET['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Rachat introuvable']);
        exit;
    }

    // Formater les chemins des images
    $result['photo_identite'] = $result['photo_identite'] ? '/assets/images/rachat/' . $result['photo_identite'] : null;
    $result['photo_appareil'] = $result['photo_appareil'] ? '/assets/images/rachat/' . $result['photo_appareil'] : null;

    // Lire le contenu des fichiers de photo client et signature
    if ($result['client_photo']) {
        $client_photo_path = __DIR__ . '/../assets/images/rachat/' . $result['client_photo'];
        if (file_exists($client_photo_path)) {
            $photo_content = base64_encode(file_get_contents($client_photo_path));
            $result['client_photo'] = 'data:image/jpeg;base64,' . $photo_content;
        } else {
            $result['client_photo'] = null;
        }
    }

    if ($result['signature']) {
        $signature_path = __DIR__ . '/../assets/images/rachat/' . $result['signature'];
        if (file_exists($signature_path)) {
            $signature_content = base64_encode(file_get_contents($signature_path));
            $result['signature'] = 'data:image/png;base64,' . $signature_content;
        } else {
            $result['signature'] = null;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($result);

} catch (Exception $e) {
    error_log('Erreur: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Erreur: ' . $e->getMessage()]);
}
?>