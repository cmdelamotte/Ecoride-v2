/**
 * @jest-environment jsdom
 */

describe('yourRidesPageHandler.js', () => {

    // Isoler les tests pour la fonction pure ne nécessite pas de DOM
    describe('calculateDuration', () => {
        test('devrait fonctionner correctement pour diverses durées', () => {
            // On importe la fonction ici, dans un contexte sans DOM
            const { calculateDuration } = require('../../../public/js/pages/yourRidesPageHandler.js');
            expect(calculateDuration('2025-01-01 10:00:00', '2025-01-01 11:30:00')).toBe('1h30');
            expect(calculateDuration('2025-01-01 10:00:00', '2025-01-01 10:05:00')).toBe('0h05');
            expect(calculateDuration('2025-01-01 12:00:00', '2025-01-01 11:00:00')).toBe('N/A');
        });
    });

    // Isoler les tests pour la fonction qui dépend du DOM
    describe('createRideCard', () => {
        let createRideCard;

        beforeAll(() => {
            global.currentUserId = 10;
        });

        beforeEach(() => {
            // 1. On s'assure que le cache des modules est vidé
            jest.resetModules();

            // 2. On crée le DOM dont le script a besoin LORS de son exécution
            document.body.innerHTML = `
                <template id="ride-card-template">
                    <div class="card">
                        <div class="card-header">
                            <span class="ride-id"></span>
                        </div>
                        <div class="card-body">
                            <h5 class="ride-title"></h5>
                            <p class="ride-date"></p>
                            <p class="ride-time"></p>
                            <p class="ride-duration"></p>
                            <p class="ride-vehicle-details"></p>
                            <div class="role-specific-info">
                                <span class="price-label"></span>
                                <span class="ride-price-amount"></span>
                            </div>
                            <p class="ride-status-text"></p>
                            <p>
                                <span class="ride-passengers-current"></span> / 
                                <span class="ride-passengers-max"></span> passagers
                            </p>
                            <span class="ride-eco-badge d-none"></span>
                            <div class="ride-actions"></div>
                            <div class="contact-info-section d-none">
                                <div class="contact-driver-info d-none">
                                    <a class="driver-phone-link"></a>
                                    <span class="driver-email-text"></span>
                                </div>
                                <div class="contact-passengers-info d-none">
                                    <ul class="passengers-contact-list"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            `;

            // 3. On importe le module MAINTENANT. Son code de haut niveau s'exécute et trouve le template.
            const handler = require('../../../public/js/pages/yourRidesPageHandler.js');
            createRideCard = handler.createRideCard;
        });

        test('devrait afficher la carte en mode conducteur', () => {
            const mockRide = {
                ride_id: 1, departure_city: 'A', arrival_city: 'B',
                departure_time: '2025-01-01 10:00:00', estimated_arrival_time: '2025-01-01 11:00:00',
                vehicle_brand_name: 'Testla', vehicle_model: 'Model T',
                driver_id: 10, // L'utilisateur est le conducteur
                estimated_earnings_per_passenger: 15, passengers_count: 2,
                ride_status: 'planned', seats_offered: 3, is_eco_ride: true
            };

            const card = createRideCard(mockRide);

            expect(card.querySelector('.ride-title').textContent).toBe('A → B');
            expect(card.querySelector('.price-label').textContent).toBe('Gain estimé : ');
            expect(card.querySelector('.ride-price-amount').textContent).toBe('30.00');
            expect(card.querySelector('.action-start-ride')).not.toBeNull();
        });

        test('devrait afficher la carte en mode passager', () => {
            const mockRide = {
                ride_id: 2, departure_city: 'C', arrival_city: 'D',
                departure_time: '2025-01-01 10:00:00', estimated_arrival_time: '2025-01-01 11:00:00',
                vehicle_brand_name: 'Testla', vehicle_model: 'Model T',
                driver_id: 99, // L'utilisateur n'est pas le conducteur
                price_per_seat: '20', ride_status: 'completed', seats_offered: 3, is_eco_ride: false
            };

            const card = createRideCard(mockRide);

            expect(card.querySelector('.price-label').textContent).toBe('Prix payé : ');
            expect(card.querySelector('.ride-price-amount').textContent).toBe('20 crédits');
            expect(card.querySelector('.action-leave-review')).not.toBeNull();
        });
    });
});
