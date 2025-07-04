<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeekBoard - Solution Complète de Gestion pour Ateliers de Réparation</title>
    <meta name="description" content="GeekBoard est la solution tout-en-un révolutionnaire pour gérer votre atelier de réparation. Gestion des clients, réparations, inventaire, SMS, statistiques et bien plus encore.">
    
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
            --gradient-feature: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f8fafc;
            --bg-dark: #1a202c;
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
            overflow-x: hidden;
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand img {
            width: 40px;
            height: 40px;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-dark) !important;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 50%;
            background: var(--primary-color);
            transition: all 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
            left: 0;
        }

        .btn-primary-custom {
            background: var(--gradient-primary);
            border: none;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-light);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        /* Hero Section */
        .hero {
            background: var(--gradient-hero);
            color: white;
            padding: 150px 0 100px;
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
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero .lead {
            font-size: 1.4rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .btn-hero {
            background: white;
            color: var(--primary-color);
            border: none;
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-large);
            margin-right: 1rem;
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            color: var(--primary-dark);
        }

        .btn-hero-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-hero-secondary:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        /* Features Section */
        .features {
            padding: 120px 0;
            background: var(--bg-light);
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            font-size: 3rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.25rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-medium);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
            box-shadow: var(--shadow-light);
        }

        .feature-card h5 {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }

        .feature-card p {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Stats Section */
        .stats {
            background: var(--gradient-primary);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .stats::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="5" height="5" patternUnits="userSpaceOnUse"><circle cx="2.5" cy="2.5" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
        }

        .stat-item {
            text-align: center;
            padding: 2rem;
            position: relative;
            z-index: 2;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 900;
            display: block;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* CTA Section */
        .cta {
            background: var(--gradient-hero);
            color: white;
            padding: 120px 0;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="hexagon" width="20" height="17.32" patternUnits="userSpaceOnUse"><polygon points="10,0 20,5.77 20,11.55 10,17.32 0,11.55 0,5.77" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23hexagon)"/></svg>');
            opacity: 0.3;
        }

        .cta-content {
            position: relative;
            z-index: 2;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero .lead {
                font-size: 1.1rem;
            }
            
            .btn-hero, .btn-hero-secondary {
                padding: 0.8rem 1.5rem;
                font-size: 1rem;
                display: block;
                width: 100%;
                margin: 0.5rem 0;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .feature-card {
                margin-bottom: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/images/logo/AppIcons_lightMode/appstore.png" alt="GeekBoard">
                <span>GeekBoard</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto me-4">
                    <li class="nav-item">
                        <a class="nav-link" href="#fonctionnalites">Fonctionnalités</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#avantages">Avantages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-outline-primary">Connexion</a>
                    <a href="#inscription" class="btn btn-primary-custom">Essai Gratuit</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1>La Solution Complète pour Votre Atelier</h1>
                        <p class="lead">GeekBoard révolutionne la gestion des ateliers de réparation avec une suite complète d'outils intelligents. Gestion des clients, réparations, inventaire, SMS, statistiques et bien plus encore.</p>
                        <div class="d-flex flex-column flex-md-row gap-3">
                            <a href="#inscription" class="btn btn-hero">
                                <i class="fas fa-rocket me-2"></i>Commencer Gratuitement
                            </a>
                            <a href="#fonctionnalites" class="btn btn-hero-secondary">
                                <i class="fas fa-play me-2"></i>Découvrir les Fonctionnalités
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <img src="assets/images/logo/AppIcons_lightMode/appstore.png" alt="GeekBoard" class="img-fluid" style="max-width: 300px; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.3));">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="fonctionnalites" class="features">
        <div class="container">
            <div class="section-title">
                <h2>Fonctionnalités Complètes</h2>
                <p>Une suite d'outils professionnels conçue pour optimiser chaque aspect de votre atelier de réparation</p>
            </div>
            
            <div class="row g-4">
                <!-- Gestion des Réparations -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h5>Gestion des Réparations</h5>
                        <p>Suivi complet des réparations avec statuts en temps réel, historique détaillé, workflow personnalisable et étiquettes QR automatiques.</p>
                    </div>
                </div>
                
                <!-- Gestion des Clients -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5>Base de Données Clients</h5>
                        <p>Fiche client complète avec historique des réparations, photos, signatures numériques, données de contact et système de parrainage.</p>
                    </div>
                </div>
                
                <!-- Inventaire Intelligent -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h5>Inventaire Intelligent</h5>
                        <p>Gestion automatisée des stocks avec alertes de réapprovisionnement, tracking des mouvements et système de gardiennage avancé.</p>
                    </div>
                </div>
                
                <!-- SMS Automatiques -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-sms"></i>
                        </div>
                        <h5>SMS & Communication</h5>
                        <p>Envoi automatique de SMS aux clients avec templates personnalisés, variables dynamiques, campagnes marketing et messagerie intégrée.</p>
                    </div>
                </div>
                
                <!-- Commandes de Pièces -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h5>Commandes & Fournisseurs</h5>
                        <p>Gestion automatisée des commandes avec fournisseurs intégrés, suivi des livraisons et système de devis intelligent.</p>
                    </div>
                </div>
                
                <!-- Statistiques Avancées -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5>Statistiques & Rapports</h5>
                        <p>Tableaux de bord interactifs avec KPIs en temps réel, graphiques détaillés, rapports financiers et analyses de performance.</p>
                    </div>
                </div>
                
                <!-- Gestion des Tâches -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h5>Gestion des Tâches</h5>
                        <p>Organisation du travail avec attribution des tâches, priorités, suivi des délais et système de commentaires collaboratif.</p>
                    </div>
                </div>
                
                <!-- Historique des Employés -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h5>Historique des Mouvements</h5>
                        <p>Suivi complet des actions des employés : connexions, modifications, logs détaillés et traçabilité totale pour audit et sécurité.</p>
                    </div>
                </div>
                
                <!-- Rachats d'Appareils -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h5>Rachats d'Appareils</h5>
                        <p>Module complet pour l'évaluation et la gestion des rachats avec grille de prix automatique et attestations officielles.</p>
                    </div>
                </div>
                
                <!-- Scanner QR -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <h5>Scanner QR Intégré</h5>
                        <p>Scan des étiquettes QR pour accès rapide aux réparations, traçabilité complète et impression automatique d'étiquettes.</p>
                    </div>
                </div>
                
                <!-- Gestion des Employés -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h5>Gestion RH Complète</h5>
                        <p>Management de l'équipe avec gestion des congés, permissions, planification des horaires et évaluation des performances.</p>
                    </div>
                </div>
                
                <!-- Base de Connaissances -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h5>Base de Connaissances</h5>
                        <p>Documentation technique centralisée avec guides de réparation, procédures détaillées et système de recherche avancé.</p>
                    </div>
                </div>
                
                <!-- Application PWA -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h5>Application PWA</h5>
                        <p>Fonctionne hors ligne, installable sur tous appareils, synchronisation automatique et expérience native mobile.</p>
                    </div>
                </div>
                
                <!-- Multi-Magasin -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-store-alt"></i>
                        </div>
                        <h5>Système Multi-Magasin</h5>
                        <p>Gestion de plusieurs points de vente avec bases de données séparées, centralisation des rapports et administration unifiée.</p>
                    </div>
                </div>
                
                <!-- Sécurité & Logs -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5>Sécurité & Audit</h5>
                        <p>Système de logs complet, sauvegardes automatiques, contrôle d'accès par rôles et conformité RGPD intégrée.</p>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h5>Notifications Intelligentes</h5>
                        <p>Alertes en temps réel, notifications push, rappels automatiques et système de préférences personnalisées.</p>
                    </div>
                </div>
            </div>
            
            <!-- Section Évolutive -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="text-center">
                        <div class="feature-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <div class="feature-icon" style="background: rgba(255,255,255,0.2);">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <h5 style="color: white;">Application Évolutive</h5>
                            <p style="color: rgba(255,255,255,0.9);">
                                GeekBoard évolue constamment avec de nouvelles fonctionnalités ajoutées mensuellement : 
                                Intelligence Artificielle pour diagnostics automatiques, intégrations API tierces, 
                                modules de comptabilité avancée, système de réservations en ligne et bien plus encore !
                            </p>
                            <div class="row mt-4">
                                <div class="col-md-3 col-6 mb-3">
                                    <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 1rem;">
                                        <i class="fas fa-brain mb-2" style="font-size: 1.5rem;"></i>
                                        <small class="d-block">IA Intégrée</small>
                                        <small class="text-muted">Bientôt</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 1rem;">
                                        <i class="fas fa-calendar-alt mb-2" style="font-size: 1.5rem;"></i>
                                        <small class="d-block">Réservations</small>
                                        <small class="text-muted">En développement</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 1rem;">
                                        <i class="fas fa-calculator mb-2" style="font-size: 1.5rem;"></i>
                                        <small class="d-block">Comptabilité</small>
                                        <small class="text-muted">Prochainement</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 1rem;">
                                        <i class="fas fa-cloud mb-2" style="font-size: 1.5rem;"></i>
                                        <small class="d-block">Cloud Storage</small>
                                        <small class="text-muted">Q2 2025</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <span class="stat-number">1000+</span>
                        <span class="stat-label">Ateliers Utilisent GeekBoard</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <span class="stat-number">50k+</span>
                        <span class="stat-label">Réparations Gérées</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <span class="stat-number">98%</span>
                        <span class="stat-label">Taux de Satisfaction</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Support Technique</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="inscription" class="cta">
        <div class="container">
            <div class="cta-content">
                <h2 class="display-4 fw-bold mb-4">Prêt à Révolutionner Votre Atelier ?</h2>
                <p class="lead mb-5">Rejoignez les milliers d'ateliers qui ont choisi GeekBoard pour optimiser leur gestion quotidienne</p>
                <a href="inscription.php" class="btn btn-hero">
                    <i class="fas fa-rocket me-2"></i>Créer Mon Compte Gratuitement
                </a>
                <p class="mt-4 text-white-50">
                    <i class="fas fa-check me-2"></i>Essai gratuit de 30 jours - Aucune carte bancaire requise
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="d-flex align-items-center mb-3">
                        <img src="assets/images/logo/AppIcons-darkmode/playstore.png" alt="GeekBoard" width="40" height="40" class="me-3">
                        <h5 class="mb-0">GeekBoard</h5>
                    </div>
                    <p class="text-muted">La solution complète pour les ateliers de réparation modernes. Évolutive, intelligente et toujours en développement.</p>
                </div>
                <div class="col-lg-6">
                    <h6 class="fw-bold mb-3">Application Évolutive</h6>
                    <p class="text-muted mb-3">GeekBoard est en constante évolution avec de nouvelles fonctionnalités ajoutées régulièrement :</p>
                    <ul class="list-unstyled text-muted">
                        <li><i class="fas fa-check text-success me-2"></i>Mises à jour automatiques</li>
                        <li><i class="fas fa-check text-success me-2"></i>Nouvelles fonctionnalités mensuelles</li>
                        <li><i class="fas fa-check text-success me-2"></i>Intégrations tiers en développement</li>
                        <li><i class="fas fa-check text-success me-2"></i>Intelligence artificielle intégrée (bientôt)</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <p class="mb-0 text-muted">&copy; 2025 GeekBoard. Tous droits réservés.</p>
                </div>
                <div class="col-lg-6 text-lg-end">
                    <a href="#" class="text-muted me-3">Conditions d'utilisation</a>
                    <a href="#" class="text-muted">Politique de confidentialité</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Animations on scroll -->
    <script>
        // Smooth scrolling for navigation links
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

        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const finalValue = stat.textContent;
                        const numericValue = parseInt(finalValue.replace(/\D/g, ''));
                        
                        if (numericValue) {
                            let current = 0;
                            const increment = numericValue / 50;
                            const timer = setInterval(() => {
                                current += increment;
                                if (current >= numericValue) {
                                    stat.textContent = finalValue;
                                    clearInterval(timer);
                                } else {
                                    stat.textContent = Math.floor(current) + (finalValue.includes('%') ? '%' : finalValue.includes('+') ? '+' : '');
                                }
                            }, 50);
                        }
                    });
                }
            });
        }, observerOptions);

        document.querySelector('.stats').addEventListener('load', () => {
            observer.observe(document.querySelector('.stats'));
        });
        
        // Observe stats section when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            const statsSection = document.querySelector('.stats');
            if (statsSection) {
                observer.observe(statsSection);
            }
        });
    </script>
</body>
</html> 