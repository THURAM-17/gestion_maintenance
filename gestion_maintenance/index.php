<?php
// Page d'accueil de l'application
require_once 'config/database.php';
require_once 'includes/functions.php';

// Inclure l'en-tête
include_once 'includes/header.php';

// Si l'utilisateur n'est pas connecté, afficher la page d'introduction
if (!isLoggedIn()) {
?>
    <div class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-b from-blue-100 to-white px-4 py-12">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-blue-800 mb-6">Système de Gestion des Maintenances Préventives</h1>
            <h2 class="text-2xl text-gray-700 mb-8">Port Autonome de Douala</h2>
            
            <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-10">
                Une solution complète pour gérer les équipements, planifier les maintenances préventives, 
                et suivre l'historique des interventions techniques.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center gap-4 mb-12">
                <a href="modules/auth/login.php" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-all">
                    <i class="fas fa-sign-in-alt mr-2"></i> Connexion
                </a>
                <a href="#fonctionnalites" class="px-6 py-3 bg-white text-blue-600 font-medium rounded-lg border border-blue-600 hover:bg-blue-50 transition-all">
                    <i class="fas fa-info-circle mr-2"></i> En savoir plus
                </a>
            </div>
        </div>
        
        <div id="fonctionnalites" class="max-w-5xl mx-auto mt-8">
            <h2 class="text-2xl font-bold text-center text-blue-800 mb-8">Fonctionnalités principales</h2>
            
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="text-blue-500 text-4xl mb-4">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Gestion des équipements</h3>
                    <p class="text-gray-600">
                        Enregistrez tous vos équipements par catégorie et suivez leur état de fonctionnement.
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="text-blue-500 text-4xl mb-4">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Planification des maintenances</h3>
                    <p class="text-gray-600">
                        Créez des plannings de maintenance préventive et assignez des tâches aux techniciens.
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="text-blue-500 text-4xl mb-4">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Rapports et suivi</h3>
                    <p class="text-gray-600">
                        Générez des fiches de vie, de suivi et de travaux pour chaque équipement.
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php
} else {
    // Récupérer des statistiques pour le tableau de bord
    $conn = getConnection();
    
    // Nombre total d'équipements
    $totalEquipments = $conn->query("SELECT COUNT(*) as total FROM equipments")->fetch_assoc()['total'] ?? 0;
    
    // Équipements par statut
    $equipmentsByStatus = $conn->query("SELECT status, COUNT(*) as count FROM equipments GROUP BY status");
    $statusData = [];
    while ($row = $equipmentsByStatus->fetch_assoc()) {
        $statusData[$row['status']] = $row['count'];
    }
    
    // Tâches planifiées pour le mois en cours
    $currentMonth = date('n');
    $currentYear = date('Y');
    $tasksThisMonth = $conn->query("SELECT COUNT(*) as total FROM schedule_items WHERE planned_month = $currentMonth AND YEAR(planned_date) = $currentYear")->fetch_assoc()['total'] ?? 0;
    
    // Tâches en attente
    $pendingTasks = $conn->query("SELECT COUNT(*) as total FROM schedule_items WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;
    
    // Fermer la connexion
    $conn->close();
?>
    <div class="py-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Tableau de bord</h1>
        
        <!-- Statistiques générales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-desktop text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-sm font-medium text-gray-500">Équipements</h2>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $totalEquipments; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-sm font-medium text-gray-500">Équipements opérationnels</h2>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $statusData['operational'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-tools text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-sm font-medium text-gray-500">En maintenance</h2>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $statusData['under_maintenance'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-sm font-medium text-gray-500">Hors service</h2>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $statusData['out_of_order'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions rapides -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-800">Actions rapides</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="modules/equipments/add.php" class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg flex items-center">
                        <i class="fas fa-plus-circle text-blue-600 mr-3"></i>
                        <span>Ajouter un équipement</span>
                    </a>
                    
                    <a href="modules/tasks/add.php" class="bg-green-50 hover:bg-green-100 p-4 rounded-lg flex items-center">
                        <i class="fas fa-tasks text-green-600 mr-3"></i>
                        <span>Créer une tâche</span>
                    </a>
                    
                    <a href="modules/schedules/add.php" class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg flex items-center">
                        <i class="fas fa-calendar-plus text-purple-600 mr-3"></i>
                        <span>Planifier une maintenance</span>
                    </a>
                    
                    <a href="modules/reports/create.php" class="bg-orange-50 hover:bg-orange-100 p-4 rounded-lg flex items-center">
                        <i class="fas fa-clipboard-check text-orange-600 mr-3"></i>
                        <span>Créer un rapport</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Tâches à venir -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-800">Tâches planifiées ce mois</h2>
                </div>
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <span class="text-3xl font-bold text-gray-800"><?php echo $tasksThisMonth; ?></span>
                            <span class="text-gray-500 ml-2">au total</span>
                        </div>
                        <div>
                            <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                                <i class="fas fa-clock mr-1"></i> <?php echo $pendingTasks; ?> en attente
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($tasksThisMonth > 0): ?>
                        <a href="modules/schedules/list.php" class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Voir les plannings
                        </a>
                    <?php else: ?>
                        <p class="text-gray-500">
                            Aucune tâche planifiée pour ce mois. 
                            <a href="modules/schedules/add.php" class="text-blue-600 hover:underline">
                                Créer un planning
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Espace pour une visualisation ou une autre statistique -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-800">Calendrier du mois</h2>
                </div>
                <div class="p-6">
                    <div id="current-month-calendar"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Initialiser le calendrier après le chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth() + 1;
            
            // Exemple de données d'événements (à remplacer par des données réelles)
            const events = [
                // Ces événements seraient normalement chargés depuis la base de données
                /*
                {
                    id: 1,
                    title: 'Maintenance PC-001',
                    date: '2025-04-15',
                    className: 'bg-blue-100 text-blue-800'
                },
                {
                    id: 2,
                    title: 'Vérification Scanner-003',
                    date: '2025-04-20',
                    className: 'bg-green-100 text-green-800'
                }
                */
            ];
            
            // Générer le calendrier
            generateCalendar('current-month-calendar', currentYear, currentMonth, events);
        });
    </script>
<?php
}

// Inclure le pied de page
include_once 'includes/footer.php';
?>