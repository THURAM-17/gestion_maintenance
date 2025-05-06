<?php
// Page d'inscription
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

// Traitement du formulaire d'inscription
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $full_name = isset($_POST['full_name']) ? $_POST['full_name'] : '';
    $role = 'user'; // Par défaut, tous les nouveaux utilisateurs sont des utilisateurs standard
    
    // Validation basique
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || empty($full_name)) {
        $error = 'Tous les champs sont obligatoires';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (!isValidEmail($email)) {
        $error = 'Adresse email invalide';
    } else {
        // Connexion à la base de données
        $conn = getConnection();
        
        // Sécuriser les entrées
        $username = sanitize($conn, $username);
        $email = sanitize($conn, $email);
        $full_name = sanitize($conn, $full_name);
        
        // Vérifier si le nom d'utilisateur existe déjà
        $sql = "SELECT id FROM users WHERE username = '$username'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $error = 'Ce nom d\'utilisateur est déjà utilisé';
        } else {
            // Vérifier si l'email existe déjà
            $sql = "SELECT id FROM users WHERE email = '$email'";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                $error = 'Cette adresse email est déjà utilisée';
            } else {
                // Hacher le mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insérer le nouvel utilisateur
                $sql = "INSERT INTO users (username, password, email, full_name, role) VALUES ('$username', '$hashed_password', '$email', '$full_name', '$role')";
                
                if ($conn->query($sql) === TRUE) {
                    $success = 'Inscription réussie! Vous pouvez maintenant vous connecter.';
                } else {
                    $error = 'Erreur lors de l\'inscription: ' . $conn->error;
                }
            }
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
        <h1 class="mb-6 text-2xl font-bold text-center text-blue-800">Inscription</h1>
        
        <?php if (!empty($error)): ?>
            <div class="p-4 mb-4 text-red-700 bg-red-100 border-l-4 border-red-500" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="p-4 mb-4 text-green-700 bg-green-100 border-l-4 border-green-500" role="alert">
                <p><?php echo $success; ?></p>
                <p class="mt-2">
                    <a href="login.php" class="font-medium text-green-700 underline">Se connecter maintenant</a>
                </p>
            </div>
        <?php else: ?>
            <form method="post" action="">
                <div class="mb-4">
                    <label for="username" class="block mb-2 text-sm font-medium text-gray-700">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Choisissez un nom d'utilisateur">
                </div>
                
                <div class="mb-4">
                    <label for="full_name" class="block mb-2 text-sm font-medium text-gray-700">Nom complet</label>
                    <input type="text" id="full_name" name="full_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Entrez votre nom complet">
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-700">Adresse email</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Entrez votre adresse email">
                </div>
                
                <div class="mb-4">
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-700">Mot de passe</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Choisissez un mot de passe">
                </div>
                
                <div class="mb-6">
                    <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-700">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Confirmez votre mot de passe">
                </div>
                
                <div>
                    <button type="submit" 
                            class="w-full px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:bg-blue-700">
                        S'inscrire
                    </button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
                Vous avez déjà un compte? 
                <a href="login.php" class="text-blue-600 hover:underline">Se connecter</a>
            </p>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>
