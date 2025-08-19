import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';
import { validateForm, getFormData, setFormLoadingState, displayFormErrors } from '../utils/formHelpers.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#publish-ride-form');
    const vehicleSelect = document.querySelector('#vehicle-id');
    const departureDatetimeInput = document.querySelector('#departure-datetime');
    const estimatedArrivalDatetimeInput = document.querySelector('#estimated-arrival-datetime');

    /**
     * Valide le formulaire de publication de trajet, y compris la date.
     * @returns {boolean} True si le formulaire est valide, false sinon.
     */
    function validatePublishForm() {
        // Réinitialiser la validité personnalisée avant de re-valider
        if (departureDatetimeInput) {
            departureDatetimeInput.setCustomValidity('');
        }
        if (estimatedArrivalDatetimeInput) {
            estimatedArrivalDatetimeInput.setCustomValidity('');
        }

        // Validation HTML5 native
        if (!form.checkValidity()) {
            form.reportValidity();
            return false;
        }

        // Validation spécifique de la date de départ (ne doit pas être dans le passé)
        if (departureDatetimeInput && departureDatetimeInput.value) {
            const today = new Date();
            const selectedDate = new Date(departureDatetimeInput.value);
            // Comparer les dates sans l'heure pour éviter les problèmes de fuseau horaire
            today.setHours(0, 0, 0, 0);
            selectedDate.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                departureDatetimeInput.setCustomValidity("La date du trajet ne peut pas être dans le passé.");
                form.reportValidity(); // Déclenche l'affichage du pop-up
                return false;
            }
        }

        // Validation : la date d'arrivée estimée ne peut pas être avant la date de départ
        if (departureDatetimeInput && departureDatetimeInput.value && estimatedArrivalDatetimeInput && estimatedArrivalDatetimeInput.value) {
            const departureDate = new Date(departureDatetimeInput.value);
            const estimatedArrivalDate = new Date(estimatedArrivalDatetimeInput.value);

            if (estimatedArrivalDate <= departureDate) {
                estimatedArrivalDatetimeInput.setCustomValidity("L'heure d'arrivée doit être postérieure à l'heure de départ.");
                form.reportValidity();
                return false;
            }
        }

        return true;
    }

    /**
     * Charge les véhicules de l'utilisateur et les ajoute au menu déroulant.
     */
    async function loadUserVehicles() {
        while (vehicleSelect.firstChild) {
            vehicleSelect.removeChild(vehicleSelect.firstChild);
        }
        try {
            const data = await apiClient.getUserVehicles();
            if (data.success && data.vehicles.length > 0) {
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Choisissez votre véhicule';
                defaultOption.selected = true;
                defaultOption.disabled = true;
                vehicleSelect.appendChild(defaultOption);
                data.vehicles.forEach(vehicle => {
                    const option = document.createElement('option');
                    option.value = vehicle.id;
                    option.textContent = `${vehicle.brand_name} ${vehicle.model_name}`;
                    vehicleSelect.appendChild(option);
                });
            } else {
                const noVehicleOption = document.createElement('option');
                noVehicleOption.value = '';
                noVehicleOption.textContent = 'Aucun véhicule trouvé. Veuillez en ajouter un.';
                noVehicleOption.selected = true;
                noVehicleOption.disabled = true;
                vehicleSelect.appendChild(noVehicleOption);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des véhicules:', error);
                            const errorOption = document.createElement('option');
                errorOption.value = '';
                errorOption.textContent = 'Erreur de chargement';
                errorOption.selected = true;
                errorOption.disabled = true;
                vehicleSelect.appendChild(errorOption);
        }
    }

    /**
     * Gère la soumission du formulaire de publication de trajet.
     * @param {Event} e L'événement de soumission.
     */
    async function handlePublishRide(e) {
        e.preventDefault();

        if (!validatePublishForm()) {
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