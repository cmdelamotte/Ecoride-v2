import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';
import { createElement, clearChildren } from '../utils/domHelpers.js';

// Sélecteurs des conteneurs de trajets
const upcomingRidesContainer = document.querySelector('#upcoming-rides .rides-list-container');
const pastRidesContainer = document.querySelector('#past-rides .rides-list-container');
const allRidesContainer = document.querySelector('#all-rides .rides-list-container');
const noRidesMessage = document.getElementById('no-rides-message');

// Template pour les cartes de trajet
const rideCardTemplate = document.getElementById('ride-card-template');

/**
 * Calcule la durée entre deux dates/heures et la formate.
 * @param {string} start La date/heure de début (format ISO).
 * @param {string} end La date/heure de fin (format ISO).
 * @returns {string} La durée formatée (ex: "2h30") ou "N/A".
 */
const calculateDuration = (start, end) => {
    const departure = new Date(start.replace(' ', 'T'));
    const arrival = new Date(end.replace(' ', 'T'));
    const durationMs = arrival - departure;
    if (durationMs > 0) {
        const hours = Math.floor(durationMs / (1000 * 60 * 60));
        const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
        return `${hours}h${minutes < 10 ? '0' : ''}${minutes}`;
    }
    return "N/A";
};

/**
 * Crée un élément de carte de trajet à partir des données.
 * @param {object} ride Les données du trajet.
 * @returns {HTMLElement} L'élément HTML de la carte de trajet.
 */
