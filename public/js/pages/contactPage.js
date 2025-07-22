import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';
import { validateForm, getFormData, setFormLoadingState, displayFormErrors } from '../utils/formHelpers.js';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#contact-form');
    const messageContactDiv = document.querySelector('#message-contact');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Réinitialiser les messages d'erreur précédents
            messageContactDiv.classList.add('d-none');
            messageContactDiv.textContent = '';

            if (!validateForm(form)) {
                return;
            }

            const formData = getFormData(form);
            setFormLoadingState(form, true);

            try {
                const response = await apiClient.submitContactForm(formData);

                if (response.success) {
                    displayFlashMessage(response.message, 'success');
                    form.reset(); // Réinitialiser le formulaire en cas de succès
                } else {
                    // Afficher les erreurs spécifiques aux champs si elles existent
                    if (response.errors) {
                        displayFormErrors(response.errors, form);
                    }
                    displayFlashMessage(response.message || 'Une erreur est survenue.', 'error');
                }
            } catch (error) {
                console.error('Erreur lors de la soumission du formulaire de contact:', error);
                // Gérer les erreurs de communication ou les erreurs non-JSON
                displayFlashMessage(error.message || 'Une erreur de communication est survenue.', 'error');
            } finally {
                setFormLoadingState(form, false);
            }
        });
    }
});
