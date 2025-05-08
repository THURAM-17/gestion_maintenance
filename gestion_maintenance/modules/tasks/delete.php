<?php
// Page de suppression d'une tâche
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

// Vérifier si la tâche existe
$check_sql = "SELECT id FROM tasks WHERE id = $id";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows === 0) {
    // La tâche n'existe pas
    $conn->close();
    header('Location: list.php');
    exit;
}

// Vérifier si la tâche est utilisée dans des planifications
$schedule_check = "SELECT id FROM schedule_items WHERE task_id = $id LIMIT 1";
$schedule_result = $conn->query($schedule_check);

if ($schedule_result->num_rows > 0) {
    // La tâche est utilisée, rediriger avec message d'erreur
    $_SESSION['error'] = "Cette tâche ne peut pas être supprimée car elle est associée à des planifications de maintenance.";
    $conn->close();
    header('Location: list.php');
    exit;
}

// Suppression de la tâche
$delete_sql = "DELETE FROM tasks WHERE id = $id";
if ($conn->query($delete_sql) === TRUE) {
    $_SESSION['success'] = "La tâche a été supprimée avec succès.";
} else {
    $_SESSION['error'] = "Erreur lors de la suppression de la tâche: " . $conn->error;
}

// Fermer la connexion
$conn->close();

// Rediriger vers la liste
header('Location: list.php');
exit;
?>
