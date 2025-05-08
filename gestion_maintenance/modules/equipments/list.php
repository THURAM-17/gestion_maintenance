<?php
// Page de liste des équipements
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

// Récupérer toutes les catégories pour le filtre
$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];
if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Appliquer les filtres si présents
$where_clause = "1=1"; // Toujours vrai pour pouvoir enchaîner les AND
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_id = (int)$_GET['category'];
    $where_clause .= " AND e.category_id = $category_id";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = sanitize($conn, $_GET['status']);
    $where_clause .= " AND e.status = '$status'";
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitize($conn, $_GET['search']);
    $where_clause .= " AND (e.name LIKE '%$search%' OR e.serial_number LIKE '%$search%' OR e.model LIKE '%$search%' OR e.manufacturer LIKE '%$search%')";
}

// Récupérer les équipements avec filtres
$sql = "SELECT e.*, c.name as category_name 
        FROM equipments e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE $where_clause
        ORDER BY e.name";
$result = $conn->query($sql);

// Récupérer les équipements
$equipments = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $equipments[] = $row;
    }
}

// Fermer la connexion
$conn->close();
?>

<div class="py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Liste des équipements</h1>
        <a href="add.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i> Ajouter un équipement
        </a>
    </div>
    
    <!-- Filtres -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <h2 class="text-lg font-medium text-gray-700 mb-4">Filtres</h2>
        <form action="" method="get" class="flex flex-wrap items-end gap-4">
            <div class="w-full md:w-auto">
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                <select name="category" id="category" class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="w-full md:w-auto">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">État</label>
                <select name="status" id="status" class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Tous les états</option>
                    <option value="operational" <?php echo isset($_GET['status']) && $_GET['status'] == 'operational' ? 'selected' : ''; ?>>Opérationnel</option>
                    <option value="under_maintenance" <?php echo isset($_GET['status']) && $_GET['status'] == 'under_maintenance' ? 'selected' : ''; ?>>En maintenance</option>
                    <option value="out_of_order" <?php echo isset($_GET['status']) && $_GET['status'] == 'out_of_order' ? 'selected' : ''; ?>>Hors service</option>
                </select>
            </div>
            
            <div class="w-full md:w-auto">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                <input type="text" name="search" id="search" placeholder="Nom, n° série, modèle..." 
                       class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-md"
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
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
    
    <!-- Liste des équipements -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (count($equipments) > 0): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Numéro de série</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modèle</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fabricant</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">État</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($equipments as $equipment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($equipment['name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($equipment['serial_number']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($equipment['model']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($equipment['manufacturer']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($equipment['category_name'] ?? 'Non catégorisé'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusClass($equipment['status']); ?>">
                                    <?php echo getEquipmentStatusLabel($equipment['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="view.php?id=<?php echo $equipment['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $equipment['id']; ?>" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $equipment['id']; ?>" class="text-red-600 hover:text-red-900 delete-btn" 
                                   data-confirm="Êtes-vous sûr de vouloir supprimer cet équipement ?">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="p-6 text-center">
                <p class="text-gray-500">Aucun équipement trouvé.</p>
                <?php if (isset($_GET['category']) || isset($_GET['status']) || isset($_GET['search'])): ?>
                    <p class="mt-2">
                        <a href="list.php" class="text-blue-600 hover:underline">
                            <i class="fas fa-arrow-left mr-1"></i> Réinitialiser les filtres
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>
