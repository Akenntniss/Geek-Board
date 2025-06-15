<?php
// Inclure le fichier de configuration
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

try {
    // Création de la connexion PDO avec getShopDBConnection()
    $shop_pdo = getShopDBConnection();
    
    // Vérification de la connexion (PDO lance automatiquement une exception en cas d'erreur)
    // Pas besoin de vérification supplémentaire
    
    // PDO utilise automatiquement UTF-8 avec notre configuration
    
} catch (Exception $e) {
    // En cas d'erreur, afficher un message d'erreur générique
    // En production, vous devriez logger l'erreur au lieu de l'afficher
    error_log($e->getMessage());
    die("Une erreur est survenue lors de la connexion à la base de données. Veuillez réessayer plus tard.");
} 
?> 