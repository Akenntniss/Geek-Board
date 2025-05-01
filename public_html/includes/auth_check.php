<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
    header('Location: /login.php');
    exit;
}

// Vérifier si l'utilisateur a les droits nécessaires (admin ou technicien)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'technicien'])) {
    // Si l'utilisateur n'a pas les droits nécessaires, rediriger vers la page d'accueil
    header('Location: /index.php');
    exit;
}

// Définir une variable globale pour le rôle de l'utilisateur
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? 'Utilisateur'; 