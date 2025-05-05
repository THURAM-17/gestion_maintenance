<?php
/**
 * Fichier contenant des fonctions utilitaires pour l'application
 */

// Fonctions de validation

/**
 * Vérifie si une chaîne est vide ou ne contient que des espaces
 */
function isEmpty($value) {
    return trim($value) === '';
}

/**
 * Valide une adresse email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Formate une date en format français (jour/mois/année)
 */
function formatDateFr($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}

/**
 * Formate une date et heure en format français
 */
function formatDateTimeFr($datetime) {
    if (empty($datetime)) return '';
    $timestamp = strtotime($datetime);
    return date('d/m/Y à H:i', $timestamp);
}

/**
 * Génère un jeton CSRF pour les formulaires
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si un jeton CSRF est valide
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token'];
}

/**
 * Récupère le numéro de la semaine à partir d'une date
 */
function getWeekNumber($date) {
    return date('W', strtotime($date));
}

/**
 * Récupère le mois à partir d'une date
 */
function getMonthNumber($date) {
    return date('n', strtotime($date));
}

/**
 * Obtient le nom d'un mois en français
 */
function getMonthName($month_number) {
    $months = [
        1 => 'Janvier',
        2 => 'Février',
        3 => 'Mars',
        4 => 'Avril',
        5 => 'Mai',
        6 => 'Juin',
        7 => 'Juillet',
        8 => 'Août',
        9 => 'Septembre',
        10 => 'Octobre',
        11 => 'Novembre',
        12 => 'Décembre'
    ];
    
    return isset($months[$month_number]) ? $months[$month_number] : '';
}

/**
 * Affiche un message d'alerte
 */
function displayAlert($message, $type = 'info') {
    $class = 'bg-blue-100 border-blue-500 text-blue-700'; // info par défaut
    
    if ($type === 'success') {
        $class = 'bg-green-100 border-green-500 text-green-700';
    } elseif ($type === 'error') {
        $class = 'bg-red-100 border-red-500 text-red-700';
    } elseif ($type === 'warning') {
        $class = 'bg-yellow-100 border-yellow-500 text-yellow-700';
    }
    
    echo "<div class='border-l-4 p-4 mb-4 {$class}' role='alert'>";
    echo "<p>{$message}</p>";
    echo "</div>";
}

/**
 * Obtient l'état d'un équipement en français
 */
function getEquipmentStatusLabel($status) {
    $labels = [
        'operational' => 'Opérationnel',
        'under_maintenance' => 'En maintenance',
        'out_of_order' => 'Hors service'
    ];
    
    return isset($labels[$status]) ? $labels[$status] : $status;
}

/**
 * Obtient l'état d'une tâche planifiée en français
 */
function getScheduleItemStatusLabel($status) {
    $labels = [
        'pending' => 'En attente',
        'completed' => 'Terminée',
        'postponed' => 'Reportée',
        'cancelled' => 'Annulée'
    ];
    
    return isset($labels[$status]) ? $labels[$status] : $status;
}

/**
 * Obtient l'état d'un rapport de maintenance en français
 */
function getReportStatusLabel($status) {
    $labels = [
        'completed' => 'Terminé',
        'incomplete' => 'Incomplet',
        'requires_followup' => 'Nécessite un suivi'
    ];
    
    return isset($labels[$status]) ? $labels[$status] : $status;
}

/**
 * Génère une classe CSS selon le statut
 */
function getStatusClass($status) {
    $classes = [
        // Statuts équipements
        'operational' => 'bg-green-100 text-green-800',
        'under_maintenance' => 'bg-yellow-100 text-yellow-800',
        'out_of_order' => 'bg-red-100 text-red-800',
        
        // Statuts planning
        'pending' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'postponed' => 'bg-yellow-100 text-yellow-800',
        'cancelled' => 'bg-red-100 text-red-800',
        
        // Statuts rapports
        'incomplete' => 'bg-yellow-100 text-yellow-800',
        'requires_followup' => 'bg-purple-100 text-purple-800'
    ];
    
    return isset($classes[$status]) ? $classes[$status] : 'bg-gray-100 text-gray-800';
}