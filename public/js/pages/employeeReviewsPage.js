import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';
import { createElement, clearChildren } from '../utils/domHelpers.js';

const reviewListContainer = document.querySelector('.review-list');
const noPendingReviewsMessage = document.getElementById('no-pending-reviews');
const pendingReviewCardTemplate = document.getElementById('pending-review-card-template');

/**
 * Crée et retourne un élément de carte d'avis en attente.
 * @param {object} reviewData Les données de l'avis.
 * @returns {HTMLElement} L'élément HTML de la carte.
 */
const createPendingReviewCard = (reviewData) => {
    const card = pendingReviewCardTemplate.content.cloneNode(true);

    card.querySelector('.review-id').textContent = `ID Avis: #${reviewData.review_id}`;
    card.querySelector('.review-passenger-name').textContent = reviewData.author_username;
    card.querySelector('.review-driver-name').textContent = reviewData.driver_username;
    card.querySelector('.review-comment-content').textContent = reviewData.comment || 'Aucun commentaire.';
    card.querySelector('.review-submitted-date').textContent = new Date(reviewData.review_created_at).toLocaleDateString('fr-FR');
    card.querySelector('.review-ride-id').textContent = reviewData.ride_id;
    card.querySelector('.review-ride-details').textContent = `${reviewData.departure_city} → ${reviewData.arrival_city} (${new Date(reviewData.departure_time).toLocaleDateString('fr-FR')})`;

    // Gérer les étoiles de notation
    const ratingStarsEl = card.querySelector('.review-rating-stars');
    const ratingTextEl = card.querySelector('.review-rating-text');
    if (ratingStarsEl && ratingTextEl) {
        const rating = reviewData.rating;
        ratingStarsEl.innerHTML = ''
        for (let i = 0; i < 5; i++) {
            const starClass = i < rating ? 'bi-star-fill text-warning' : 'bi-star';
            ratingStarsEl.appendChild(createElement('i', ['bi', ...starClass.split(' ')], {}));
        }
        ratingTextEl.textContent = `${rating}/5`;
    }

    // Attacher les IDs aux boutons pour les actions
    const validateButton = card.querySelector('.action-validate-review');
    const rejectButton = card.querySelector('.action-reject-review');
    if (validateButton) validateButton.dataset.reviewId = reviewData.review_id;
    if (rejectButton) rejectButton.dataset.reviewId = reviewData.review_id;

    return card;
};

/**
 * Charge et affiche les avis en attente de modération.
 */
const loadPendingReviews = async () => {
    clearChildren(reviewListContainer);
    noPendingReviewsMessage.classList.add('d-none');
    reviewListContainer.appendChild(createElement('p', ['text-center', 'text-muted'], {}, 'Chargement des avis...'));

    try {
        const response = await apiClient.getPendingReviews(); // Cette méthode doit être ajoutée à apiClient.js

        clearChildren(reviewListContainer);

        if (response.success && response.reviews.length > 0) {
            response.reviews.forEach(review => {
                reviewListContainer.appendChild(createPendingReviewCard(review));
            });
        } else {
            noPendingReviewsMessage.classList.remove('d-none');
        }
    } catch (error) {
        console.error('Erreur lors du chargement des avis en attente:', error);
        displayFlashMessage('Erreur lors du chargement des avis en attente.', 'danger');
        clearChildren(reviewListContainer);
        reviewListContainer.appendChild(createElement('p', ['text-center', 'text-danger'], {}, 'Impossible de charger les avis.'));
    }
};

/**
 * Gère les actions de validation/rejet d'avis.
 * @param {Event} event L'événement de clic.
 */
const handleReviewAction = async (event) => {
    const target = event.target;
    const reviewId = target.dataset.reviewId;
    if (!reviewId) return;

    let apiCallPromise = null;
    let successMessage = '';
    let errorMessage = '';

    if (target.classList.contains('action-validate-review')) {
        apiCallPromise = apiClient.approveReview(reviewId); // Cette méthode doit être ajoutée à apiClient.js
        successMessage = 'Avis validé avec succès.';
        errorMessage = "Erreur lors de la validation de l'avis.";
    } else if (target.classList.contains('action-reject-review')) {
        apiCallPromise = apiClient.rejectReview(reviewId); // Cette méthode doit être ajoutée à apiClient.js
        successMessage = 'Avis rejeté avec succès.';
        errorMessage = "Erreur lors du rejet de l'avis.";
    }

    if (!apiCallPromise) return;

    target.disabled = true;
    target.textContent = '...';

    try {
        const response = await apiCallPromise;
        if (response.success) {
            displayFlashMessage(successMessage, 'success');
            loadPendingReviews(); // Recharger la liste après l'action
        } else {
            displayFlashMessage(response.message || errorMessage, 'danger');
        }
    } catch (error) {
        console.error('Erreur API:', error);
        displayFlashMessage('Erreur de communication avec le serveur.', 'danger');
    } finally {
        target.disabled = false;
        target.textContent = target.classList.contains('action-validate-review') ? "Valider l'avis" : "Refuser l'avis";
    }
};

document.addEventListener('DOMContentLoaded', () => {
    loadPendingReviews();

    // Attacher l'écouteur d'événements pour les actions de modération
    if (reviewListContainer) {
        reviewListContainer.addEventListener('click', handleReviewAction);
    }
});
