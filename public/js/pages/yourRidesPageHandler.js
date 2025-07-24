import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';
import { createElement, clearChildren } from '../utils/domHelpers.js';
import { Pagination } from '../components/Pagination.js';
import { ReviewModalHandler } from '../components/reviewModalHandler.js';

// Sélecteurs des conteneurs de trajets
const upcomingRidesContainer = document.querySelector('#upcoming-rides .rides-list-container');
const pastRidesContainer = document.querySelector('#past-rides .rides-list-container');
const allRidesContainer = document.querySelector('#all-rides .rides-list-container');
const noRidesMessage = document.getElementById('no-rides-message');

// Sélecteurs des conteneurs de pagination
const upcomingPaginationContainer = document.querySelector('#upcoming-rides-pagination');
const pastPaginationContainer = document.querySelector('#past-rides-pagination');
const allPaginationContainer = document.querySelector('#all-rides-pagination');

// Instances
let upcomingPagination;
let pastPagination;
let allPagination;
let reviewModalHandler;
let ridesCache = new Map();

// Template pour les cartes de trajet
const rideCardTemplate = document.getElementById('ride-card-template');

// Variables pour l'état de la pagination
const RIDES_PER_PAGE = 5;
let currentUpcomingPage = 1;
let currentPastPage = 1;
let currentAllPage = 1;

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

