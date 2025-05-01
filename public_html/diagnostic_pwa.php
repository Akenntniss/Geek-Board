<?php
/**
 * Script de diagnostic pour l'optimisation PWA de GeekBoard
 * Ce script vérifie l'état actuel de l'installation PWA et génère un rapport
 */

// Définir les chemins des fichiers
$root_path = __DIR__;

// Fonction pour vérifier si un fichier existe
function verifier_fichier($chemin, $obligatoire = true) {
    $existe = file_exists($chemin);
    $statut = $existe ? 'success' : ($obligatoire ? 'danger' : 'warning');
    $message = $existe ? 'Présent' : ($obligatoire ? 'Manquant (requis)' : 'Manquant (optionnel)');
    
    return [
        'existe' => $existe,
        'statut' => $statut,
        'message' => $message
    ];
}

// Fonction pour vérifier le contenu d'un fichier
function verifier_contenu($chemin, $chaine_recherche) {
    if (!file_exists($chemin)) {
        return [
            'trouve' => false,
            'statut' => 'danger',
            'message' => 'Fichier manquant'
        ];
    }
    
    $contenu = file_get_contents($chemin);
    $trouve = strpos($contenu, $chaine_recherche) !== false;
    
    return [
        'trouve' => $trouve,
        'statut' => $trouve ? 'success' : 'warning',
        'message' => $trouve ? 'Trouvé' : 'Non trouvé'
    ];
}

// Vérifier les fichiers principaux
$fichiers_principaux = [
    'Service Worker' => verifier_fichier($root_path . '/service-worker.js'),
    'Service Worker Optimisé' => verifier_fichier($root_path . '/service-worker-optimized.js'),
    'Manifest' => verifier_fichier($root_path . '/manifest.json'),
    'Manifest Optimisé' => verifier_fichier($root_path . '/manifest-optimized.json'),
    'Page Hors Ligne' => verifier_fichier($root_path . '/offline.html'),
    'Guide PWA' => verifier_fichier($root_path . '/pwa-guide.html', false)
];

// Vérifier les scripts JS
$scripts_js = [
    'Commandes Offline' => verifier_fichier($root_path . '/assets/js/commandes-offline.js'),
    'Intégration PWA' => verifier_fichier($root_path . '/assets/js/pwa-integration.js'),
    'Synchronisation Offline' => verifier_fichier($root_path . '/assets/js/offline-sync.js')
];

// Vérifier le point de terminaison pour la synchronisation
$endpoints = [
    'Synchronisation Commandes' => verifier_fichier($root_path . '/ajax/sync_commande.php')
];

// Vérifier l'intégration dans index.php
$integration_index = verifier_contenu($root_path . '/index.php', 'serviceWorker.register');

// Vérifier les méta-tags pour iOS
$meta_ios = verifier_contenu($root_path . '/index.php', 'apple-mobile-web-app-capable');

// Calculer le score global
$total_points = 0;
$points_max = 0;

// Fonction pour calculer les points
function calculer_points($verification) {
    global $total_points, $points_max;
    
    $points_max += 10;
    if (isset($verification['existe']) && $verification['existe']) {
        $total_points += 10;
    } elseif (isset($verification['trouve']) && $verification['trouve']) {
        $total_points += 10;
    }
}

// Calculer les points pour chaque vérification
foreach ($fichiers_principaux as $verification) {
    calculer_points($verification);
}

foreach ($scripts_js as $verification) {
    calculer_points($verification);
}

foreach ($endpoints as $verification) {
    calculer_points($verification);
}

calculer_points($integration_index);
calculer_points($meta_ios);

// Calculer le pourcentage
$pourcentage = ($points_max > 0) ? round(($total_points / $points_max) * 100) : 0;

// Déterminer le statut global
if ($pourcentage >= 90) {
    $statut_global = 'success';
    $message_global = 'Excellent! Votre installation PWA est optimale.';
} elseif ($pourcentage >= 70) {
    $statut_global = 'warning';
    $message_global = 'Bon! Votre installation PWA est fonctionnelle mais peut être améliorée.';
} else {
    $statut_global = 'danger';
    $message_global = 'Attention! Votre installation PWA nécessite des améliorations importantes.';
}

// Générer des recommandations
$recommandations = [];

if (!$fichiers_principaux['Service Worker']['existe']) {
    $recommandations[] = 'Installez le service worker pour activer les fonctionnalités offline.';
}

if (!$fichiers_principaux['Manifest']['existe']) {
    $recommandations[] = 'Créez un fichier manifest.json pour permettre l\'installation de l\'application.';
}

