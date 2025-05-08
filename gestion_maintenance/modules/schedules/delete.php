<?php
// Page de suppression d'une planification de maintenance
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

// Vérifier si la planification existe
$check_sql = "SELECT id FROM schedules WHERE id = $id";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows === 0) {
    // La planification n'existe pas
    $conn->close();
    header('Location: list.php');
    exit;
}

// Commencer une transaction
$conn->begin_transaction();

try {
    // Supprimer d'abord les éléments de planification associés
    $delete_items_sql = "DELETE FROM schedule_items WHERE schedule_id = $id";
    if ($conn->query($delete_items_sql) !== TRUE) {
        throw new Exception('Erreur lors de la suppression des éléments de planification: ' . $conn->error);
    }
    
    // Supprimer la planification
    $delete_sql = "DELETE FROM schedules WHERE id = $id";
    if ($conn->query($delete_sql) !== TRUE) {
        throw new Exception('Erreur lors de la suppression de la planification: ' . $conn->error);
    }
    
    // Valider la transaction
    $conn->commit();
    
    $_SESSION['success'] = "La planification a été supprimée avec succès.";
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}

// Fermer la connexion
$conn->close();

// Rediriger vers la liste
header('Location: list.php');
exit;
?>
