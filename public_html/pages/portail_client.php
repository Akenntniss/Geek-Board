<?php
// Configuration et initialisation
require_once '../config/database.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté comme client
session_start();
$client_logged_in = isset($_SESSION['client_id']);
$error_message = '';

// Traitement de la connexion client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
    $telephone = isset($_POST['telephone']) ? cleanInput($_POST['telephone']) : '';
    
    if (empty($email) || empty($telephone)) {
        $error_message = "Veuillez remplir tous les champs";
    } else {
        try {
            // Vérifier les informations du client
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ? AND telephone = ?");
            $stmt->execute([$email, $telephone]);
            $client = $stmt->fetch();
            
            if ($client) {
                // Créer la session client
                $_SESSION['client_id'] = $client['id'];
                $_SESSION['client_nom'] = $client['nom'];
                $_SESSION['client_prenom'] = $client['prenom'];
                $_SESSION['client_email'] = $client['email'];
                
                // Rediriger vers la même page pour afficher le tableau de bord
                header('Location: portail_client.php');
                exit;
            } else {
                $error_message = "Informations incorrectes. Veuillez réessayer.";
            }
        } catch (PDOException $e) {
            $error_message = "Erreur de connexion à la base de données";
            error_log("Erreur PDO : " . $e->getMessage());
        }
    }
}

