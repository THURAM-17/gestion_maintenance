<?php
// Page de modification d'une catégorie
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

// Récupérer les informations de la catégorie
$sql = "SELECT * FROM categories WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows !== 1) {
    // La catégorie n'existe pas
    $conn->close();
    header('Location: list.php');
    exit;
}

$category = $result->fetch_assoc();

// Récupérer toutes les catégories pour la sélection du parent
$categories_sql = "SELECT id, name FROM categories WHERE id != $id ORDER BY name";
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
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    
    // Validation basique
    if (empty($name)) {
        $error = 'Le nom de la catégorie est obligatoire';
    } else {
        // Connexion à la base de données
        $conn = getConnection();
        
        // Sécuriser les entrées
        $name = sanitize($conn, $name);
        $description = sanitize($conn, $description);
        
        // Vérifier si la catégorie existe déjà (autre que celle en cours d'édition)
        $check_sql = "SELECT id FROM categories WHERE name = '$name' AND id != $id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $error = 'Une catégorie avec ce nom existe déjà';
        } else {
            // Vérifier que le parent n'est pas un enfant de cette catégorie (éviter les boucles)
            if ($parent_id > 0) {
                $is_child = false;
                $check_parent = $parent_id;
                
                // Remonter l'arbre des catégories pour vérifier
                while ($check_parent > 0 && !$is_child) {
                    $parent_sql = "SELECT parent_id FROM categories WHERE id = $check_parent";
                    $parent_result = $conn->query($parent_sql);
                    
                    if ($parent_result->num_rows === 1) {
                        $parent_row = $parent_result->fetch_assoc();
                        $check_parent = $parent_row['parent_id'] ? (int)$parent_row['parent_id'] : 0;
                        
                        if ($check_parent === $id) {
                            $is_child = true;
                        }
                    } else {
                        break;
                    }
                }
                
                if ($is_child) {
                    $error = 'Impossible d\'utiliser cette catégorie parent : cela créerait une boucle';
                    $parent_id = $category['parent_id']; // Réinitialiser à la valeur précédente
                }
            }
            
            if (empty($error)) {
                // Préparer la requête de mise à jour
                $sql = "UPDATE categories SET 
                        name = '$name', 
                        description = '$description', 
                        parent_id = " . ($parent_id ? "$parent_id" : "NULL") . " 
                        WHERE id = $id";
                
                // Exécuter la requête
                if ($conn->query($sql) === TRUE) {
                    $_SESSION['success'] = 'Catégorie mise à jour avec succès';
                    
                    // Redirection
                    header('Location: list.php');
                    exit;
                } else {
                    $error = 'Erreur lors de la mise à jour de la catégorie: ' . $conn->error;
                }
            }
        }
        
        // Fermer la connexion
        $conn->close();
    }
}
?>

<div class="py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Modifier une catégorie</h1>
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom de la catégorie *</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           value="<?php echo htmlspecialchars($category['name']); ?>">
                </div>
                
                <div>
                    <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">Catégorie parent</label>
                    <select id="parent_id" name="parent_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Aucune (catégorie principale)</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category['parent_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($category['description']); ?></textarea>
                </div>
            </div>
            
            <!-- Boutons de soumission -->
            <div class="flex justify-end space-x-3 mt-6">
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
