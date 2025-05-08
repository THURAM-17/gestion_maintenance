<?php
// Page d'ajout d'une planification de maintenance
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Inclure l'en-tête
include_once '../../includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: /gestion_maintenance/modules/auth/login.php');
    exit;
}

// Connexion à la base de données
$conn = getConnection();

// Récupérer les équipements pour le formulaire
$equipments_sql = "SELECT id, name, category_id FROM equipments ORDER BY name";
$equipments_result = $conn->query($equipments_sql);
$equipments = [];
if ($equipments_result->num_rows > 0) {
    while ($row = $equipments_result->fetch_assoc()) {
        $equipments[] = $row;
    }
}

// Récupérer les tâches pour le formulaire
$tasks_sql = "SELECT id, name, category_id FROM tasks ORDER BY name";
$tasks_result = $conn->query($tasks_sql);
$tasks = [];
if ($tasks_result->num_rows > 0) {
    while ($row = $tasks_result->fetch_assoc()) {
        $tasks[] = $row;
    }
}

// Récupérer les utilisateurs pour l'assignation
$users_sql = "SELECT id, username, full_name FROM users ORDER BY username";
$users_result = $conn->query($users_sql);
$users = [];
if ($users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fermer la connexion (elle sera rouverte si le formulaire est soumis)
$conn->close();

// Traitement du formulaire d'ajout
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    
    // Valider les données
    if (empty($name)) {
        $error = 'Le nom de la planification est obligatoire';
    } else {
        // Connexion à la base de données
        $conn = getConnection();
        
        // Sécuriser les entrées
        $name = sanitize($conn, $name);
        $description = sanitize($conn, $description);
        $created_by = $_SESSION['user_id'];
        
        // Commencer une transaction
        $conn->begin_transaction();
        
        try {
            // Insérer la planification
            $sql = "INSERT INTO schedules (name, description, created_by) VALUES ('$name', '$description', $created_by)";
            
            if ($conn->query($sql) !== TRUE) {
                throw new Exception('Erreur lors de l\'ajout de la planification: ' . $conn->error);
            }
            
            $schedule_id = $conn->insert_id;
            
            // Traiter les éléments de planification
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (empty($item['equipment_id']) || empty($item['task_id'])) {
                        continue; // Ignorer les éléments incomplets
                    }
                    
                    $equipment_id = (int)$item['equipment_id'];
                    $task_id = (int)$item['task_id'];
                    $planned_date = sanitize($conn, $item['planned_date'] ?? '');
                    $planned_month = !empty($item['planned_month']) ? (int)$item['planned_month'] : NULL;
                    $status = sanitize($conn, $item['status'] ?? 'pending');
                    $assigned_to = !empty($item['assigned_to']) ? (int)$item['assigned_to'] : NULL;
                    $notes = sanitize($conn, $item['notes'] ?? '');
                    
                    // Insérer l'élément de planification
                    $item_sql = "INSERT INTO schedule_items (schedule_id, equipment_id, task_id, planned_date, planned_month, status, assigned_to, notes) 
                                VALUES ($schedule_id, $equipment_id, $task_id, " . 
                                (!empty($planned_date) ? "'$planned_date'" : "NULL") . ", " .
                                ($planned_month !== NULL ? "$planned_month" : "NULL") . ", " .
                                "'$status', " .
                                ($assigned_to !== NULL ? "$assigned_to" : "NULL") . ", " .
                                "'$notes')";
                    
                    if ($conn->query($item_sql) !== TRUE) {
                        throw new Exception('Erreur lors de l\'ajout d\'un élément de planification: ' . $conn->error);
                    }
                    
                    // Mettre à jour la date de prochaine maintenance de l'équipement si nécessaire
                    if (!empty($planned_date)) {
                        $update_equipment_sql = "UPDATE equipments SET next_maintenance_date = '$planned_date' 
                                               WHERE id = $equipment_id 
                                               AND (next_maintenance_date IS NULL OR next_maintenance_date > '$planned_date')";
                        $conn->query($update_equipment_sql);
                    }
                }
            }
            
            // Valider la transaction
            $conn->commit();
            
            $_SESSION['success'] = 'Planification ajoutée avec succès';
            
            // Redirection
            header('Location: list.php');
            exit;
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $conn->rollback();
            $error = $e->getMessage();
        }
        
        // Fermer la connexion
        $conn->close();
    }
}
?>

