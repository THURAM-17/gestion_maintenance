<nav class="bg-blue-800 text-white">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-3">
            <div class="flex items-center">
                <a href="/gestion_maintenance/index.php" class="text-xl font-bold">
                    PAD - Maintenance Préventive
                </a>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <div class="hidden md:flex space-x-4">
                    <a href="/gestion_maintenance/index.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-home mr-1"></i> Accueil
                    </a>
                    <a href="/gestion_maintenance/modules/equipments/list.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-desktop mr-1"></i> Équipements
                    </a>
                    <a href="/gestion_maintenance/modules/categories/list.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-tags mr-1"></i> Catégories
                    </a>
                    <a href="/gestion_maintenance/modules/tasks/list.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-tasks mr-1"></i> Tâches
                    </a>
                    <a href="/gestion_maintenance/modules/schedules/list.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-calendar-alt mr-1"></i> Plannings
                    </a>
                    <a href="/gestion_maintenance/modules/reports/list.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-clipboard-list mr-1"></i> Rapports
                    </a>
                </div>
                
                <div class="flex items-center">
                    <div class="hidden md:flex items-center">
                        <span class="mr-4">
                            <i class="fas fa-user mr-1"></i> 
                            <?php echo $_SESSION['username'] ?? 'Utilisateur'; ?>
                        </span>
                        <a href="/gestion_maintenance/modules/auth/logout.php" class="py-2 px-4 bg-red-600 hover:bg-red-700 rounded">
                            <i class="fas fa-sign-out-alt mr-1"></i> Déconnexion
                        </a>
                    </div>
                    
                    <button class="md:hidden focus:outline-none" id="menuToggle">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            <?php else: ?>
                <div class="flex space-x-4">
                    <a href="/gestion_maintenance/modules/auth/login.php" class="py-2 px-4 bg-blue-600 hover:bg-blue-700 rounded">
                        <i class="fas fa-sign-in-alt mr-1"></i> Connexion
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Menu mobile -->
        <?php if (isLoggedIn()): ?>
            <div class="md:hidden hidden" id="mobileMenu">
                <div class="flex flex-col space-y-2 pb-4">
                    <a href="/gestion_maintenance/index.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-home mr-1"></i> Accueil
                    </a>
                    <a href="/gestion_maintenance/modules/equipments/list.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-desktop mr-1"></i> Équipements
                    </a>
                    <a href="/gestion_maintenance/modules/categories/list.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-tags mr-1"></i> Catégories
                    </a>
                    <a href="/gestion_maintenance/modules/tasks/list.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-tasks mr-1"></i> Tâches
                    </a>
                    <a href="/gestion_maintenance/modules/schedules/list.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-calendar-alt mr-1"></i> Plannings
                    </a>
                    <a href="/gestion_maintenance/modules/reports/list.php" class="py-2 px-3 hover:bg-blue-700 rounded">
                        <i class="fas fa-clipboard-list mr-1"></i> Rapports
                    </a>
                    <a href="/gestion_maintenance/modules/auth/logout.php" class="py-2 px-3 text-red-300 hover:bg-red-700 rounded">
                        <i class="fas fa-sign-out-alt mr-1"></i> Déconnexion
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>

<script>
    // Script pour le menu mobile
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        
        if (menuToggle && mobileMenu) {
            menuToggle.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
    });
</script>