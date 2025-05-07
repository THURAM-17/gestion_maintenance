<?php ob_start(); ?>
<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Reste du code...

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
// (sauf pour les pages de connexion et d'inscription)
$current_page = basename($_SERVER['PHP_SELF']);
$auth_pages = ['login.php', 'register.php', 'index.php'];

if (!isLoggedIn() && !in_array($current_page, $auth_pages) && strpos($current_page, 'api') === false) {
    header('Location: /gestion_maintenance/modules/auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Maintenances Préventives </title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/gestion_maintenance/assets/css/styles.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <?php include_once 'navbar.php'; ?>
    <div class="container mx-auto p-4 flex-grow">
