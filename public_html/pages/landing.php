<?php
// Page de landing pour mdgeek.top
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeekBoard - Solution complète de gestion pour réparateurs</title>
    <meta name="description" content="GeekBoard est la solution tout-en-un pour gérer votre atelier de réparation d'appareils électroniques. Gestion des clients, réparations, inventaire et plus encore.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="favicon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0078e8;
            --primary-dark: #0056b3;
            --secondary-color: #6c757d;
            --accent-color: #28a745;
            --gradient-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f7fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        /* Hero Section */
        .hero {
            background: var(--gradient-bg);
            color: white;
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero .lead {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .btn-hero {
            background: white;
            color: var(--primary-color);
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            color: var(--primary-dark);
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: var(--bg-light);
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 2rem;
            color: white;
        }

        .feature-card h5 {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-dark);
        }

        .feature-card p {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Stats Section */
        .stats {
            background: var(--primary-color);
            color: white;
            padding: 80px 0;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            display: block;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* CTA Section */
        .cta {
            background: var(--gradient-bg);
            color: white;
            padding: 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
        }

        .cta-content {
            position: relative;
            z-index: 2;
        }

        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: #2d3748;
            color: white;
            padding: 60px 0 30px;
        }

        .footer h6 {
            font-weight: 600;
            margin-bottom: 20px;
        }

        .footer a {
            color: #a0aec0;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #4a5568;
            padding-top: 30px;
            margin-top: 40px;
            text-align: center;
            color: #a0aec0;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero .lead {
                font-size: 1.1rem;
            }
            
            .feature-card {
                margin-bottom: 30px;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .cta h2 {
                font-size: 2rem;
            }
        }

        /* Floating elements animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        /* Contact Modal Styles */
        .modal-content {
            border-radius: 20px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: var(--gradient-bg);
            color: white;
            border-bottom: none;
            padding: 25px 30px;
        }

        .modal-title {
            font-weight: 600;
        }

        .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            padding: 30px;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 120, 232, 0.25);
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-microchip me-2"></i>GeekBoard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fonctionnalités</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">À propos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact" data-bs-toggle="modal" data-bs-target="#contactModal">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content animate-fade-in-up">
                    <h1>La solution complète pour votre atelier de réparation</h1>
                    <p class="lead">GeekBoard révolutionne la gestion de votre activité de réparation avec une plateforme intuitive et puissante.</p>
                    <button class="btn btn-hero" data-bs-toggle="modal" data-bs-target="#contactModal">
                        <i class="fas fa-rocket me-2"></i>Démarrez maintenant
                    </button>
                </div>
                <div class="col-lg-6">
                    <div class="text-center floating">
                        <i class="fas fa-laptop" style="font-size: 15rem; opacity: 0.1;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="display-4 fw-bold mb-3">Tout ce dont vous avez besoin</h2>
                    <p class="lead text-muted">Une solution complète pour optimiser votre activité</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5>Gestion des clients</h5>
                        <p>Base de données complète avec historique des réparations, coordonnées et suivi personnalisé de chaque client.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h5>Suivi des réparations</h5>
                        <p>Workflow complet de la prise en charge au retour client, avec statuts en temps réel et notifications automatiques.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h5>Gestion d'inventaire</h5>
                        <p>Suivi des pièces détachées, gestion des stocks, commandes fournisseurs et alertes de réapprovisionnement.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h5>Application mobile</h5>
                        <p>Accès complet depuis mobile et tablette, mode hors-ligne et synchronisation automatique.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-sms"></i>
                        </div>
                        <h5>SMS automatiques</h5>
                        <p>Notifications clients par SMS : réparation terminée, devis prêt, rappels et campagnes marketing.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5>Statistiques avancées</h5>
                        <p>Tableaux de bord détaillés, analyses de performance et rapports pour optimiser votre activité.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Ateliers équipés</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">50k+</span>
                        <span class="stat-label">Réparations gérées</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">99.9%</span>
                        <span class="stat-label">Disponibilité</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Support technique</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-5" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-4">Pourquoi choisir GeekBoard ?</h2>
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-success fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>Installation rapide</h5>
                                    <p class="text-muted">Mise en service en moins de 24h avec migration de vos données existantes.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-shield-alt text-primary fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>Sécurité maximale</h5>
                                    <p class="text-muted">Hébergement sécurisé en France avec sauvegardes automatiques quotidiennes.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-headset text-info fa-2x"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5>Support dédié</h5>
                                    <p class="text-muted">Équipe support française disponible pour vous accompagner.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-cogs" style="font-size: 12rem; opacity: 0.1; color: var(--primary-color);"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta" id="contact">
        <div class="container">
            <div class="cta-content">
                <h2>Prêt à transformer votre atelier ?</h2>
                <p>Rejoignez des centaines d'ateliers qui font confiance à GeekBoard</p>
                <button class="btn btn-hero" data-bs-toggle="modal" data-bs-target="#contactModal">
                    <i class="fas fa-phone me-2"></i>Contactez-nous
                </button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <h6><i class="fas fa-microchip me-2"></i>GeekBoard</h6>
                    <p class="text-muted">La solution complète pour la gestion de votre atelier de réparation d'appareils électroniques.</p>
                </div>
                <div class="col-lg-2">
                    <h6>Solution</h6>
                    <ul class="list-unstyled">
                        <li><a href="#features">Fonctionnalités</a></li>
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Démo</a></li>
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Tarifs</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6>Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Documentation</a></li>
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Formation</a></li>
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Support</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6>Contact</h6>
                    <p class="text-muted">
                        <i class="fas fa-envelope me-2"></i>contact@mdgeek.top<br>
                        <i class="fas fa-phone me-2"></i>+33 (0)X XX XX XX XX
                    </p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 GeekBoard. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Contact Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel">
                        <i class="fas fa-paper-plane me-2"></i>Contactez-nous
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="contactForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="col-12">
                                <label for="company" class="form-label">Entreprise</label>
                                <input type="text" class="form-control" id="company" name="company">
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Sujet</label>
                                <select class="form-control" id="subject" name="subject" required>
                                    <option value="">Sélectionnez un sujet</option>
                                    <option value="demo">Demande de démonstration</option>
                                    <option value="pricing">Information tarifs</option>
                                    <option value="migration">Migration depuis autre solution</option>
                                    <option value="support">Support technique</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="4" required placeholder="Décrivez votre projet ou posez vos questions..."></textarea>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-send me-2"></i>Envoyer le message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scrolling pour les liens d'ancrage
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animation au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .stat-item').forEach(el => {
            observer.observe(el);
        });

        // Animation des nombres
        function animateNumbers() {
            const numbers = document.querySelectorAll('.stat-number');
            numbers.forEach(number => {
                const target = parseInt(number.textContent.replace(/[^\d]/g, ''));
                let current = 0;
                const increment = target / 100;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    const suffix = number.textContent.replace(/[\d]/g, '');
                    number.textContent = Math.floor(current) + suffix;
                }, 20);
            });
        }

        // Démarrer l'animation des nombres quand la section est visible
        const statsSection = document.querySelector('.stats');
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateNumbers();
                    statsObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);

        if (statsSection) {
            statsObserver.observe(statsSection);
        }

        // Gestion du formulaire de contact
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            const form = this;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi en cours...';
            button.disabled = true;
            
            // Préparer les données du formulaire
            const formData = new FormData(form);
            
            // Envoyer la requête
            fetch('/contact_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.innerHTML = '<i class="fas fa-check me-2"></i>Message envoyé !';
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-success');
                    
                    // Afficher un message de succès
                    const successMessage = document.createElement('div');
                    successMessage.className = 'alert alert-success mt-3';
                    successMessage.innerHTML = '<i class="fas fa-check me-2"></i>' + data.message;
                    form.appendChild(successMessage);
                    
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('contactModal'));
                        modal.hide();
                        form.reset();
                        button.innerHTML = originalText;
                        button.disabled = false;
                        button.classList.remove('btn-success');
                        button.classList.add('btn-primary');
                        
                        // Supprimer le message de succès
                        if (successMessage.parentNode) {
                            successMessage.parentNode.removeChild(successMessage);
                        }
                    }, 3000);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                button.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erreur';
                button.classList.remove('btn-primary');
                button.classList.add('btn-danger');
                
                // Afficher un message d'erreur
                const errorMessage = document.createElement('div');
                errorMessage.className = 'alert alert-danger mt-3';
                errorMessage.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + (error.message || 'Une erreur est survenue. Veuillez réessayer.');
                form.appendChild(errorMessage);
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.classList.remove('btn-danger');
                    button.classList.add('btn-primary');
                    
                    // Supprimer le message d'erreur
                    if (errorMessage.parentNode) {
                        errorMessage.parentNode.removeChild(errorMessage);
                    }
                }, 5000);
            });
        });

        // Effet parallax léger sur le hero
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero');
            if (hero) {
                hero.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });
    </script>
</body>
</html> 