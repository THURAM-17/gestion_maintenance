<?php
// Page de modification d'un équipement
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
$sql = "SELECT * FROM equipments WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows !== 1) {
    // L'équipement n'existe pas
    $conn->close();
    header('Location: list.php');
    exit;
}

$equipment = $result->fetch_assoc();

// Récupérer toutes les catégories
$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];
if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fermer la connexion (elle sera rouverte si le formulaire est soumis)
$conn->close();

// Traitement du formulaire de modification
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $serial_number = isset($_POST['serial_number']) ? $_POST['serial_number'] : '';
    $model = isset($_POST['model']) ? $_POST['model'] : '';
    $manufacturer = isset($_POST['manufacturer']) ? $_POST['manufacturer'] : '';
    $acquisition_date = isset($_POST['acquisition_date']) ? $_POST['acquisition_date'] : '';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $location = isset($_POST['location']) ? $_POST['location'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'operational';
    $last_maintenance_date = isset($_POST['last_maintenance_date']) ? $_POST['last_maintenance_date'] : '';
    $next_maintenance_date = isset($_POST['next_maintenance_date']) ? $_POST['next_maintenance_date'] : '';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Validation basique
    if (empty($name)) {
        $error = 'Le nom de l\'équipement est obligatoire';
    } else {
        // Connexion à la base de données
        $conn = getConnection();
        
        // Sécuriser les entrées
        $name = sanitize($conn, $name);
        $serial_number = sanitize($conn, $serial_number);
        $model = sanitize($conn, $model);
        $manufacturer = sanitize($conn, $manufacturer);
        $acquisition_date = sanitize($conn, $acquisition_date);
        $location = sanitize($conn, $location);
        $status = sanitize($conn, $status);
        $last_maintenance_date = sanitize($conn, $last_maintenance_date);
        $next_maintenance_date = sanitize($conn, $next_maintenance_date);
        $notes = sanitize($conn, $notes);
        
        // Préparer la requête de mise à jour
        $sql = "UPDATE equipments SET 
                name = '$name', 
                serial_number = '$serial_number', 
                model = '$model', 
                manufacturer = '$manufacturer', 
                acquisition_date = " . ($acquisition_date ? "'$acquisition_date'" : "NULL") . ", 
                category_id = " . ($category_id ? "$category_id" : "NULL") . ", 
                location = '$location', 
                status = '$status', 
                last_maintenance_date = " . ($last_maintenance_date ? "'$last_maintenance_date'" : "NULL") . ", 
                next_maintenance_date = " . ($next_maintenance_date ? "'$next_maintenance_date'" : "NULL") . ", 
                notes = '$notes' 
                WHERE id = $id";
        
        // Exécuter la requête
        if ($conn->query($sql) === TRUE) {
            // Ajouter dans l'historique
            $user_id = $_SESSION['user_id'];
            $history_sql = "INSERT INTO equipment_history (equipment_id, action_type, description, performed_by) 
                            VALUES ($id, 'updated', 'Équipement mis à jour', $user_id)";
            $conn->query($history_sql);
            
            $success = 'Équipement mis à jour avec succès';
            
            // Mettre à jour les informations affichées
            $equipment['name'] = $name;
            $equipment['serial_number'] = $serial_number;
            $equipment['model'] = $model;
            $equipment['manufacturer'] = $manufacturer;
            $equipment['acquisition_date'] = $acquisition_date;
            $equipment['category_id'] = $category_id;
            $equipment['location'] = $location;
            $equipment['status'] = $status;
            $equipment['last_maintenance_date'] = $last_maintenance_date;
            $equipment['next_maintenance_date'] = $next_maintenance_date;
            $equipment['notes'] = $notes;
        } else {
            $error = 'Erreur lors de la mise à jour de l\'équipement: ' . $conn->error;
        }
        
        // Fermer la connexion
        $conn->close();
    }
}
?>

<div class="py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Modifier un équipement</h1>
        <a href="list.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
            <i class="fas fa-arrow-left mr-2"></i> Retour à la liste
        </a>
    </div>
    
    <?php if (!empty($success)): ?>
        <div class="p-4 mb-4 text-green-700 bg-green-100 border-l-4 border-green-500 alert-fade" role="alert">
            <p><?php echo $success; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="p-4 mb-4 text-red-700 bg-red-100 border-l-4 border-red-500" role="alert">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Formulaire de modification -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="post" action="">
            <!-- Informations générales -->
            <div class="mb-6">
                <h2 class="text-lg font-medium text-gray-700 mb-4">Informations générales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de l'équipement *</label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo htmlspecialchars($equipment['name']); ?>">
                    </div>
                    
                    <div>
                        <label for="serial_number" class="block text-sm font-medium text-gray-700 mb-1">Numéro de série</label>
                        <input type="text" id="serial_number" name="serial_number"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo htmlspecialchars($equipment['serial_number']); ?>">
                    </div>
                    
                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Modèle</label>
                        <input type="text" id="model" name="model"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo htmlspecialchars($equipment['model']); ?>">
                    </div>
                    
                    <div>
                        <label for="manufacturer" class="block text-sm font-medium text-gray-700 mb-1">Fabricant</label>
                        <input type="text" id="manufacturer" name="manufacturer"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo htmlspecialchars($equipment['manufacturer']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Informations supplémentaires -->
            <div class="mb-6">
                <h2 class="text-lg font-medium text-gray-700 mb-4">Informations supplémentaires</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                        <select id="category_id" name="category_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $equipment['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="acquisition_date" class="block text-sm font-medium text-gray-700 mb-1">Date d'acquisition</label>
                        <input type="date" id="acquisition_date" name="acquisition_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo $equipment['acquisition_date']; ?>">
                    </div>
                    
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Emplacement</label>
                        <input type="text" id="location" name="location"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo htmlspecialchars($equipment['location']); ?>">
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">État</label>
                        <select id="status" name="status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="operational" <?php echo $equipment['status'] == 'operational' ? 'selected' : ''; ?>>Opérationnel</option>
                            <option value="under_maintenance" <?php echo $equipment['status'] == 'under_maintenance' ? 'selected' : ''; ?>>En maintenance</option>
                            <option value="out_of_order" <?php echo $equipment['status'] == 'out_of_order' ? 'selected' : ''; ?>>Hors service</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="last_maintenance_date" class="block text-sm font-medium text-gray-700 mb-1">Dernière maintenance</label>
                        <input type="date" id="last_maintenance_date" name="last_maintenance_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo $equipment['last_maintenance_date']; ?>">
                    </div>
                    
                    <div>
                        <label for="next_maintenance_date" class="block text-sm font-medium text-gray-700 mb-1">Prochaine maintenance</label>
                        <input type="date" id="next_maintenance_date" name="next_maintenance_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo $equipment['next_maintenance_date']; ?>">
                    </div>
                </div>
            </div>
            
            <!-- Notes -->
            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($equipment['notes']); ?></textarea>
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

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>
