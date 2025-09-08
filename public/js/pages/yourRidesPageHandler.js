import { apiClient } from '../utils/apiClient.js';
import { displayFlashMessage } from '../utils/displayFlashMessage.js';
import { createElement, clearChildren } from '../utils/domHelpers.js';
import { Pagination } from '../components/Pagination.js';
import { ReviewModalHandler } from '../components/reviewModalHandler.js';
import { simpleEscapeHtml } from '../utils/htmlSanitizer.js';

// Sélecteurs des conteneurs de trajets
const upcomingRidesContainer = document.querySelector('#upcoming-rides .rides-list-container');
const pastRidesContainer = document.querySelector('#past-rides .rides-list-container');
const allRidesContainer = document.querySelector('#all-rides .rides-list-container');
const noRidesMessage = document.getElementById('no-rides-message');
const currentRideHighlightContainer = document.getElementById('current-ride-highlight'); // Nouvelle constante

// Sélecteurs des conteneurs de pagination
const upcomingPaginationContainer = document.querySelector('#upcoming-rides-pagination');
const pastPaginationContainer = document.querySelector('#past-rides-pagination');
const allPaginationContainer = document.querySelector('#all-rides-pagination');

// Instances de pagination
let upcomingPagination;
let pastPagination;
let allPagination;
let reviewModalHandler;
let ridesCache = new Map();

// Template pour les cartes de trajet
const rideCardTemplate = document.getElementById('ride-card-template');

// Variables pour l'état de la pagination
const RIDES_PER_PAGE = 5; // Nombre de trajets par page
let currentUpcomingPage = 1;
let currentPastPage = 1;
let currentAllPage = 1;

/**
 * Calcule la durée entre deux dates/heures et la formate.
 * @param {string} start La date/heure de début (format ISO).
 * @param {string} end La date/heure de fin (format ISO).
 * @returns {string} La durée formatée (ex: "2h30") ou "N/A".
 */
