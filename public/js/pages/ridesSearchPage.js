import { SearchForm } from '../components/SearchForm.js';
import { FilterForm } from '../components/FilterForm.js';
import { apiClient } from '../utils/apiClient.js';
import { createElement, clearChildren } from '../utils/domHelpers.js';
import { RideCard } from '../components/RideCard.js';
import { Pagination } from '../components/Pagination.js';

document.addEventListener('DOMContentLoaded', () => {
    // Initialiser le formulaire de recherche principal
    new SearchForm('search-form');
    // Initialiser le formulaire de filtres
    new FilterForm('filter-form');

    const rideResultsContainer = document.getElementById('ride-results-container');
    const noResultsMessage = document.getElementById('no-results-message');
    const loadingIndicator = document.getElementById('loading-indicator');
    const paginationContainer = document.querySelector('ul.pagination');

    // Initialiser la pagination
    const pagination = new Pagination('ul.pagination', (page) => {
        const currentSearchParams = new URLSearchParams(window.location.search);
        currentSearchParams.set('page', page);
        window.history.pushState({}, '', `?${currentSearchParams.toString()}`);
        fetchAndDisplayRides();
    });

    // Lancer la recherche initiale au chargement de la page
    fetchAndDisplayRides();

    // Écouter les mises à jour du formulaire de recherche et de filtres
    window.addEventListener('search-updated', (event) => {
        const updatedSearchParams = event.detail; // L'événement contient déjà les bons paramètres
        fetchAndDisplayRides(updatedSearchParams);
    });

    async function fetchAndDisplayRides(searchParams = null) {
        if (!rideResultsContainer || !noResultsMessage || !loadingIndicator || !paginationContainer) {
            console.error("Éléments DOM manquants pour la recherche.");
            return;
        }

        let queryParams;
        if (searchParams instanceof URLSearchParams) {
            queryParams = searchParams;
        } else if (typeof searchParams === 'object' && searchParams !== null) {
            // Si c'est un objet simple (du SearchForm), le convertir en URLSearchParams
            queryParams = new URLSearchParams(searchParams);
        } else {
            // Par défaut, utiliser les paramètres de l'URL actuelle
            queryParams = new URLSearchParams(window.location.search);
        }

        // Vérifier si les paramètres de recherche principaux sont présents ET non vides
        const departureCity = queryParams.get('departure_city');
        const arrivalCity = queryParams.get('arrival_city');
        const date = queryParams.get('date');

        if (!departureCity || departureCity.trim() === '' ||
            !arrivalCity || arrivalCity.trim() === '' ||
            !date || date.trim() === '') {
            noResultsMessage.textContent = "Veuillez utiliser le formulaire ci-dessus pour rechercher un trajet.";
            noResultsMessage.classList.remove('d-none');
            return; // Ne pas lancer de recherche si les critères de base sont absents
        }

        // Nettoyer les résultats précédents
        clearChildren(rideResultsContainer);
        clearChildren(noResultsMessage);
        noResultsMessage.classList.add('d-none');

        loadingIndicator.classList.remove('d-none');

        try {
            const data = await apiClient.searchRides(queryParams.toString());

            if (data.success) {
                if (data.rides && data.rides.length > 0) {
                    data.rides.forEach(ride => {
                        const rideCard = new RideCard(ride);
                        if (rideCard.element) {
                            rideResultsContainer.appendChild(rideCard.element);
                        }
                    });
                    pagination.render(data.page, data.totalPages, queryParams);
                } else {
                    let messageText = data.message || "Aucun trajet ne correspond à vos critères pour la date sélectionnée.";
                    if (data.nextAvailableDate) {
                        const formattedNextDate = new Date(data.nextAvailableDate + 'T00:00:00').toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        
                        clearChildren(noResultsMessage);
                        const messageParagraph = createElement('p', [], {}, messageText);
                        noResultsMessage.appendChild(messageParagraph);

                        const nextDateParagraph = createElement('p', [], {});
                        nextDateParagraph.appendChild(document.createTextNode("Le prochain trajet disponible pour cet itinéraire est le "));
                        const strongElement = createElement('strong', [], {}, formattedNextDate);
                        nextDateParagraph.appendChild(strongElement);
                        nextDateParagraph.appendChild(document.createTextNode("."));
                        noResultsMessage.appendChild(nextDateParagraph);
                        
                        const searchNextDateButton = createElement('button', ['btn', 'primary-btn', 'btn-sm', 'mt-3'], {}, `Rechercher pour le ${formattedNextDate}`);
                        searchNextDateButton.onclick = () => {
                            const newSearchParams = new URLSearchParams(window.location.search);
                            newSearchParams.set('date', data.nextAvailableDate);
                            newSearchParams.set('page', '1');
                            window.history.pushState({ date: data.nextAvailableDate }, "", `?${newSearchParams.toString()}`);
                            fetchAndDisplayRides(newSearchParams); 
                        };
                        noResultsMessage.appendChild(searchNextDateButton);
                    } else {
                         noResultsMessage.textContent = messageText;
                    }
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
});
