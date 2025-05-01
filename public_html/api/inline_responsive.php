<?php
// Script pour ajouter les styles et scripts responsives directement dans les fichiers existants

echo "<h1>Ajout des styles et scripts responsives</h1>";

// Contenu CSS responsive
$css_responsive_content = <<<'EOT'
/* Styles responsives pour l'application */
@media (min-width: 1200px) {
    .container { max-width: 1140px; }
    .sidebar { width: 250px; }
    main.col-lg-10 { padding-left: 2rem; padding-right: 2rem; }
    .table th, .table td { padding: 1rem; }
}

@media (min-width: 768px) and (max-width: 1199px) {
    .sidebar { width: 200px; }
    body { font-size: 0.95rem; }
    .card .display-4 { font-size: 2rem; }
    .btn { padding: 0.6rem 1.2rem; font-size: 1rem; }
    .form-control, .form-select { padding: 0.5rem 0.75rem; height: calc(2.5rem + 2px); }
    .table th, .table td { padding: 0.75rem; }
    .btn-group .btn { margin-right: 5px; }
}

@media (max-width: 767px) {
    .navbar-brand { font-size: 1.2rem; }
    .sidebar { width: 100%; position: static; height: auto; padding: 1rem 0; border-right: none; border-bottom: 1px solid #dee2e6; margin-bottom: 1rem; }
    body { font-size: 0.9rem; padding-top: 56px; }
    .card { margin-bottom: 1rem; }
    .card .display-4 { font-size: 1.8rem; }
    .btn { padding: 0.7rem 1.4rem; font-size: 1.1rem; }
    .btn-group { display: flex; flex-direction: column; width: 100%; }
    .btn-group .btn { margin-right: 0; margin-bottom: 0.5rem; border-radius: 0.375rem !important; }
    .form-control, .form-select { padding: 0.6rem 0.75rem; height: calc(2.8rem + 2px); font-size: 1rem; }
    .table th, .table td { padding: 0.5rem; font-size: 0.85rem; }
}

@media (max-width: 575px) {
    .container { padding-left: 10px; padding-right: 10px; }
    h1 { font-size: 1.8rem; }
    h2 { font-size: 1.5rem; }
    .card-body { padding: 0.75rem; }
    .card .display-4 { font-size: 1.5rem; }
}
EOT;

// Contenu JS responsive
$js_responsive_content = <<<'EOT'
// Script pour améliorer l'expérience mobile et tablette
document.addEventListener('DOMContentLoaded', function() {
    // Rendre les tableaux responsives sur mobile
    if (window.innerWidth < 768) {
        const tables = document.querySelectorAll('.table');
        tables.forEach(function(table) {
            // Récupérer les en-têtes
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
            
            // Ajouter les attributs data-label aux cellules
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                const cells = row.querySelectorAll('td');
                cells.forEach(function(cell, index) {
                    if (headers[index]) {
                        cell.setAttribute('data-label', headers[index]);
                    }
                });
            });
        });
        
        // Améliorer les formulaires
        const formControls = document.querySelectorAll('.form-control, .form-select');
        formControls.forEach(function(control) {
            control.classList.add('form-control-lg');
        });
        
        // Ajouter un bouton flottant pour les actions principales
        if (window.location.href.includes('page=clients') || window.location.href.includes('page=reparations') || window.location.href.includes('index.php')) {
            const fabContainer = document.createElement('div');
            fabContainer.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 1000;';
            
            const fab = document.createElement('button');
            fab.className = 'btn btn-primary rounded-circle';
            fab.style.cssText = 'width: 60px; height: 60px; font-size: 24px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); display: flex; align-items: center; justify-content: center;';
            fab.innerHTML = '<i class="fas fa-plus"></i>';
            
            fabContainer.appendChild(fab);
            document.body.appendChild(fabContainer);
            
            fab.addEventListener('click', function() {
                if (window.location.href.includes('page=clients')) {
                    window.location.href = 'index.php?page=ajouter_client';
                } else {
                    window.location.href = 'index.php?page=ajouter_reparation';
                }
            });
        }
    }
});
EOT;