if (!$scripts_js['Commandes Offline']['existe']) {
    $recommandations[] = 'Implémentez le script de gestion des commandes offline pour permettre le travail sans connexion.';
}

if (!$endpoints['Synchronisation Commandes']['existe']) {
    $recommandations[] = 'Créez le point de terminaison pour la synchronisation des données offline.';
}

if (!$integration_index['trouve']) {
    $recommandations[] = 'Intégrez le code d\'enregistrement du service worker dans votre fichier index.php.';
}

if (!$meta_ios['trouve']) {
    $recommandations[] = 'Ajoutez les méta-tags pour une meilleure intégration sur iOS.';
}

// Afficher l'interface utilisateur
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic PWA - GeekBoard</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 200px;
            margin-bottom: 20px;
        }
        .score-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .score {
            font-size: 48px;
            font-weight: bold;
        }
        .progress {
            height: 30px;
            margin-bottom: 20px;
        }
        .progress-bar {
            font-size: 16px;
            line-height: 30px;
        }
        .section {
            margin-bottom: 30px;
        }
        .table th {
            width: 40%;
        }
        .recommendations {
            margin-top: 30px;
            padding: 20px;
            background-color: #e9ecef;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="/assets/images/logo.png" alt="GeekBoard Logo">
            <h1>Diagnostic PWA</h1>
            <p class="lead">Ce rapport analyse l'état actuel de l'installation PWA de votre application GeekBoard</p>
        </div>
        
        <div class="score-container">
            <div class="alert alert-<?php echo $statut_global; ?>">
                <h2><?php echo $message_global; ?></h2>
            </div>
            
            <div class="score"><?php echo $pourcentage; ?>%</div>
            <div class="progress">
                <div class="progress-bar bg-<?php echo $statut_global; ?>" role="progressbar" style="width: <?php echo $pourcentage; ?>%" 
                     aria-valuenow="<?php echo $pourcentage; ?>" aria-valuemin="0" aria-valuemax="100">
                    <?php echo $pourcentage; ?>%
                </div>
            </div>
        </div>
        
        <div class="section">
            <h3>Fichiers principaux</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Fichier</th>
                        <th>Statut</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fichiers_principaux as $nom => $verification): ?>
                    <tr>
                        <td><?php echo $nom; ?></td>
                        <td><span class="badge badge-<?php echo $verification['statut']; ?>"><?php echo $verification['statut'] == 'success' ? 'OK' : 'Erreur'; ?></span></td>
                        <td><?php echo $verification['message']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h3>Scripts JavaScript</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Script</th>
                        <th>Statut</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scripts_js as $nom => $verification): ?>
                    <tr>
                        <td><?php echo $nom; ?></td>
                        <td><span class="badge badge-<?php echo $verification['statut']; ?>"><?php echo $verification['statut'] == 'success' ? 'OK' : 'Erreur'; ?></span></td>
                        <td><?php echo $verification['message']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h3>Points de terminaison API</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>Statut</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($endpoints as $nom => $verification): ?>
                    <tr>
                        <td><?php echo $nom; ?></td>
                        <td><span class="badge badge-<?php echo $verification['statut']; ?>"><?php echo $verification['statut'] == 'success' ? 'OK' : 'Erreur'; ?></span></td>
                        <td><?php echo $verification['message']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h3>Intégration</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Vérification</th>
                        <th>Statut</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Enregistrement Service Worker</td>
                        <td><span class="badge badge-<?php echo $integration_index['statut']; ?>"><?php echo $integration_index['statut'] == 'success' ? 'OK' : 'Erreur'; ?></span></td>
                        <td><?php echo $integration_index['message']; ?></td>
                    </tr>
                    <tr>
                        <td>Méta-tags iOS</td>
                        <td><span class="badge badge-<?php echo $meta_ios['statut']; ?>"><?php echo $meta_ios['statut'] == 'success' ? 'OK' : 'Erreur'; ?></span></td>
                        <td><?php echo $meta_ios['message']; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($recommandations)): ?>
        <div class="recommendations">
            <h3>Recommandations</h3>
            <ul>
                <?php foreach ($recommandations as $recommandation): ?>
                <li><?php echo $recommandation; ?></li>
                <?php endforeach; ?>
            </ul>
            
            <div class="mt-4">
                <a href="/installer_pwa_optimisee.php" class="btn btn-primary">Installer la PWA optimisée</a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="/index.php" class="btn btn-secondary">Retour à l'accueil</a>
            <button onclick="window.location.reload()" class="btn btn-info ml-2">Actualiser le diagnostic</button>
        </div>
    </div>
    
    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>