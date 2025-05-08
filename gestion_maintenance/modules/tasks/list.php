<?php
// Page de modification d'une tâche de maintenance
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

// Récupérer les informations de la tâche
$sql = "SELECT * FROM tasks WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows !== 1) {
    // La tâche n'existe pas
    $conn->close();
    header('Location: list.php');
    exit;
}

$task = $result->fetch_assoc();

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
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $estimated_duration = isset($_POST['estimated_duration']) ? (int)$_POST['estimated_duration'] : 0;
    $frequency = isset($_POST['frequency']) ? $_POST['frequency'] : 'monthly';
    $frequency_value = isset($_POST['frequency_value']) ? (int)$_POST['frequency_value'] : 1;
    $frequency_unit = isset($_POST['frequency_unit']) ? $_POST['frequency_unit'] : '';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    
    // Validation basique
    if (empty($name)) {
        $error = 'Le nom de la tâche est obligatoire';
    } else {
        // Connexion à la base de données
        $conn = getConnection();
        
        // Sécuriser les entrées
        $name = sanitize($conn, $name);
        $description = sanitize($conn, $description);
        $frequency = sanitize($conn, $frequency);
        $frequency_unit = sanitize($conn, $frequency_unit);
        
        // Préparer la requête de mise à jour
        $sql = "UPDATE tasks SET 
                name = '$name', 
                description = '$description', 
                estimated_duration = $estimated_duration, 
                frequency = '$frequency', 
                frequency_value = $frequency_value, 
                frequency_unit = " . ($frequency_unit ? "'$frequency_unit'" : "NULL") . ", 
                category_id = " . ($category_id ? "$category_id" : "NULL") . " 
                WHERE id = $id";
        
        // Exécuter la requête
        if ($conn->query($sql) === TRUE) {
            $success = 'Tâche mise à jour avec succès';
            
            // Mettre à jour les informations affichées
            $task['name'] = $name;
            $task['description'] = $description;
            $task['estimated_duration'] = $estimated_duration;
            $task['frequency'] = $frequency;
            $task['frequency_value'] = $frequency_value;
            $task['frequency_unit'] = $frequency_unit;
            $task['category_id'] = $category_id;
        } else {
            $error = 'Erreur lors de la mise à jour de la tâche: ' . $conn->error;
        }
        
        // Fermer la connexion
        $conn->close();
    }
}
?>

<div class="py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Modifier une tâche de maintenance</h1>
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
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la tâche *</label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo htmlspecialchars($task['name']); ?>">
                    </div>
                    
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                        <select id="category_id" name="category_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $task['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($task['description']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Paramètres de la tâche -->
            <div class="mb-6">
                <h2 class="text-lg font-medium text-gray-700 mb-4">Paramètres de la tâche</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="estimated_duration" class="block text-sm font-medium text-gray-700 mb-1">Durée estimée (minutes)</label>
                        <input type="number" id="estimated_duration" name="estimated_duration" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               value="<?php echo $task['estimated_duration']; ?>">
                    </div>
                    
                    <div>
                        <label for="frequency" class="block text-sm font-medium text-gray-700 mb-1">Fréquence</label>
                        <select id="frequency" name="frequency"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="daily" <?php echo $task['frequency'] == 'daily' ? 'selected' : ''; ?>>Quotidienne</option>
                            <option value="weekly" <?php echo $task['frequency'] == 'weekly' ? 'selected' : ''; ?>>Hebdomadaire</option>
                            <option value="monthly" <?php echo $task['frequency'] == 'monthly' ? 'selected' : ''; ?>>Mensuelle</option>
                            <option value="quarterly" <?php echo $task['frequency'] == 'quarterly' ? 'selected' : ''; ?>>Trimestrielle</option>
                            <option value="biannually" <?php echo $task['frequency'] == 'biannually' ? 'selected' : ''; ?>>Semestrielle</option>
                            <option value="annually" <?php echo $task['frequency'] == 'annually' ? 'selected' : ''; ?>>Annuelle</option>
                            <option value="custom" <?php echo $task['frequency'] == 'custom' ? 'selected' : ''; ?>>Personnalisée</option>
                        </select>
                    </div>
                    
                    <div id="custom-frequency-container" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6" style="<?php echo $task['frequency'] == 'custom' ? '' : 'display: none;'; ?>">
                        <div>
                            <label for="frequency_value" class="block text-sm font-medium text-gray-700 mb-1">Valeur de fréquence</label>
                            <input type="number" id="frequency_value" name="frequency_value" min="1"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                   value="<?php echo $task['frequency_value']; ?>">
                        </div>
                        
                        <div>
                            <label for="frequency_unit" class="block text-sm font-medium text-gray-700 mb-1">Unité de fréquence</label>
                            <select id="frequency_unit" name="frequency_unit"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="days" <?php echo $task['frequency_unit'] == 'days' ? 'selected' : ''; ?>>Jours</option>
                                <option value="weeks" <?php echo $task['frequency_unit'] == 'weeks' ? 'selected' : ''; ?>>Semaines</option>
                                <option value="months" <?php echo $task['frequency_unit'] == 'months' ? 'selected' : ''; ?>>Mois</option>
                                <option value="years" <?php echo $task['frequency_unit'] == 'years' ? 'selected' : ''; ?>>Années</option>
                            </select>
                        </div>
                    </div>
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

<script>
    // Afficher/masquer les champs de fréquence personnalisée
    document.addEventListener('DOMContentLoaded', function() {
        const frequencySelect = document.getElementById('frequency');
        const customFrequencyContainer = document.getElementById('custom-frequency-container');
        
        function toggleCustomFrequency() {
            if (frequencySelect.value === 'custom') {
                customFrequencyContainer.style.display = 'grid';
            } else {
                customFrequencyContainer.style.display = 'none';
            }
        }
        
        // Écouteur d'événement
        frequencySelect.addEventListener('change', toggleCustomFrequency);
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>
