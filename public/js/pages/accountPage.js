/**
 * accountPage.js
 * Point d'entrée principal pour la page du compte utilisateur.
 * Orchestre l'initialisation et les interactions entre les composants.
 */

import { VehicleForm } from '../components/VehicleForm.js';
import { VehicleList } from '../components/VehicleList.js';
import { RoleForm } from '../components/RoleForm.js';
import { PreferencesForm } from '../components/PreferencesForm.js';
import { apiClient } from '../utils/apiClient.js';

document.addEventListener('DOMContentLoaded', () => {
    const vehiclesListElement = document.getElementById('vehicles-list');
    const driverInfoSection = document.getElementById('driver-info-section');

    // Initialisation des composants
    const vehicleList = new VehicleList('#vehicles-list');
    const vehicleForm = new VehicleForm('#vehicle-form-container', '#add-vehicle-btn', vehiclesListElement);
    const roleForm = new RoleForm('#role-form', driverInfoSection);
    const preferencesForm = new PreferencesForm('#driver-preferences-form');

    // Charger et afficher les véhicules initiaux
    if (typeof initialVehiclesData !== 'undefined') {
        vehicleList.render(initialVehiclesData);
    }

    // Écouteurs d'événements personnalisés
    vehiclesListElement.addEventListener('editVehicleRequested', (event) => {
        vehicleForm.show(true, event.detail);
    });

    vehiclesListElement.addEventListener('vehicleUpdated', () => {
        // Recharger la liste des véhicules après une mise à jour/ajout
        // Pour l'instant, on recharge la page pour simplifier, mais on pourrait faire un appel API ici
        window.location.reload();
    });

    vehiclesListElement.addEventListener('vehicleDeleted', () => {
        // Recharger la liste des véhicules après une suppression
        // Pour l'instant, on recharge la page pour simplifier, mais on pourrait faire un appel API ici
        window.location.reload();
    });

    // Gestion de la suppression de compte
    const deleteAccountBtn = document.getElementById('confirm-delete-btn');
    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', async () => {
            try {
                const result = await apiClient.deleteAccount();
                if (result.success) {
                    alert(result.message);
                    window.location.href = '/login'; // Rediriger vers la page de connexion après suppression
                } else {
                    alert(result.error || 'Erreur lors de la suppression du compte.');
                }
            } catch (error) {
                alert('Une erreur de communication est survenue lors de la suppression du compte.');
            }
        });
    }
});
