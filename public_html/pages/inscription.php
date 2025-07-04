<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - GeekBoard | Créez Votre Compte Gratuitement</title>
    <meta name="description" content="Inscrivez-vous gratuitement à GeekBoard et découvrez la solution complète de gestion pour votre atelier de réparation.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/logo/AppIcons_lightMode/appstore.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0078e8;
            --primary-dark: #0056b3;
            --secondary-color: #6c757d;
            --accent-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --gradient-primary: linear-gradient(135deg, #0078e8 0%, #0056b3 100%);
            --gradient-hero: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
            --shadow-light: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-medium: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-large: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--bg-light);
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
            box-shadow: var(--shadow-large);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
        }

        .signup-header {
            background: var(--gradient-primary);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .signup-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .signup-header-content {
            position: relative;
            z-index: 2;
        }

        .signup-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
        }

        .signup-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .signup-form {
            padding: 3rem 2rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-section h3 i {
            color: var(--primary-color);
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 120, 232, 0.25);
        }

        .btn-signup {
            background: var(--gradient-primary);
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 50px;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-light);
        }

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
            color: white;
        }

        .features-list {
            background: var(--bg-light);
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
        }

        .features-list h4 {
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
        }

        .feature-item i {
            color: var(--accent-color);
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .feature-item span {
            color: var(--text-dark);
            font-weight: 500;
        }

        .terms-checkbox {
            margin: 2rem 0;
        }

        .terms-checkbox .form-check-input {
            border-radius: 5px;
            width: 1.2rem;
            height: 1.2rem;
        }

        .terms-checkbox .form-check-label {
            margin-left: 0.5rem;
            color: var(--text-light);
        }

        .terms-checkbox .form-check-label a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .terms-checkbox .form-check-label a:hover {
            text-decoration: underline;
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

        .demo-alert h5 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .demo-alert p {
            margin-bottom: 0;
            opacity: 0.9;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand img {
            width: 32px;
            height: 32px;
        }

        .back-link {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .signup-header {
                padding: 2rem 1rem;
            }
            
            .signup-header h1 {
                font-size: 2rem;
            }
            
            .signup-form {
                padding: 2rem 1rem;
            }
            
            .features-list {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="landing_new.php">
                <img src="assets/images/logo/AppIcons_lightMode/appstore.png" alt="GeekBoard">
                <span>GeekBoard</span>
            </a>
            <a href="landing_new.php" class="back-link">
                <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
            </a>
        </div>
    </nav>

    <!-- Signup Container -->
    <div class="signup-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="signup-card">
                        <!-- Header -->
                        <div class="signup-header">
                            <div class="signup-header-content">
                                <h1><i class="fas fa-rocket me-3"></i>Créez Votre Compte</h1>
                                <p>Rejoignez les milliers d'ateliers qui optimisent leur gestion avec GeekBoard</p>
                            </div>
                        </div>

                        <!-- Form -->
                        <div class="signup-form">
                            <!-- Demo Alert -->
                            <div class="demo-alert">
                                <h5><i class="fas fa-info-circle me-2"></i>Mode Démonstration</h5>
                                <p>Cette inscription est actuellement en mode démonstration. Le processus d'inscription complet sera bientôt disponible.</p>
                            </div>

                            <form id="signupForm">
                                <!-- Informations personnelles -->
                                <div class="form-section">
                                    <h3><i class="fas fa-user"></i>Informations Personnelles</h3>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Prénom" required>
                                                <label for="prenom">Prénom *</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" id="nom" name="nom" placeholder="Nom" required>
                                                <label for="nom">Nom *</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                                        <label for="email">Adresse email *</label>
                                    </div>
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="Téléphone" required>
                                        <label for="telephone">Téléphone *</label>
                                    </div>
                                </div>

                                <!-- Informations de l'atelier -->
                                <div class="form-section">
                                    <h3><i class="fas fa-store"></i>Informations de l'Atelier</h3>
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nom_atelier" name="nom_atelier" placeholder="Nom de l'atelier" required>
                                        <label for="nom_atelier">Nom de l'atelier *</label>
                                    </div>
                                    <div class="form-floating">
                                        <textarea class="form-control" id="adresse" name="adresse" placeholder="Adresse" style="height: 100px" required></textarea>
                                        <label for="adresse">Adresse complète *</label>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" id="code_postal" name="code_postal" placeholder="Code postal" required>
                                                <label for="code_postal">Code postal *</label>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" id="ville" name="ville" placeholder="Ville" required>
                                                <label for="ville">Ville *</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-floating">
                                        <select class="form-control" id="type_atelier" name="type_atelier" required>
                                            <option value="">Choisissez votre spécialité</option>
                                            <option value="smartphones">Réparation Smartphones</option>
                                            <option value="ordinateurs">Réparation Ordinateurs</option>
                                            <option value="consoles">Réparation Consoles de Jeux</option>
                                            <option value="tablettes">Réparation Tablettes</option>
                                            <option value="tous">Tous Appareils Électroniques</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                        <label for="type_atelier">Type d'atelier *</label>
                                    </div>
                                </div>

                                <!-- Sécurité -->
                                <div class="form-section">
                                    <h3><i class="fas fa-lock"></i>Sécurité</h3>
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" placeholder="Mot de passe" required>
                                        <label for="mot_de_passe">Mot de passe *</label>
                                    </div>
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" placeholder="Confirmer le mot de passe" required>
                                        <label for="confirmer_mot_de_passe">Confirmer le mot de passe *</label>
                                    </div>
                                </div>

                                <!-- Terms and conditions -->
                                <div class="terms-checkbox">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                        <label class="form-check-label" for="terms">
                                            J'accepte les <a href="#" target="_blank">conditions d'utilisation</a> et la <a href="#" target="_blank">politique de confidentialité</a> de GeekBoard
                                        </label>
                                    </div>
                                </div>

                                <div class="terms-checkbox">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                                        <label class="form-check-label" for="newsletter">
                                            Je souhaite recevoir les actualités et conseils de GeekBoard par email
                                        </label>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-signup">
                                    <i class="fas fa-rocket me-2"></i>Créer Mon Compte Gratuitement
                                </button>

                                <p class="text-center mt-3 text-muted">
                                    <i class="fas fa-shield-alt me-2"></i>Essai gratuit de 30 jours - Aucune carte bancaire requise
                                </p>
                            </form>

                            <!-- Features List -->
                            <div class="features-list">
                                <h4>🎯 Ce que vous obtenez avec GeekBoard</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Gestion complète des réparations</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Base de données clients avancée</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Inventaire intelligent automatisé</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Envoi de SMS automatiques</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Gestion des commandes de pièces</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Statistiques et rapports détaillés</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Application PWA (fonctionne hors ligne)</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Scanner QR intégré</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Gestion des employés et congés</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Base de connaissances technique</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Multi-magasin avec données séparées</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Support technique 24/7</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Vérification des mots de passe
            const motDePasse = document.getElementById('mot_de_passe').value;
            const confirmerMotDePasse = document.getElementById('confirmer_mot_de_passe').value;
            
            if (motDePasse !== confirmerMotDePasse) {
                alert('Les mots de passe ne correspondent pas. Veuillez vérifier.');
                return;
            }
            
            // Vérification des conditions d'utilisation
            const terms = document.getElementById('terms').checked;
            if (!terms) {
                alert('Vous devez accepter les conditions d\'utilisation pour continuer.');
                return;
            }
            
            // Animation du bouton
            const submitBtn = document.querySelector('.btn-signup');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Création en cours...';
            submitBtn.disabled = true;
            
            // Simulation de la création de compte
            setTimeout(() => {
                // Afficher un message de succès
                const form = document.getElementById('signupForm');
                form.innerHTML = `
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="text-success mb-3">Demande d'inscription reçue !</h3>
                        <p class="text-muted mb-4">
                            Merci pour votre intérêt pour GeekBoard ! Votre demande d'inscription a été enregistrée avec succès.
                        </p>
                        <div class="alert alert-info" role="alert">
                            <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Prochaines étapes</h5>
                            <p class="mb-0">
                                Notre équipe va examiner votre demande et vous contacter sous 24h pour finaliser 
                                la création de votre compte et vous donner accès à GeekBoard.
                            </p>
                        </div>
                        <div class="mt-4">
                            <a href="landing_new.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </a>
                        </div>
                    </div>
                `;
            }, 2000);
        });

        // Animation pour les champs de formulaire
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.parentElement.classList.remove('focused');
                }
            });
        });

        // Validation en temps réel du mot de passe
        document.getElementById('confirmer_mot_de_passe').addEventListener('input', function() {
            const motDePasse = document.getElementById('mot_de_passe').value;
            const confirmerMotDePasse = this.value;
            
            if (confirmerMotDePasse && motDePasse !== confirmerMotDePasse) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                if (confirmerMotDePasse) {
                    this.classList.add('is-valid');
                }
            }
        });

        // Validation de l'email
        document.getElementById('email').addEventListener('input', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                this.setCustomValidity('Veuillez entrer une adresse email valide');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                if (this.value) {
                    this.classList.add('is-valid');
                }
            }
        });

        // Validation du téléphone français
        document.getElementById('telephone').addEventListener('input', function() {
            const phoneRegex = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
            if (this.value && !phoneRegex.test(this.value)) {
                this.setCustomValidity('Veuillez entrer un numéro de téléphone français valide');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                if (this.value) {
                    this.classList.add('is-valid');
                }
            }
        });
    </script>
</body>
</html> 