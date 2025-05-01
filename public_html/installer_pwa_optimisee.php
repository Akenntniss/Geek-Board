<?php
/**
 * Script d'installation automatisée pour l'optimisation PWA de GeekBoard
 * Ce script implémente toutes les étapes décrites dans README-PWA-OPTIMISATION.md
 */

// Définir les chemins des fichiers
$root_path = __DIR__;
$backup_dir = $root_path . '/backups/' . date('Y-m-d_H-i-s');

// Créer le répertoire de sauvegarde s'il n'existe pas
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Fonction pour afficher les messages
function afficher_message($message, $type = 'info') {
    $class = ($type == 'success') ? 'success' : (($type == 'error') ? 'danger' : 'info');
    echo "<div class=\"alert alert-{$class}\" role=\"alert\">$message</div>";
}

// Fonction pour sauvegarder un fichier
function sauvegarder_fichier($chemin_source, $backup_dir) {
    if (file_exists($chemin_source)) {
        $nom_fichier = basename($chemin_source);
        $chemin_destination = $backup_dir . '/' . $nom_fichier;
        if (copy($chemin_source, $chemin_destination)) {
            return true;
        }
    }
    return false;
}

// Fonction pour remplacer un fichier
function remplacer_fichier($source, $destination) {
    if (file_exists($source) && file_exists($destination)) {
        if (copy($source, $destination)) {
            return true;
        }
    }
    return false;
}

// Vérifier si le formulaire a été soumis
$installation_terminee = false;
if (isset($_POST['installer'])) {
    // Étape 1: Sauvegarder les fichiers existants
    $sauvegarde_service_worker = sauvegarder_fichier($root_path . '/service-worker.js', $backup_dir);
    $sauvegarde_manifest = sauvegarder_fichier($root_path . '/manifest.json', $backup_dir);
    
    // Étape 2: Remplacer le service worker
    $remplacement_service_worker = remplacer_fichier(
        $root_path . '/service-worker-optimized.js', 
        $root_path . '/service-worker.js'
    );
    
    // Étape 3: Remplacer le manifest
    $remplacement_manifest = remplacer_fichier(
        $root_path . '/manifest-optimized.json', 
        $root_path . '/manifest.json'
    );
    
    // Étape 4: Vérifier si les fichiers JS nécessaires existent, sinon les créer
    $dossier_js = $root_path . '/assets/js';
    $fichiers_js = [
        'commandes-offline.js',
        'pwa-integration.js',
        'offline-sync.js'
    ];
    
    $creation_js = true;
    foreach ($fichiers_js as $fichier) {
        if (!file_exists($dossier_js . '/' . $fichier)) {
            // Créer le fichier s'il n'existe pas
            $contenu_par_defaut = "// Fichier créé automatiquement par l'installateur PWA\n// Voir README-PWA-OPTIMISATION.md pour plus d'informations\n";
            if (!file_put_contents($dossier_js . '/' . $fichier, $contenu_par_defaut)) {
                $creation_js = false;
                afficher_message("Erreur lors de la création du fichier {$fichier}", 'error');
            }
        }
    }
    
    // Étape 5: Vérifier si le point de terminaison pour la synchronisation existe
    $sync_endpoint = $root_path . '/ajax/sync_commande.php';
    $creation_sync = true;
    if (!file_exists($sync_endpoint)) {
        // Créer le fichier à partir du contenu du README
        $contenu_sync = file_get_contents($root_path . '/README-PWA-OPTIMISATION.md');
        preg_match('/```php\n(.*?)```/s', $contenu_sync, $matches);
        
        if (isset($matches[1])) {
            $code_php = $matches[1];
            if (!file_put_contents($sync_endpoint, $code_php)) {
                $creation_sync = false;
                afficher_message("Erreur lors de la création du point de terminaison pour la synchronisation", 'error');
            }
        } else {
            $creation_sync = false;
            afficher_message("Impossible de trouver le code PHP pour le point de terminaison dans le README", 'error');
        }
    }
    
    // Étape 6: Vérifier si le guide d'utilisation existe
    $guide_path = $root_path . '/pwa-guide.html';
    $guide_existe = file_exists($guide_path);
    
    // Vérifier si l'installation est réussie
    $installation_terminee = $sauvegarde_service_worker && $sauvegarde_manifest && 
                            $remplacement_service_worker && $remplacement_manifest && 
                            $creation_js && $creation_sync;
}

