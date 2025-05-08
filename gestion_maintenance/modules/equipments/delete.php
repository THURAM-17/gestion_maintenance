<?php
// Page de suppression d'un équipement
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

// Vérifier si l'équipement existe
$check_sql = "SELECT id FROM equipments WHERE id = $id";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows === 0) {
    // L'équipement n'existe pas
    $conn->close();
    header('Location: list.php');
    exit;
}

// Vérifier si l'équipement est utilisé dans des planifications
$schedule_check = "SELECT id FROM schedule_items WHERE equipment_id = $id LIMIT 1";
$schedule_result = $conn->query($schedule_check);

if ($schedule_result->num_rows > 0) {
    // L'équipement est utilisé, rediriger avec message d'erreur
    $_SESSION['error'] = "Cet équipement ne peut pas être supprimé car il est associé à des planifications de maintenance.";
    $conn->close();
    header('Location: list.php');
    exit;
}

// Suppression de l'équipement
$delete_history = "DELETE FROM equipment_history WHERE equipment_id = $id";
$conn->query($delete_history); // Supprimer d'abord l'historique

$delete_sql = "DELETE FROM equipments WHERE id = $id";
if ($conn->query($delete_sql) === TRUE) {
    $_SESSION['success'] = "L'équipement a été supprimé avec succès.";
} else {
    $_SESSION['error'] = "Erreur lors de la suppression de l'équipement: " . $conn->error;
}

// Fermer la connexion
$conn->close();

// Rediriger vers la liste
header('Location: list.php');
exit;
?>
