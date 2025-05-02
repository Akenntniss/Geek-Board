<?php
// Script de déconnexion pour le super administrateur
session_start();

// Supprimer les variables de session liées au super admin
unset($_SESSION['superadmin_id']);
unset($_SESSION['superadmin_username']);
unset($_SESSION['superadmin_name']);

// Supprimer également les variables de session liées au magasin
unset($_SESSION['shop_id']);
unset($_SESSION['shop_name']);

// Définir un message de déconnexion
$_SESSION['message'] = "Vous avez été déconnecté avec succès.";

// Rediriger vers la page de connexion
header('Location: login.php');
exit;
?> 