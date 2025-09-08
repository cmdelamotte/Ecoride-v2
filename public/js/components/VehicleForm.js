/**
 * VehicleForm.js
 * Gère toute la logique liée au formulaire d'ajout/modification de véhicule.
 */

import { apiClient } from '../utils/apiClient.js';
import { getFormData, displayFormErrors } from '../utils/formHelpers.js';
import { clearChildren, createElement } from '../utils/domHelpers.js';

export class VehicleForm {
    constructor(containerSelector, addBtnSelector, vehicleListElement) {
        this.container = document.querySelector(containerSelector);
        this.addBtn = document.querySelector(addBtnSelector);
        this.vehicleListElement = vehicleListElement; // Pour recharger la liste après succès

        this.form = this.container.querySelector('#vehicle-form');
        this.titleElement = this.container.querySelector('#vehicle-form-title');
        this.editingIdInput = this.container.querySelector('#editing-vehicle-id');
        this.brandSelect = this.container.querySelector('#vehicle-brand-select');
        this.cancelBtn = this.container.querySelector('#cancel-vehicle-form-btn');
        this.saveBtn = this.container.querySelector('#save-vehicle-btn');

        this.initEventListeners();
        this.populateBrandSelect();
    }

    initEventListeners() {
        if (this.addBtn) {
            this.addBtn.addEventListener('click', () => this.show(false));
        }
        if (this.cancelBtn) {
            this.cancelBtn.addEventListener('click', () => this.hide());
        }
        if (this.form) {
            this.form.addEventListener('submit', this.handleSubmit.bind(this));
        }
    }

    async populateBrandSelect() {
        if (!this.brandSelect) return;

        clearChildren(this.brandSelect);
        this.brandSelect.appendChild(createElement('option', [], { value: "", selected: true, disabled: true }, 'Chargement des marques...'));

        try {
            const response = await apiClient.getBrands();
            clearChildren(this.brandSelect);

            if (response.success && response.brands && response.brands.length > 0) {
                this.brandSelect.appendChild(createElement('option', [], { value: "", selected: true, disabled: true }, 'Sélectionnez une marque...'));
                response.brands.forEach(brand => {
                    this.brandSelect.appendChild(createElement('option', [], { value: brand.id }, brand.name));
                });
            } else {
                this.brandSelect.appendChild(createElement('option', [], { value: "", selected: true, disabled: true }, 'Aucune marque disponible'));
            }
        } catch (error) {
            clearChildren(this.brandSelect);
            this.brandSelect.appendChild(createElement('option', [], { value: "", selected: true, disabled: true }, 'Erreur chargement marques'));
        }
    }

    show(isEditing = false, vehicleData = null) {
        if (!this.container || !this.form || !this.titleElement || !this.editingIdInput || !this.addBtn || !this.brandSelect) return;

        this.form.reset();
        this.form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        this.form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        if (isEditing && vehicleData) {
            this.titleElement.textContent = "Modifier le Véhicule";
            this.editingIdInput.value = vehicleData.id || "";
            this.brandSelect.value = vehicleData.brand_id || "";
            document.getElementById('vehicle-model').value = vehicleData.model_name || '';
            document.getElementById('vehicle-color').value = vehicleData.color || '';
            document.getElementById('vehicle-license-plate').value = vehicleData.license_plate || '';
            document.getElementById('vehicle-registration-date').value = vehicleData.registration_date || '';
            document.getElementById('vehicle-seats').value = vehicleData.passenger_capacity || '';
            document.getElementById('vehicle-electric').checked = vehicleData.is_electric || false;
            const energyTypeEl = document.getElementById('vehicle-energy-type');
            if (energyTypeEl) energyTypeEl.value = vehicleData.energy_type || '';
        } else {
            this.titleElement.textContent = "Ajouter un Véhicule";
            this.editingIdInput.value = "";
            this.brandSelect.value = "";
            const energyTypeEl = document.getElementById('vehicle-energy-type');
            if (energyTypeEl) energyTypeEl.value = '';
            const electricEl = document.getElementById('vehicle-electric');
            if (electricEl) electricEl.checked = false;
        }
        this.container.classList.remove('d-none');
        this.addBtn.classList.add('d-none');
        this.brandSelect.focus();
    }

    hide() {
        if (!this.container || !this.addBtn) return;
        this.container.classList.add('d-none');
        this.addBtn.classList.remove('d-none');
    }

    async handleSubmit(event) {
        event.preventDefault();

        this.saveBtn.disabled = true;
        this.saveBtn.textContent = 'Enregistrement...';

        const vehicleData = {
            brand_id: parseInt(document.getElementById('vehicle-brand-select').value),
            model: document.getElementById('vehicle-model').value,
            color: document.getElementById('vehicle-color').value,
            license_plate: document.getElementById('vehicle-license-plate').value,
            registration_date: document.getElementById('vehicle-registration-date').value,
            passenger_capacity: parseInt(document.getElementById('vehicle-seats').value),
            is_electric: document.getElementById('vehicle-electric').checked,
            energy_type: document.getElementById('vehicle-energy-type') ? document.getElementById('vehicle-energy-type').value : ''
        };

        const editingId = this.editingIdInput.value;
        let result;

        try {
            if (editingId) {
                result = await apiClient.updateVehicle(editingId, vehicleData);
            } else {
                // Pour l'ajout, l'userId est géré côté PHP via la session
                result = await apiClient.addVehicle(vehicleData);
            }

            if (result.success) {
                alert(result.message);
                this.hide();
                // Déclencher un événement personnalisé pour que la page mette à jour la liste
                this.vehicleListElement.dispatchEvent(new CustomEvent('vehicleUpdated'));
            } else {
                if (result.errors) {
                    displayFormErrors(result.errors, this.form);
                } else {
                    alert(result.error || 'Une erreur est survenue.');
                }
            }
        } catch (error) {
            alert('Une erreur de communication est survenue. Veuillez vérifier votre connexion ou réessayer.');
        } finally {
            this.saveBtn.disabled = false;
            this.saveBtn.textContent = 'Enregistrer Véhicule';
        }
    }
}
