<?php
// Script pour préparer les icônes PWA à partir du logo existant
header('Content-Type: text/html; charset=utf-8');

// Vérifier si GD est disponible
if (!extension_loaded('gd')) {
    die('L\'extension GD est requise pour générer les icônes.');
}

echo "<h1>Préparation des icônes PWA</h1>";

// Créer le dossier de sortie s'il n'existe pas
$output_dir = __DIR__ . '/assets/images/pwa-icons';
if (!file_exists($output_dir)) {
    mkdir($output_dir, 0777, true);
    echo "<p>Dossier créé: $output_dir</p>";
}

// Source du logo
$source_logo = __DIR__ . '/assets/images/logo/geekboard-logo.png';

// Vérifier si le logo source existe
if (!file_exists($source_logo)) {
    die("<p style='color:red'>Le logo source n'existe pas: $source_logo</p>");
}

// Tailles d'icônes à générer
$icon_sizes = [512, 384, 192, 180, 167, 152, 144, 128, 96, 72, 48];

// Fonction pour redimensionner une image
function resizeImage($source_path, $output_path, $size) {
    // Charger l'image source
    $source_img = imagecreatefrompng($source_path);
    if (!$source_img) {
        return false;
    }
    
    // Créer une nouvelle image avec la taille souhaitée
    $resized_img = imagecreatetruecolor($size, $size);
    
    // Préserver la transparence
    imagealphablending($resized_img, false);
    imagesavealpha($resized_img, true);
    $transparent = imagecolorallocatealpha($resized_img, 255, 255, 255, 127);
    imagefilledrectangle($resized_img, 0, 0, $size, $size, $transparent);
    
    // Redimensionner l'image
    imagecopyresampled(
        $resized_img, $source_img,
        0, 0, 0, 0,
        $size, $size, imagesx($source_img), imagesy($source_img)
    );
    
    // Enregistrer l'image redimensionnée
    $result = imagepng($resized_img, $output_path);
    
    // Libérer la mémoire
    imagedestroy($source_img);
    imagedestroy($resized_img);
    
    return $result;
}

echo "<h2>Génération des icônes</h2>";
echo "<ul>";

// Générer les icônes pour chaque taille
foreach ($icon_sizes as $size) {
    $output_path = $output_dir . "/icon-{$size}x{$size}.png";
    
    if (resizeImage($source_logo, $output_path, $size)) {
        echo "<li>Icône générée: icon-{$size}x{$size}.png</li>";
    } else {
        echo "<li style='color:red'>Erreur lors de la génération de l'icône: icon-{$size}x{$size}.png</li>";
    }
}

// Copier l'icône de taille 192x192 sous le nom requis pour generate_badge_icons.php
$source_icon = $output_dir . "/icon-192x192.png";
$target_icon = $output_dir . "/icon-192x192.png"; // Même nom car déjà généré

echo "</ul>";
echo "<p>Génération des icônes PWA terminée!</p>";
echo "<p>Vous pouvez maintenant lancer le script generate_badge_icons.sh pour générer les icônes avec badge.</p>";
?> 