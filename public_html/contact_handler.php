<?php
// Handler pour le formulaire de contact de la page de landing
session_start();

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et nettoyer les données du formulaire
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$company = trim($_POST['company'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validation des champs obligatoires
$errors = [];

if (empty($firstName)) {
    $errors[] = 'Le prénom est requis';
}

if (empty($lastName)) {
    $errors[] = 'Le nom est requis';
}

if (empty($email)) {
    $errors[] = 'L\'email est requis';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'L\'email n\'est pas valide';
}

if (empty($subject)) {
    $errors[] = 'Le sujet est requis';
}

if (empty($message)) {
    $errors[] = 'Le message est requis';
}

// Si des erreurs sont présentes, retourner les erreurs
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Préparer le contenu de l'email
$email_subject = "Contact GeekBoard - " . ucfirst($subject);
$email_body = "
Nouveau message de contact depuis la page de landing GeekBoard

Informations du contact :
- Prénom : $firstName
- Nom : $lastName
- Email : $email
- Téléphone : $phone
- Entreprise : $company
- Sujet : $subject

Message :
$message

---
Envoyé depuis : " . $_SERVER['HTTP_HOST'] . "
Date : " . date('d/m/Y H:i:s') . "
IP : " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . "
";

// Headers pour l'email
$headers = "From: noreply@mdgeek.top\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Email de destination (à modifier selon vos besoins)
$to_email = "contact@mdgeek.top";

// Essayer d'envoyer l'email
$mail_sent = mail($to_email, $email_subject, $email_body, $headers);

// Optionnel : Sauvegarder dans une base de données
try {
    // Si vous voulez sauvegarder les contacts dans la base de données
    require_once __DIR__ . '/config/database.php';
    
    $pdo = getMainDBConnection();
    
    // Créer la table si elle n'existe pas
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS contact_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        company VARCHAR(255),
        subject VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($create_table_sql);
    
    // Insérer la demande de contact
    $stmt = $pdo->prepare("
        INSERT INTO contact_requests 
        (first_name, last_name, email, phone, company, subject, message, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $firstName,
        $lastName,
        $email,
        $phone,
        $company,
        $subject,
        $message,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
} catch (Exception $e) {
    // Logger l'erreur mais ne pas faire échouer la requête
    error_log("Erreur lors de la sauvegarde du contact : " . $e->getMessage());
}

// Retourner la réponse
if ($mail_sent) {
    echo json_encode([
        'success' => true, 
        'message' => 'Votre message a été envoyé avec succès ! Nous vous recontacterons bientôt.'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de l\'envoi du message. Veuillez réessayer plus tard.'
    ]);
}
?> 