/**
 * PreferencesForm.js
 * Gère la logique du formulaire des préférences de conduite.
 */

import { apiClient } from '../utils/apiClient.js';

export class PreferencesForm {
    constructor(formSelector) {
        this.form = document.querySelector(formSelector);
        this.initEventListeners();
    }

    initEventListeners() {
        if (this.form) {
            this.form.addEventListener('submit', this.handleSubmit.bind(this));
        }
    }

    async handleSubmit(event) {
        event.preventDefault();

        const prefSmoker = document.getElementById('pref-smoker').checked;
        const prefAnimals = document.getElementById('pref-animals').checked;
        const prefMusic = document.getElementById('pref-music') ? document.getElementById('pref-music').checked : false; // Gérer le cas où la checkbox n'existe pas
        const prefCustom = document.getElementById('pref-custom').value.trim();

        const submitButton = this.form.querySelector('button[type="submit"]');

        submitButton.disabled = true;
        submitButton.textContent = 'Enregistrement...';

        try {
            const result = await apiClient.updateDriverPreferences({
                pref_smoker: prefSmoker,
                pref_animals: prefAnimals,
                pref_music: prefMusic,
                pref_custom: prefCustom
            });

            if (result.success) {
                alert(result.message);
            } else {
                alert('Erreur : ' + result.error);
            }
        } catch (error) {
            alert('Une erreur de communication est survenue.');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Enregistrer les Préférences';
        }
    }
}
