<?php
// Inclure le fichier de configuration
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

try {
    // Création de la connexion MySQLi avec les constantes du fichier de configuration
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Vérification de la connexion
    if ($conn->connect_error) {
        throw new Exception("Erreur de connexion à la base de données : " . $conn->connect_error);
    }
    
    // Définir le jeu de caractères en UTF-8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // En cas d'erreur, afficher un message d'erreur générique
    // En production, vous devriez logger l'erreur au lieu de l'afficher
    error_log($e->getMessage());
    die("Une erreur est survenue lors de la connexion à la base de données. Veuillez réessayer plus tard.");
} 