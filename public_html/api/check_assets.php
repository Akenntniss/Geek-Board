<?php
// Script pour vérifier et créer les fichiers CSS et JS

// Afficher les erreurs pendant l'installation
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Vérification des fichiers CSS et JS</h1>";

// Contenu du fichier CSS
$css_content = <<<'EOT'
/* Styles personnalisés pour l'application de gestion des réparations */

/* Styles généraux */
body {
    background-color: #f8f9fa;
}

/* Styles pour les cartes */
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1.5rem;
}

.card-header {
    font-weight: 500;
}

/* Styles pour les tableaux */
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

/* Styles pour les badges de statut */
.badge {
    font-size: 0.85rem;
    padding: 0.35em 0.65em;
}

/* Styles pour les formulaires */
.form-label {
    font-weight: 500;
}

/* Styles pour le sidebar */
.sidebar .nav-link.active {
    background-color: #e9ecef;
    color: #0d6efd;
    font-weight: bold;
}

/* Styles pour les boutons d'action */
.btn-group .btn {
    margin-right: 2px;
}

/* Styles pour les statistiques sur la page d'accueil */
.card .display-4 {
    font-size: 2.5rem;
    font-weight: 500;
}

/* Styles pour les alertes */
.alert {
    margin-bottom: 1.5rem;
}

/* Styles pour le footer */
footer {
    margin-top: 3rem;
    padding: 1rem 0;
}
EOT;

// Contenu du fichier JS
$js_content = <<<'EOT'
// Script personnalisé pour l'application de gestion des réparations

// Attendre que le document soit chargé
document.addEventListener('DOMContentLoaded', function() {
    
    // Fermeture automatique des alertes après 5 secondes
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Activer les tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Activer les popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Confirmation avant suppression
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                e.preventDefault();
            }
        });
    });
    
});
EOT;

// Vérifier et créer le fichier CSS
$css_file = __DIR__ . '/assets/css/style.css';
echo "<h2>Vérification du fichier CSS</h2>";
if (file_exists($css_file)) {
    echo "<p>Le fichier CSS existe déjà.</p>";
    
    // Vérifier le contenu
    $current_css = file_get_contents($css_file);
    if (trim($current_css) == '') {
        echo "<p>Le fichier CSS est vide. Mise à jour du contenu...</p>";
        if (file_put_contents($css_file, $css_content)) {
            echo "<p style='color: green;'>Le fichier CSS a été mis à jour avec succès.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de la mise à jour du fichier CSS.</p>";
        }
    } else {
        echo "<p>Le fichier CSS a déjà du contenu.</p>";
    }
} else {
    echo "<p>Le fichier CSS n'existe pas. Création du fichier...</p>";
    if (file_put_contents($css_file, $css_content)) {
        echo "<p style='color: green;'>Le fichier CSS a été créé avec succès.</p>";
    } else {
        echo "<p style='color: red;'>Erreur lors de la création du fichier CSS.</p>";
    }
}

// Vérifier et créer le fichier JS
$js_file = __DIR__ . '/assets/js/script.js';
echo "<h2>Vérification du fichier JS</h2>";
if (file_exists($js_file)) {
    echo "<p>Le fichier JS existe déjà.</p>";
    
    // Vérifier le contenu
    $current_js = file_get_contents($js_file);
    if (trim($current_js) == '') {
        echo "<p>Le fichier JS est vide. Mise à jour du contenu...</p>";
        if (file_put_contents($js_file, $js_content)) {
            echo "<p style='color: green;'>Le fichier JS a été mis à jour avec succès.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de la mise à jour du fichier JS.</p>";
        }
    } else {
        echo "<p>Le fichier JS a déjà du contenu.</p>";
    }
} else {
    echo "<p>Le fichier JS n'existe pas. Création du fichier...</p>";
    if (file_put_contents($js_file, $js_content)) {
        echo "<p style='color: green;'>Le fichier JS a été créé avec succès.</p>";
    } else {
        echo "<p style='color: red;'>Erreur lors de la création du fichier JS.</p>";
    }
}

// Vérifier les chemins dans les fichiers header.php et footer.php
echo "<h2>Vérification des chemins dans les fichiers header.php et footer.php</h2>";

$header_file = __DIR__ . '/includes/header.php';
$footer_file = __DIR__ . '/includes/footer.php';

if (file_exists($header_file)) {
    $header_content = file_get_contents($header_file);
    echo "<p>Fichier header.php trouvé.</p>";
    
    // Vérifier si le chemin CSS est correct
    if (strpos($header_content, 'href="assets/css/style.css"') !== false) {
        echo "<p>Correction du chemin CSS dans header.php...</p>";
        $header_content = str_replace(
            'href="assets/css/style.css"',
            'href="<?php echo str_replace($_SERVER[\'DOCUMENT_ROOT\'], \'\', BASE_PATH); ?>/assets/css/style.css"',
            $header_content
        );
        if (file_put_contents($header_file, $header_content)) {
            echo "<p style='color: green;'>Chemin CSS corrigé dans header.php.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de la correction du chemin CSS dans header.php.</p>";
        }
    } else {
        echo "<p>Le chemin CSS dans header.php semble déjà correct.</p>";
    }
} else {
    echo "<p style='color: red;'>Fichier header.php non trouvé.</p>";
}

if (file_exists($footer_file)) {
    $footer_content = file_get_contents($footer_file);
    echo "<p>Fichier footer.php trouvé.</p>";
    
    // Vérifier si le chemin JS est correct
    if (strpos($footer_content, 'src="assets/js/script.js"') !== false) {
        echo "<p>Correction du chemin JS dans footer.php...</p>";
        $footer_content = str_replace(
            'src="assets/js/script.js"',
            'src="<?php echo str_replace($_SERVER[\'DOCUMENT_ROOT\'], \'\', BASE_PATH); ?>/assets/js/script.js"',
            $footer_content
        );
        if (file_put_contents($footer_file, $footer_content)) {
            echo "<p style='color: green;'>Chemin JS corrigé dans footer.php.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de la correction du chemin JS dans footer.php.</p>";
        }
    } else {
        echo "<p>Le chemin JS dans footer.php semble déjà correct.</p>";
    }
} else {
    echo "<p style='color: red;'>Fichier footer.php non trouvé.</p>";
}

echo "<h2>Finalisation</h2>";
echo "<p>La vérification des fichiers CSS et JS est terminée.</p>";
echo "<p><a href='index.php' class='btn btn-primary'>Accéder à l'application</a></p>";

// Ajouter un peu de style
echo "
<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
        padding: 0;
        color: #333;
    }
    h1, h2, h3 {
        color: #0066cc;
    }
    p {
        margin: 10px 0;
    }
    .btn-primary {
        display: inline-block;
        background-color: #0066cc;
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 4px;
    }
    .btn-primary:hover {
        background-color: #0052a3;
    }
</style>
";
?>