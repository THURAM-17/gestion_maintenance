<?php
// Page de détails d'un équipement
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Inclure l'en-tête
include_once '../../includes/header.php';

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

// Récupérer les informations de l'équipement
$sql = "SELECT e.*, c.name as category_name 
        FROM equipments e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE e.id = $id";
$result = $conn->query($sql);

if ($result->num_rows !== 1) {
    // L'équipement n'existe pas
    $conn->close();
    header('Location: list.php');
    exit;
}

$equipment = $result->fetch_assoc();

// Récupérer l'historique de l'équipement
$history_sql = "SELECT h.*, u.username as performed_by_username 
                FROM equipment_history h 
                LEFT JOIN users u ON h.performed_by = u.id 
                WHERE h.equipment_id = $id 
                ORDER BY h.performed_date DESC";
$history_result = $conn->query($history_sql);

$history = [];
if ($history_result->num_rows > 0) {
    while ($row = $history_result->fetch_assoc()) {
        $history[] = $row;
    }
}

// Récupérer les maintenances planifiées pour cet équipement
$maintenance_sql = "SELECT si.*, s.name as schedule_name, t.name as task_name, 
                   u.username as assigned_to_username 
                   FROM schedule_items si 
                   LEFT JOIN schedules s ON si.schedule_id = s.id 
                   LEFT JOIN tasks t ON si.task_id = t.id 
                   LEFT JOIN users u ON si.assigned_to = u.id 
                   WHERE si.equipment_id = $id 
                   ORDER BY si.planned_date DESC";
$maintenance_result = $conn->query($maintenance_sql);

$maintenances = [];
if ($maintenance_result->num_rows > 0) {
    while ($row = $maintenance_result->fetch_assoc()) {
        $maintenances[] = $row;
    }
}

// Fermer la connexion
$conn->close();
?>

<div class="py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Détails de l'équipement</h1>
        <div class="flex space-x-2">
            <a href="edit.php?id=<?php echo $id; ?>" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                <i class="fas fa-edit mr-2"></i> Modifier
            </a>
            <a href="list.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                <i class="fas fa-arrow-left mr-2"></i> Retour à la liste
            </a>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="p-4 mb-4 text-green-700 bg-green-100 border-l-4 border-green-500 alert-fade" role="alert">
            <p><?php echo $_SESSION['success']; ?></p>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="p-4 mb-4 text-red-700 bg-red-100 border-l-4 border-red-500 alert-fade" role="alert">
            <p><?php echo $_SESSION['error']; ?></p>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <!-- Informations de l'équipement -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-800"><?php echo htmlspecialchars($equipment['name']); ?></h2>
                <span class="px-3 py-1 inline-flex text-sm font-medium rounded-full <?php echo getStatusClass($equipment['status']); ?>">
                    <?php echo getEquipmentStatusLabel($equipment['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Numéro de série</h3>
                    <p class="text-gray-800"><?php echo htmlspecialchars($equipment['serial_number']) ?: 'Non spécifié'; ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Modèle</h3>
                    <p class="text-gray-800"><?php echo htmlspecialchars($equipment['model']) ?: 'Non spécifié'; ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Fabricant</h3>
                    <p class="text-gray-800"><?php echo htmlspecialchars($equipment['manufacturer']) ?: 'Non spécifié'; ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Catégorie</h3>
                    <p class="text-gray-800"><?php echo htmlspecialchars($equipment['category_name']) ?: 'Non catégorisé'; ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Date d'acquisition</h3>
                    <p class="text-gray-800"><?php echo $equipment['acquisition_date'] ? formatDateFr($equipment['acquisition_date']) : 'Non spécifiée'; ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Emplacement</h3>
                    <p class="text-gray-800"><?php echo htmlspecialchars($equipment['location']) ?: 'Non spécifié'; ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Dernière maintenance</h3>
                    <p class="text-gray-800"><?php echo $equipment['last_maintenance_date'] ? formatDateFr($equipment['last_maintenance_date']) : 'Aucune'; ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Prochaine maintenance</h3>
                    <p class="text-gray-800"><?php echo $equipment['next_maintenance_date'] ? formatDateFr($equipment['next_maintenance_date']) : 'Non planifiée'; ?></p>
                </div>
            </div>
            
            <?php if ($equipment['notes']): ?>
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Notes</h3>
                    <div class="p-4 bg-gray-50 rounded">
                        <p class="text-gray-800 whitespace-pre-line"><?php echo htmlspecialchars($equipment['notes']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Onglets pour l'historique et les maintenances -->
    <div class="bg-white rounded-lg shadow">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button class="tab-link px-6 py-3 border-b-2 border-blue-500 text-blue-500 bg-white" data-tab="history-tab">
                    Historique
                </button>
                <button class="tab-link px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 bg-white" data-tab="maintenance-tab">
                    Maintenances planifiées
                </button>
            </nav>
        </div>
        
        <!-- Onglet Historique -->
        <div id="history-tab" class="tab-content p-6 active">
            <?php if (count($history) > 0): ?>
                <div class="flow-root">
                    <ul class="timeline">
                        <?php foreach ($history as $entry): ?>
                            <li class="timeline-item mb-6">
                                <div class="flex items-center">
                                    <div class="flex justify-center items-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 mr-4">
                                        <?php if ($entry['action_type'] == 'added'): ?>
                                            <i class="fas fa-plus-circle"></i>
                                        <?php elseif ($entry['action_type'] == 'updated'): ?>
                                            <i class="fas fa-edit"></i>
                                        <?php elseif ($entry['action_type'] == 'maintenance'): ?>
                                            <i class="fas fa-tools"></i>
                                        <?php elseif ($entry['action_type'] == 'status_change'): ?>
                                            <i class="fas fa-exchange-alt"></i>
                                        <?php elseif ($entry['action_type'] == 'repaired'): ?>
                                            <i class="fas fa-wrench"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($entry['description']); ?></div>
                                        <div class="text-sm text-gray-500">
                                            Le <?php echo formatDateTimeFr($entry['performed_date']); ?> par
                                            <?php echo htmlspecialchars($entry['performed_by_username'] ?: 'Utilisateur inconnu'); ?>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center">Aucun historique disponible pour cet équipement.</p>
            <?php endif; ?>
        </div>
        
        <!-- Onglet Maintenances planifiées -->
        <div id="maintenance-tab" class="tab-content p-6" style="display: none;">
            <?php if (count($maintenances) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tâche</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Planification</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date prévue</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">État</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigné à</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($maintenances as $maintenance): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($maintenance['task_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($maintenance['schedule_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo $maintenance['planned_date'] ? formatDateFr($maintenance['planned_date']) : 'Non spécifiée'; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusClass($maintenance['status']); ?>">
                                            <?php echo getScheduleItemStatusLabel($maintenance['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($maintenance['assigned_to_username'] ?: 'Non assigné'); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center">Aucune maintenance planifiée pour cet équipement.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Initialisation des onglets
    document.addEventListener('DOMContentLoaded', function() {
        const tabLinks = document.querySelectorAll('.tab-link');
        
        tabLinks.forEach(link => {
            link.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Désactive tous les onglets
                document.querySelectorAll('.tab-link').forEach(tab => {
                    tab.classList.remove('border-blue-500', 'text-blue-500');
                    tab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700');
                });
                
                // Masque tout le contenu des onglets
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                });
                
                // Active l'onglet sélectionné
                this.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700');
                this.classList.add('border-blue-500', 'text-blue-500');
                
                // Affiche le contenu de l'onglet sélectionné
                document.getElementById(tabId).style.display = 'block';
            });
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>
