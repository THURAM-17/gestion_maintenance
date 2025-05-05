<?php
// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Utilisateur par défaut de XAMPP
define('DB_PASS', '');          // Mot de passe par défaut (vide)
define('DB_NAME', 'gestion_maintenance');

// Création de la connexion à la base de données
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Vérification de la connexion
    if ($conn->connect_error) {
        die("Échec de la connexion : " . $conn->connect_error);
    }
    
    // Définition du jeu de caractères
    $conn->set_charset("utf8");
    
    return $conn;
}

// Fonction pour sécuriser les entrées utilisateur
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Fonction pour exécuter une requête et retourner un tableau associatif
function executeQuery($sql) {
    $conn = getConnection();
    $result = $conn->query($sql);
    
    if (!$result) {
        die("Erreur dans la requête : " . $conn->error);
    }
    
    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    $conn->close();
    return $data;
}

// Fonction pour exécuter une requête sans retour de données
function executeNonQuery($sql) {
    $conn = getConnection();
    $result = $conn->query($sql);
    
    if (!$result) {
        die("Erreur dans la requête : " . $conn->error);
    }
    
    $affected = $conn->affected_rows;
    $insertId = $conn->insert_id;
    $conn->close();
    
    return [
        'affected_rows' => $affected,
        'insert_id' => $insertId
    ];
}
?>