export const calculateDuration = (start, end) => {
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
export const createRideCard = (ride) => {
    const card = rideCardTemplate.content.cloneNode(true);

    // Remplir les données de la carte
    card.querySelector('.ride-id').textContent = `#${ride.ride_id}`;
    card.querySelector('.ride-title').textContent = `${ride.departure_city} → ${ride.arrival_city}`;
    card.querySelector('.ride-date').textContent = new Date(ride.departure_time).toLocaleDateString('fr-FR');
    card.querySelector('.ride-time').textContent = new Date(ride.departure_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    card.querySelector('.ride-duration').textContent = calculateDuration(ride.departure_time, ride.estimated_arrival_time); // AJOUT

    // Nouvelles infos adresses
    const depAddrEl = card.querySelector('.ride-departure-address');
    if (depAddrEl) depAddrEl.textContent = ride.departure_address || '';
    const arrAddrEl = card.querySelector('.ride-arrival-address');
    if (arrAddrEl) arrAddrEl.textContent = ride.arrival_address || '';

    card.querySelector('.ride-vehicle-details').textContent = `${ride.vehicle_brand_name} ${ride.vehicle_model}`; // AJOUT
    // Gérer le rôle (conducteur/passager) et l'affichage du prix
    const priceLabelEl = card.querySelector('.price-label');
    const ridePriceAmountEl = card.querySelector('.ride-price-amount');

    if (ride.driver_id === currentUserId) {
        // L'utilisateur est le conducteur de ce trajet
        priceLabelEl.textContent = 'Gain estimé : ';
        // Utiliser la propriété estimated_earnings_per_passenger du backend
        let totalEstimatedGain = ride.estimated_earnings_per_passenger * ride.passengers_count;
        ridePriceAmountEl.textContent = `${totalEstimatedGain.toFixed(2)}`;
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

    // Afficher le nombre de passagers
    const passengersCurrentEl = card.querySelector('.ride-passengers-current');
    const passengersMaxEl = card.querySelector('.ride-passengers-max');
    if (passengersCurrentEl && passengersMaxEl) {
        passengersCurrentEl.textContent = ride.passengers_count;
        passengersMaxEl.textContent = ride.seats_offered;
    }

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
    } else if ((ride.ride_status === 'completed' || ride.ride_status === 'completed_pending_confirmation') && ride.driver_id !== currentUserId) {
        // Action pour un trajet terminé (uniquement pour les passagers) : laisser un avis.
        const reviewButton = createElement('button', ['btn', 'secondary-btn', 'btn-sm', 'w-100', 'action-leave-review'], { 'data-ride-id': ride.ride_id }, 'Laisser un avis');
        if (ride.has_reviewed) {
            reviewButton.textContent = 'Avis soumis';
            reviewButton.disabled = true;
        }
        rideActionsContainer.appendChild(reviewButton);
    }
    // Ajouter le pseudo du chauffeur en haut de la carte
    const cardHeader = card.querySelector('.card-header');
    const rideIdSpan = card.querySelector('.ride-id');
    if (cardHeader && rideIdSpan) {
        let driverInfoText = '';
        if (ride.driver_id === currentUserId) {
            driverInfoText = 'Vous êtes le chauffeur';
        } else {
            driverInfoText = `Chauffeur: ${ride.driver_username}`;
        }
       if (driverInfoText) { // N'ajoute le span que si il y a du texte à afficher
            const driverInfoSpan = createElement('span', ['form-label'], {}, driverInfoText);
            cardHeader.insertBefore(driverInfoSpan, rideIdSpan);
        }
    }

    // Gérer l'affichage des informations de contact
    const contactInfoSection = card.querySelector('.contact-info-section');
    const contactDriverInfo = card.querySelector('.contact-driver-info');
    const driverPhoneLink = contactDriverInfo.querySelector('.driver-phone-link');
    const driverEmailText = contactDriverInfo.querySelector('.driver-email-text');

    const contactPassengersInfo = card.querySelector('.contact-passengers-info');
    const passengersContactList = contactPassengersInfo.querySelector('.passengers-contact-list');

    if (ride.driver_id === currentUserId) { // L'utilisateur est le chauffeur
        // Afficher les contacts des passagers
        if (ride.passengers_details && ride.passengers_details.length > 0) {
            contactPassengersInfo.classList.remove('d-none');
            ride.passengers_details.forEach(passenger => {
                const li = createElement('li', [], {}, '');
                    const strongUsername = createElement('strong', [], {}, passenger.username);
                    li.appendChild(strongUsername);
                    li.appendChild(document.createTextNode(` (${passenger.seats_booked} place(s))`));
                    li.appendChild(createElement('br'));

                    const phoneIcon = createElement('i', ['bi', 'bi-telephone-fill', 'me-2']);
                    const phoneLink = createElement('a', [], { href: `tel:${passenger.phone_number}` }, passenger.phone_number);
                    li.appendChild(phoneIcon);
                    li.appendChild(phoneLink);
                    li.appendChild(createElement('br'));

                    const emailIcon = createElement('i', ['bi', 'bi-envelope-fill', 'me-2']);
                    const emailLink = createElement('a', [], { href: `mailto:${passenger.email}` }, 'Envoyer un email');
                    li.appendChild(emailIcon);
                    li.appendChild(emailLink);
                passengersContactList.appendChild(li);
            });
        }
    } else { // L'utilisateur est un passager
        // Afficher le contact du chauffeur
        if (ride.driver_phone && ride.driver_email) {
            contactDriverInfo.classList.remove('d-none');
            driverPhoneLink.href = `tel:${ride.driver_phone}`;
            driverPhoneLink.textContent = ride.driver_phone;
            const emailLink = createElement('a', [], { href: `mailto:${ride.driver_email}` }, 'Envoyer un email');
            driverEmailText.appendChild(emailLink);
        }
    }

    // Afficher la section contact si des informations sont présentes
    if (!contactDriverInfo.classList.contains('d-none') || !contactPassengersInfo.classList.contains('d-none')) {
        contactInfoSection.classList.remove('d-none');
    } else {
        contactInfoSection.classList.add('d-none');
    }

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

    actionButton.disabled = true; // Désactiver le bouton pendant l'appel

    try {
        const response = await apiCallPromise;

        if (response.success) {
            displayFlashMessage(response.message, 'success');
            // Recharger la page actuelle de l'onglet actif après action réussie
            const activeTab = document.querySelector('#ridesTabs button.active');
            if (activeTab) {
                const tabType = activeTab.id.replace('-rides-tab', ''); // 'upcoming', 'past', 'all'
                let currentPage;
                switch (tabType) {
                    case 'upcoming':
                        currentPage = currentUpcomingPage;
                        break;
                    case 'past':
                        currentPage = currentPastPage;
                        break;
                    case 'all':
                        currentPage = currentAllPage;
                        break;
                    default:
                        currentPage = 1;
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
        actionButton.disabled = false; // Réactiver le bouton
    }
};

/**
 * Charge et affiche les trajets de l'utilisateur pour un type et une page donnés.
 * @param {string} type Le type de trajets à charger ('all', 'upcoming', 'past').
 * @param {number} page Le numéro de page à charger.
 */
const loadUserRides = async (type, page) => {
    // Cacher le message 'aucun trajet' par défaut
    noRidesMessage.classList.add('d-none');

    let container;
    let paginationInstance;
    let currentPageVar;

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

    // Afficher un indicateur de chargement si nécessaire
    clearChildren(container); // Vider le conteneur avant de charger
    container.appendChild(createElement('p', ['text-center', 'text-muted'], {}, 'Chargement des trajets...'));

    try {
        const response = await apiClient.getUserRides(type, page, RIDES_PER_PAGE);

        if (response.success) {
            // Mettre à jour la variable de page actuelle
            window[currentPageVar] = page;

            // Met en cache les données des trajets pour un accès rapide (utile pour la modale d'avis).
            if (response.rides && response.rides.length > 0) {
                response.rides.forEach(ride => ridesCache.set(ride.ride_id.toString(), ride));
            }

            // Filtrer le trajet "ongoing" pour l'afficher séparément
            let ongoingRide = null;
            const filteredRides = response.rides.filter(ride => {
                if (ride.ride_status === 'ongoing' && (ride.driver_id === currentUserId || ride.user_role_in_ride === 'passenger')) {
                    ongoingRide = ride;
                    return false; // Exclure le trajet ongoing de la liste principale
                }
                return true;
            });
            // Afficher le trajet ongoing si trouvé
            if (ongoingRide) {
                clearChildren(currentRideHighlightContainer);
                currentRideHighlightContainer.appendChild(createRideCard(ongoingRide));
                currentRideHighlightContainer.classList.remove('d-none');
            } else {
                currentRideHighlightContainer.classList.add('d-none');
            }

            displayRides(container, filteredRides); // Affiche les trajets filtrés.
            
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

// Exposer loadUserRides pour le débogage si nécessaire
// window.loadUserRides = loadUserRides;