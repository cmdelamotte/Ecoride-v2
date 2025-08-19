import { RideCard } from '../../../public/js/components/RideCard.js';
import { apiClient } from '../../../public/js/utils/apiClient.js';

// On dit à Jest de remplacer apiClient par une simulation (un mock)
jest.mock('../../../public/js/utils/apiClient.js');

/**
 * @jest-environment jsdom
 */
describe('RideCard', () => {

  const mockRideData = {
    ride_id: 101,
    driver_username: 'Test Driver',
    driver_photo: 'img/driver.png',
    departure_city: 'Ville de Départ',
    arrival_city: "Ville d'Arrivée",
    departure_time: '2025-08-20 10:00:00',
    estimated_arrival_time: '2025-08-20 12:30:00',
    price_per_seat: '25',
    seats_available: 3,
    is_eco_ride: true,
  };

  const mockRideDetailsData = {
    vehicle_brand_name: 'Test Brand',
    vehicle_model: 'Test Model',
    reviews: [],
  };

  beforeEach(() => {
    // Ce template est maintenant beaucoup plus complet pour correspondre à ce que le composant attend.
    document.body.innerHTML = `
      <template id="ride-card-template">
        <div class="ride-card">
          <img class="driver-profile-photo">
          <p class="driver-username"></p>
          <p class="ride-departure-location"></p>
          <p class="ride-arrival-location"></p>
          <p class="ride-departure-time"></p>
          <p class="ride-estimated-duration"></p>
          <p class="ride-price"></p>
          <p class="ride-available-seats"></p>
          <div class="form-check">
            <input class="is-ride-eco" type="checkbox" id="ecoCheck_ride_template">
            <label class="is-ride-eco" for="ecoCheck_ride_template"></label>
          </div>
          <button class="ride-details-button"></button>
          <div class="collapse">
            <div class="loading-details-message d-none">Chargement...</div>
            <div class="error-details-message d-none">Erreur.</div>
            <div class="ride-details-content-wrapper">
                <p class="ride-departure-address-details"></p>
                <p class="ride-arrival-address-details"></p>
                <div class="vehicle-info-container">
                    <p class="ride-car-model"></p>
                    <p class="ride-car-registration-year"></p>
                    <p class="ride-car-energy"></p>
                </div>
                <div class="driver-preferences-text"></div>
                <p class="no-prefs-message d-none"></p>
                <div class="driver-reviews-container"></div>
            </div>
          </div>
        </div>
      </template>
      <template id="driver-review-item-template">
        <div class="review-item">
            <p class="review-author"></p>
            <p class="review-date"></p>
            <div class="review-stars"></div>
            <p class="review-comment"></p>
        </div>
      </template>
    `;
    apiClient.getRideDetails.mockClear();
  });

  test('devrait afficher correctement les informations de base du trajet', () => {
    const card = new RideCard(mockRideData);
    const element = card.element;

    expect(element.querySelector('.driver-username').textContent).toBe('Test Driver');
    expect(element.querySelector('.ride-departure-location').textContent).toBe('Ville de Départ');
    expect(element.querySelector('.ride-price').textContent).toBe('25 crédits');
    expect(element.querySelector('.ride-estimated-duration').textContent).toBe('2h30');
  });

  test('devrait charger et afficher les détails lors du clic', async () => {
    apiClient.getRideDetails.mockResolvedValue({
      success: true,
      details: mockRideDetailsData
    });

    const card = new RideCard(mockRideData);
    const element = card.element;
    const detailsButton = element.querySelector('.ride-details-button');

    detailsButton.click();

    expect(apiClient.getRideDetails).toHaveBeenCalledWith(mockRideData.ride_id);

    await new Promise(resolve => setTimeout(resolve, 0));

    const modelElement = element.querySelector('.ride-car-model');
    expect(modelElement.textContent).toBe('Test Brand Test Model');
  });
});
