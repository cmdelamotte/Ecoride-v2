import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';
import { setFormLoadingState, resetFormValidation } from '../utils/formHelpers.js';

/**
 * Gère la logique de la modale de soumission d'avis.
 */
export class ReviewModalHandler {
    constructor(currentUserId) {
        this.currentUserId = currentUserId;
        this.modalElement = document.getElementById('reviewModal');
        if (!this.modalElement) {
            console.error('Review modal element (#reviewModal) not found.');
            return;
        }
        this.modal = new bootstrap.Modal(this.modalElement);

        // Éléments du formulaire
        this.form = document.getElementById('submit-review-form');
        this.rideDetailsEl = document.getElementById('review-modal-ride-details');
        this.driverNameEl = document.getElementById('review-modal-driver-name');
        this.ratingStarsContainer = document.getElementById('review-rating-stars');
        this.ratingInput = document.getElementById('ratingValueHiddenInput');
        this.commentInput = document.getElementById('review-comment');
        this.ratingErrorMessage = document.getElementById('rating-error-message');

        // Données du trajet en cours
        this.currentRide = null;

        this._initListeners();
    }

    /**
     * Initialise les écouteurs d'événements pour la modale.
     * @private
     */
    _initListeners() {
        if (!this.form) return;

        this.form.addEventListener('submit', this._handleSubmit.bind(this));
        this.ratingStarsContainer.addEventListener('click', this._handleRatingClick.bind(this));
        
        // Accessibilité : permettre la notation avec le clavier
        this.ratingStarsContainer.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this._handleRatingClick(e);
            }
        });
    }

    /**
     * Ouvre la modale et la remplit avec les données du trajet.
     * @param {object} ride - L'objet contenant les détails du trajet.
     */
    open(ride) {
        this.currentRide = ride;
        this._resetForm();

        this.rideDetailsEl.textContent = `${ride.departure_city} → ${ride.arrival_city}`;
        this.driverNameEl.textContent = ride.driver_username || 'le conducteur';
        
        const ratingLabel = this.form.querySelector('label[for="review-rating-stars"]');
        if (ratingLabel) {
            ratingLabel.textContent = `Votre note pour ${this.driverNameEl.textContent} :`;
        }

        this.modal.show();
    }

    /**
     * Gère la sélection de la note via les étoiles.
     * @param {Event} e - L'événement de clic ou de clavier.
     * @private
     */
    _handleRatingClick(e) {
        const star = e.target.closest('[data-value]');
        if (!star) return;

        const rating = parseInt(star.dataset.value, 10);
        this.ratingInput.value = rating;

        // Mise à jour visuelle des étoiles
        const allStars = this.ratingStarsContainer.querySelectorAll('[data-value]');
        allStars.forEach(s => {
            s.classList.toggle('bi-star-fill', parseInt(s.dataset.value, 10) <= rating);
            s.classList.toggle('bi-star', parseInt(s.dataset.value, 10) > rating);
        });

        this.ratingErrorMessage.classList.add('d-none');
    }

    /**
     * Gère la soumission du formulaire d'avis.
     * @param {Event} e - L'événement de soumission.
     * @private
     */
    async _handleSubmit(e) {
        e.preventDefault();

        const rating = parseInt(this.ratingInput.value, 10);
        if (!rating || rating < 1 || rating > 5) {
            this.ratingErrorMessage.textContent = 'Veuillez sélectionner une note de 1 à 5.';
            this.ratingErrorMessage.classList.remove('d-none');
            return;
        }

        const reviewData = {
            ride_id: this.currentRide.ride_id,
            author_id: this.currentUserId, // Utilisation de this.currentUserId
            driver_id: this.currentRide.driver_id,
            rating: rating,
            comment: this.commentInput.value.trim()
        };

        setFormLoadingState(this.form, true, "Envoi de l'avis...");

        try {
            const response = await apiClient.submitReview(reviewData);
            
            if (response.success) {
                displayFlashMessage(response.message, 'success');
                this.modal.hide();
                
                // Marquer le bouton comme "Avis soumis" pour éviter une nouvelle soumission
                const reviewButton = document.querySelector(`.action-leave-review[data-ride-id="${this.currentRide.ride_id}"]`);
                if (reviewButton) {
                    reviewButton.textContent = 'Avis soumis';
                    reviewButton.disabled = true;
                }
            } else {
                displayFlashMessage(response.message || 'Une erreur est survenue.', 'danger');
            }
        } catch (error) {
            console.error("Erreur lors de la soumission de l'avis:", error);
            displayFlashMessage(error.message || 'Erreur de communication avec le serveur.', 'danger');
        } finally {
            setFormLoadingState(this.form, false);
        }
    }

    /**
     * Réinitialise l'état du formulaire.
     * @private
     */
    _resetForm() {
        this.form.reset();
        resetFormValidation(this.form);
        this.ratingInput.value = '';
        this.ratingErrorMessage.classList.add('d-none');

        // Réinitialise les étoiles
        const allStars = this.ratingStarsContainer.querySelectorAll('[data-value]');
        allStars.forEach(s => {
            s.classList.remove('bi-star-fill');
            s.classList.add('bi-star');
        });
    }
}
