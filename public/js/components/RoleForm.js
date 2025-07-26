/**
 * RoleForm.js
 * Gère la logique du formulaire de sélection du rôle utilisateur.
 */

import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';

export class RoleForm {
    constructor(formSelector, driverInfoSection) {
        this.form = document.querySelector(formSelector);
        this.driverInfoSection = driverInfoSection;
        this.initEventListeners();
    }

    initEventListeners() {
        if (this.form) {
            this.form.addEventListener('submit', this.handleSubmit.bind(this));
        }
    }

    async handleSubmit(event) {
        event.preventDefault();

        const formData = new FormData(this.form);
        const selectedRole = formData.get('user_role_form');
        const submitButton = this.form.querySelector('button[type="submit"]');

        submitButton.disabled = true;
        submitButton.textContent = 'Enregistrement...';

        try {
            const result = await apiClient.updateUserRole(selectedRole);

            if (result.success) {
                displayFlashMessage(result.message, 'success');
                // Recharger la page pour que la navbar reflète les nouveaux rôles de session
                window.location.reload();
            } else {
                displayFlashMessage(result.error, 'danger');
            }
        } catch (error) {
            displayFlashMessage('Une erreur de communication est survenue.', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Enregistrer mon rôle';
        }
    }
}