// Afficher l'interface utilisateur
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation PWA Optimisée - GeekBoard</title>
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
        .step {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .step h3 {
            margin-top: 0;
            color: #0078e8;
        }
        .success-message {
            text-align: center;
            padding: 20px;
            background-color: #d4edda;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .next-steps {
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
            <h1>Installation PWA Optimisée</h1>
            <p class="lead">Ce script va installer la version optimisée de la PWA GeekBoard selon les instructions du README-PWA-OPTIMISATION.md</p>
        </div>
        
        <?php if ($installation_terminee): ?>
            <div class="success-message">
                <h2><i class="fas fa-check-circle"></i> Installation terminée avec succès!</h2>
                <p>Toutes les étapes ont été complétées. Votre application GeekBoard est maintenant optimisée en tant que PWA.</p>
            </div>
            
            <div class="next-steps">
                <h3>Prochaines étapes</h3>
                <ol>
                    <li>Vérifiez que le service worker est correctement enregistré en visitant la page d'accueil</li>
                    <li>Testez l'installation de l'application sur un appareil mobile</li>
                    <li>Vérifiez le fonctionnement hors ligne des commandes</li>
                    <li>Consultez le <a href="/pwa-guide.html">guide d'utilisation</a> pour plus d'informations</li>
                </ol>
                
                <div class="mt-4">
                    <a href="/index.php" class="btn btn-primary">Retour à l'accueil</a>
                    <a href="/pwa-guide.html" class="btn btn-info ml-2">Consulter le guide</a>
                </div>
            </div>
        <?php else: ?>
            <div class="step">
                <h3>Étape 1: Sauvegarde des fichiers existants</h3>
                <p>Les fichiers suivants seront sauvegardés avant d'être remplacés:</p>
                <ul>
                    <li><code>service-worker.js</code> → <code><?php echo $backup_dir; ?>/service-worker.js</code></li>
                    <li><code>manifest.json</code> → <code><?php echo $backup_dir; ?>/manifest.json</code></li>
                </ul>
            </div>
            
            <div class="step">
                <h3>Étape 2: Remplacement des fichiers</h3>
                <p>Les fichiers suivants seront remplacés par leurs versions optimisées:</p>
                <ul>
                    <li><code>service-worker-optimized.js</code> → <code>service-worker.js</code></li>
                    <li><code>manifest-optimized.json</code> → <code>manifest.json</code></li>
                </ul>
            </div>
            
            <div class="step">
                <h3>Étape 3: Vérification des scripts JS</h3>
                <p>Les fichiers JavaScript suivants seront créés s'ils n'existent pas:</p>
                <ul>
                    <li><code>/assets/js/commandes-offline.js</code></li>
                    <li><code>/assets/js/pwa-integration.js</code></li>
                    <li><code>/assets/js/offline-sync.js</code></li>
                </ul>
            </div>
            
            <div class="step">
                <h3>Étape 4: Point de terminaison pour la synchronisation</h3>
                <p>Le fichier <code>/ajax/sync_commande.php</code> sera créé s'il n'existe pas.</p>
            </div>
            
            <form method="post" action="">
                <div class="alert alert-warning">
                    <strong>Attention!</strong> Cette opération va remplacer certains fichiers. Assurez-vous d'avoir une sauvegarde complète de votre site avant de continuer.
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" name="installer" class="btn btn-primary btn-lg">Installer la PWA optimisée</button>
                    <a href="/index.php" class="btn btn-secondary btn-lg ml-2">Annuler</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/fontawesome.min.js"></script>
</body>
</html>