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
 * Crée un élément de carte de trajet à partir des données.
 * @param {object} ride Les données du trajet.
 * @returns {HTMLElement} L'élément HTML de la carte de trajet.
 */
function createRideCard(ride) {
    const card = rideCardTemplate.content.cloneNode(true);

    // Remplir les données de la carte
    card.querySelector('.ride-id').textContent = `#${ride.ride_id}`;
    card.querySelector('.ride-title').textContent = `${ride.departure_city} → ${ride.arrival_city}`;
    card.querySelector('.ride-date').textContent = new Date(ride.departure_time).toLocaleDateString('fr-FR');
    card.querySelector('.ride-time').textContent = new Date(ride.departure_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    card.querySelector('.ride-price-amount').textContent = ride.price_per_seat;
    card.querySelector('.ride-status-text').textContent = ride.ride_status;

    // Gérer le badge éco
    const ecoBadge = card.querySelector('.ride-eco-badge');
    if (ride.is_eco_ride) {
        ecoBadge.classList.remove('d-none');
    } else {
        ecoBadge.classList.add('d-none');
    }

    // Gérer le rôle (conducteur/passager) - À déterminer si c'est un trajet conduit ou réservé
    // Pour l'instant, on peut laisser vide ou ajouter une logique plus tard
    // card.querySelector('.ride-role').textContent = 'Passager'; // Ou 'Conducteur'

    // Gérer les actions (boutons annuler, noter, etc.)
    const rideActionsContainer = card.querySelector('.ride-actions');
    // Exemple: Ajouter un bouton d'annulation pour les trajets à venir
    if (ride.ride_status === 'planned') {
        const cancelButton = createElement('button', ['btn', 'btn-sm', 'btn-danger', 'cancel-ride-btn'], { 'data-ride-id': ride.ride_id }, 'Annuler');
        rideActionsContainer.appendChild(cancelButton);

        // Ajouter l'écouteur d'événement pour le bouton d'annulation
        cancelButton.addEventListener('click', async () => {
            if (confirm('Êtes-vous sûr de vouloir annuler ce trajet ? Cette action est irréversible.')) {
                try {
                    const response = await apiClient.cancelRide(ride.ride_id);
                    if (response.success) {
                        displayFlashMessage(response.message, 'success');
                        // Recharger les trajets après annulation réussie
                        loadUserRides();
                    } else {
                        displayFlashMessage(response.message || "Erreur lors de l'annulation du trajet.", 'danger');
                    }
                } catch (error) {
                    console.error("Erreur lors de l'appel API d'annulation:", error);
                    displayFlashMessage("Une erreur de communication est survenue lors de l'annulation.", 'danger');
                }
            }
        });
    }

    return card;
}

/**
 * Affiche les trajets dans le conteneur spécifié.
 * @param {HTMLElement} container L'élément conteneur.
 * @param {Array} rides Le tableau de trajets.
 */
function displayRides(container, rides) {
    clearChildren(container);
    if (rides && rides.length > 0) {
        rides.forEach(ride => {
            container.appendChild(createRideCard(ride));
        });
    } else {
        container.appendChild(createElement('p', ['text-muted', 'text-center'], {}, 'Aucun trajet pour le moment.'));
    }
}

/**
 * Charge et affiche les trajets de l'utilisateur.
 */
async function loadUserRides() {
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
        } else {
            displayFlashMessage(upcomingRidesResponse.message || 'Erreur lors du chargement des trajets à venir.', 'danger');
        }

        // Récupérer les trajets passés
        const pastRidesResponse = await apiClient.getUserRides('past');
        if (pastRidesResponse.success) {
            displayRides(pastRidesContainer, pastRidesResponse.rides);
        } else {
            displayFlashMessage(pastRidesResponse.message || 'Erreur lors du chargement des trajets passés.', 'danger');
        }

        // Afficher le message 'aucun trajet' si toutes les listes sont vides
        if (allRidesResponse.rides.length === 0) {
            noRidesMessage.classList.remove('d-none');
        }

    } catch (error) {
        console.error("Erreur lors du chargement des trajets de l'utilisateur:", error);
        displayFlashMessage('Une erreur de communication est survenue lors du chargement de vos trajets.', 'danger');
    }
}

document.addEventListener('DOMContentLoaded', loadUserRides);
