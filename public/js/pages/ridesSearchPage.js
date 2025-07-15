import { SearchForm } from '../components/SearchForm.js';
import { apiClient } from '../utils/apiClient.js';
import { createElement, clearChildren } from '../utils/domHelpers.js';

document.addEventListener('DOMContentLoaded', () => {
    // Initialiser le formulaire de recherche principal
    new SearchForm('search-form');

    const rideResultsContainer = document.getElementById('ride-results-container');
    const noResultsMessage = document.getElementById('no-results-message');
    const loadingIndicator = document.getElementById('loading-indicator');
    const paginationContainer = document.querySelector('ul.pagination');

    // Lancer la recherche initiale au chargement de la page
    fetchAndDisplayRides();

    // Écouter les mises à jour du formulaire de recherche (si on est déjà sur la page)
    window.addEventListener('search-updated', () => {
        fetchAndDisplayRides();
    });

    async function fetchAndDisplayRides() {
        if (!rideResultsContainer || !noResultsMessage || !loadingIndicator || !paginationContainer) {
            console.error("Éléments DOM manquants pour la recherche.");
            return;
        }

        loadingIndicator.classList.remove('d-none');
        clearChildren(rideResultsContainer);
        clearChildren(noResultsMessage);
        noResultsMessage.classList.add('d-none');
        clearChildren(paginationContainer);

        const queryParams = new URLSearchParams(window.location.search);

        try {
            const data = await apiClient.searchRides(queryParams.toString());

            if (data.success) {
                if (data.rides && data.rides.length > 0) {
                    data.rides.forEach(ride => {
                        const rideCard = createRideCardElement(ride);
                        if (rideCard) {
                            rideResultsContainer.appendChild(rideCard);
                        }
                    });
                    renderPaginationControls(data.page, data.totalPages, queryParams);
                } else {
                    noResultsMessage.textContent = data.message || "Aucun trajet trouvé.";
                    noResultsMessage.classList.remove('d-none');
                }
            } else {
                noResultsMessage.textContent = data.message || "Erreur lors de la recherche.";
                noResultsMessage.classList.remove('d-none');
            }
        } catch (error) {
            console.error("Erreur lors de la recherche de trajets:", error);
            noResultsMessage.textContent = "Une erreur de communication est survenue.";
            noResultsMessage.classList.remove('d-none');
        } finally {
            loadingIndicator.classList.add('d-none');
        }
    }

    function createRideCardElement(rideData) {
        const template = document.getElementById('ride-card-template');
        if (!template) return null;

        const card = template.content.cloneNode(true).querySelector('.ride-card');
        
        // Remplissage simple des données
        card.querySelector('.driver-username').textContent = rideData.driver_username;
        card.querySelector('.ride-departure-location').textContent = rideData.departure_city;
        card.querySelector('.ride-arrival-location').textContent = rideData.arrival_city;
        card.querySelector('.ride-price').textContent = `${rideData.price_per_seat} crédits`;
        card.querySelector('.ride-available-seats').textContent = rideData.seats_available;

        // ... (remplissage des autres champs comme la date, l'heure, etc.)

        return card;
    }

    function renderPaginationControls(currentPage, totalPages, currentSearchParams) {
        clearChildren(paginationContainer);
        if (totalPages <= 1) return;

        for (let i = 1; i <= totalPages; i++) {
            const pageItem = createElement('li', ['page-item', i === currentPage ? 'active' : '']);
            const pageLink = createElement('a', ['page-link'], { href: '#' }, i);
            
            pageLink.addEventListener('click', (e) => {
                e.preventDefault();
                currentSearchParams.set('page', i);
                window.history.pushState({}, '', `?${currentSearchParams.toString()}`);
                fetchAndDisplayRides();
            });

            pageItem.appendChild(pageLink);
            paginationContainer.appendChild(pageItem);
        }
    }
});
