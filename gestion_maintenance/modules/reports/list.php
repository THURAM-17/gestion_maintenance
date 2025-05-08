<?php
// Page de liste des rapports de maintenance
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

// Appliquer les filtres si présents
$where_clause = "1=1"; // Toujours vrai pour pouvoir enchaîner les AND
if (isset($_GET['equipment']) && !empty($_GET['equipment'])) {
    $equipment_id = (int)$_GET['equipment'];
    $where_clause .= " AND mr.equipment_id = $equipment_id";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = sanitize($conn, $_GET['status']);
    $where_clause .= " AND mr.status = '$status'";
}

if (isset($_GET['date_start']) && !empty($_GET['date_start'])) {
    $date_start = sanitize($conn, $_GET['date_start']);
    $where_clause .= " AND mr.intervention_date >= '$date_start'";
}

if (isset($_GET['date_end']) && !empty($_GET['date_end'])) {
    $date_end = sanitize($conn, $_GET['date_end']);
    $where_clause .= " AND mr.intervention_date <= '$date_end'";
}

// Récupérer les rapports de maintenance
$sql = "SELECT mr.*, e.name as equipment_name, u.username as performed_by_username 
        FROM maintenance_reports mr 
        LEFT JOIN equipments e ON mr.equipment_id = e.id 
        LEFT JOIN users u ON mr.performed_by = u.id 
        WHERE $where_clause 
        ORDER BY mr.intervention_date DESC";
$result = $conn->query($sql);

// Récupérer les rapports
$reports = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}

// Récupérer tous les équipements pour le filtre
$equipments_sql = "SELECT id, name FROM equipments ORDER BY name";
$equipments_result = $conn->query($equipments_sql);
$equipments = [];
if ($equipments_result && $equipments_result->num_rows > 0) {
    while ($row = $equipments_result->fetch_assoc()) {
        $equipments[] = $row;
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
        <h1 class="text-2xl font-bold text-gray-800">Rapports de maintenance</h1>
        <a href="create.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i> Créer un rapport
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
    
    <!-- Filtres -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <h2 class="text-lg font-medium text-gray-700 mb-4">Filtres</h2>
        <form action="" method="get" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="equipment" class="block text-sm font-medium text-gray-700 mb-1">Équipement</label>
                <select name="equipment" id="equipment" class="px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Tous les équipements</option>
                    <?php foreach ($equipments as $equipment): ?>
                        <option value="<?php echo $equipment['id']; ?>" <?php echo isset($_GET['equipment']) && $_GET['equipment'] == $equipment['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($equipment['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="status" id="status" class="px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Tous les statuts</option>
                    <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] == 'completed' ? 'selected' : ''; ?>>Terminé</option>
                    <option value="incomplete" <?php echo isset($_GET['status']) && $_GET['status'] == 'incomplete' ? 'selected' : ''; ?>>Incomplet</option>
                    <option value="requires_followup" <?php echo isset($_GET['status']) && $_GET['status'] == 'requires_followup' ? 'selected' : ''; ?>>Nécessite un suivi</option>
                </select>
            </div>
            
            <div>
                <label for="date_start" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                <input type="date" name="date_start" id="date_start" class="px-3 py-2 border border-gray-300 rounded-md"
                       value="<?php echo isset($_GET['date_start']) ? htmlspecialchars($_GET['date_start']) : ''; ?>">
            </div>
            
            <div>
                <label for="date_end" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                <input type="date" name="date_end" id="date_end" class="px-3 py-2 border border-gray-300 rounded-md"
                       value="<?php echo isset($_GET['date_end']) ? htmlspecialchars($_GET['date_end']) : ''; ?>">
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-filter mr-2"></i> Filtrer
                </button>
                <a href="list.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                    <i class="fas fa-times mr-2"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>
    
    <!-- Liste des rapports -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (count($reports) > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Équipement</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'intervention</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Technicien</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($report['equipment_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo formatDateFr($report['intervention_date']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($report['performed_by_username']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusClass($report['status']); ?>">
                                    <?php echo getReportStatusLabel($report['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="view.php?id=<?php echo $report['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $report['id']; ?>" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="print.php?id=<?php echo $report['id']; ?>" class="text-green-600 hover:text-green-900 mr-3" title="Imprimer">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="p-6 text-center">
                <p class="text-gray-500">Aucun rapport de maintenance trouvé.</p>
                <p class="mt-2">
                    <a href="create.php" class="text-blue-600 hover:underline">
                        <i class="fas fa-plus-circle mr-1"></i> Créer un nouveau rapport
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>
