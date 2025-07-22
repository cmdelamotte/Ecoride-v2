import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';
import { validateForm, getFormData, setFormLoadingState, displayFormErrors } from '../utils/formHelpers.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#publish-ride-form');
    const vehicleSelect = document.querySelector('#vehicle-id');

    /**
     * Charge les véhicules de l'utilisateur et les ajoute au menu déroulant.
     */
    async function loadUserVehicles() {
        try {
            const data = await apiClient.getUserVehicles();
            if (data.success && data.vehicles.length > 0) {
                vehicleSelect.innerHTML = '<option selected disabled value="">Choisissez votre véhicule</option>';
                data.vehicles.forEach(vehicle => {
                    const option = document.createElement('option');
                    option.value = vehicle.id;
                    option.textContent = `${vehicle.brand_name} ${vehicle.model}`;
                    vehicleSelect.appendChild(option);
                });
            } else {
                vehicleSelect.innerHTML = '<option selected disabled value="">Aucun véhicule trouvé. Veuillez en ajouter un.</option>';
            }
        } catch (error) {
            console.error('Erreur lors du chargement des véhicules:', error);
            vehicleSelect.innerHTML = '<option selected disabled value="">Erreur de chargement</option>';
        }
    }

    /**
     * Gère la soumission du formulaire de publication de trajet.
     * @param {Event} e L'événement de soumission.
     */
    async function handlePublishRide(e) {
        e.preventDefault();

        if (!validateForm(form)) {
            return;
        }

        const formData = getFormData(form);
        setFormLoadingState(form, true);

        try {
            const response = await apiClient.publishRide(formData);

            if (response.success) {
                displayFlashMessage(response.message, 'success');
                setTimeout(() => {
                    window.location.href = '/your-rides';
                }, 3000);
            }
        } catch (error) {
            if (error.status === 422) { // Erreurs de validation
                displayFormErrors(error.data.errors, form);
                displayFlashMessage('#publish-ride-message', 'Veuillez corriger les erreurs dans le formulaire.', 'error');
            } else {
                displayFlashMessage('#publish-ride-message', error.message || 'Une erreur est survenue.', 'error');
            }
        } finally {
            setFormLoadingState(form, false);
        }
    }

    // Initialisation
    if (form) {
        loadUserVehicles();
        form.addEventListener('submit', handlePublishRide);
    }
});