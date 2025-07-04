<?php
// VERSION DE TEST - Database Manager (SANS AUTHENTIFICATION)
// ⚠️  À SUPPRIMER EN PRODUCTION ⚠️
session_start();

// SIMULATION SESSION SUPERADMIN POUR TEST
$_SESSION['superadmin_id'] = 1;
$_SESSION['superadmin_username'] = 'test_admin';
$_SESSION['superadmin_name'] = 'Test Administrator';

// Afficher un avertissement de sécurité
echo '<div style="background:#ff4444;color:white;padding:10px;text-align:center;font-weight:bold;">
        ⚠️ VERSION DE TEST - AUTHENTIFICATION DÉSACTIVÉE - À SUPPRIMER EN PRODUCTION ⚠️
      </div>';

// Inclure le gestionnaire principal
require_once('database_manager.php');
?> 