// Récupération des réparations du client si connecté
$reparations = [];
if ($client_logged_in) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, s.nom as statut_nom, sc.couleur as statut_couleur 
            FROM reparations r
            LEFT JOIN statuts s ON r.statut_id = s.id
            LEFT JOIN statut_categories sc ON s.categorie_id = sc.id
            WHERE r.client_id = ?
            ORDER BY r.date_modification DESC
        ");
        $stmt->execute([$_SESSION['client_id']]);
        $reparations = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des réparations : " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Portail Client - Suivez vos réparations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0078e8;
            --primary-light: #e6f2ff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .navbar-brand img {
            height: 40px;
        }
        
        .client-header {
            background: linear-gradient(135deg, var(--primary-color), #4a96e6);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .repair-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .repair-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .repair-status {
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.8rem;
            border-radius: 30px;
            display: inline-block;
        }
        
        .repair-details {
            padding: 1.5rem;
        }
        
        .timeline {
            position: relative;
            margin: 1.5rem 0;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 30px;
            margin-bottom: 15px;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: var(--primary-light);
        }
        
        .timeline-dot {
            position: absolute;
            left: -6px;
            top: 5px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: var(--primary-color);
            z-index: 1;
        }
        
        .timeline-content {
            padding: 5px 0;
        }
        
        .stage-done .timeline-dot {
            background-color: var(--success-color);
        }
        
        .stage-current .timeline-dot {
            background-color: var(--primary-color);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 120, 232, 0.6);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(0, 120, 232, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(0, 120, 232, 0);
            }
        }
        
        .feedback-form {
            margin-top: 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #0065c8;
            border-color: #0065c8;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .footer {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-card, .repair-card {
                margin: 1rem;
            }
            
            .client-header {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="../assets/images/logo/logodarkmode.png" alt="GeekBoard Logo">
                <span class="ms-2">GeekBoard</span>
            </a>
            <?php if ($client_logged_in): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-tools me-1"></i> Mes réparations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-user me-1"></i> Mon profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="portail_client.php?logout=1"><i class="fas fa-sign-out-alt me-1"></i> Déconnexion</a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>

    <?php if ($client_logged_in): ?>
        <!-- Header pour client connecté -->
        <header class="client-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['client_prenom'] . ' ' . $_SESSION['client_nom']); ?></h1>
                        <p class="lead">Suivez l'avancement de vos réparations en temps réel</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="#" class="btn btn-light"><i class="fas fa-plus-circle me-2"></i> Nouvelle demande</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Tableau de bord du client -->
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Suivez en temps réel l'avancement de vos réparations et recevez des notifications à chaque étape.
                    </div>
                </div>
            </div>

            <!-- Liste des réparations -->
            <h2 class="mb-4">Vos réparations</h2>
            
            <?php if (empty($reparations)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> Vous n'avez pas encore de réparation en cours.
                </div>
            <?php else: ?>
                <?php foreach ($reparations as $reparation): ?>
                    <div class="repair-card card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-laptop-medical me-2"></i>
                                <?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['marque'] . ' ' . $reparation['modele']); ?>
                            </h5>
                            <span class="repair-status" style="background-color: <?php echo !empty($reparation['statut_couleur']) ? '#' . $reparation['statut_couleur'] : '#6c757d'; ?>; color: white;">
                                <?php echo !empty($reparation['statut_nom']) ? htmlspecialchars($reparation['statut_nom']) : htmlspecialchars($reparation['statut']); ?>
                            </span>
                        </div>
                        <div class="repair-details">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-calendar-alt me-2"></i> Reçu le: <?php echo date('d/m/Y', strtotime($reparation['date_reception'])); ?>
                                    </p>
                                    <p class="mb-3">
                                        <strong>Problème signalé:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?>
                                    </p>
                                    
                                    <?php if (!empty($reparation['notes_techniques'])): ?>
                                    <div class="mb-3">
                                        <strong>Notes techniques:</strong><br>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($reparation['notes_techniques'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($reparation['prix_reparation'])): ?>
                                    <div class="alert alert-primary d-flex align-items-center">
                                        <i class="fas fa-euro-sign fs-5 me-2"></i>
                                        <div>
                                            <strong>Prix de la réparation:</strong> <?php echo number_format($reparation['prix_reparation'], 2, ',', ' '); ?> €
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-3">Progression de votre réparation</h6>
                                    <div class="timeline">
                                        <div class="timeline-item stage-done">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content">
                                                <p class="mb-0"><strong>Réception</strong></p>
                                                <p class="text-muted small"><?php echo date('d/m/Y', strtotime($reparation['date_reception'])); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="timeline-item <?php echo (strpos($reparation['statut'], 'diag') !== false || strpos($reparation['statut'], 'cours') !== false) ? 'stage-current' : ''; ?>">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content">
                                                <p class="mb-0"><strong>Diagnostic</strong></p>
                                                <p class="text-muted small">Identification du problème</p>
                                            </div>
                                        </div>
                                        
                                        <div class="timeline-item <?php echo (strpos($reparation['statut'], 'cours_intervention') !== false) ? 'stage-current' : ''; ?>">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content">
                                                <p class="mb-0"><strong>Réparation</strong></p>
                                                <p class="text-muted small">Travaux de réparation</p>
                                            </div>
                                        </div>
                                        
                                        <div class="timeline-item <?php echo (strpos($reparation['statut'], 'effectue') !== false) ? 'stage-current' : ''; ?>">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content">
                                                <p class="mb-0"><strong>Terminé</strong></p>
                                                <p class="text-muted small">Prêt pour récupération</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 d-flex justify-content-end">
                                        <a href="#" class="btn btn-outline-primary btn-sm me-2" data-repair-id="<?php echo $reparation['id']; ?>">
                                            <i class="fas fa-question-circle me-1"></i> Poser une question
                                        </a>
                                        <a href="#" class="btn btn-primary btn-sm" data-repair-id="<?php echo $reparation['id']; ?>">
                                            <i class="fas fa-eye me-1"></i> Détails
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Page de connexion -->
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="text-center my-5">
                        <img src="../assets/images/logo/logodarkmode.png" alt="GeekBoard Logo" class="img-fluid mb-4" style="max-width: 150px;">
                        <h2>Portail Client</h2>
                        <p class="lead text-muted">Suivez l'avancement de vos réparations en temps réel</p>
                    </div>
                    
                    <div class="login-card">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="portail_client.php">
                            <input type="hidden" name="action" value="login">
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required placeholder="Votre adresse email">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="telephone" class="form-label">Numéro de téléphone</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" required placeholder="Votre numéro de téléphone">
                                </div>
                                <small class="form-text text-muted">Le numéro que vous avez fourni lors de la dépose de votre appareil</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                            </button>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">Pas de compte ? Contactez-nous pour accéder à vos réparations</small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>GeekBoard</h5>
                    <p>Solution professionnelle pour le suivi de vos réparations informatiques et électroniques.</p>
                </div>
                <div class="col-md-4">
                    <h5>Liens utiles</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50">Conditions générales</a></li>
                        <li><a href="#" class="text-white-50">Politique de confidentialité</a></li>
                        <li><a href="#" class="text-white-50">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <ul class="list-unstyled text-white-50">
                        <li><i class="fas fa-phone me-2"></i> (123) 456-7890</li>
                        <li><i class="fas fa-envelope me-2"></i> contact@geekboard.com</li>
                    </ul>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="text-white-50 mb-0">&copy; <?php echo date('Y'); ?> GeekBoard. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script pour les interactions client
        document.addEventListener('DOMContentLoaded', function() {
            // Animation des cartes de réparation
            const repairCards = document.querySelectorAll('.repair-card');
            repairCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-5px)';
                });
            });
            
            // Gestionnaire pour les boutons "Poser une question"
            const questionButtons = document.querySelectorAll('[data-repair-id]');
            questionButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.innerHTML.includes('Poser une question')) {
                        e.preventDefault();
                        const repairId = this.getAttribute('data-repair-id');
                        // Ici vous pourriez ouvrir un modal pour poser une question
                        alert('Cette fonctionnalité sera bientôt disponible !');
                    }
                });
            });
        });
    </script>
</body>
</html> 