const createRideCard = (ride) => {
    const card = rideCardTemplate.content.cloneNode(true);

    // Remplir les données de la carte
    card.querySelector('.ride-id').textContent = `#${ride.ride_id}`;
    card.querySelector('.ride-title').textContent = `${ride.departure_city} → ${ride.arrival_city}`;
    card.querySelector('.ride-date').textContent = new Date(ride.departure_time).toLocaleDateString('fr-FR');
    card.querySelector('.ride-time').textContent = new Date(ride.departure_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    card.querySelector('.ride-duration').textContent = calculateDuration(ride.departure_time, ride.estimated_arrival_time); // AJOUT
    card.querySelector('.ride-vehicle-details').textContent = `${ride.vehicle_brand_name} ${ride.vehicle_model}`; // AJOUT
    // Gérer le rôle (conducteur/passager) et l'affichage du prix
    const priceLabelEl = card.querySelector('.price-label');
    const ridePriceAmountEl = card.querySelector('.ride-price-amount');

    if (ride.driver_id === currentUserId) {
        // L'utilisateur est le conducteur de ce trajet
        priceLabelEl.textContent = 'Gain estimé : ';
        // Calculer le gain estimé (prix par siège * (sièges offerts - sièges disponibles))
        const estimatedGain = ride.price_per_seat * (ride.seats_offered - (ride.seats_available || 0));
        ridePriceAmountEl.textContent = `${estimatedGain.toFixed(2)} crédits`;
    } else {
        // L'utilisateur est un passager de ce trajet
        priceLabelEl.textContent = 'Prix payé : ';
        ridePriceAmountEl.textContent = `${ride.price_per_seat} crédits`;
    }

    // Afficher le nombre de sièges réservés si l'utilisateur est passager
    if (ride.driver_id !== currentUserId && ride.seats_booked_by_user) {
        const seatsBookedEl = createElement('p', ['card-text', 'mb-1'], {}, `Sièges réservés : ${ride.seats_booked_by_user}`);
        card.querySelector('.role-specific-info').appendChild(seatsBookedEl);
    }
    card.querySelector('.ride-status-text').textContent = ride.ride_status;

    // Gérer le badge éco
    const ecoBadge = card.querySelector('.ride-eco-badge');
    if (ride.is_eco_ride) {
        ecoBadge.classList.remove('d-none');
    } else {
        ecoBadge.classList.add('d-none');
    }

    // Gérer les actions (boutons annuler, noter, etc.)
    const rideActionsContainer = card.querySelector('.ride-actions');
    clearChildren(rideActionsContainer); // Vider le contenu existant de manière sécurisée

    // Assurez-vous que currentUserId est accessible globalement
    // (il est injecté via un script dans your-rides.php)

    if (ride.ride_status === 'planned') {
        if (ride.driver_id === currentUserId) { // Trajet planifié du conducteur
            const startButton = createElement('button', ['btn', 'primary-btn', 'btn-sm', 'mb-1', 'w-100', 'action-start-ride'], { 'data-ride-id': ride.ride_id }, 'Démarrer le trajet');
            const cancelButton = createElement('button', ['btn', 'btn-outline-danger', 'btn-sm', 'w-100', 'action-cancel-ride-driver'], { 'data-ride-id': ride.ride_id }, 'Annuler ce trajet');
            rideActionsContainer.appendChild(startButton);
            rideActionsContainer.appendChild(cancelButton);
        } else { // Trajet planifié du passager
            const cancelButton = createElement('button', ['btn', 'btn-outline-danger', 'btn-sm', 'w-100', 'action-cancel-booking'], { 'data-ride-id': ride.ride_id }, 'Annuler ma réservation');
            rideActionsContainer.appendChild(cancelButton);
        }
    } else if (ride.ride_status === 'ongoing' && ride.driver_id === currentUserId) { // Trajet en cours du conducteur
        const finishButton = createElement('button', ['btn', 'primary-btn', 'btn-sm', 'mb-1', 'w-100', 'action-finish-ride'], { 'data-ride-id': ride.ride_id }, 'Arrivée à destination');
        rideActionsContainer.appendChild(finishButton);
    }
    // Pour les trajets terminés ou annulés, aucun bouton d'action n'est nécessaire ici.

    return card;
};

/**
 * Affiche les trajets dans le conteneur spécifié.
 * @param {HTMLElement} container L'élément conteneur.
 * @param {Array} rides Le tableau de trajets.
 */
const displayRides = (container, rides) => {
    clearChildren(container);
    if (rides && rides.length > 0) {
        rides.forEach(ride => {
            container.appendChild(createRideCard(ride));
        });
    } else {
        container.appendChild(createElement('p', ['text-muted', 'text-center'], {}, 'Aucun trajet pour le moment.'));
    }
};

/**
 * Gère les actions sur les boutons de trajet (démarrer, terminer, annuler).
 * Utilise la délégation d'événements.
 */
const handleRideAction = async (event) => {
    const target = event.target;
    const actionButton = target.closest('button[data-ride-id]'); 
    if (!actionButton) return;

    const rideId = actionButton.getAttribute('data-ride-id');
    if (!rideId) {
        console.error("handleRideAction: rideId manquant sur le bouton d'action.");
        return;
    }

    let apiCallPromise = null;
    let confirmMessage = "Êtes-vous sûr de vouloir effectuer cette action ?";

    if (actionButton.classList.contains('action-start-ride')) {
        apiCallPromise = apiClient.startRide(rideId);
        confirmMessage = `Démarrer le trajet ID ${rideId} ?`;
    } else if (actionButton.classList.contains('action-finish-ride')) {
        apiCallPromise = apiClient.finishRide(rideId);
        confirmMessage = `Marquer le trajet ID ${rideId} comme terminé ?`;
    } else if (actionButton.classList.contains('action-cancel-ride-driver') || actionButton.classList.contains('action-cancel-booking')) {
        apiCallPromise = apiClient.cancelRide(rideId);
        if (actionButton.classList.contains('action-cancel-ride-driver')) {
            confirmMessage = `Annuler le trajet ID ${rideId} ? Les passagers seront remboursés.`;
        } else {
            confirmMessage = `Annuler votre réservation pour le trajet ID ${rideId} ? Vous serez remboursé.`;
        }
    }

    if (!apiCallPromise) {
        console.warn(`handleRideAction: Aucune API définie pour le bouton cliqué sur trajet ${rideId}. Action simulée ou à implémenter.`);
        return;
    }

    if (!confirm(confirmMessage)) {
        return;
    }

    actionButton.disabled = true; // Désactiver le bouton pendant l'appel

    try {
        const response = await apiCallPromise;

        if (response.success) {
            displayFlashMessage(response.message, 'success');
            loadUserRides(); // Recharger les trajets après action réussie
        } else {
            displayFlashMessage(response.message || `Erreur lors de l'action (statut ${response.status}).`, 'danger');
        }

    } catch (error) {
        console.error(`Erreur Fetch globale (action sur trajet ${rideId}):`, error);
        displayFlashMessage("Erreur de communication avec le serveur : " + error.message, 'danger');
    } finally {
        actionButton.disabled = false; // Réactiver le bouton
    }
};

/**
 * Charge et affiche les trajets de l'utilisateur.
 */
const loadUserRides = async () => {
    // Cacher le message 'aucun trajet' par défaut
    noRidesMessage.classList.add('d-none');

    // Afficher un indicateur de chargement si nécessaire
    // displayFlashMessage('Chargement de vos trajets...', 'info');

    try {
        // Récupérer tous les trajets
        const allRidesResponse = await apiClient.getUserRides('all');
        if (allRidesResponse.success) {
            displayRides(allRidesContainer, allRidesResponse.rides);
        } else {
            displayFlashMessage(allRidesResponse.message || 'Erreur lors du chargement de tous les trajets.', 'danger');
        }

        // Récupérer les trajets à venir
        const upcomingRidesResponse = await apiClient.getUserRides('upcoming');
        if (upcomingRidesResponse.success) {
            displayRides(upcomingRidesContainer, upcomingRidesResponse.rides);
        }

        // Récupérer les trajets passés
        const pastRidesResponse = await apiClient.getUserRides('past');
        if (pastRidesResponse.success) {
            displayRides(pastRidesContainer, pastRidesResponse.rides);
        }

        // Afficher le message 'aucun trajet' si toutes les listes sont vides
        if (allRidesResponse.rides.length === 0) {
            noRidesMessage.classList.remove('d-none');
        }

    } catch (error) {
        console.error("Erreur lors du chargement des trajets de l'utilisateur:", error);
        displayFlashMessage('Une erreur de communication est survenue lors du chargement de vos trajets.', 'danger');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    loadUserRides();

    // Listeners pour les actions et les onglets 
    const ridesHistorySection = document.querySelector('.rides-history-section');
    if (ridesHistorySection) {
        ridesHistorySection.addEventListener('click', handleRideAction);
    }

    const rideTabs = document.querySelectorAll('#ridesTabs button[data-bs-toggle="tab"]');
    rideTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (event) {
            const activeTabPaneId = event.target.getAttribute('data-bs-target'); 
            const activeTabPane = document.querySelector(activeTabPaneId);
            const noRidesMessageGlobal = document.getElementById('no-rides-message');

            if (noRidesMessageGlobal) {
                const hasContentInActiveTab = activeTabPane?.querySelector('.ride-card');
                const isCurrentRideVisible = !document.getElementById('current-ride-highlight')?.classList.contains('d-none');

                if (hasContentInActiveTab || isCurrentRideVisible) {
                    noRidesMessageGlobal.classList.add('d-none');
                } else {
                    const allRidesContainer = document.querySelector('#all-rides .rides-list-container');
                    if (allRidesContainer?.children.length === 0 && !isCurrentRideVisible) {
                        noRidesMessageGlobal.classList.remove('d-none');
                    } else {
                        noRidesMessageGlobal.classList.add('d-none'); 
                    }
                }
            }
        });
    });
});