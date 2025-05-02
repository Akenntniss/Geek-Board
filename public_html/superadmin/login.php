<?php
// Page de connexion du super administrateur
session_start();

// Rediriger si déjà connecté
if (isset($_SESSION['superadmin_id'])) {
    header('Location: index.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

$error = '';
$username = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Rechercher l'utilisateur dans la base de données
        $pdo = getMainDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM superadmins WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['superadmin_id'] = $user['id'];
            $_SESSION['superadmin_username'] = $user['username'];
            $_SESSION['superadmin_name'] = $user['full_name'];
            
            // Redirection vers le tableau de bord
            header('Location: index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects ou compte inactif.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeekBoard - Connexion Super Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            align-items: center;
            background-color: #f5f5f5;
        }
        .form-signin {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }
        .form-signin .form-floating:focus-within {
            z-index: 2;
        }
        .form-signin input[type="text"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo-text {
            font-weight: bold;
            font-size: 1.8rem;
            color: #0d6efd;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <main class="form-signin">
        <form method="post" action="">
            <div class="logo-container">
                <i class="fas fa-tools fa-4x text-primary"></i>
                <div class="logo-text">GeekBoard</div>
                <h4 class="mt-3 mb-3 fw-normal text-muted">Administration centrale</h4>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Nom d'utilisateur" value="<?php echo htmlspecialchars($username); ?>" required>
                <label for="username">Nom d'utilisateur</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                <label for="password">Mot de passe</label>
            </div>
            
            <button class="w-100 btn btn-lg btn-primary" type="submit">
                <i class="fas fa-sign-in-alt me-2"></i>Connexion
            </button>
            
            <p class="mt-3 mb-3 text-center">
                <a href="../index.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Retour à l'accueil
                </a>
            </p>
            
            <p class="mt-5 mb-3 text-muted text-center">&copy; <?php echo date('Y'); ?> GeekBoard</p>
        </form>
    </main>
    
    <!-- Bootstrap JS & Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 