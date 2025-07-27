import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';

document.addEventListener('DOMContentLoaded', () => {
    const totalCreditsEl = document.getElementById('admin-total-credits');
    const ridesPerDayCanvas = document.getElementById('ridesPerDayChart').getContext('2d');
    const creditsGainedPerDayCanvas = document.getElementById('creditsGainedPerDayChart').getContext('2d');
    const employeesTableBody = document.getElementById('employees-table-body');
    const usersTableBody = document.getElementById('users-table-body');
    const createEmployeeForm = document.getElementById('create-employee-form');

    const employeeRowTemplate = document.getElementById('employee-row-template');
    const userRowTemplate = document.getElementById('user-row-template');

    let ridesChart = null;
    let creditsChart = null;

    /**
     * Je charge toutes les données nécessaires au tableau de bord en parallèle.
     */
    const loadDashboardData = async () => {
        try {
            const [totalCredits, rideStats, creditStats, employees, users] = await Promise.all([
                apiClient.getTotalCreditsEarned(),
                apiClient.getRideStats(),
                apiClient.getCreditStats(),
                apiClient.getAllEmployees(),
                apiClient.getAllUsers()
            ]);

            // J'affiche les statistiques et les graphiques.
            totalCreditsEl.textContent = totalCredits.total.toFixed(2) + ' crédits';
            renderRidesChart(rideStats);
            renderCreditsChart(creditStats);

            // Je remplis les tableaux.
            renderEmployeesTable(employees);
            renderUsersTable(users);

        } catch (error) {
            console.error('Erreur lors du chargement des données du tableau de bord:', error);
            displayFlashMessage('Impossible de charger les données du tableau de bord.', 'danger');
        }
    };

    /**
     * Je crée le graphique des trajets par jour.
     */
    const renderRidesChart = (stats) => {
        if (ridesChart) ridesChart.destroy();
        ridesChart = new Chart(ridesPerDayCanvas, {
            type: 'bar',
            data: {
                labels: stats.labels,
                datasets: [{
                    label: 'Nombre de covoiturages',
                    data: stats.data,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    };

    /**
     * Je crée le graphique des crédits gagnés par jour.
     */
    const renderCreditsChart = (stats) => {
        if (creditsChart) creditsChart.destroy();
        creditsChart = new Chart(creditsGainedPerDayCanvas, {
            type: 'line',
            data: {
                labels: stats.labels,
                datasets: [{
                    label: 'Crédits gagnés',
                    data: stats.data,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    };

    /**
     * Je remplis le tableau des employés.
     */
    const renderEmployeesTable = (employees) => {
        employeesTableBody.innerHTML = '';
        if (employees.length === 0) {
            document.getElementById('no-employees-message').classList.remove('d-none');
            return;
        }
        document.getElementById('no-employees-message').classList.add('d-none');

        employees.forEach(employee => {
            const clone = employeeRowTemplate.content.cloneNode(true);
            clone.querySelector('[data-label="ID_Employé"]').textContent = employee.id;
            clone.querySelector('[data-label="Nom"]').textContent = employee.last_name;
            clone.querySelector('[data-label="Prénom"]').textContent = employee.first_name;
            clone.querySelector('[data-label="Email"]').textContent = employee.email;
            
            const statusBadge = clone.querySelector('[data-label="Statut"] > span');
            statusBadge.textContent = employee.account_status;
            statusBadge.className = `badge ${employee.account_status === 'active' ? 'bg-success' : 'bg-danger'}`;

            const suspendBtn = clone.querySelector('.action-suspend');
            const reactivateBtn = clone.querySelector('.action-reactivate');

            if (employee.account_status === 'active') {
                reactivateBtn.classList.add('d-none');
            } else {
                suspendBtn.classList.add('d-none');
            }

            suspendBtn.addEventListener('click', () => handleUpdateStatus(employee.id, 'suspended'));
            reactivateBtn.addEventListener('click', () => handleUpdateStatus(employee.id, 'active'));

            employeesTableBody.appendChild(clone);
        });
    };

    /**
     * Je remplis le tableau des utilisateurs.
     */
    const renderUsersTable = (users) => {
        usersTableBody.innerHTML = '';
        if (users.length === 0) {
            document.getElementById('no-users-message').classList.remove('d-none');
            return;
        }
        document.getElementById('no-users-message').classList.add('d-none');

        users.forEach(user => {
            const clone = userRowTemplate.content.cloneNode(true);
            clone.querySelector('[data-label="ID_Utilisateur"]').textContent = user.id;
            clone.querySelector('[data-label="Pseudo"]').textContent = user.username;
            clone.querySelector('[data-label="Email"]').textContent = user.email;
            clone.querySelector('[data-label="Crédits"]').textContent = user.credits.toFixed(2);

            const statusBadge = clone.querySelector('[data-label="Statut"] > span');
            statusBadge.textContent = user.account_status;
            statusBadge.className = `badge ${user.account_status === 'active' ? 'bg-success' : 'bg-danger'}`;

            const suspendBtn = clone.querySelector('.user-action-suspend');
            const reactivateBtn = clone.querySelector('.user-action-reactivate');

            if (user.account_status === 'active') {
                reactivateBtn.classList.add('d-none');
            } else {
                suspendBtn.classList.add('d-none');
            }

            suspendBtn.addEventListener('click', () => handleUpdateStatus(user.id, 'suspended'));
            reactivateBtn.addEventListener('click', () => handleUpdateStatus(user.id, 'active'));

            usersTableBody.appendChild(clone);
        });
    };

    /**
     * Je gère la mise à jour du statut d'un utilisateur (employé ou client).
     */
    const handleUpdateStatus = async (userId, status) => {
        if (!confirm(`Êtes-vous sûr de vouloir ${status === 'suspended' ? 'suspendre' : 'réactiver'} ce compte ?`)) {
            return;
        }

        try {
            const result = await apiClient.updateUserStatus(userId, status);
            if (result.success) {
                displayFlashMessage('Statut mis à jour avec succès.', 'success');
                loadDashboardData(); // Je recharge tout pour refléter les changements.
            } else {
                throw new Error(result.error || 'Une erreur est survenue.');
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour du statut:', error);
            displayFlashMessage(error.message, 'danger');
        }
    };

    /**
     * Je gère la soumission du formulaire de création d'employé.
     */
    createEmployeeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
            first_name: document.getElementById('emp-prenom').value,
            last_name: document.getElementById('emp-nom').value,
            email: document.getElementById('emp-email').value,
            password: document.getElementById('emp-password').value
        };

        try {
            const result = await apiClient.createEmployee(data);
            if (result.id) {
                displayFlashMessage('Employé créé avec succès.', 'success');
                loadDashboardData(); // Je recharge pour voir le nouvel employé.
                const modal = bootstrap.Modal.getInstance(document.getElementById('createEmployeeModal'));
                modal.hide();
                createEmployeeForm.reset();
            } else {
                throw new Error(result.error || 'Une erreur est survenue lors de la création.');
            }
        } catch (error) {
            console.error("Erreur lors de la création de l'employé:", error);
            displayFlashMessage(error.message, 'danger');
        }
    });

    // Je charge les données initiales au chargement de la page.
    loadDashboardData();
});