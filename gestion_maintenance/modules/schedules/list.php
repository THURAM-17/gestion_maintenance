<?php
// Page de liste des planifications de maintenance
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

// Période sélectionnée (mois/année)
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Vérifier que le mois est valide
if ($currentMonth < 1 || $currentMonth > 12) {
    $currentMonth = date('n');
}

// Récupérer les planifications pour le mois sélectionné
$sql = "SELECT si.*, s.name as schedule_name, e.name as equipment_name, 
        t.name as task_name, u.username as assigned_to_username 
        FROM schedule_items si 
        LEFT JOIN schedules s ON si.schedule_id = s.id 
        LEFT JOIN equipments e ON si.equipment_id = e.id 
        LEFT JOIN tasks t ON si.task_id = t.id 
        LEFT JOIN users u ON si.assigned_to = u.id 
        WHERE (si.planned_month = $currentMonth AND YEAR(si.planned_date) = $currentYear) 
           OR (si.planned_date IS NOT NULL AND MONTH(si.planned_date) = $currentMonth AND YEAR(si.planned_date) = $currentYear)
        ORDER BY si.planned_date, si.id";
$result = $conn->query($sql);

// Récupérer les planifications
$schedules = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}

// Fermer la connexion
$conn->close();

// Traitement des messages de session
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';

// Supprimer les messages de session après les avoir récupérés
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Planifications de maintenance</h1>
        <a href="add.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i> Créer une planification
        </a>
    </div>
    
    <?php if (!empty($success)): ?>
        <div class="p-4 mb-4 text-green-700 bg-green-100 border-l-4 border-green-500 alert-fade" role="alert">
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="p-4 mb-4 text-red-700 bg-red-100 border-l-4 border-red-500 alert-fade" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Sélecteur de mois/année -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form action="" method="get" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Mois</label>
                <select id="month" name="month" class="px-3 py-2 border border-gray-300 rounded-md">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $currentMonth == $m ? 'selected' : ''; ?>>
                            <?php echo getMonthName($m); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div>
                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Année</label>
                <select id="year" name="year" class="px-3 py-2 border border-gray-300 rounded-md">
                    <?php 
                    $startYear = date('Y') - 2;
                    $endYear = date('Y') + 5;
                    for ($y = $startYear; $y <= $endYear; $y++):
                    ?>
                        <option value="<?php echo $y; ?>" <?php echo $currentYear == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-filter mr-2"></i> Filtrer
            </button>
        </form>
    </div>
    
    <!-- Liste des planifications -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (count($schedules) > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Planification</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Équipement</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tâche</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date prévue</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigné à</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['schedule_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($schedule['equipment_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($schedule['task_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo $schedule['planned_date'] ? formatDateFr($schedule['planned_date']) : '-'; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusClass($schedule['status']); ?>">
                                    <?php echo getScheduleItemStatusLabel($schedule['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($schedule['assigned_to_username'] ?: 'Non assigné'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="edit_item.php?id=<?php echo $schedule['id']; ?>" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="complete_item.php?id=<?php echo $schedule['id']; ?>" class="text-green-600 hover:text-green-900 mr-3" title="Marquer comme terminé">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="delete_item.php?id=<?php echo $schedule['id']; ?>" class="text-red-600 hover:text-red-900 delete-btn" 
                                   data-confirm="Êtes-vous sûr de vouloir supprimer cette planification ?">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="p-6 text-center">
                <p class="text-gray-500">Aucune planification trouvée pour <?php echo getMonthName($currentMonth) . ' ' . $currentYear; ?>.</p>
                <p class="mt-2">
                    <a href="add.php" class="text-blue-600 hover:underline">
                        <i class="fas fa-plus-circle mr-1"></i> Créer une nouvelle planification
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Calendrier mensuel -->
    <div class="mt-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Calendrier - <?php echo getMonthName($currentMonth) . ' ' . $currentYear; ?></h2>
        <div class="bg-white rounded-lg shadow p-6">
            <div id="calendar-container"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Convertir les planifications PHP en format utilisable par JavaScript
        const schedules = <?php echo json_encode($schedules); ?>;
        
        // Formater les événements pour le calendrier
        const events = schedules.map(schedule => {
            // Déterminer la classe CSS en fonction du statut
            let className = '';
            switch (schedule.status) {
                case 'pending':
                    className = 'bg-blue-100 text-blue-800';
                    break;
                case 'completed':
                    className = 'bg-green-100 text-green-800';
                    break;
                case 'postponed':
                    className = 'bg-yellow-100 text-yellow-800';
                    break;
                case 'cancelled':
                    className = 'bg-red-100 text-red-800';
                    break;
                default:
                    className = 'bg-gray-100 text-gray-800';
            }
            
            return {
                id: schedule.id,
                title: schedule.task_name + ' - ' + schedule.equipment_name,
                date: schedule.planned_date,
                className: className
            };
        });
        
        // Générer le calendrier
        generateCalendar('calendar-container', <?php echo $currentYear; ?>, <?php echo $currentMonth; ?>, events);
    });
    
    // Fonction pour afficher les détails d'un événement
    function showEventDetails(event) {
        alert(`Tâche: ${event.title}\nDate: ${event.date ? new Date(event.date).toLocaleDateString('fr-FR') : 'Non spécifiée'}`);
    }
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>
