<?php
// Script pour corriger l'inclusion du navbar dans header.php

// Chemin vers le fichier header.php
$header_file = __DIR__ . '/includes/header.php';

// Vérifier si le fichier header.php existe
if (!file_exists($header_file)) {
    echo "Le fichier header.php n'existe pas à l'emplacement spécifié.\n";
    exit(1);
}

// Lire le contenu du fichier
$header_content = file_get_contents($header_file);

// Nouvelle inclusion avec vérification d'existence
$new_include = <<<'EOT'
<?php 
// Vérifier si le fichier navbar.php existe avant de l'inclure
$navbar_path = 'components/navbar.php';
if (file_exists($navbar_path)) {
    include_once $navbar_path;
}
?>
EOT;

// Remplacer l'ancienne inclusion par la nouvelle
$modified_content = preg_replace('/\<\?php\s+include_once\s+\'components\/navbar\.php\'\;\s+\?\>/', $new_include, $header_content);

// Vérifier si le remplacement a eu lieu
if ($modified_content === $header_content) {
    echo "Aucun changement n'a été effectué. Le motif à rechercher n'a pas été trouvé.\n";
} else {
    // Sauvegarder une copie de sauvegarde
    file_put_contents($header_file . '.bak', $header_content);
    
    // Écrire le contenu modifié
    if (file_put_contents($header_file, $modified_content)) {
        echo "Le fichier header.php a été mis à jour avec succès.\n";
    } else {
        echo "Erreur lors de l'écriture du fichier header.php.\n";
    }
}

// Maintenant, créons le répertoire components s'il n'existe pas
$components_dir = __DIR__ . '/components';
if (!is_dir($components_dir)) {
    if (mkdir($components_dir, 0755, true)) {
        echo "Le répertoire components a été créé.\n";
    } else {
        echo "Erreur lors de la création du répertoire components.\n";
    }
}

// Copier le fichier navbar.php dans le répertoire components
$navbar_file = $components_dir . '/navbar.php';
if (!file_exists($navbar_file)) {
    // Créer un fichier navbar.php minimal
    $navbar_content = <<<'EOT'
<!-- Fichier navbar.php minimal -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">TechBoard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="index.php">Accueil</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=reparations">Réparations</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=clients">Clients</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php?page=statistiques">Statistiques</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
EOT;
    
    if (file_put_contents($navbar_file, $navbar_content)) {
        echo "Un fichier navbar.php minimal a été créé.\n";
    } else {
        echo "Erreur lors de la création du fichier navbar.php.\n";
    }
}

echo "Terminé. Veuillez recharger la page pour vérifier les changements.\n"; 