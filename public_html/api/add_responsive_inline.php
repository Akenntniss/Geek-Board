<?php
// Script pour ajouter directement le code CSS responsive dans le header.php

echo "<h1>Ajout du code CSS responsive directement dans le header</h1>";

// Contenu CSS responsive
$css_responsive_content = <<<'EOT'
/* Styles responsives */
@media (max-width: 767px) {
    .sidebar {
        position: static !important;
        height: auto !important;
        border-right: none !important;
        border-bottom: 1px solid #dee2e6 !important;
        margin-bottom: 1rem !important;
    }
    
    body {
        padding-top: 56px !important;
    }
    
    .navbar-brand {
        font-size: 1.1rem !important;
    }
    
    .d-md-none {
        display: block !important;
    }
    
    .d-none {
        display: none !important;
    }
    
    .d-md-table-cell {
        display: none !important;
    }
    
    .d-lg-table-cell {
        display: none !important;
    }
    
    .btn-group {
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
    }
    
    .btn-group .btn {
        margin-right: 0 !important;
        margin-bottom: 0.5rem !important;
        border-radius: 0.375rem !important;
    }
    
    .card {
        margin-bottom: 1rem !important;
    }
    
    .card .display-4 {
        font-size: 1.8rem !important;
    }
    
    .table th, .table td {
        padding: 0.5rem !important;
        font-size: 0.85rem !important;
    }
    
    .col-md-6 {
        width: 100% !important;
    }
    
    .col-lg-3 {
        width: 50% !important;
    }
}

@media (min-width: 768px) and (max-width: 991px) {
    .sidebar {
        width: 200px !important;
    }
    
    .card .display-4 {
        font-size: 2rem !important;
    }
}
EOT;

// Mettre à jour le fichier header.php
$header_file = __DIR__ . '/includes/header.php';
if (file_exists($header_file)) {
    $header_content = file_get_contents($header_file);
    
    // Vérifier si le CSS responsive est déjà inclus
    if (strpos($header_content, '/* Styles responsives */') === false) {
        // Ajouter le CSS responsive juste avant la fermeture de la balise </style>
        $header_content = str_replace(
            '</style>',
            $css_responsive_content . "\n    </style>",
            $header_content
        );
        
        // Sauvegarder les modifications
        if (file_put_contents($header_file, $header_content)) {
            echo "<p style='color: green;'>Le CSS responsive a été ajouté avec succès dans le fichier header.php.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de l'ajout du CSS responsive dans le fichier header.php.</p>";
        }
    } else {
        echo "<p>Le CSS responsive est déjà inclus dans le fichier header.php.</p>";
    }
} else {
    echo "<p style='color: red;'>Le fichier header.php n'existe pas.</p>";
}

// Ajouter du JavaScript pour améliorer l'expérience mobile
$footer_file = __DIR__ . '/includes/footer.php';
if (file_exists($footer_file)) {
    $footer_content = file_get_contents($footer_file);
    
    // Vérifier si le JavaScript responsive est déjà inclus
    if (strpos($footer_content, '// Code pour améliorer l\'expérience mobile') === false) {
        // Ajouter le JavaScript responsive juste avant la fermeture de la balise </body>
        $footer_content = str_replace(
            '</body>',
            '<script>
// Code pour améliorer l\'expérience mobile
document.addEventListener("DOMContentLoaded", function() {
    // Ajouter un bouton flottant pour les actions principales sur mobile
    if (window.innerWidth < 768) {
        // Créer le bouton flottant
        var fab = document.createElement("button");
        fab.className = "btn btn-primary";
        fab.style.position = "fixed";
        fab.style.bottom = "20px";
        fab.style.right = "20px";
        fab.style.width = "60px";
        fab.style.height = "60px";
        fab.style.borderRadius = "50%";
        fab.style.fontSize = "24px";
        fab.style.boxShadow = "0 4px 8px rgba(0, 0, 0, 0.2)";
        fab.style.zIndex = "1000";
        fab.innerHTML = "<i class=\"fas fa-plus\"></i>";
        
        // Ajouter le bouton au body
        document.body.appendChild(fab);
        
        // Ajouter un événement de clic
        fab.addEventListener("click", function() {
            if (window.location.href.includes("page=clients")) {
                window.location.href = "index.php?page=ajouter_client";
            } else {
                window.location.href = "index.php?page=ajouter_reparation";
            }
        });
    }
});
</script>
</body>',
            $footer_content
        );
        
        // Sauvegarder les modifications
        if (file_put_contents($footer_file, $footer_content)) {
            echo "<p style='color: green;'>Le JavaScript responsive a été ajouté avec succès dans le fichier footer.php.</p>";
        } else {
            echo "<p style='color: red;'>Erreur lors de l'ajout du JavaScript responsive dans le fichier footer.php.</p>";
        }
    } else {
        echo "<p>Le JavaScript responsive est déjà inclus dans le fichier footer.php.</p>";
    }
} else {
    echo "<p style='color: red;'>Le fichier footer.php n'existe pas.</p>";
}

echo "<h2>Finalisation</h2>";
echo "<p>Le code CSS responsive a été ajouté directement dans le header et le JavaScript responsive a été ajouté dans le footer.</p>";
echo "<p>Votre application devrait maintenant être optimisée pour les appareils mobiles sans nécessiter de fichiers CSS ou JS supplémentaires.</p>";
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