<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0078e8;
            --primary-dark: #0056b3;
            --gradient-primary: linear-gradient(135deg, #0078e8 0%, #0056b3 100%);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
        
        .signup-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        
        .signup-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        
        .signup-header {
            background: var(--gradient-primary);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .btn-signup {
            background: var(--gradient-primary);
            border: none;
            padding: 1rem 2rem;
            font-weight: 700;
            border-radius: 50px;
            color: white;
            width: 100%;
        }
        
        .demo-alert {
            background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="signup-card">
                        <div class="signup-header">
                            <h1><i class="fas fa-rocket me-3"></i>Créez Votre Compte GeekBoard</h1>
                            <p>Rejoignez les milliers d'ateliers qui optimisent leur gestion avec GeekBoard</p>
                        </div>
                        
                        <div class="p-4">
                            <div class="demo-alert">
                                <h5><i class="fas fa-info-circle me-2"></i>Mode Démonstration</h5>
                                <p>Cette inscription est actuellement en mode démonstration. Le processus d'inscription complet sera bientôt disponible.</p>
                            </div>

                            <form id="signupForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Prénom *</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nom *</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Téléphone *</label>
                                    <input type="tel" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Nom de l'atelier *</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Type d'atelier *</label>
                                    <select class="form-control" required>
                                        <option value="">Choisissez votre spécialité</option>
                                        <option value="smartphones">Réparation Smartphones</option>
                                        <option value="ordinateurs">Réparation Ordinateurs</option>
                                        <option value="consoles">Réparation Consoles de Jeux</option>
                                        <option value="tablettes">Réparation Tablettes</option>
                                        <option value="tous">Tous Appareils Électroniques</option>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Mot de passe *</label>
                                        <input type="password" class="form-control" id="password" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Confirmer le mot de passe *</label>
                                        <input type="password" class="form-control" id="confirmPassword" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" required>
                                        <label class="form-check-label" for="terms">
                                            J'accepte les conditions d'utilisation et la politique de confidentialité
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-signup">
                                    <i class="fas fa-rocket me-2"></i>Créer Mon Compte Gratuitement
                                </button>
                                
                                <p class="text-center mt-3 text-muted">
                                    <i class="fas fa-shield-alt me-2"></i>Essai gratuit de 30 jours - Aucune carte bancaire requise
                                </p>
                            </form>
                            
                            <div class="row mt-5">
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">🎯 Fonctionnalités incluses :</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Gestion des réparations</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Base de données clients</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Inventaire intelligent</li>
                                        <li><i class="fas fa-check text-success me-2"></i>SMS automatiques</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Commandes de pièces</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Statistiques avancées</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">⚡ Fonctionnalités avancées :</h6>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Application PWA</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Scanner QR intégré</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Gestion des employés</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Base de connaissances</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Multi-magasin</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Support 24/7</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="pages/landing_new.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirmPassword) {
                alert('Les mots de passe ne correspondent pas.');
                return;
            }
            
            if (!terms) {
                alert('Vous devez accepter les conditions d\'utilisation.');
                return;
            }
            
            const submitBtn = e.target.querySelector('.btn-signup');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Création en cours...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                const form = document.getElementById('signupForm').parentElement;
                form.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle text-success mb-4" style="font-size: 4rem;"></i>
                        <h3 class="text-success mb-3">Demande d'inscription reçue !</h3>
                        <p class="text-muted mb-4">
                            Merci pour votre intérêt pour GeekBoard ! Notre équipe va examiner votre demande 
                            et vous contacter sous 24h pour finaliser la création de votre compte.
                        </p>
                        <div class="alert alert-info">
                            <strong>Mode démonstration :</strong> Cette fonctionnalité est actuellement simulée. 
                            Le système d'inscription complet sera bientôt disponible.
                        </div>
                        <a href="pages/landing_new.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i>Retour à l'accueil
                        </a>
                    </div>
                `;
            }, 2000);
        });
    </script>
</body>
</html> 