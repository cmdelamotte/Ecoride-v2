/**
 * VehicleList.js
 * Gère l'affichage et les interactions avec la liste des véhicules.
 */

import { apiClient } from '../utils/apiClient.js';
import { clearChildren, createElement } from '../utils/domHelpers.js';

export class VehicleList {
    constructor(containerSelector) {
        this.container = document.querySelector(containerSelector);
        this.initEventListeners();
    }

    initEventListeners() {
        if (this.container) {
            this.container.addEventListener('click', this.handleClick.bind(this));
        }
    }

    async handleClick(event) {
        const target = event.target;
        const vehicleItem = target.closest('.vehicle-item');
        if (!vehicleItem) return;

        const vehicleId = vehicleItem.dataset.vehicleId;

        // Clic sur le bouton "Supprimer"
        if (target.classList.contains('delete-vehicle-btn')) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce véhicule ? Cette action est irréversible.')) {
                try {
                    const result = await apiClient.deleteVehicle(vehicleId);
                    if (result.success) {
                        alert(result.message);
                        vehicleItem.remove(); // Supprime l'élément de la liste
                        // Déclencher un événement personnalisé pour que la page mette à jour la liste
                        this.container.dispatchEvent(new CustomEvent('vehicleDeleted'));
                    } else {
                        alert(result.error || 'Erreur lors de la suppression du véhicule.');
                    }
                } catch (error) {
                    alert('Une erreur de communication est survenue.');
                }
            }
        }

        // Clic sur le bouton "Modifier"
        if (target.classList.contains('edit-vehicle-btn')) {
            // Récupérer les données complètes du véhicule à partir des data-attributes
            const vehicleData = {
                id: vehicleId,
                brand_id: parseInt(vehicleItem.dataset.brandId),
                model_name: vehicleItem.dataset.model,
                color: vehicleItem.dataset.color,
                license_plate: vehicleItem.dataset.plate,
                registration_date: vehicleItem.dataset.year,
                passenger_capacity: parseInt(vehicleItem.dataset.seats),
                is_electric: vehicleItem.dataset.isElectric === 'true'
            };
            // Émettre un événement pour que la page parente puisse afficher le formulaire d'édition
            this.container.dispatchEvent(new CustomEvent('editVehicleRequested', { detail: vehicleData }));
        }
    }

    /**
     * Affiche la liste des véhicules de l'utilisateur.
     * @param {Array} vehiclesData - Les données des véhicules.
     */
    render(vehiclesData) {
        if (!this.container) return;

        clearChildren(this.container);

        if (!vehiclesData || vehiclesData.length === 0) {
            this.container.appendChild(createElement('p', ['text-muted'], {}, 'Vous n\'avez pas encore de véhicule enregistré.'));
            return;
        }

        vehiclesData.forEach(vehicle => {
            const vehicleElement = createElement('div', ['vehicle-item', 'card', 'card-body', 'mb-2']);

            // Stocke les données complètes du véhicule dans les data-attributes pour l'édition future
            vehicleElement.setAttribute('data-vehicle-id', vehicle.id);
            vehicleElement.setAttribute('data-brand-id', vehicle.brand_id);
            vehicleElement.setAttribute('data-model', vehicle.model_name);
            vehicleElement.setAttribute('data-plate', vehicle.license_plate);
            vehicleElement.setAttribute('data-color', vehicle.color || "");
            vehicleElement.setAttribute('data-year', vehicle.registration_date);
            vehicleElement.setAttribute('data-seats', vehicle.passenger_capacity);
            vehicleElement.setAttribute('data-is-electric', vehicle.is_electric);

            // Création des éléments internes
            const p = createElement('p', ['mb-1']);

            p.appendChild(createElement('span', ['form-label'], {}, 'Marque :'));
            p.appendChild(document.createTextNode(' '));
            p.appendChild(createElement('span', ['vehicle-brand-display'], {}, vehicle.brand_name));
            p.appendChild(createElement('br'));

            p.appendChild(createElement('span', ['form-label'], {}, 'Modèle :'));
            p.appendChild(document.createTextNode(' '));
            p.appendChild(createElement('span', ['vehicle-model-display'], {}, vehicle.model_name));
            p.appendChild(createElement('br'));

            p.appendChild(createElement('span', ['form-label'], {}, 'Plaque :'));
            p.appendChild(document.createTextNode(' '));
            p.appendChild(createElement('span', ['vehicle-plate-display'], {}, vehicle.license_plate));

            const divButtons = createElement('div', ['mt-2']);

            const editButton = createElement('button', ['btn', 'btn-sm', 'btn-outline-secondary', 'edit-vehicle-btn'], { type: 'button' }, 'Modifier');
            const deleteButton = createElement('button', ['btn', 'btn-sm', 'btn-outline-danger', 'mt-1', 'mt-sm-0', 'ms-sm-1', 'delete-vehicle-btn'], { type: 'button' }, 'Supprimer');

            divButtons.appendChild(editButton);
            divButtons.appendChild(deleteButton);

            vehicleElement.appendChild(p);
            vehicleElement.appendChild(divButtons);

            this.container.appendChild(vehicleElement);
        });
    }
}
