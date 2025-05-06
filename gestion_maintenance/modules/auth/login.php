<?php
// Page de connexion
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si l'utilisateur est déjà connecté, le rediriger vers la page d'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: /gestion_maintenance/index.php');
    exit;
}

// Traitement du formulaire de connexion
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validation basique
    if (empty($username) || empty($password)) {
        $error = 'Tous les champs sont obligatoires';
    } else {
        // Connexion à la base de données
        $conn = getConnection();
        
        // Sécuriser les entrées
        $username = sanitize($conn, $username);
        
        // Vérifier les identifiants
        $sql = "SELECT id, username, password, full_name, role FROM users WHERE username = '$username'";
        $result = $conn->query($sql);
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Vérifier le mot de passe
            if (password_verify($password, $user['password'])) {
                // Authentification réussie, créer la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // Redirection vers la page d'accueil
                header('Location: /gestion_maintenance/index.php');
                exit;
            } else {
                $error = 'Identifiants incorrects';
            }
        } else {
            $error = 'Identifiants incorrects';
        }
        
        // Fermer la connexion
        $conn->close();
    }
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 bg-white rounded-lg shadow-md">
        <h1 class="mb-6 text-2xl font-bold text-center text-blue-800">Connexion</h1>
        
        <?php if (!empty($error)): ?>
            <div class="p-4 mb-4 text-red-700 bg-red-100 border-l-4 border-red-500" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="mb-4">
                <label for="username" class="block mb-2 text-sm font-medium text-gray-700">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Entrez votre nom d'utilisateur">
            </div>
            
            <div class="mb-6">
                <label for="password" class="block mb-2 text-sm font-medium text-gray-700">Mot de passe</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Entrez votre mot de passe">
            </div>
            
            <div>
                <button type="submit" 
                        class="w-full px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:bg-blue-700">
                    Se connecter
                </button>
            </div>
        </form>
        
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
                Vous n'avez pas de compte? 
                <a href="register.php" class="text-blue-600 hover:underline">S'inscrire</a>
            </p>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>
