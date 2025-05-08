<?php
// Page de suppression d'une catégorie
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir la fonction isLoggedIn si elle n'existe pas
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: /gestion_maintenance/modules/auth/login.php');
    exit;
}

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];

// Connexion à la base de données
$conn = getConnection();

// Vérifier si la catégorie existe
$check_sql = "SELECT id FROM categories WHERE id = $id";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows === 0) {
    // La catégorie n'existe pas
    $conn->close();
    header('Location: list.php');
    exit;
}

// Vérifier si la catégorie a des sous-catégories
$subcategories_check = "SELECT id FROM categories WHERE parent_id = $id LIMIT 1";
$subcategories_result = $conn->query($subcategories_check);

if ($subcategories_result->num_rows > 0) {
    // La catégorie a des sous-catégories, rediriger avec message d'erreur
    $_SESSION['error'] = "Cette catégorie ne peut pas être supprimée car elle a des sous-catégories.";
    $conn->close();
    echo "<script>window.location.href = 'list.php';</script>";
    exit;
}

// Vérifier si la catégorie est utilisée par des équipements
$equipment_check = "SELECT id FROM equipments WHERE category_id = $id LIMIT 1";
$equipment_result = $conn->query($equipment_check);

if ($equipment_result->num_rows > 0) {
    // La catégorie est utilisée par des équipements, rediriger avec message d'erreur
    $_SESSION['error'] = "Cette catégorie ne peut pas être supprimée car elle est associée à des équipements.";
    $conn->close();
    echo "<script>window.location.href = 'list.php';</script>";
    exit;
}

// Vérifier si la catégorie est utilisée par des tâches
$task_check = "SELECT id FROM tasks WHERE category_id = $id LIMIT 1";
$task_result = $conn->query($task_check);

if ($task_result->num_rows > 0) {
    // La catégorie est utilisée par des tâches, rediriger avec message d'erreur
    $_SESSION['error'] = "Cette catégorie ne peut pas être supprimée car elle est associée à des tâches.";
    $conn->close();
    echo "<script>window.location.href = 'list.php';</script>";
    exit;
}

// Suppression de la catégorie
$delete_sql = "DELETE FROM categories WHERE id = $id";
if ($conn->query($delete_sql) === TRUE) {
    $_SESSION['success'] = "La catégorie a été supprimée avec succès.";
} else {
    $_SESSION['error'] = "Erreur lors de la suppression de la catégorie: " . $conn->error;
}

// Fermer la connexion
$conn->close();

// Rediriger vers la liste
echo "<script>window.location.href = 'list.php';</script>";
exit;
?>