<div class="py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Créer une planification de maintenance</h1>
        <a href="list.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
            <i class="fas fa-arrow-left mr-2"></i> Retour à la liste
        </a>
    </div>
    
    <?php if (!empty($success)): ?>
        <div class="p-4 mb-4 text-green-700 bg-green-100 border-l-4 border-green-500 alert-fade" role="alert">
            <p><?php echo $success; ?></p>
            <p class="mt-2 text-sm">Redirection en cours...</p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="p-4 mb-4 text-red-700 bg-red-100 border-l-4 border-red-500" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Formulaire d'ajout -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="post" action="" id="scheduleForm">
            <!-- Informations générales -->
            <div class="mb-6">
                <h2 class="text-lg font-medium text-gray-700 mb-4">Informations générales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la planification *</label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Ex: Maintenance mensuelle - Mai 2025">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Description détaillée de la planification..."></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Éléments de planification -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-gray-700">Éléments de planification</h2>
                    <button type="button" id="addItemBtn" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                        <i class="fas fa-plus mr-2"></i> Ajouter un élément
                    </button>
                </div>
                
                <div id="scheduleItems" class="space-y-6">
                    <!-- Le template pour les éléments sera ajouté dynamiquement -->
                </div>
                
                <div id="noItemsMessage" class="p-4 text-center text-gray-500">
                    Aucun élément de planification ajouté. Cliquez sur "Ajouter un élément" pour commencer.
                </div>
            </div>
            
            <!-- Boutons de soumission -->
            <div class="flex justify-end space-x-3">
                <a href="list.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                    Annuler
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Template pour un élément de planification -->
<template id="itemTemplate">
    <div class="schedule-item bg-gray-50 p-4 rounded-lg border border-gray-200">
        <div class="flex justify-between mb-4">
            <h3 class="text-md font-medium text-gray-700">Élément #<span class="item-number"></span></h3>
            <button type="button" class="remove-item-btn text-red-600 hover:text-red-900">
                <i class="fas fa-times"></i> Supprimer
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Équipement *</label>
                <select name="items[INDEX][equipment_id]" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Sélectionner un équipement</option>
                    <?php foreach ($equipments as $equipment): ?>
                        <option value="<?php echo $equipment['id']; ?>">
                            <?php echo htmlspecialchars($equipment['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tâche *</label>
                <select name="items[INDEX][task_id]" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Sélectionner une tâche</option>
                    <?php foreach ($tasks as $task): ?>
                        <option value="<?php echo $task['id']; ?>">
                            <?php echo htmlspecialchars($task['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date planifiée</label>
                <input type="date" name="items[INDEX][planned_date]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mois (si date non spécifiée)</label>
                <select name="items[INDEX][planned_month]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Sélectionner un mois</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>">
                            <?php echo getMonthName($m); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assigné à</label>
                <select name="items[INDEX][assigned_to]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Non assigné</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['username'] . ' (' . $user['full_name'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="items[INDEX][status]" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="pending">En attente</option>
                    <option value="completed">Terminée</option>
                    <option value="postponed">Reportée</option>
                    <option value="cancelled">Annulée</option>
                </select>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="items[INDEX][notes]" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"
                          placeholder="Notes supplémentaires sur cette tâche..."></textarea>
            </div>
        </div>
    </div>
</template>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const scheduleItems = document.getElementById('scheduleItems');
        const noItemsMessage = document.getElementById('noItemsMessage');
        const addItemBtn = document.getElementById('addItemBtn');
        const itemTemplate = document.getElementById('itemTemplate');
        
        let itemCount = 0;
        
        // Fonction pour ajouter un nouvel élément
        function addItem() {
            itemCount++;
            
            // Cloner le template
            const newItem = document.importNode(itemTemplate.content, true);
            
            // Mettre à jour le numéro de l'élément
            newItem.querySelector('.item-number').textContent = itemCount;
            
            // Mettre à jour les indices dans les noms des champs
            const fieldNames = newItem.querySelectorAll('[name*="INDEX"]');
            fieldNames.forEach(field => {
                field.name = field.name.replace('INDEX', itemCount - 1);
            });
            
            // Ajouter un gestionnaire d'événements pour le bouton de suppression
            const removeBtn = newItem.querySelector('.remove-item-btn');
            removeBtn.addEventListener('click', function() {
                this.closest('.schedule-item').remove();
                
                // Vérifier s'il reste des éléments
                if (scheduleItems.children.length === 0) {
                    noItemsMessage.style.display = 'block';
                }
            });
            
            // Ajouter l'élément au conteneur
            scheduleItems.appendChild(newItem);
            
            // Cacher le message "Aucun élément"
            noItemsMessage.style.display = 'none';
        }
        
        // Ajouter un gestionnaire d'événements pour le bouton d'ajout
        addItemBtn.addEventListener('click', addItem);
        
        // Ajouter un premier élément par défaut
        addItem();
        
        // Validation du formulaire
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            if (scheduleItems.children.length === 0) {
                e.preventDefault();
                alert('Veuillez ajouter au moins un élément de planification.');
            }
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>
