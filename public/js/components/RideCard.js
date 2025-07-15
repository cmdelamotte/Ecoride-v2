import { apiClient } from '../utils/apiClient.js';
import { createElement, clearChildren } from '../utils/domHelpers.js';

export class RideCard {
    constructor(rideData) {
        this.rideData = rideData;
        this.cardElement = this.createCardElement();
    }

    createCardElement() {
        const template = document.getElementById('ride-card-template');
        if (!template) return null;

        const card = template.content.cloneNode(true).querySelector('.ride-card');
        
        // Remplissage des données de base
        const driverPhotoEl = card.querySelector('.driver-profile-photo');
        if (driverPhotoEl && this.rideData.driver_photo) {
            driverPhotoEl.src = this.rideData.driver_photo;
            driverPhotoEl.alt = `Photo de ${this.rideData.driver_username}`;
        } else if (driverPhotoEl) {
            driverPhotoEl.src = "/img/default-profile.png"; // Chemin par défaut
            driverPhotoEl.alt = 'Photo de profil par défaut';
        }
        card.querySelector('.driver-username').textContent = this.rideData.driver_username;
        // Note: Le rating du driver n'est pas dans la recherche initiale, il sera chargé avec les détails
        card.querySelector('.ride-departure-location').textContent = this.rideData.departure_city;
        card.querySelector('.ride-arrival-location').textContent = this.rideData.arrival_city;
        
        // Formatage des dates et heures
        const departureTimeEl = card.querySelector('.ride-departure-time');
        if (departureTimeEl && this.rideData.departure_time) {
            const depDate = new Date(this.rideData.departure_time.replace(' ', 'T'));
            departureTimeEl.textContent = `${depDate.toLocaleDateString([], {day:'2-digit', month:'2-digit', year:'numeric'})} ${depDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
        }

        // Calcul et affichage de la durée estimée
        const estimatedDurationEl = card.querySelector('.ride-estimated-duration');
        if (estimatedDurationEl && this.rideData.departure_time && this.rideData.estimated_arrival_time) {
            const departure = new Date(this.rideData.departure_time.replace(' ', 'T'));
            const arrival = new Date(this.rideData.estimated_arrival_time.replace(' ', 'T'));
            const durationMs = arrival - departure;
            if (durationMs > 0) {
                const hours = Math.floor(durationMs / (1000 * 60 * 60));
                const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
                estimatedDurationEl.textContent = `${hours}h${minutes < 10 ? '0' : ''}${minutes}`;
            } else { estimatedDurationEl.textContent = "N/A"; }
        } else if (estimatedDurationEl) { estimatedDurationEl.textContent = "N/A"; }

        card.querySelector('.ride-price').textContent = `${this.rideData.price_per_seat} crédits`;
        card.querySelector('.ride-available-seats').textContent = this.rideData.seats_available;

        // Gérer le badge écologique
        const ecoCheckbox = card.querySelector('input.is-ride-eco');
        const ecoLabel = card.querySelector('label.is-ride-eco');
        if (ecoCheckbox) {
            const newEcoId = `ecoCheck_ride_${this.rideData.ride_id}`;
            ecoCheckbox.id = newEcoId;
            if (ecoLabel) ecoLabel.setAttribute('for', newEcoId);
            ecoCheckbox.checked = this.rideData.is_eco_ride || false;
            ecoCheckbox.disabled = true;
            const ecoCheckWrapper = ecoCheckbox.closest('.form-check');
            if (ecoCheckWrapper) ecoCheckWrapper.style.display = this.rideData.is_eco_ride ? 'inline-block' : 'none';
        }

        // Gérer le bouton détails et le collapse
        const detailsButton = card.querySelector('.ride-details-button');
        const collapseElement = card.querySelector('.collapse');
        if (collapseElement) {
            const newCollapseId = `detailsCollapse_ride_${this.rideData.ride_id}`;
            collapseElement.id = newCollapseId;
            if (detailsButton) {
                detailsButton.setAttribute('data-bs-target', `#${newCollapseId}`);
                detailsButton.setAttribute('aria-controls', newCollapseId);
                detailsButton.setAttribute('data-ride-id', this.rideData.ride_id);
                // Ajouter l'écouteur d'événement pour charger les détails
                detailsButton.addEventListener('click', () => this.loadDetails());
            }
        }

        // Remplissage des adresses détaillées (initialement vides, remplies par les détails)
        card.querySelector('.ride-departure-address-details').textContent = this.rideData.departure_address || '';
        card.querySelector('.ride-arrival-address-details').textContent = this.rideData.arrival_address || '';