const createRideCard = (ride) => {
    const card = rideCardTemplate.content.cloneNode(true);

    card.querySelector('.ride-id').textContent = `#${ride.ride_id}`;
    card.querySelector('.ride-title').textContent = `${ride.departure_city} → ${ride.arrival_city}`;
    card.querySelector('.ride-date').textContent = new Date(ride.departure_time).toLocaleDateString('fr-FR');
    card.querySelector('.ride-time').textContent = new Date(ride.departure_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    card.querySelector('.ride-duration').textContent = calculateDuration(ride.departure_time, ride.estimated_arrival_time);
    card.querySelector('.ride-vehicle-details').textContent = `${ride.vehicle_brand_name} ${ride.vehicle_model}`;
    
    const priceLabelEl = card.querySelector('.price-label');
    const ridePriceAmountEl = card.querySelector('.ride-price-amount');

    if (ride.driver_id === currentUserId) {
        priceLabelEl.textContent = 'Gain estimé : ';
        let totalEstimatedGain = ride.estimated_earnings_per_passenger * ride.passengers_count;
        ridePriceAmountEl.textContent = `${totalEstimatedGain.toFixed(2)}`;
    } else {
        priceLabelEl.textContent = 'Prix payé : ';
        ridePriceAmountEl.textContent = `${ride.price_per_seat} crédits`;
    }

    if (ride.driver_id !== currentUserId && ride.seats_booked_by_user) {
        const seatsBookedEl = createElement('p', ['card-text', 'mb-1'], {}, `Sièges réservés : ${ride.seats_booked_by_user}`);
        card.querySelector('.role-specific-info').appendChild(seatsBookedEl);
    }
    card.querySelector('.ride-status-text').textContent = ride.ride_status;

    const passengersCurrentEl = card.querySelector('.ride-passengers-current');
    const passengersMaxEl = card.querySelector('.ride-passengers-max');
    if (passengersCurrentEl && passengersMaxEl) {
        passengersCurrentEl.textContent = ride.passengers_count;
        passengersMaxEl.textContent = ride.seats_offered;
    }

    const ecoBadge = card.querySelector('.ride-eco-badge');
    if (ride.is_eco_ride) {
        ecoBadge.classList.remove('d-none');
    } else {
        ecoBadge.classList.add('d-none');
    }

    const rideActionsContainer = card.querySelector('.ride-actions');
    clearChildren(rideActionsContainer);

    if (ride.ride_status === 'planned') {
        if (ride.driver_id === currentUserId) {
            const startButton = createElement('button', ['btn', 'primary-btn', 'btn-sm', 'mb-1', 'w-100', 'action-start-ride'], { 'data-ride-id': ride.ride_id }, 'Démarrer le trajet');
            const cancelButton = createElement('button', ['btn', 'btn-outline-danger', 'btn-sm', 'w-100', 'action-cancel-ride-driver'], { 'data-ride-id': ride.ride_id }, 'Annuler ce trajet');
            rideActionsContainer.appendChild(startButton);
            rideActionsContainer.appendChild(cancelButton);
        } else {
            const cancelButton = createElement('button', ['btn', 'btn-outline-danger', 'btn-sm', 'w-100', 'action-cancel-booking'], { 'data-ride-id': ride.ride_id }, 'Annuler ma réservation');
            rideActionsContainer.appendChild(cancelButton);
        }
    } else if (ride.ride_status === 'ongoing' && ride.driver_id === currentUserId) {
        const finishButton = createElement('button', ['btn', 'primary-btn', 'btn-sm', 'mb-1', 'w-100', 'action-finish-ride'], { 'data-ride-id': ride.ride_id }, 'Arrivée à destination');
        rideActionsContainer.appendChild(finishButton);
    } else if (ride.ride_status === 'completed' && ride.driver_id !== currentUserId) {
        const reviewButton = createElement('button', ['btn', 'secondary-btn', 'btn-sm', 'w-100', 'action-leave-review'], { 'data-ride-id': ride.ride_id }, 'Laisser un avis');
        rideActionsContainer.appendChild(reviewButton);
    }

    return card;
};

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

const handleRideAction = async (event) => {
    const target = event.target;
    const actionButton = target.closest('button[data-ride-id]'); 
    if (!actionButton) return;

    const rideId = actionButton.getAttribute('data-ride-id');
    if (!rideId) {
        console.error("handleRideAction: rideId manquant sur le bouton d'action.");
        return;
    }

    if (actionButton.classList.contains('action-leave-review')) {
        event.preventDefault(); // Empêche l'action par défaut du bouton
        const rideData = ridesCache.get(rideId);
        if (rideData) {
            reviewModalHandler.open(rideData);
        } else {
            console.error(`Impossible de trouver les données du trajet ${rideId} pour laisser un avis.`);
            displayFlashMessage("Une erreur est survenue. Impossible d'ouvrir le formulaire d'avis.", 'danger');
        }
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

    actionButton.disabled = true;

    try {
        const response = await apiCallPromise;

        if (response.success) {
            displayFlashMessage(response.message, 'success');
            const activeTab = document.querySelector('#ridesTabs button.active');
            if (activeTab) {
                const tabType = activeTab.id.replace('-rides-tab', '');
                let currentPage;
                switch (tabType) {
                    case 'upcoming': currentPage = currentUpcomingPage; break;
                    case 'past': currentPage = currentPastPage; break;
                    case 'all': currentPage = currentAllPage; break;
                    default: currentPage = 1;
                }
                loadUserRides(tabType, currentPage);
            }
        } else {
            displayFlashMessage(response.message || `Erreur lors de l'action (statut ${response.status}).`, 'danger');
        }

    } catch (error) {
        console.error(`Erreur Fetch globale (action sur trajet ${rideId}):`, error);
        displayFlashMessage("Erreur de communication avec le serveur : " + error.message, 'danger');
    } finally {
        actionButton.disabled = false;
    }
};

const loadUserRides = async (type, page) => {
    noRidesMessage.classList.add('d-none');

    let container, paginationInstance, currentPageVar;

    switch (type) {
        case 'upcoming':
            container = upcomingRidesContainer;
            paginationInstance = upcomingPagination;
            currentPageVar = 'currentUpcomingPage';
            break;
        case 'past':
            container = pastRidesContainer;
            paginationInstance = pastPagination;
            currentPageVar = 'currentPastPage';
            break;
        case 'all':
            container = allRidesContainer;
            paginationInstance = allPagination;
            currentPageVar = 'currentAllPage';
            break;
        default:
            console.error('Type de trajet inconnu:', type);
            return;
    }

    clearChildren(container);
    container.appendChild(createElement('p', ['text-center', 'text-muted'], {}, 'Chargement des trajets...'));

    try {
        const response = await apiClient.getUserRides(type, page, RIDES_PER_PAGE);

        if (response.success) {
            window[currentPageVar] = page;

            if (response.rides && response.rides.length > 0) {
                response.rides.forEach(ride => ridesCache.set(ride.ride_id.toString(), ride));
            }

            displayRides(container, response.rides);
            
            if (paginationInstance) {
                paginationInstance.render(response.pagination.current_page, response.pagination.total_pages);
            }

        } else {
            displayFlashMessage(response.message || `Erreur lors du chargement des trajets ${type}.`, 'danger');
            container.appendChild(createElement('p', ['text-center', 'text-danger'], {}, 'Erreur lors du chargement des trajets.'));
        }

    } catch (error) {
        console.error(`Erreur lors du chargement des trajets ${type}:`, error);
        displayFlashMessage('Une erreur de communication est survenue lors du chargement de vos trajets.', 'danger');
        container.appendChild(createElement('p', ['text-center', 'text-danger'], {}, 'Erreur de communication.'));
    } finally {
        updateNoRidesMessage();
    }
};

const updateNoRidesMessage = async () => {
    try {
        const response = await apiClient.getUserRides('all', 1, 1);
        if (response.success && response.pagination.total_rides === 0) {
            noRidesMessage.classList.remove('d-none');
        } else {
            noRidesMessage.classList.add('d-none');
        }
    } catch (error) {
        console.error("Erreur lors de la mise à jour du message 'aucun trajet':", error);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    reviewModalHandler = new ReviewModalHandler(currentUserId);
    console.log('DOMContentLoaded: reviewModalHandler', reviewModalHandler);
    console.log('DOMContentLoaded: typeof reviewModalHandler.open', typeof reviewModalHandler.open);

    upcomingPagination = new Pagination('#upcoming-rides-pagination', (page) => loadUserRides('upcoming', page));
    pastPagination = new Pagination('#past-rides-pagination', (page) => loadUserRides('past', page));
    allPagination = new Pagination('#all-rides-pagination', (page) => loadUserRides('all', page));

    loadUserRides('upcoming', currentUpcomingPage);

    updateNoRidesMessage();

    const ridesHistorySection = document.querySelector('.rides-history-section');
    if (ridesHistorySection) {
        ridesHistorySection.addEventListener('click', handleRideAction);
    }

    const rideTabs = document.querySelectorAll('#ridesTabs button[data-bs-toggle="tab"]');
    rideTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (event) {
            const tabType = event.target.id.replace('-rides-tab', '');
            let currentPage;
            switch (tabType) {
                case 'upcoming': currentPage = currentUpcomingPage; break;
                case 'past': currentPage = currentPastPage; break;
                case 'all': currentPage = currentAllPage; break;
                default: currentPage = 1;
            }
            loadUserRides(tabType, currentPage);
        });
    });
});