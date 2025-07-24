/**
 * yourRidesPageHandler.js
 * Gère l'affichage et les interactions sur la page "Mes Trajets" de l'utilisateur.
 * Ce script est responsable du chargement des données de trajets, de leur affichage
 * dans les différents onglets (à venir, passés, tous), de la pagination, et de la
 * gestion des actions utilisateur sur ces trajets (démarrer, terminer, annuler, laisser un avis).
 * Il utilise des modules helpers pour les appels API, les messages flash, et la manipulation du DOM.
 */

import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';
import { createElement, clearChildren } from '../utils/domHelpers.js';
import { Pagination } from '../components/Pagination.js';
import { ReviewModalHandler } from '../components/reviewModalHandler.js';

// --- Sélecteurs DOM --- //
// Conteneurs principaux pour l'affichage des listes de trajets.
const upcomingRidesContainer = document.querySelector('#upcoming-rides .rides-list-container');
const pastRidesContainer = document.querySelector('#past-rides .rides-list-container');
const allRidesContainer = document.querySelector('#all-rides .rides-list-container');
// Message affiché lorsque l'utilisateur n'a aucun trajet.
const noRidesMessage = document.getElementById('no-rides-message');

// Conteneurs pour les éléments de pagination.
const upcomingPaginationContainer = document.querySelector('#upcoming-rides-pagination');
const pastPaginationContainer = document.querySelector('#past-rides-pagination');
const allPaginationContainer = document.querySelector('#all-rides-pagination');

// --- Instances et Variables Globales --- //
// Instances des objets de pagination pour chaque onglet.
let upcomingPagination;
let pastPagination;
let allPagination;
// Instance du gestionnaire de la modale d'avis.
let reviewModalHandler;
// Cache pour stocker les données des trajets récupérées, afin de les réutiliser
// lors de l'ouverture de la modale d'avis sans refaire un appel API.
let ridesCache = new Map();

// Template HTML pour la création des cartes de trajet.
const rideCardTemplate = document.getElementById('ride-card-template');

// Nombre de trajets à afficher par page et variables pour suivre la page actuelle de chaque onglet.
const RIDES_PER_PAGE = 5;
let currentUpcomingPage = 1;
let currentPastPage = 1;
let currentAllPage = 1;

// --- Fonctions Utilitaires --- //

/**
 * Calcule la durée entre deux points temporels et la formate en heures et minutes.
 * @param {string} start - La date et l'heure de début (format ISO 8601).
 * @param {string} end - La date et l'heure de fin (format ISO 8601).
 * @returns {string} La durée formatée (ex: "2h30") ou "N/A" si la durée est invalide.
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
 * Crée un élément de carte de trajet (HTML) à partir des données d'un trajet.
 * Cette fonction peuple le template `ride-card-template` avec les informations spécifiques du trajet.
 * Elle gère également l'affichage des actions possibles (boutons) en fonction du statut du trajet
 * et du rôle de l'utilisateur (conducteur/passager).
 * @param {object} ride - L'objet contenant toutes les données du trajet.
 * @returns {DocumentFragment} Un fragment de document contenant la carte de trajet prête à être insérée dans le DOM.
 */
