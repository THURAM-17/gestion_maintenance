<?php
/**
 * API pour la gestion des équipements
 * Permet de récupérer, ajouter, modifier et supprimer des équipements
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
        // Récupération des équipements
        if ($action == 'list') {
            // Récupérer tous les équipements avec les noms de catégories
            $sql = "SELECT e.*, c.name as category_name 
                    FROM equipments e 
                    LEFT JOIN categories c ON e.category_id = c.id 
                    ORDER BY e.name";
            $result = $conn->query($sql);
            
            $equipments = [];
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $equipments[] = $row;
                }
            }
            
            echo json_encode($equipments);
        } elseif ($action == 'get' && isset($_GET['id'])) {
            // Récupérer un équipement spécifique
            $id = (int)$_GET['id'];
            $sql = "SELECT e.*, c.name as category_name 
                    FROM equipments e 
                    LEFT JOIN categories c ON e.category_id = c.id 
                    WHERE e.id = $id";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $equipment = $result->fetch_assoc();
                echo json_encode($equipment);
            } else {
                header('HTTP/1.0 404 Not Found');
                echo json_encode(['error' => 'Équipement non trouvé']);
            }
        } else {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'Action non valide']);
        }
        break;
        
    case 'POST':
        // Ajouter un nouvel équipement
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'Données invalides']);
            break;
        }
        
        // Valider les données
        if (empty($data['name'])) {
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(['error' => 'Le nom de l\'équipement est obligatoire']);
            break;
        }
        
        // Préparer les données pour l'insertion
        $name = sanitize($conn, $data['name']);
        $serial_number = sanitize($conn, $data['serial_number'] ?? '');
        $model = sanitize($conn, $data['model'] ?? '');
        $manufacturer = sanitize($conn, $data['manufacturer'] ?? '');
        $acquisition_date = sanitize($conn, $data['acquisition_date'] ?? null);
        $category_id = isset($data['category_id']) && $data['category_id'] ? (int)$data['category_id'] : 'NULL';
        $location = sanitize($conn, $data['location'] ?? '');
        $status = sanitize($conn, $data['status'] ?? 'operational');
        $notes = sanitize($conn, $data['notes'] ?? '');
        
        // Insérer l'équipement
        $sql = "INSERT INTO equipments (name, serial_number, model, manufacturer, acquisition_date, 
                category_id, location, status, notes) 
                VALUES ('$name', '$serial_number', '$model', '$manufacturer', " . 
                ($acquisition_date ? "'$acquisition_date'" : "NULL") . ", " .
                ($category_id != 'NULL' ? "$category_id" : "NULL") . ", " .
                "'$location', '$status', '$notes')";
        
        if ($conn->query($sql) === TRUE) {
            $equipment_id = $conn->insert_id;
            
            // Ajouter dans l'historique
            $user_id = $_SESSION['user_id'];
            $sql = "INSERT INTO equipment_history (equipment_id, action_type, description, performed_by, performed_date) 
                    VALUES ($equipment_id, 'added', 'Nouvel équipement ajouté', $user_id, NOW())";
            $conn->query($sql);
            
            echo json_encode([
                'success' => true,
                'message' => 'Équipement ajouté avec succès',
                'id' => $equipment_id
            ]);
        } else {
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(['error' => 'Erreur lors de l\'ajout de l\'équipement: ' . $conn->error]);
        }
        break;
        
    case 'PUT':
        // Modifier un équipement existant
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
            echo json_encode(['error' => 'Le nom de l\'équipement est obligatoire']);
            break;
        }
        
        // Préparer les données pour la mise à jour
        $name = sanitize($conn, $data['name']);
        $serial_number = sanitize($conn, $data['serial_number'] ?? '');
        $model = sanitize($conn, $data['model'] ?? '');
        $manufacturer = sanitize($conn, $data['manufacturer'] ?? '');
        $acquisition_date = sanitize($conn, $data['acquisition_date'] ?? null);
        $category_id = isset($data['category_id']) && $data['category_id'] ? (int)$data['category_id'] : 'NULL';
        $location = sanitize($conn, $data['location'] ?? '');
        $status = sanitize($conn, $data['status'] ?? 'operational');
        $last_maintenance_date = sanitize($conn, $data['last_maintenance_date'] ?? null);
        $next_maintenance_date = sanitize($conn, $data['next_maintenance_date'] ?? null);
        $notes = sanitize($conn, $data['notes'] ?? '');
        
        // Vérifier si l'équipement existe
        $check_sql = "SELECT id FROM equipments WHERE id = $id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows == 0) {
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['error' => 'Équipement non trouvé']);
            break;
        }
        
        // Mettre à jour l'équipement
        $sql = "UPDATE equipments SET 
                name = '$name', 
                serial_number = '$serial_number', 
                model = '$model', 
                manufacturer = '$manufacturer', 
                acquisition_date = " . ($acquisition_date ? "'$acquisition_date'" : "NULL") . ", 
                category_id = " . ($category_id != 'NULL' ? "$category_id" : "NULL") . ", 
                location = '$location', 
                status = '$status', 
                last_maintenance_date = " . ($last_maintenance_date ? "'$last_maintenance_date'" : "NULL") . ", 
                next_maintenance_date = " . ($next_maintenance_date ? "'$next_maintenance_date'" : "NULL") . ", 
                notes = '$notes' 
                WHERE id = $id";
        
        if ($conn->query($sql) === TRUE) {
            // Ajouter dans l'historique
            $user_id = $_SESSION['user_id'];
            $sql = "INSERT INTO equipment_history (equipment_id, action_type, description, performed_by, performed_date) 
                    VALUES ($id, 'updated', 'Équipement mis à jour', $user_id, NOW())";
            $conn->query($sql);
            
            echo json_encode([
                'success' => true,
                'message' => 'Équipement mis à jour avec succès'
            ]);
        } else {
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(['error' => 'Erreur lors de la mise à jour de l\'équipement: ' . $conn->error]);
        }
        break;
        
    case 'DELETE':
        // Supprimer un équipement
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            
            // Vérifier si l'équipement existe
            $check_sql = "SELECT id FROM equipments WHERE id = $id";
            $check_result = $conn->query($check_sql);
            
            if ($check_result->num_rows == 0) {
                header('HTTP/1.0 404 Not Found');
                echo json_encode(['error' => 'Équipement non trouvé']);
                break;
            }
            
            // Supprimer l'équipement (les contraintes de clé étrangère s'occuperont des entrées associées)
            $sql = "DELETE FROM equipments WHERE id = $id";
            
            if ($conn->query($sql) === TRUE) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Équipement supprimé avec succès'
                ]);
            } else {
                header('HTTP/1.0 500 Internal Server Error');
                echo json_encode(['error' => 'Erreur lors de la suppression de l\'équipement: ' . $conn->error]);
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
