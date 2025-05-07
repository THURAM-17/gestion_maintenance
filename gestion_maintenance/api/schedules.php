<?php
/**
 * API pour la gestion des planifications de maintenance
 * Permet de récupérer, ajouter, modifier et supprimer des planifications
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
        // Récupération des planifications
        if ($action == 'list') {
            // Récupérer toutes les planifications
            $sql = "SELECT s.*, u.username as created_by_username 
                    FROM schedules s 
                    LEFT JOIN users u ON s.created_by = u.id 
                    ORDER BY s.created_at DESC";
            $result = $conn->query($sql);
            
            $schedules = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $schedules[] = $row;
                }
            }
            
            echo json_encode($schedules);
        } elseif ($action == 'get' && isset($_GET['id'])) {
            // Récupérer une planification spécifique
            $id = (int)$_GET['id'];
            $sql = "SELECT s.*, u.username as created_by_username 
                    FROM schedules s 
                    LEFT JOIN users u ON s.created_by = u.id 
                    WHERE s.id = $id";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $schedule = $result->fetch_assoc();
                
                // Récupérer les éléments de la planification
                $items_sql = "SELECT si.*, e.name as equipment_name, t.name as task_name, 
                              u.username as assigned_to_username 
                              FROM schedule_items si 
                              LEFT JOIN equipments e ON si.equipment_id = e.id 
                              LEFT JOIN tasks t ON si.task_id = t.id 
                              LEFT JOIN users u ON si.assigned_to = u.id 
                              WHERE si.schedule_id = $id";
                $items_result = $conn->query($items_sql);
                
                $items = [];
                if ($items_result->num_rows > 0) {
                    while ($item = $items_result->fetch_assoc()) {
                        $items[] = $item;
                    }
                }
                
                $schedule['items'] = $items;
                echo json_encode($schedule);
            } else {
                header('HTTP/1.0 404 Not Found');
                echo json_encode(['error' => 'Planification non trouvée']);
            }
        } elseif ($action == 'monthly' && isset($_GET['month']) && isset($_GET['year'])) {
            // Récupérer les planifications pour un mois spécifique
            $month = (int)$_GET['month'];
            $year = (int)$_GET['year'];
            
            $sql = "SELECT si.*, s.name as schedule_name, e.name as equipment_name, 
                    t.name as task_name, u.username as assigned_to_username 
                    FROM schedule_items si 
                    LEFT JOIN schedules s ON si.schedule_id = s.id 
                    LEFT JOIN equipments e ON si.equipment_id = e.id 
                    LEFT JOIN tasks t ON si.task_id = t.id 
                    LEFT JOIN users u ON si.assigned_to = u.id 
                    WHERE si.planned_month = $month 
                    AND (YEAR(si.planned_date) = $year OR si.planned_date IS NULL)";
            $result = $conn->query($sql);
            
            $items = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $items[] = $row;
                }
            }
            
            echo json_encode($items);
        } else {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'Action non valide']);
        }
        break;
        
    case 'POST':
        // Ajouter une nouvelle planification
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'Données invalides']);
            break;
        }
        
        // Valider les données
        if (empty($data['name'])) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'Le nom de la planification est obligatoire']);
            break;
        }
        
        // Préparer les données pour l'insertion
        $name = sanitize($conn, $data['name']);
        $description = sanitize($conn, $data['description'] ?? '');
        $created_by = $_SESSION['user_id'];
        
        // Commencer une transaction
        $conn->begin_transaction();
        
        try {
            // Insérer la planification
            $sql = "INSERT INTO schedules (name, description, created_by) 
                    VALUES ('$name', '$description', $created_by)";
            
            if ($conn->query($sql) !== TRUE) {
                throw new Exception('Erreur lors de l\'ajout de la planification: ' . $conn->error);
            }
            
            $schedule_id = $conn->insert_id;
            
            // Insérer les éléments de la planification
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (empty($item['equipment_id']) || empty($item['task_id'])) {
                        continue;
                    }
                    
                    $equipment_id = (int)$item['equipment_id'];
                    $task_id = (int)$item['task_id'];
                    $planned_date = sanitize($conn, $item['planned_date'] ?? null);
                    $planned_week = isset($item['planned_week']) ? (int)$item['planned_week'] : 'NULL';
                    $planned_month = isset($item['planned_month']) ? (int)$item['planned_month'] : 'NULL';
                    $status = sanitize($conn, $item['status'] ?? 'pending');
                    $assigned_to = isset($item['assigned_to']) && $item['assigned_to'] ? (int)$item['assigned_to'] : 'NULL';
                    $notes = sanitize($conn, $item['notes'] ?? '');
                    
                    $item_sql = "INSERT INTO schedule_items (schedule_id, equipment_id, task_id, 
                                planned_date, planned_week, planned_month, status, assigned_to, notes) 
                                VALUES ($schedule_id, $equipment_id, $task_id, " . 
                                ($planned_date ? "'$planned_date'" : "NULL") . ", " .
                                ($planned_week != 'NULL' ? "$planned_week" : "NULL") . ", " .
                                ($planned_month != 'NULL' ? "$planned_month" : "NULL") . ", " .
                                "'$status', " .
                                ($assigned_to != 'NULL' ? "$assigned_to" : "NULL") . ", " .
                                "'$notes')";
                    
                    if ($conn->query($item_sql) !== TRUE) {
                        throw new Exception('Erreur lors de l\'ajout d\'un élément de planification: ' . $conn->error);
                    }
                }
            }
            
            // Valider la transaction
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Planification ajoutée avec succès',
                'id' => $schedule_id
            ]);
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $conn->rollback();
            
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Modifier une planification existante
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
            echo json_encode(['error' => 'Le nom de la planification est obligatoire']);
            break;
        }
        
        // Préparer les données pour la mise à jour
        $name = sanitize($conn, $data['name']);
        $description = sanitize($conn, $data['description'] ?? '');
        
        // Vérifier si la planification existe
        $check_sql = "SELECT id FROM schedules WHERE id = $id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows == 0) {
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['error' => 'Planification non trouvée']);
            break;
        }
        
        // Commencer une transaction
        $conn->begin_transaction();
        
        try {
            // Mettre à jour la planification
            $sql = "UPDATE schedules SET 
                    name = '$name', 
                    description = '$description' 
                    WHERE id = $id";
            
            if ($conn->query($sql) !== TRUE) {
                throw new Exception('Erreur lors de la mise à jour de la planification: ' . $conn->error);
            }
            
            // Mettre à jour les éléments de la planification
            if (isset($data['items']) && is_array($data['items'])) {
                // Supprimer les éléments existants qui ne sont pas dans la nouvelle liste
                if (isset($data['delete_existing']) && $data['delete_existing']) {
                    $delete_sql = "DELETE FROM schedule_items WHERE schedule_id = $id";
                    if ($conn->query($delete_sql) !== TRUE) {
                        throw new Exception('Erreur lors de la suppression des éléments existants: ' . $conn->error);
                    }
                }
                
                foreach ($data['items'] as $item) {
                    $item_id = isset($item['id']) ? (int)$item['id'] : 0;
                    $equipment_id = (int)$item['equipment_id'];
                    $task_id = (int)$item['task_id'];
                    $planned_date = sanitize($conn, $item['planned_date'] ?? null);
                    $planned_week = isset($item['planned_week']) ? (int)$item['planned_week'] : 'NULL';
                    $planned_month = isset($item['planned_month']) ? (int)$item['planned_month'] : 'NULL';
                    $status = sanitize($conn, $item['status'] ?? 'pending');
                    $assigned_to = isset($item['assigned_to']) && $item['assigned_to'] ? (int)$item['assigned_to'] : 'NULL';
                    $notes = sanitize($conn, $item['notes'] ?? '');
                    
                    if ($item_id > 0) {
                        // Mettre à jour un élément existant
                        $item_sql = "UPDATE schedule_items SET 
                                    equipment_id = $equipment_id, 
                                    task_id = $task_id, 
                                    planned_date = " . ($planned_date ? "'$planned_date'" : "NULL") . ", 
                                    planned_week = " . ($planned_week != 'NULL' ? "$planned_week" : "NULL") . ", 
                                    planned_month = " . ($planned_month != 'NULL' ? "$planned_month" : "NULL") . ", 
                                    status = '$status', 
                                    assigned_to = " . ($assigned_to != 'NULL' ? "$assigned_to" : "NULL") . ", 
                                    notes = '$notes' 
                                    WHERE id = $item_id AND schedule_id = $id";
                    } else {
                        // Ajouter un nouvel élément
                        $item_sql = "INSERT INTO schedule_items (schedule_id, equipment_id, task_id, 
                                    planned_date, planned_week, planned_month, status, assigned_to, notes) 
                                    VALUES ($id, $equipment_id, $task_id, " . 
                                    ($planned_date ? "'$planned_date'" : "NULL") . ", " .
                                    ($planned_week != 'NULL' ? "$planned_week" : "NULL") . ", " .
                                    ($planned_month != 'NULL' ? "$planned_month" : "NULL") . ", " .
                                    "'$status', " .
                                    ($assigned_to != 'NULL' ? "$assigned_to" : "NULL") . ", " .
                                    "'$notes')";
                    }
                    
                    if ($conn->query($item_sql) !== TRUE) {
                        throw new Exception('Erreur lors de la mise à jour d\'un élément de planification: ' . $conn->error);
                    }
                }
            }
            
            // Valider la transaction
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Planification mise à jour avec succès'
            ]);
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $conn->rollback();
            
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Supprimer une planification
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            
            // Vérifier si la planification existe
            $check_sql = "SELECT id FROM schedules WHERE id = $id";
            $check_result = $conn->query($check_sql);
            
            if ($check_result->num_rows == 0) {
                header('HTTP/1.0 404 Not Found');
                echo json_encode(['error' => 'Planification non trouvée']);
                break;
            }
            
            // Commencer une transaction
            $conn->begin_transaction();
            
            try {
                // Supprimer les éléments de la planification
                $delete_items_sql = "DELETE FROM schedule_items WHERE schedule_id = $id";
                if ($conn->query($delete_items_sql) !== TRUE) {
                    throw new Exception('Erreur lors de la suppression des éléments de la planification: ' . $conn->error);
                }
                
                // Supprimer la planification
                $delete_sql = "DELETE FROM schedules WHERE id = $id";
                if ($conn->query($delete_sql) !== TRUE) {
                    throw new Exception('Erreur lors de la suppression de la planification: ' . $conn->error);
                }
                
                // Valider la transaction
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Planification supprimée avec succès'
                ]);
            } catch (Exception $e) {
                // Annuler la transaction en cas d'erreur
                $conn->rollback();
                
                header('HTTP/1.0 500 Internal Server Error');
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'ID manquant']);
        }
        break;
        
    default:
        header('HTTP/1.0 405 Method Not Allowed');
        echo json_encode(['error' => 'Méthode non autorisée']);
        break;
}

// Fermer la connexion
$conn->close();
?>
