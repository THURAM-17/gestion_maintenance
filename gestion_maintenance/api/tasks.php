<?php
/**
 * API pour la gestion des tâches de maintenance
 * Permet de récupérer, ajouter, modifier et supprimer des tâches
 */

// Inclure les fichiers nécessaires
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Vérifier que la requête est de type AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    header('HTTP/1.0 403 Forbidden');
    echo 'Cette page est accessible uniquement via AJAX';
    exit;
}

// Démarrer la session pour récupérer l'utilisateur connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['error' => 'Utilisateur non connecté']);
    exit;
}

// Récupérer la méthode HTTP et l'action demandée
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Connexion à la base de données
$conn = getConnection();

// Traitement en fonction de la méthode HTTP
switch ($method) {
    case 'GET':
        // Récupération des tâches
        if ($action == 'list') {
            // Récupérer toutes les tâches avec les noms de catégories
            $sql = "SELECT t.*, c.name as category_name 
                    FROM tasks t 
                    LEFT JOIN categories c ON t.category_id = c.id 
                    ORDER BY t.name";
            $result = $conn->query($sql);
            
            $tasks = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $tasks[] = $row;
                }
            }
            
            echo json_encode($tasks);
        } elseif ($action == 'get' && isset($_GET['id'])) {
            // Récupérer une tâche spécifique
            $id = (int)$_GET['id'];
            $sql = "SELECT t.*, c.name as category_name 
                    FROM tasks t 
                    LEFT JOIN categories c ON t.category_id = c.id 
                    WHERE t.id = $id";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $task = $result->fetch_assoc();
                echo json_encode($task);
            } else {
                header('HTTP/1.0 404 Not Found');
                echo json_encode(['error' => 'Tâche non trouvée']);
            }
        } elseif ($action == 'by_category' && isset($_GET['category_id'])) {
            // Récupérer les tâches d'une catégorie spécifique
            $category_id = (int)$_GET['category_id'];
            $sql = "SELECT * FROM tasks WHERE category_id = $category_id ORDER BY name";
            $result = $conn->query($sql);
            
            $tasks = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $tasks[] = $row;
                }
            }
            
            echo json_encode($tasks);
        } else {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'Action non valide']);
        }
        break;
        
    case 'POST':
        // Ajouter une nouvelle tâche
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'Données invalides']);
            break;
        }
        
        // Valider les données
        if (empty($data['name'])) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'Le nom de la tâche est obligatoire']);
            break;
        }
        
        // Préparer les données pour l'insertion
        $name = sanitize($conn, $data['name']);
        $description = sanitize($conn, $data['description'] ?? '');
        $estimated_duration = isset($data['estimated_duration']) ? (int)$data['estimated_duration'] : 0;
        $frequency = sanitize($conn, $data['frequency'] ?? 'monthly');
        $frequency_value = isset($data['frequency_value']) ? (int)$data['frequency_value'] : 1;
        $frequency_unit = sanitize($conn, $data['frequency_unit'] ?? null);
        $category_id = isset($data['category_id']) && $data['category_id'] ? (int)$data['category_id'] : 'NULL';
        
        // Insérer la tâche
        $sql = "INSERT INTO tasks (name, description, estimated_duration, frequency, frequency_value, frequency_unit, category_id) 
                VALUES ('$name', '$description', $estimated_duration, '$frequency', $frequency_value, " . 
                ($frequency_unit ? "'$frequency_unit'" : "NULL") . ", " .
                ($category_id != 'NULL' ? "$category_id" : "NULL") . ")";
        
        if ($conn->query($sql) === TRUE) {
            $task_id = $conn->insert_id;
            
            echo json_encode([
                'success' => true,
                'message' => 'Tâche ajoutée avec succès',
                'id' => $task_id
            ]);
        } else {
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(['error' => 'Erreur lors de l\'ajout de la tâche: ' . $conn->error]);
        }
        break;
        
    case 'PUT':
        // Modifier une tâche existante
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['id'])) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'Données invalides ou ID manquant']);
            break;
        }
        
        $id = (int)$data['id'];
        
        // Valider les données
        if (empty($data['name'])) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'Le nom de la tâche est obligatoire']);
            break;
        }
        
        // Préparer les données pour la mise à jour
        $name = sanitize($conn, $data['name']);
        $description = sanitize($conn, $data['description'] ?? '');
        $estimated_duration = isset($data['estimated_duration']) ? (int)$data['estimated_duration'] : 0;
        $frequency = sanitize($conn, $data['frequency'] ?? 'monthly');
        $frequency_value = isset($data['frequency_value']) ? (int)$data['frequency_value'] : 1;
        $frequency_unit = sanitize($conn, $data['frequency_unit'] ?? null);
        $category_id = isset($data['category_id']) && $data['category_id'] ? (int)$data['category_id'] : 'NULL';
        
        // Vérifier si la tâche existe
        $check_sql = "SELECT id FROM tasks WHERE id = $id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows == 0) {
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['error' => 'Tâche non trouvée']);
            break;
        }
        
        // Mettre à jour la tâche
        $sql = "UPDATE tasks SET 
                name = '$name', 
                description = '$description', 
                estimated_duration = $estimated_duration, 
                frequency = '$frequency', 
                frequency_value = $frequency_value, 
                frequency_unit = " . ($frequency_unit ? "'$frequency_unit'" : "NULL") . ", 
                category_id = " . ($category_id != 'NULL' ? "$category_id" : "NULL") . " 
                WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode([
                'success' => true,
                'message' => 'Tâche mise à jour avec succès'
            ]);
        } else {
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(['error' => 'Erreur lors de la mise à jour de la tâche: ' . $conn->error]);
        }
        break;
        
    case 'DELETE':
        // Supprimer une tâche
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            
            // Vérifier si la tâche existe
            $check_sql = "SELECT id FROM tasks WHERE id = $id";
            $check_result = $conn->query($check_sql);
            
            if ($check_result->num_rows == 0) {
                header('HTTP/1.0 404 Not Found');
                echo json_encode(['error' => 'Tâche non trouvée']);
                break;
            }
            
            // Vérifier si la tâche est utilisée dans des planifications
            $check_schedules = "SELECT id FROM schedules WHERE task_id = $id LIMIT 1";
            $schedules_result = $conn->query($check_schedules);
            
            if ($schedules_result->num_rows > 0) {
                header('HTTP/1.0 400 Bad Request');
                echo json_encode(['error' => 'Cette tâche est associée à des planifications et ne peut pas être supprimée']);
                break;
            }
            
            // Supprimer la tâche
            $sql = "DELETE FROM tasks WHERE id = $id";
            
            if ($conn->query($sql) === TRUE) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Tâche supprimée avec succès'
                ]);
            } else {
                header('HTTP/1.0 500 Internal Server Error');
                echo json_encode(['error' => 'Erreur lors de la suppression de la tâche: ' . $conn->error]);
            }
