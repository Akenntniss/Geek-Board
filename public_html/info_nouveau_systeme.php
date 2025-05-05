<?php
// Titre de la page
$pageTitle = "Nouveau système de sélection de magasin";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 40px;
        }
        .info-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 30px;
        }
        .step-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 20px;
            border-left: 4px solid #0078e8;
            transition: transform 0.3s ease;
        }
        .step-card:hover {
            transform: translateY(-5px);
        }
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #0078e8;
            color: white;
            font-weight: bold;
            margin-right: 10px;
        }
        h1 {
            color: #0078e8;
            margin-bottom: 1.5rem;
        }
        .btn-action {
            background: linear-gradient(135deg, #0078e8 0%, #37a1ff 100%);
            border: none;
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 120, 232, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <!-- Carte d'information principale -->
                <div class="info-card text-center">
                    <img src="assets/images/logo/AppIcons_lightMode/appstore.png" alt="GeekBoard Logo" height="80" class="mb-3">
                    <h1><?php echo $pageTitle; ?></h1>
                    <p class="lead mb-4">
                        Nous avons simplifié la façon dont vous accédez aux différents magasins dans GeekBoard
                    </p>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Changement important :</strong> Le système de sous-domaines a été remplacé par un sélecteur de magasin plus simple à utiliser.
                    </div>
                </div>
                
                <!-- Les étapes à suivre -->
                <h2 class="mb-4">Comment ça fonctionne maintenant?</h2>
                
                <div class="step-card">
                    <h3><span class="step-number">1</span> Connexion initiale</h3>
                    <p>
                        Lors de votre connexion, vous serez invité à sélectionner un magasin dans le menu déroulant sur la page de connexion.
                    </p>
                    <div class="text-center my-3">
                        <img src="assets/images/logo/shop_selector.png" alt="Sélecteur de magasin" class="img-fluid rounded border shadow-sm" style="max-height: 200px;">
                    </div>
                </div>
                
                <div class="step-card">
                    <h3><span class="step-number">2</span> Changer de magasin</h3>
                    <p>
                        Pour changer de magasin à tout moment, vous pouvez :
                    </p>
                    <ul>
                        <li>Cliquer sur l'option <strong>"Changer de magasin"</strong> dans le menu principal</li>
                        <li>Ou vous déconnecter et sélectionner un autre magasin lors de la reconnexion</li>
                    </ul>
                </div>
                
                <div class="step-card">
                    <h3><span class="step-number">3</span> Plus de sous-domaines</h3>
                    <p>
                        Vous n'avez plus besoin d'utiliser des adresses comme <code>magasin1.mdgeek.top</code>. 
                        Une seule adresse <code>mdgeek.top</code> suffit maintenant pour accéder à tous les magasins.
                    </p>
                </div>
                
                <!-- Les avantages du nouveau système -->
                <h2 class="mt-5 mb-4">Pourquoi ce changement?</h2>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-rocket text-primary me-2"></i> Plus simple</h5>
                                <p class="card-text">
                                    Une seule adresse à retenir, plus besoin de gérer plusieurs sous-domaines différents.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-shield-alt text-primary me-2"></i> Plus fiable</h5>
                                <p class="card-text">
                                    Fonctionne sur tous les navigateurs et réseaux sans dépendre de configurations DNS complexes.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-exchange-alt text-primary me-2"></i> Changement facile</h5>
                                <p class="card-text">
                                    Passez d'un magasin à l'autre facilement sans changer d'URL ou de navigateur.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-mobile-alt text-primary me-2"></i> Compatible mobile</h5>
                                <p class="card-text">
                                    Fonctionne parfaitement sur les appareils mobiles et lors de l'utilisation de l'application PWA.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bouton de retour à l'accueil -->
                <div class="text-center mt-4 mb-5">
                    <a href="index.php" class="btn btn-action">
                        <i class="fas fa-home me-2"></i> Retourner à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 