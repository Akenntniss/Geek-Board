<?php
// Script pour générer des icônes avec badge pour iOS PWA
header('Content-Type: text/html; charset=utf-8');

// Vérifier si GD est disponible
if (!extension_loaded('gd')) {
    die('L\'extension GD est requise pour générer les icônes avec badge.');
}

// Créer le dossier de sortie s'il n'existe pas
$output_dir = __DIR__;
if (!file_exists($output_dir)) {
    mkdir($output_dir, 0777, true);
}

// Configuration des tailles d'icônes
$icon_sizes = [192, 180, 167, 152, 128];

// Chemin vers l'icône source principale
$source_icon = __DIR__ . '/icon-192x192.png';

// Vérifier si l'icône source existe
if (!file_exists($source_icon)) {
    die('L\'icône source n\'existe pas: ' . $source_icon);
}

// Fonction pour créer une icône avec badge
function createBadgeIcon($source_path, $output_path, $badge_number, $size) {
    // Charger l'image source
    $source_img = imagecreatefrompng($source_path);
    
    // Redimensionner si nécessaire
    if (imagesx($source_img) != $size) {
        $resized_img = imagecreatetruecolor($size, $size);
        imagealphablending($resized_img, false);
        imagesavealpha($resized_img, true);
        $transparent = imagecolorallocatealpha($resized_img, 255, 255, 255, 127);
        imagefilledrectangle($resized_img, 0, 0, $size, $size, $transparent);
        imagecopyresampled($resized_img, $source_img, 0, 0, 0, 0, $size, $size, imagesx($source_img), imagesy($source_img));
        imagedestroy($source_img);
        $source_img = $resized_img;
    }
    
    // Calculer la taille du badge en fonction de la taille de l'icône
    $badge_size = intval($size * 0.4); // 40% de la taille de l'icône
    $badge_position_x = $size - $badge_size * 0.8;
    $badge_position_y = $size - $badge_size * 0.8;
    
    // Créer le badge
    $badge_img = imagecreatetruecolor($badge_size, $badge_size);
    imagealphablending($badge_img, true);
    
    // Couleur rouge pour le badge
    $red = imagecolorallocate($badge_img, 255, 0, 0);
    imagefilledellipse($badge_img, $badge_size/2, $badge_size/2, $badge_size, $badge_size, $red);
    
    // Couleur blanche pour le texte
    $white = imagecolorallocate($badge_img, 255, 255, 255);
    
    // Adapter la taille du texte au nombre de chiffres
    $font_size = $badge_size * 0.5;
    if ($badge_number > 9) {
        $font_size = $badge_size * 0.4;
    }
    if ($badge_number > 99) {
        $badge_number = '99+';
        $font_size = $badge_size * 0.35;
    }
    
    // Centrer le texte sur le badge
    $font_path = __DIR__ . '/../../fonts/Arial.ttf'; // Chemin vers une police
    if (!file_exists($font_path)) {
        // Utiliser une police système si la police spécifiée n'existe pas
        $font_path = 5; // Police système
        // Dessiner le nombre (avec police système)
        imagestring($badge_img, $font_path, $badge_size/2 - strlen($badge_number) * 3, $badge_size/2 - 5, $badge_number, $white);
    } else {
        // Dessiner le nombre (avec police TTF)
        $text_box = imagettfbbox($font_size, 0, $font_path, $badge_number);
        $text_width = $text_box[2] - $text_box[0];
        $text_height = $text_box[1] - $text_box[7];
        $text_x = ($badge_size - $text_width) / 2;
        $text_y = ($badge_size + $text_height) / 2;
        imagettftext($badge_img, $font_size, 0, $text_x, $text_y, $white, $font_path, $badge_number);
    }
    
    // Fusionner le badge avec l'icône
    imagecopyresampled($source_img, $badge_img, $badge_position_x, $badge_position_y, 0, 0, $badge_size, $badge_size, $badge_size, $badge_size);
    
    // Enregistrer l'image résultante
    imagepng($source_img, $output_path);
    
    // Libérer la mémoire
    imagedestroy($source_img);
    imagedestroy($badge_img);
    
    return true;
}

// Générer des icônes avec différents nombres de badge
$badge_numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 50, 99];

echo '<h1>Génération des icônes avec badge</h1>';
echo '<ul>';

foreach ($badge_numbers as $number) {
    foreach ($icon_sizes as $size) {
        $output_file = $output_dir . "/icon-badge-{$number}-{$size}x{$size}.png";
        if (createBadgeIcon($source_icon, $output_file, $number, $size)) {
            echo "<li>Icône générée: icon-badge-{$number}-{$size}x{$size}.png</li>";
        } else {
            echo "<li style='color:red'>Erreur lors de la génération de l'icône: icon-badge-{$number}-{$size}x{$size}.png</li>";
        }
    }
}

echo '</ul>';
echo '<p>Génération terminée!</p>';

// Créer un fichier de manifeste pour les badges iOS
$manifest_file = $output_dir . '/badge-manifest.json';
$manifest = [
    'badge_icons' => []
];

foreach ($badge_numbers as $number) {
    $manifest['badge_icons'][$number] = [
        '192x192' => "icon-badge-{$number}-192x192.png",
        '180x180' => "icon-badge-{$number}-180x180.png",
        '167x167' => "icon-badge-{$number}-167x167.png",
        '152x152' => "icon-badge-{$number}-152x152.png",
        '128x128' => "icon-badge-{$number}-128x128.png"
    ];
}

file_put_contents($manifest_file, json_encode($manifest, JSON_PRETTY_PRINT));
echo "<p>Fichier de manifeste créé: badge-manifest.json</p>"; 