const createRideCard = (ride) => {
    const card = rideCardTemplate.content.cloneNode(true);

    // Remplissage des informations de base du trajet.
    card.querySelector('.ride-id').textContent = `#${ride.ride_id}`;
    card.querySelector('.ride-title').textContent = `${ride.departure_city} → ${ride.arrival_city}`;
    card.querySelector('.ride-date').textContent = new Date(ride.departure_time).toLocaleDateString('fr-FR');
    card.querySelector('.ride-time').textContent = new Date(ride.departure_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    card.querySelector('.ride-duration').textContent = calculateDuration(ride.departure_time, ride.estimated_arrival_time);
    card.querySelector('.ride-vehicle-details').textContent = `${ride.vehicle_brand_name} ${ride.vehicle_model}`;
    
    // Gestion de l'affichage du prix/gain estimé en fonction du rôle de l'utilisateur.
    const priceLabelEl = card.querySelector('.price-label');
    const ridePriceAmountEl = card.querySelector('.ride-price-amount');

    if (ride.driver_id === currentUserId) {
        // Si l'utilisateur est le conducteur, afficher le gain estimé.
        priceLabelEl.textContent = 'Gain estimé : ';
        let totalEstimatedGain = ride.estimated_earnings_per_passenger * ride.passengers_count;
        ridePriceAmountEl.textContent = `${totalEstimatedGain.toFixed(2)}`;
    } else {
        // Si l'utilisateur est un passager, afficher le prix payé.
        priceLabelEl.textContent = 'Prix payé : ';
        ridePriceAmountEl.textContent = `${ride.price_per_seat} crédits`;
    }

    // Afficher le nombre de sièges réservés si l'utilisateur est passager.
    if (ride.driver_id !== currentUserId && ride.seats_booked_by_user) {
        const seatsBookedEl = createElement('p', ['card-text', 'mb-1'], {}, `Sièges réservés : ${ride.seats_booked_by_user}`);
        card.querySelector('.role-specific-info').appendChild(seatsBookedEl);
    }
    card.querySelector('.ride-status-text').textContent = ride.ride_status;

    // Affichage du nombre de passagers pour les trajets du conducteur.
    const passengersCurrentEl = card.querySelector('.ride-passengers-current');
    const passengersMaxEl = card.querySelector('.ride-passengers-max');
    if (passengersCurrentEl && passengersMaxEl) {
        passengersCurrentEl.textContent = ride.passengers_count;
        passengersMaxEl.textContent = ride.seats_offered;
    }

    // Gestion du badge "Éco" pour les trajets écologiques.
    const ecoBadge = card.querySelector('.ride-eco-badge');
    if (ride.is_eco_ride) {
        ecoBadge.classList.remove('d-none');
    } else {
        ecoBadge.classList.add('d-none');
    }

    // Gestion des boutons d'action spécifiques au trajet (démarrer, terminer, annuler, laisser un avis).
    const rideActionsContainer = card.querySelector('.ride-actions');
    clearChildren(rideActionsContainer); // S'assurer que le conteneur d'actions est vide avant d'ajouter des boutons.

    // `currentUserId` est une variable globale injectée par PHP dans la vue.

    if (ride.ride_status === 'planned') {
        // Actions pour un trajet planifié.
        if (ride.driver_id === currentUserId) { 
            // Si l'utilisateur est le conducteur.
            const startButton = createElement('button', ['btn', 'primary-btn', 'btn-sm', 'mb-1', 'w-100', 'action-start-ride'], { 'data-ride-id': ride.ride_id }, 'Démarrer le trajet');
            const cancelButton = createElement('button', ['btn', 'btn-outline-danger', 'btn-sm', 'w-100', 'action-cancel-ride-driver'], { 'data-ride-id': ride.ride_id }, 'Annuler ce trajet');
            rideActionsContainer.appendChild(startButton);
            rideActionsContainer.appendChild(cancelButton);
        } else {
            // Si l'utilisateur est un passager.
            const cancelButton = createElement('button', ['btn', 'btn-outline-danger', 'btn-sm', 'w-100', 'action-cancel-booking'], { 'data-ride-id': ride.ride_id }, 'Annuler ma réservation');
            rideActionsContainer.appendChild(cancelButton);
        }
    } else if (ride.ride_status === 'ongoing' && ride.driver_id === currentUserId) {
        // Action pour un trajet en cours (uniquement pour le conducteur).
        const finishButton = createElement('button', ['btn', 'primary-btn', 'btn-sm', 'mb-1', 'w-100', 'action-finish-ride'], { 'data-ride-id': ride.ride_id }, 'Arrivée à destination');
        rideActionsContainer.appendChild(finishButton);
    } else if ((ride.ride_status === 'completed' || ride.ride_status === 'completed_pending_confirmation') && ride.driver_id !== currentUserId) {
        // Action pour un trajet terminé (uniquement pour les passagers) : laisser un avis.
        const reviewButton = createElement('button', ['btn', 'secondary-btn', 'btn-sm', 'w-100', 'action-leave-review'], { 'data-ride-id': ride.ride_id }, 'Laisser un avis');
        rideActionsContainer.appendChild(reviewButton);
    }

    return card;
};

/**
 * Affiche une liste de trajets dans un conteneur DOM spécifié.
 * @param {HTMLElement} container - L'élément DOM où les cartes de trajet doivent être affichées.
 * @param {Array<object>} rides - Un tableau d'objets trajet à afficher.
 */
const displayRides = (container, rides) => {
    clearChildren(container); // Vide le conteneur avant d'ajouter de nouvelles cartes.
    if (rides && rides.length > 0) {
        rides.forEach(ride => {
            container.appendChild(createRideCard(ride));
        });
    } else {
        // Affiche un message si aucun trajet n'est disponible.
        container.appendChild(createElement('p', ['text-muted', 'text-center'], {}, 'Aucun trajet pour le moment.'));
    }
};

/**
 * Gère les actions déclenchées par les boutons sur les cartes de trajet (démarrer, terminer, annuler, laisser un avis).
 * Utilise la délégation d'événements pour une gestion efficace des clics sur les boutons dynamiquement ajoutés.
 * @param {Event} event - L'événement de clic déclenché.
 */
const handleRideAction = async (event) => {
    const target = event.target;
    // Trouve le bouton d'action le plus proche qui a un attribut `data-ride-id`.
    const actionButton = target.closest('button[data-ride-id]'); 
    if (!actionButton) return; // Si le clic n'est pas sur un bouton d'action, ignorer.

    const rideId = actionButton.getAttribute('data-ride-id');
    if (!rideId) {
        console.error("handleRideAction: rideId manquant sur le bouton d'action.");
        return;
    }

    // Gère l'action "Laisser un avis" en ouvrant la modale d'avis.
    if (actionButton.classList.contains('action-leave-review')) {
        event.preventDefault(); // Empêche l'action par défaut du bouton (ex: soumission de formulaire si le bouton est de type submit).
        const rideData = ridesCache.get(rideId); // Récupère les données complètes du trajet depuis le cache.
        if (rideData) {
            reviewModalHandler.open(rideData); // Ouvre la modale d'avis avec les données du trajet.
        } else {
            console.error(`Impossible de trouver les données du trajet ${rideId} pour laisser un avis.`);
            displayFlashMessage("Une erreur est survenue. Impossible d'ouvrir le formulaire d'avis.", 'danger');
        }
        return; // Termine la fonction car l'action spécifique à l'avis a été gérée.
    }

    // --- Logique pour les autres actions (démarrer, terminer, annuler) --- //
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

    // Demande de confirmation avant d'exécuter l'action API.
    if (!confirm(confirmMessage)) {
        return;
    }

    actionButton.disabled = true; // Désactive le bouton pour éviter les clics multiples pendant l'appel API.

    try {
        const response = await apiCallPromise;

        if (response.success) {
            displayFlashMessage(response.message, 'success');
            // Après une action réussie, rafraîchit l'onglet actif pour refléter les changements.
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
        actionButton.disabled = false; // Réactive le bouton après la fin de l'appel API (succès ou échec).
    }
};

/**
 * Charge et affiche les trajets de l'utilisateur pour un type d'onglet et une page donnés.
 * Cette fonction est le point d'entrée pour rafraîchir les listes de trajets.
 * @param {string} type - Le type de trajets à charger ('all', 'upcoming', 'past').
 * @param {number} page - Le numéro de page à charger.
 */
const loadUserRides = async (type, page) => {
    // Cache le message "aucun trajet" par défaut avant le chargement.
    noRidesMessage.classList.add('d-none');

    let container, paginationInstance, currentPageVar;

    // Détermine le conteneur et l'instance de pagination appropriés en fonction du type de trajet.
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

    clearChildren(container); // Vide le conteneur pour afficher l'indicateur de chargement.
    container.appendChild(createElement('p', ['text-center', 'text-muted'], {}, 'Chargement des trajets...'));

    try {
        // Appel à l'API pour récupérer les trajets de l'utilisateur.
        const response = await apiClient.getUserRides(type, page, RIDES_PER_PAGE);

        if (response.success) {
            window[currentPageVar] = page; // Met à jour la variable de page actuelle.

            // Met en cache les données des trajets pour un accès rapide (utile pour la modale d'avis).
            if (response.rides && response.rides.length > 0) {
                response.rides.forEach(ride => ridesCache.set(ride.ride_id.toString(), ride));
            }

            displayRides(container, response.rides); // Affiche les trajets récupérés.
            
            // Met à jour la pagination si une instance est disponible.
            if (paginationInstance) {
                paginationInstance.render(response.pagination.current_page, response.pagination.total_pages);
            }

        } else {
            // Affiche un message d'erreur si la récupération des trajets échoue.
            displayFlashMessage(response.message || `Erreur lors du chargement des trajets ${type}.`, 'danger');
            container.appendChild(createElement('p', ['text-center', 'text-danger'], {}, 'Erreur lors du chargement des trajets.'));
        }

    } catch (error) {
        // Gère les erreurs de communication réseau.
        console.error(`Erreur lors du chargement des trajets ${type}:`, error);
        displayFlashMessage('Une erreur de communication est survenue lors du chargement de vos trajets.', 'danger');
        container.appendChild(createElement('p', ['text-center', 'text-danger'], {}, 'Erreur de communication.'));
    } finally {
        // Met à jour le message "aucun trajet" après le chargement, quelle que soit l'issue.
        updateNoRidesMessage();
    }
};

/**
 * Met à jour l'affichage du message "Vous n'avez aucun trajet" en fonction du nombre total de trajets.
 * Cette fonction est appelée après chaque chargement de trajets pour ajuster la visibilité du message.
 */
const updateNoRidesMessage = async () => {
    try {
        // Récupère le nombre total de trajets pour déterminer si le message doit être affiché.
        const response = await apiClient.getUserRides('all', 1, 1);
        if (response.success && response.pagination.total_rides === 0) {
            noRidesMessage.classList.remove('d-none');
        } else {
            noRidesMessage.classList.add('d-none');
        }
    } catch (error) {
        console.error("Erreur lors de la mise à jour du message 'aucun trajet':", error);
        // Ne pas afficher de flash message ici pour éviter de spammer l'utilisateur en cas d'erreur répétée.
    }
};

// --- Initialisation au chargement du DOM --- //

document.addEventListener('DOMContentLoaded', () => {
    // Initialise le gestionnaire de la modale d'avis en lui passant l'ID de l'utilisateur courant.
    // `currentUserId` est une variable globale injectée par PHP dans la vue.
    reviewModalHandler = new ReviewModalHandler(currentUserId);

    // Initialise les instances de pagination pour chaque section de trajets.
    upcomingPagination = new Pagination('#upcoming-rides-pagination', (page) => loadUserRides('upcoming', page));
    pastPagination = new Pagination('#past-rides-pagination', (page) => loadUserRides('past', page));
    allPagination = new Pagination('#all-rides-pagination', (page) => loadUserRides('all', page));

    // Charge les trajets à venir par défaut au premier chargement de la page.
    loadUserRides('upcoming', currentUpcomingPage);

    // Met à jour le message "aucun trajet" après le chargement initial.
    updateNoRidesMessage();

    // Attache un écouteur d'événements global pour gérer les actions sur les cartes de trajet.
    // Utilise la délégation d'événements pour capturer les clics sur les boutons dynamiquement ajoutés.
    const ridesHistorySection = document.querySelector('.rides-history-section');
    if (ridesHistorySection) {
        ridesHistorySection.addEventListener('click', handleRideAction);
    }

    // Attache des écouteurs d'événements aux onglets pour recharger les trajets
    // correspondants lorsque l'onglet est changé.
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
