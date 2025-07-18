import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js'; // <-- IMPORT

/**
 * BookingHandler.js
 * 
 * Gère toute la logique de la modale de confirmation de réservation et l'appel API.
 * Ce module est conçu pour être autonome et réutilisable.
 */

// Sélection des éléments du DOM de la modale une seule fois
const confirmationModalEl = document.getElementById('confirmationModal');
const departureLocationEl = document.getElementById('modal-ride-departure-location');
const arrivalLocationEl = document.getElementById('modal-ride-arrival-location');
const dateEl = document.getElementById('modal-ride-date-text');
const timeEl = document.getElementById('modal-ride-time-text');
const creditsCostEl = document.getElementById('modal-ride-credits-cost');
const confirmBookingBtn = document.getElementById('confirm-booking-btn');

let currentRideId = null;

/**
 * Prépare et affiche la modale de confirmation avec les détails du trajet.
 * @param {object} rideData - Les données du trajet à afficher.
 */
function populateAndShowModal(rideData) {
    currentRideId = rideData.ride_id; // Correction : Utiliser ride_id au lieu de id

    // Remplissage des informations de la modale
    departureLocationEl.textContent = rideData.departure_city || 'N/A';
    arrivalLocationEl.textContent = rideData.arrival_city || 'N/A';
    creditsCostEl.textContent = rideData.price_per_seat || 'N/A';

    // Formatage de la date et de l'heure
    if (rideData.departure_time) {
        const departureDate = new Date(rideData.departure_time);
        dateEl.textContent = departureDate.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
        timeEl.textContent = departureDate.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    } else {
        dateEl.textContent = 'N/A';
        timeEl.textContent = 'N/A';
    }

    // Le data-bs-toggle="modal" gère déjà l'affichage, cette fonction ne fait que peupler les données.
}

/**
 * Gère le clic sur le bouton de confirmation final.
 * Appelle l'API et gère la réponse.
 */
async function handleConfirmBooking() {
    if (!currentRideId) {
        alert('Erreur : ID du trajet non trouvé.');
        return;
    }

    // Désactiver le bouton pour éviter les doubles clics
    confirmBookingBtn.disabled = true;
    confirmBookingBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Confirmation...';

    try {
        const response = await apiClient.bookRide(currentRideId);

        // Cacher la modale via l'API de Bootstrap
        const modal = bootstrap.Modal.getInstance(confirmationModalEl);
        modal.hide();

        if (response.success) {
            displayFlashMessage(response.message || 'Réservation réussie !', 'success');
            // Optionnel : rediriger ou rafraîchir une partie de la page
            setTimeout(() => {
                window.location.href = '/your-rides'; // Laisser le temps au message de s'afficher
            }, 1500); // 1.5 secondes
        } else {
            displayFlashMessage(response.message || 'Une erreur est survenue.', 'danger');
        }

    } catch (error) {
        console.error('Erreur lors de la réservation:', error);
        displayFlashMessage('Une erreur de communication est survenue. Veuillez réessayer.', 'danger');
    } finally {
        // Réactiver le bouton
        confirmBookingBtn.disabled = false;
        confirmBookingBtn.innerHTML = 'Confirmer et utiliser les crédits';
    }
}

/**
 * Initialise le gestionnaire de réservation.
 * Ajoute les écouteurs d'événements nécessaires.
 */
export function initBookingHandler() {
    // Écouteur sur le bouton de confirmation final dans la modale
    confirmBookingBtn.addEventListener('click', handleConfirmBooking);

    // Bootstrap déclenche des événements sur la modale. Nous pouvons les utiliser
    // pour savoir quand la modale est sur le point de s'afficher.
    confirmationModalEl.addEventListener('show.bs.modal', function (event) {
        // event.relatedTarget est le bouton qui a déclenché la modale
        const button = event.relatedTarget;
        
        // Récupérer les données du trajet stockées sur le bouton ou sa carte parente
        const rideCard = button.closest('.ride-card');
        if (rideCard && rideCard.dataset.ride) {
            const rideData = JSON.parse(rideCard.dataset.ride);
            populateAndShowModal(rideData);
        } else {
            console.error("Impossible de récupérer les données du trajet pour la modale.");
            // Empêcher la modale de s'ouvrir si les données sont manquantes
            event.preventDefault(); 
        }
    });
}
