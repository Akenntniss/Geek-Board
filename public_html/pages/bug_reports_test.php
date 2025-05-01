<?php
echo "<h1>Page de test pour les signalements de bugs</h1>";
echo "<p>Si vous voyez ce message, la page fonctionne correctement.</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Non connecté') . "</p>";
?> 