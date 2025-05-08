
<?php
// Page d'ajout d'un équipement
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

// Récupérer toutes les catégories
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

// Traitement du formulaire d'ajout
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
        $notes = sanitize($conn, $notes);
        
        // Préparer la requête d'insertion
        $sql = "INSERT INTO equipments (name, serial_number, model, manufacturer, acquisition_date, category_id, location, status, notes) 
                VALUES ('$name', '$serial_number', '$model', '$manufacturer', " . 
                ($acquisition_date ? "'$acquisition_date'" : "NULL") . ", " .
                ($category_id ? "$category_id" : "NULL") . ", " .
                "'$location', '$status', '$notes')";
        
        // Exécuter la requête
        if ($conn->query($sql) === TRUE) {
            $equipment_id = $conn->insert_id;
            
            // Ajouter dans l'historique
            $user_id = $_SESSION['user_id'];
            $history_sql = "INSERT INTO equipment_history (equipment_id, action_type, description, performed_by) 
                            VALUES ($equipment_id, 'added', 'Nouvel équipement ajouté', $user_id)";
            $conn->query($history_sql);
            
            $success = 'Équipement ajouté avec succès';
            
            // Redirection après délai
            header("refresh:2;url=list.php");
        } else {
            $error = 'Erreur lors de l\'ajout de l\'équipement: ' . $conn->error;
        }
        
        // Fermer la connexion
        $conn->close();
    }
}
?>

<div class="py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Ajouter un équipement</h1>
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
        <form method="post" action="">
            <!-- Informations générales -->
            <div class="mb-6">
                <h2 class="text-lg font-medium text-gray-700 mb-4">Informations générales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de l'équipement *</label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Ex: Serveur principal">
                    </div>
                    
                    <div>
                        <label for="serial_number" class="block text-sm font-medium text-gray-700 mb-1">Numéro de série</label>
                        <input type="text" id="serial_number" name="serial_number"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Ex: SN123456789">
                    </div>
                    
                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Modèle</label>
                        <input type="text" id="model" name="model"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Ex: PowerEdge R740">
                    </div>
                    
                    <div>
                        <label for="manufacturer" class="block text-sm font-medium text-gray-700 mb-1">Fabricant</label>
                        <input type="text" id="manufacturer" name="manufacturer"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Ex: Dell">
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
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="acquisition_date" class="block text-sm font-medium text-gray-700 mb-1">Date d'acquisition</label>
                        <input type="date" id="acquisition_date" name="acquisition_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Emplacement</label>
                        <input type="text" id="location" name="location"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Ex: Salle serveur - Rack 3">
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">État</label>
                        <select id="status" name="status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="operational">Opérationnel</option>
                            <option value="under_maintenance">En maintenance</option>
                            <option value="out_of_order">Hors service</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Notes -->
            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea id="notes" name="notes" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Informations supplémentaires sur l'équipement..."></textarea>
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
