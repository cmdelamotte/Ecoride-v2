/**
 * RoleForm.js
 * Gère la logique du formulaire de sélection du rôle utilisateur.
 */

import { apiClient } from '../utils/apiClient.js';

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
                alert(result.message);
                if (this.driverInfoSection) {
                    if (result.new_functional_role === 'driver' || result.new_functional_role === 'passenger_driver') {
                        this.driverInfoSection.classList.remove('d-none');
                    } else {
                        this.driverInfoSection.classList.add('d-none');
                    }
                }
            } else {
                alert('Erreur : ' + result.error);
            }
        } catch (error) {
            alert('Une erreur de communication est survenue.');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Enregistrer mon rôle';
        }
    }
}
