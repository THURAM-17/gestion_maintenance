/**
 * Script principal pour l'application de gestion des maintenances préventives
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des alertes temporaires
    initAlerts();
    
    // Initialisation des onglets
    initTabs();
    
    // Initialisation des sélecteurs de date
    initDatePickers();
    
    // Initialisation des confirmations de suppression
    initDeleteConfirmations();
    
    // Initialisation du filtrage des tableaux
    initTableFilters();
});

/**
 * Fait disparaître les alertes après quelques secondes
 */
function initAlerts() {
    const alerts = document.querySelectorAll('.alert-fade');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('hide');
            
            // Supprime l'alerte du DOM après la fin de l'animation
            setTimeout(() => {
                alert.remove();
            }, 1000);
        }, 5000);
    });
}

/**
 * Initialise la fonctionnalité d'onglets
 */
function initTabs() {
    const tabLinks = document.querySelectorAll('.tab-link');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const tabId = this.getAttribute('data-tab');
            
            // Désactive tous les onglets
            document.querySelectorAll('.tab-link').forEach(tab => {
                tab.classList.remove('bg-blue-500', 'text-white');
                tab.classList.add('bg-white', 'text-blue-500');
            });
            
            // Masque tout le contenu des onglets
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Active l'onglet sélectionné
            this.classList.remove('bg-white', 'text-blue-500');
            this.classList.add('bg-blue-500', 'text-white');
            
            // Affiche le contenu de l'onglet sélectionné
            document.getElementById(tabId).classList.add('active');
        });
    });
}

/**
 * Initialise les sélecteurs de date
 * Remarque: Ceci est une version simplifiée. Dans un projet réel,
 * vous pourriez utiliser une bibliothèque comme flatpickr ou datepicker.js
 */
function initDatePickers() {
    // Ce code est un exemple simplifié
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        // Vous pouvez ajouter ici une initialisation de bibliothèque de date
        // Par exemple, avec flatpickr:
        // flatpickr(input, { dateFormat: 'Y-m-d' });
    });
}

/**
 * Ajoute des confirmations pour les actions de suppression
 */
function initDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('.delete-btn, .delete-link');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const confirmMessage = this.getAttribute('data-confirm') || 'Êtes-vous sûr de vouloir supprimer cet élément ?';
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Initialise les filtres de recherche pour les tableaux
 */
function initTableFilters() {
    const tableFilters = document.querySelectorAll('.table-filter');
    
    tableFilters.forEach(filter => {
        filter.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                
                if (text.indexOf(searchText) === -1) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
    });
}

/**
 * Fonction utilitaire pour effectuer des requêtes AJAX
 */
function ajaxRequest(url, method = 'GET', data = null, callback = null) {
    const xhr = new XMLHttpRequest();
    
    xhr.open(method, url, true);
    
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                let response;
                
                try {
                    response = JSON.parse(xhr.responseText);
                } catch (e) {
                    response = xhr.responseText;
                }
                
                if (callback) {
                    callback(null, response);
                }
            } else {
                if (callback) {
                    callback(new Error('Erreur lors de la requête AJAX: ' + xhr.status), null);
                }
            }
        }
    };
    
    if (data) {
        xhr.send(JSON.stringify(data));
    } else {
        xhr.send();
    }
}

/**
 * Fonction pour générer dynamiquement un calendrier mensuel
 */
function generateCalendar(containerId, year, month, events = []) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Vider le conteneur
    container.innerHTML = '';
    
    // Créer le titre du mois
    const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    
    const monthTitle = document.createElement('h3');
    monthTitle.className = 'text-xl font-bold text-center mb-4';
    monthTitle.textContent = monthNames[month - 1] + ' ' + year;
    container.appendChild(monthTitle);
    
    // Créer l'en-tête du calendrier
    const dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    const calendarGrid = document.createElement('div');
    calendarGrid.className = 'grid grid-cols-7 gap-1';
    
    // Ajouter les en-têtes des jours
    dayNames.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'p-2 bg-gray-200 text-center font-bold';
        dayHeader.textContent = day;
        calendarGrid.appendChild(dayHeader);
    });
    
    // Obtenir le premier jour du mois et le nombre de jours dans le mois
    const firstDayOfMonth = new Date(year, month - 1, 1).getDay();
    const daysInMonth = new Date(year, month, 0).getDate();
    
    // Ajouter les cellules vides pour les jours avant le début du mois
    for (let i = 0; i < firstDayOfMonth; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'p-2 bg-gray-100 border border-gray-200';
        calendarGrid.appendChild(emptyDay);
    }
    
    // Ajouter les jours du mois
    for (let day = 1; day <= daysInMonth; day++) {
        const dayCell = document.createElement('div');
        dayCell.className = 'p-2 bg-white border border-gray-200 calendar-day';
        
        // Ajouter le numéro du jour
        const dayNumber = document.createElement('div');
        dayNumber.className = 'font-bold';
        dayNumber.textContent = day;
        dayCell.appendChild(dayNumber);
        
        // Ajouter les événements pour ce jour
        const dayEvents = events.filter(event => {
            const eventDate = new Date(event.date);
            return eventDate.getDate() === day && 
                   eventDate.getMonth() === month - 1 && 
                   eventDate.getFullYear() === year;
        });
        
        dayEvents.forEach(event => {
            const eventDiv = document.createElement('div');
            eventDiv.className = `calendar-event ${event.className || 'bg-blue-100 text-blue-800'}`;
            eventDiv.textContent = event.title;
            
            // Ajouter des attributs de données pour plus d'informations
            if (event.id) {
                eventDiv.setAttribute('data-id', event.id);
            }
            
            // Ajouter un clic pour voir les détails
            eventDiv.addEventListener('click', function() {
                if (typeof showEventDetails === 'function') {
                    showEventDetails(event);
                } else {
                    alert(`Événement: ${event.title}\nDate: ${event.date}`);
                }
            });
            
            dayCell.appendChild(eventDiv);
        });
        
        calendarGrid.appendChild(dayCell);
    }
    
    container.appendChild(calendarGrid);
}