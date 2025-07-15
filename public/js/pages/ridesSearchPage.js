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

        const queryParams = new URLSearchParams(window.location.search);

        // Vérifier si les paramètres de recherche principaux sont présents
        if (!queryParams.has('departure_city') || !queryParams.has('arrival_city') || !queryParams.has('date')) {
            noResultsMessage.innerHTML = "Veuillez utiliser le formulaire ci-dessus pour rechercher un trajet.";
            noResultsMessage.classList.remove('d-none');
            return; // Ne pas lancer de recherche si les critères de base sont absents
        }

        // Nettoyer les résultats précédents
        clearChildren(rideResultsContainer);
        clearChildren(noResultsMessage);
        noResultsMessage.classList.add('d-none');
        clearChildren(paginationContainer);

        loadingIndicator.classList.remove('d-none');

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
                    let messageHtml = data.message || "Aucun trajet ne correspond à vos critères pour la date sélectionnée.";
                    if (data.nextAvailableDate) {
                        const formattedNextDate = new Date(data.nextAvailableDate + 'T00:00:00').toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        messageHtml += `<br>Le prochain trajet disponible pour cet itinéraire est le <strong>${formattedNextDate}</strong>.`;
                        
                        const searchNextDateButton = createElement('button', ['btn', 'primary-btn', 'btn-sm', 'mt-3'], {}, `Rechercher pour le ${formattedNextDate}`);
                        searchNextDateButton.onclick = () => {
                            const newSearchParams = new URLSearchParams(window.location.search);
                            newSearchParams.set('date', data.nextAvailableDate);
                            newSearchParams.set('page', '1');
                            window.history.pushState({ date: data.nextAvailableDate }, "", `?${newSearchParams.toString()}`);
                            fetchAndDisplayRides(); 
                        };
                        
                        clearChildren(noResultsMessage);
                        const messageParagraph = createElement('p', [], {}, '');
                        messageParagraph.innerHTML = messageHtml;
                        noResultsMessage.appendChild(messageParagraph);
                        noResultsMessage.appendChild(searchNextDateButton);
                    } else {
                         noResultsMessage.innerHTML = messageHtml;
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

    function createRideCardElement(rideData) {
        const template = document.getElementById('ride-card-template');
        if (!template) return null;

        const card = template.content.cloneNode(true).querySelector('.ride-card');
        
        // Remplissage des données de base
        const driverPhotoEl = card.querySelector('.driver-profile-photo');
        if (driverPhotoEl && rideData.driver_photo) {
            driverPhotoEl.src = rideData.driver_photo;
            driverPhotoEl.alt = `Photo de ${rideData.driver_username}`;
        } else if (driverPhotoEl) {
            driverPhotoEl.src = "/img/default-profile.png"; // Chemin par défaut
            driverPhotoEl.alt = 'Photo de profil par défaut';
        }
        card.querySelector('.driver-username').textContent = rideData.driver_username;
        // Note: Le rating du driver n'est pas dans la recherche initiale, il sera chargé avec les détails
        card.querySelector('.ride-departure-location').textContent = rideData.departure_city;
        card.querySelector('.ride-arrival-location').textContent = rideData.arrival_city;
        
        // Formatage des dates et heures
        const departureTimeEl = card.querySelector('.ride-departure-time');
        if (departureTimeEl && rideData.departure_time) {
            const depDate = new Date(rideData.departure_time.replace(' ', 'T'));
            departureTimeEl.textContent = `${depDate.toLocaleDateString([], {day:'2-digit', month:'2-digit', year:'numeric'})} ${depDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
        }

        // Calcul et affichage de la durée estimée
        const estimatedDurationEl = card.querySelector('.ride-estimated-duration');
        if (estimatedDurationEl && rideData.departure_time && rideData.estimated_arrival_time) {
            const departure = new Date(rideData.departure_time.replace(' ', 'T'));
            const arrival = new Date(rideData.estimated_arrival_time.replace(' ', 'T'));
            const durationMs = arrival - departure;
            if (durationMs > 0) {
                const hours = Math.floor(durationMs / (1000 * 60 * 60));
                const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
                estimatedDurationEl.textContent = `${hours}h${minutes < 10 ? '0' : ''}${minutes}`;
            } else { estimatedDurationEl.textContent = "N/A"; }
        } else if (estimatedDurationEl) { estimatedDurationEl.textContent = "N/A"; }

        card.querySelector('.ride-price').textContent = `${rideData.price_per_seat} crédits`;
        card.querySelector('.ride-available-seats').textContent = rideData.seats_available;

        // Gérer le badge écologique
        const ecoCheckbox = card.querySelector('input.is-ride-eco');
        const ecoLabel = card.querySelector('label.is-ride-eco');
        if (ecoCheckbox) {
            const newEcoId = `ecoCheck_ride_${rideData.ride_id}`;
            ecoCheckbox.id = newEcoId;
            if (ecoLabel) ecoLabel.setAttribute('for', newEcoId);
            ecoCheckbox.checked = rideData.is_eco_ride || false;
            ecoCheckbox.disabled = true;
            const ecoCheckWrapper = ecoCheckbox.closest('.form-check');
            if (ecoCheckWrapper) ecoCheckWrapper.style.display = rideData.is_eco_ride ? 'inline-block' : 'none';
        }

        // Gérer le bouton détails et le collapse
        const detailsButton = card.querySelector('.ride-details-button');
        const collapseElement = card.querySelector('.collapse');
        if (collapseElement) {
            const newCollapseId = `detailsCollapse_ride_${rideData.ride_id}`;
            collapseElement.id = newCollapseId;
            if (detailsButton) {
                detailsButton.setAttribute('data-bs-target', `#${newCollapseId}`);
                detailsButton.setAttribute('aria-controls', newCollapseId);
                detailsButton.setAttribute('data-ride-id', rideData.ride_id);
                // Ajouter l'écouteur d'événement pour charger les détails
                detailsButton.addEventListener('click', () => fetchAndRenderRideDetails(rideData.ride_id, card));
            }
        }

        // Remplissage des adresses détaillées (initialement vides, remplies par les détails)
        card.querySelector('.ride-departure-address-details').textContent = rideData.departure_address || '';
        card.querySelector('.ride-arrival-address-details').textContent = rideData.arrival_address || '';

        return card;
    }

    async function fetchAndRenderRideDetails(rideId, cardElement) {
        const loadingMessageEl = cardElement.querySelector('.loading-details-message');
        const errorMessageEl = cardElement.querySelector('.error-details-message');
        const contentWrapperEl = cardElement.querySelector('.ride-details-content-wrapper');

        if (!loadingMessageEl || !errorMessageEl || !contentWrapperEl) {
            console.error("Éléments DOM de détails manquants dans le template.");
            return;
        }

        // Afficher l'indicateur de chargement, masquer le contenu et les erreurs
        loadingMessageEl.classList.remove('d-none');
        errorMessageEl.classList.add('d-none');
        contentWrapperEl.classList.add('d-none');

        try {
            const response = await apiClient.getRideDetails(rideId);
            console.log("API Response for ride details:", response);

            if (response.success && response.details) {
                const details = response.details;
                
                // Remplir les informations du véhicule
                const vehicleInfoContainer = cardElement.querySelector('.vehicle-info-container');
                if (vehicleInfoContainer) {
                    const carModelEl = vehicleInfoContainer.querySelector('.ride-car-model');
                    if (carModelEl) carModelEl.textContent = `${details.vehicle_brand_name || ''} ${details.vehicle_model || ''}`.trim();
                    
                    const carRegYearEl = vehicleInfoContainer.querySelector('.ride-car-registration-year');
                    if (carRegYearEl) carRegYearEl.textContent = details.vehicle_registration_date ? details.vehicle_registration_date.substring(0, 4) : 'N/A';
                    
                    const carEnergyEl = vehicleInfoContainer.querySelector('.ride-car-energy');
                    if (carEnergyEl) carEnergyEl.textContent = details.vehicle_energy_type || 'N/A';
                }

                // Remplir les préférences du conducteur
                const prefsContainer = cardElement.querySelector('.driver-preferences-text');
                const noPrefsMsg = cardElement.querySelector('.no-prefs-message');
                if (prefsContainer) {
                    clearChildren(prefsContainer);
                    let hasPref = false;
                    if (details.driver_pref_smoker !== undefined) { hasPref = true; prefsContainer.appendChild(createElement('p', ['mb-1'], {}, details.driver_pref_smoker ? 'Accepte les fumeurs' : 'Non-fumeur')); }
                    if (details.driver_pref_animals !== undefined) { hasPref = true; prefsContainer.appendChild(createElement('p', ['mb-1'], {}, details.driver_pref_animals ? 'Accepte les animaux' : 'N’accepte pas les animaux')); }
                    if (details.driver_pref_custom && details.driver_pref_custom.trim() !== '') { hasPref = true; prefsContainer.appendChild(createElement('p', ['mb-1'], {}, details.driver_pref_custom)); }
                    if (!hasPref && noPrefsMsg) noPrefsMsg.classList.remove('d-none'); else if (noPrefsMsg) noPrefsMsg.classList.add('d-none');
                }

                // Remplir les avis
                const reviewsContainer = cardElement.querySelector('.driver-reviews-container');
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
