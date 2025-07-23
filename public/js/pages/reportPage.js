import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';
import { validateForm, getFormData, setFormLoadingState, displayFormErrors, resetFormValidation } from '../utils/formHelpers.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#report-form');
    const reportMessageDiv = document.querySelector('#report-message');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Réinitialiser les messages d'erreur précédents
            reportMessageDiv.classList.add('d-none');
            reportMessageDiv.textContent = '';

            if (!validateForm(form)) {
                return;
            }

            const formData = getFormData(form);
            setFormLoadingState(form, true);

            try {
                const response = await apiClient.submitReport(formData);

                if (response.success) {
                    displayFlashMessage(response.message, 'success');
                    form.reset(); // Réinitialiser le formulaire en cas de succès
                    resetFormValidation(form); // Réinitialiser l'état de validation
                    setTimeout(() => {
                        window.location.href = '/'; // Redirection vers l'accueil
                    }, 3000); // Redirection après 3 secondes
                } else {
                    // Afficher les erreurs spécifiques aux champs si elles existent
                    if (response.errors) {
                        displayFormErrors(response.errors, form);
                    }
                    displayFlashMessage(response.message || 'Une erreur est survenue.', 'error');
                    // Redirection même en cas d'erreur de validation pour les doublons
                    if (response.errors && response.errors.general) {
                        setTimeout(() => {
                            window.location.href = '/'; // Redirection vers l'accueil
                        }, 3000); // Redirection après 3 secondes
                    }
                }
            } catch (error) {
                console.error('Erreur lors de la soumission du signalement:', error);
                // Gérer les erreurs de communication ou les erreurs non-JSON
                displayFlashMessage(error.message || 'Une erreur de communication est survenue.', 'error');
            } finally {
                setFormLoadingState(form, false);
            }
        });
    }
});
