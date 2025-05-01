<?php
// Récupérer le contenu du fichier
$content = file_get_contents('header.php');

// Vérifier que le fichier existe et qu'on peut le lire
if ($content === false) {
    echo "Erreur: Impossible de lire le fichier header.php\n";
    exit(1);
}

// Ajouter l'écouteur d'événement pour priceConverterButton avec bootstrap
$buttonCode = <<<EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation du bouton de la calculette
    if (typeof bootstrap !== 'undefined') {
        const priceConverterModal = document.getElementById('priceConverterModal') ? 
            new bootstrap.Modal(document.getElementById('priceConverterModal')) : null;
        
        const priceConverterButton = document.getElementById('priceConverterButton');
        if (priceConverterButton && priceConverterModal) {
            priceConverterButton.addEventListener('click', function() {
                priceConverterModal.show();
            });
        }
    }
});
</script>
</body>
</html>
EOT;

// Remplacer la balise de fermeture </body></html> par notre nouveau code
$content = preg_replace('/<\/body>\s*<\/html>/s', $buttonCode, $content);

// Sauvegarder le fichier
file_put_contents('header.php', $content);
echo "Script ajouté avec succès.\n"; 