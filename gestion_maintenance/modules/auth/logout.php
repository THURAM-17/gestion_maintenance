<?php
// Page de déconnexion
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
header('Location: /gestion_maintenance/modules/auth/login.php');
exit;
?>