// Mettre à jour le fichier style.css
$style_file = __DIR__ . '/assets/css/style.css';
if (file_exists($style_file)) {
    $style_content = file_get_contents($style_file);
    
    // Vérifier si les styles responsives sont déjà inclus
    if (strpos($style_content, '/* Styles responsives pour l\'application */') === false) {
        $style_content .= "\n\n" . $css_responsive_content;
        
        echo "<h2>Mise à jour du fichier style.css</h2>";
        if (file_put_contents($style_file, $style_content)) {
            echo "<p style='color: green;'>Les styles responsives ont été ajoutés avec succès au fichier style.css.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de l'ajout des styles responsives au fichier style.css.</p>";
        }
    } else {
        echo "<h2>Vérification du fichier style.css</h2>";
        echo "<p>Les styles responsives sont déjà inclus dans style.css.</p>";
    }
} else {
    echo "<h2>Création du fichier style.css</h2>";
    $style_content = "/* Styles personnalisés pour l'application de gestion des réparations */\n\n";
    $style_content .= $css_responsive_content;
    
    if (file_put_contents($style_file, $style_content)) {
        echo "<p style='color: green;'>Le fichier style.css a été créé avec succès.</p>";
    } else {
        echo "<p style='color: red;'>Erreur lors de la création du fichier style.css.</p>";
    }
}

// Mettre à jour le fichier script.js
$script_file = __DIR__ . '/assets/js/script.js';
if (file_exists($script_file)) {
    $script_content = file_get_contents($script_file);
    
    // Vérifier si les scripts responsives sont déjà inclus
    if (strpos($script_content, '// Script pour améliorer l\'expérience mobile et tablette') === false) {
        $script_content .= "\n\n" . $js_responsive_content;
        
        echo "<h2>Mise à jour du fichier script.js</h2>";
        if (file_put_contents($script_file, $script_content)) {
            echo "<p style='color: green;'>Les scripts responsives ont été ajoutés avec succès au fichier script.js.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de l'ajout des scripts responsives au fichier script.js.</p>";
        }
    } else {
        echo "<h2>Vérification du fichier script.js</h2>";
        echo "<p>Les scripts responsives sont déjà inclus dans script.js.</p>";
    }
} else {
    echo "<h2>Création du fichier script.js</h2>";
    $script_content = "// Script personnalisé pour l'application de gestion des réparations\n\n";
    $script_content .= "document.addEventListener('DOMContentLoaded', function() {\n";
    $script_content .= "    // Fermeture automatique des alertes après 5 secondes\n";
    $script_content .= "    setTimeout(function() {\n";
    $script_content .= "        const alerts = document.querySelectorAll('.alert');\n";
    $script_content .= "        alerts.forEach(function(alert) {\n";
    $script_content .= "            const bsAlert = new bootstrap.Alert(alert);\n";
    $script_content .= "            bsAlert.close();\n";
    $script_content .= "        });\n";
    $script_content .= "    }, 5000);\n";
    $script_content .= "});\n\n";
    $script_content .= $js_responsive_content;
    
    if (file_put_contents($script_file, $script_content)) {
        echo "<p style='color: green;'>Le fichier script.js a été créé avec succès.</p>";
    } else {
        echo "<p style='color: red;'>Erreur lors de la création du fichier script.js.</p>";
    }
}

echo "<h2>Finalisation</h2>";
echo "<p>Les styles et scripts responsives ont été ajoutés directement dans les fichiers existants.</p>";
echo "<p>Votre application devrait maintenant être optimisée pour les PC, tablettes et mobiles.</p>";
echo "<p><a href='index.php' style='display: inline-block; background-color: #0066cc; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Accéder à l'application</a></p>";

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
</style>
";
?>