        return card;
    }

    async loadDetails() {
        const loadingMessageEl = this.cardElement.querySelector('.loading-details-message');
        const errorMessageEl = this.cardElement.querySelector('.error-details-message');
        const contentWrapperEl = this.cardElement.querySelector('.ride-details-content-wrapper');

        if (!loadingMessageEl || !errorMessageEl || !contentWrapperEl) {
            console.error("Éléments DOM de détails manquants dans le template.");
            return;
        }

        // Afficher l'indicateur de chargement, masquer le contenu et les erreurs
        loadingMessageEl.classList.remove('d-none');
        errorMessageEl.classList.add('d-none');
        contentWrapperEl.classList.add('d-none');

        try {
            const response = await apiClient.getRideDetails(this.rideData.ride_id);
            console.log("API Response for ride details:", response);

            if (response.success && response.details) {
                const details = response.details;
                
                // Remplir les informations du véhicule
                const vehicleInfoContainer = this.cardElement.querySelector('.vehicle-info-container');
                if (vehicleInfoContainer) {
                    const carModelEl = vehicleInfoContainer.querySelector('.ride-car-model');
                    if (carModelEl) carModelEl.textContent = `${details.vehicle_brand_name || ''} ${details.vehicle_model || ''}`.trim();
                    
                    const carRegYearEl = vehicleInfoContainer.querySelector('.ride-car-registration-year');
                    if (carRegYearEl) carRegYearEl.textContent = details.vehicle_registration_date ? details.vehicle_registration_date.substring(0, 4) : 'N/A';
                    
                    const carEnergyEl = vehicleInfoContainer.querySelector('.ride-car-energy');
                    if (carEnergyEl) carEnergyEl.textContent = details.vehicle_energy_type || 'N/A';
                }

                // Remplir les préférences du conducteur
                const prefsContainer = this.cardElement.querySelector('.driver-preferences-text');
                const noPrefsMsg = this.cardElement.querySelector('.no-prefs-message');
                if (prefsContainer) {
                    clearChildren(prefsContainer);
                    let hasPref = false;
                    if (details.driver_pref_smoker !== undefined) { hasPref = true; prefsContainer.appendChild(createElement('p', ['mb-1'], {}, details.driver_pref_smoker ? 'Accepte les fumeurs' : 'Non-fumeur')); }
                    if (details.driver_pref_animals !== undefined) { hasPref = true; prefsContainer.appendChild(createElement('p', ['mb-1'], {}, details.driver_pref_animals ? 'Accepte les animaux' : 'N’accepte pas les animaux')); }
                    if (details.driver_pref_custom && details.driver_pref_custom.trim() !== '') { hasPref = true; prefsContainer.appendChild(createElement('p', ['mb-1'], {}, details.driver_pref_custom)); }
                    if (!hasPref && noPrefsMsg) noPrefsMsg.classList.remove('d-none'); else if (noPrefsMsg) noPrefsMsg.classList.add('d-none');
                }

                // Remplir les avis
                const reviewsContainer = this.cardElement.querySelector('.driver-reviews-container');
                const reviewTemplate = document.getElementById('driver-review-item-template');
                if (reviewsContainer && reviewTemplate) {
                    clearChildren(reviewsContainer);
                    reviewsContainer.appendChild(createElement('h5', ['mb-2', 'form-label'], {}, 'Avis sur le conducteur'));
                    if (details.reviews && details.reviews.length > 0) {
                        details.reviews.forEach(review => {
                            const reviewClone = reviewTemplate.content.cloneNode(true);
                            const authorEl = reviewClone.querySelector('.review-author');
                            if (authorEl) authorEl.textContent = review.author_username || "Utilisateur";
                            const dateEl = reviewClone.querySelector('.review-date');
                            if (dateEl) dateEl.textContent = new Date(review.submission_date.replace(' ', 'T')).toLocaleDateString('fr-FR');
                            const starsEl = reviewClone.querySelector('.review-stars');
                            if (starsEl) { const stars = parseInt(review.rating, 10); starsEl.innerHTML = '★'.repeat(stars) + '☆'.repeat(5 - stars); }
                            const commentEl = reviewClone.querySelector('.review-comment');
                            if (commentEl) commentEl.textContent = review.comment || "";
                            reviewsContainer.appendChild(reviewClone);
                        });
                    } else {
                        reviewsContainer.appendChild(createElement('p', [], {}, 'Aucun avis pour ce conducteur.'));
                    }
                }
                contentWrapperEl.classList.remove('d-none'); // Afficher le contenu
            } else {
                errorMessageEl.textContent = response.message || 'Impossible de charger les détails.';
                errorMessageEl.classList.remove('d-none');
            }
        } catch (error) {
            console.error("Erreur lors du chargement des détails du trajet:", error);
            errorMessageEl.textContent = 'Erreur de communication lors du chargement des détails.';
            errorMessageEl.classList.remove('d-none');
        } finally {
            loadingMessageEl.classList.add('d-none'); // Masquer l'indicateur de chargement
        }
    }

    get element() {
        return this.cardElement;
    }
}
