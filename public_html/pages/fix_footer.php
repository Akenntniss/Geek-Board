<?php
// Ce script doit être placé à la racine du site

// Obtenir le chemin complet du fichier rachat_appareils.php
$file_path = __DIR__ . '/pages/rachat_appareils.php';

if (file_exists($file_path)) {
    // Lire le contenu du fichier
    $content = file_get_contents($file_path);
    
    // Supprimer toute inclusion directe du footer
    $content = preg_replace('/include.*footer\.php.*;?/', '', $content);
    
    // Supprimer toute duplication éventuelle des balises HTML de fermeture
    $content = preg_replace('/<\/script>\s*<\/body>\s*<\/html>/', '</script>', $content);
    
    // Écrire le contenu modifié dans le fichier
    if (file_put_contents($file_path, $content)) {
        echo "<p style='color: green'>Le fichier rachat_appareils.php a été corrigé avec succès.</p>";
    } else {
        echo "<p style='color: red'>Erreur lors de l'écriture dans le fichier rachat_appareils.php.</p>";
    }
} else {
    echo "<p style='color: red'>Le fichier rachat_appareils.php n'existe pas.</p>";
}

// Vérifier et corriger le fichier footer.php
$footer_path = __DIR__ . '/includes/footer.php';

if (file_exists($footer_path)) {
    $footer_content = file_get_contents($footer_path);
    
    // S'assurer qu'il n'y a qu'un seul copyright
    $footer_content = preg_replace('/(© \d{4} - GestiRep \| Application de Gestion des Réparations.*?)\1/s', '$1', $footer_content);
    
    // Simplifier le footer
    $footer_content = <<<EOT
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">© 2025 - GestiRep | Application de Gestion des Réparations</span>
        </div>
    </footer>

    <!-- Scripts Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
EOT;
    
    if (file_put_contents($footer_path, $footer_content)) {
        echo "<p style='color: green'>Le fichier footer.php a été simplifié avec succès.</p>";
    } else {
        echo "<p style='color: red'>Erreur lors de l'écriture dans le fichier footer.php.</p>";
    }
} else {
    echo "<p style='color: red'>Le fichier footer.php n'existe pas.</p>";
}

echo "<p>Retournez à la <a href='index.php'>page d'accueil</a>.</p>";
?> 