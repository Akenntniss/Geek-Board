<?php
// Page de test pour le signalement de bugs
echo "<div style='margin: 50px auto; max-width: 800px; padding: 20px; background-color: #f8f9fa; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
echo "<h1 style='color: #dc3545;'><i class='fas fa-bug'></i> Signalements de bugs - Page de test</h1>";
echo "<p>Cette page est accessible. Le problème n'est pas lié à la configuration de l'index.php.</p>";
echo "<p>Date et heure du serveur : " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Session active : " . (session_id() ? 'Oui' : 'Non') . "</p>";
echo "<p>Utilisateur connecté : " . (isset($_SESSION['user_id']) ? 'Oui (ID: '.$_SESSION['user_id'].')' : 'Non') . "</p>";
echo "<hr>";
echo "<p><a href='index.php?page=accueil' style='color: #007bff;'>Retour à l'accueil</a></p>";
echo "</div>";
?> 