<?php
// Activer temporairement l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Démarrer la session pour accéder aux informations de l'utilisateur connecté
session_start();

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Récupérer les données JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Vérifier que les données requises sont présentes
    if (!isset($data['repair_id'])) {
        throw new Exception('Données manquantes: ID de réparation non fourni');
    }

    $repair_id = (int)$data['repair_id'];
    $send_sms = isset($data['send_sms']) ? (bool)$data['send_sms'] : false;
    $sms_text = isset($data['sms_text']) ? $data['sms_text'] : '';

    // Récupérer les informations sur la réparation et le client
    $stmt = $shop_pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.email as client_email, c.telephone as client_telephone
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$repair_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reparation) {
        throw new Exception('Réparation non trouvée');
    }

    // Vérifier que la réparation est en attente d'accord client
    if ($reparation['statut'] !== 'en_attente_accord_client') {
        throw new Exception('La réparation n\'est pas en attente d\'accord client');
    }

    // Générer le lien d'acceptation de devis
    $lien_acceptation = genererLienAcceptationDevis($repair_id, $reparation['client_email']);

    // Préparer l'email
    $destinataire = $reparation['client_email'];
    $sujet = 'Acceptation de devis - Réparation #' . $repair_id;
    
    $message = '
    <html>
    <head>
        <title>Acceptation de devis</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #0078e8; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f5f7fa; }
            .button { display: inline-block; background-color: #28a745; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; margin-top: 20px; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Devis de réparation</h1>
            </div>
            <div class="content">
                <p>Bonjour ' . htmlspecialchars($reparation['client_prenom'] . ' ' . $reparation['client_nom']) . ',</p>
                
                <p>Suite au diagnostic de votre appareil, nous avons le plaisir de vous faire parvenir un devis pour sa réparation.</p>
                
                <p><strong>Détails de la réparation :</strong></p>
                <ul>
                    <li>Appareil : ' . htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['marque'] . ' ' . $reparation['modele']) . '</li>
                    <li>Problème : ' . htmlspecialchars($reparation['description_probleme']) . '</li>';
    
    if (!empty($reparation['notes_techniques'])) {
        $message .= '<li>Diagnostic : ' . htmlspecialchars($reparation['notes_techniques']) . '</li>';
    }
    
    $message .= '<li>Montant du devis : ' . formatPrice($reparation['prix_reparation']) . '</li>
                </ul>
                
                <p>Pour accepter ce devis et nous autoriser à procéder à la réparation, veuillez cliquer sur le bouton ci-dessous :</p>
                
                <p style="text-align: center;">
                    <a href="' . $lien_acceptation . '" class="button">Accepter le devis</a>
                </p>
                
                <p>Vous pouvez également accéder directement à la page d\'acceptation via ce lien :</p>
                <p>' . $lien_acceptation . '</p>
                
                <p>Si vous avez des questions concernant ce devis, n\'hésitez pas à nous contacter.</p>
                
                <p>Cordialement,<br>L\'équipe technique</p>
            </div>
            <div class="footer">
                <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.</p>
            </div>
        </div>
    </body>
    </html>';
    
    // En-têtes pour l'email HTML
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
    $headers .= 'From: support@votredomaine.com' . "\r\n";
    
    // Envoyer l'email
    $envoi_reussi = mail($destinataire, $sujet, $message, $headers);
    
    if (!$envoi_reussi) {
        throw new Exception('Erreur lors de l\'envoi de l\'email');
    }
    
    // Envoyer un SMS si demandé
    $sms_status = null;
    if ($send_sms && !empty($reparation['client_telephone'])) {
        // Utiliser la fonction send_sms si elle existe
        if (function_exists('send_sms')) {
            $sms_status = send_sms($reparation['client_telephone'], $sms_text);
        } else {
            // Méthode alternative d'envoi de SMS (exemple)
            // Ici vous pourriez implémenter votre propre logique d'envoi de SMS
            // ou utiliser une API tierce
            
            // Simulation de succès pour l'exemple
            $sms_status = true;
        }
    }
    
    // Journaliser l'envoi du lien
    $employe_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    if ($employe_id) {
        $stmt = $shop_pdo->prepare("
            INSERT INTO reparation_logs 
            (reparation_id, employe_id, action_type, date_action, details) 
            VALUES (?, ?, 'envoi_lien_devis', NOW(), ?)
        ");
        
        $details = "Envoi du lien d'acceptation de devis à l'adresse " . $reparation['client_email'];
        
        if ($send_sms && $sms_status) {
            $details .= " et par SMS au " . $reparation['client_telephone'];
        }
        
        $stmt->execute([
            $repair_id, 
            $employe_id, 
            $details
        ]);
    }
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Lien d\'acceptation envoyé avec succès à ' . $reparation['client_email'],
        'lien' => $lien_acceptation,
        'sms_sent' => $send_sms && $sms_status,
        'sms_status' => $sms_status
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 