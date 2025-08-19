/**
 * SearchForm.js
 * Gère la logique du formulaire de recherche de trajet principal.
 * Ce composant est conçu pour être réutilisable (ex: sur la page d'accueil et la page de résultats).
 */
export class SearchForm {
    constructor(formId) {
        this.form = document.getElementById(formId);
        if (!this.form) {
            console.error(`Le formulaire avec l'ID '${formId}' est introuvable.`);
            return;
        }
        this.departureInput = this.form.querySelector('#search-form-departure');
        this.destinationInput = this.form.querySelector('#search-form-destination');
        this.dateInput = this.form.querySelector('#search-form-date');
        this.passengersInput = this.form.querySelector('#search-form-passenger-numbers');

        this.init();
    }

    init() {
        this.prefillFormFromURL();
        this.form.addEventListener('submit', this.handleSubmit.bind(this));
        if (this.dateInput) {
            this.dateInput.addEventListener('input', () => {
                this.dateInput.setCustomValidity("");
            });
        }
    }

    prefillFormFromURL() {
        const queryParams = new URLSearchParams(window.location.search);
        if (this.departureInput) this.departureInput.value = queryParams.get('departure_city') || '';
        if (this.destinationInput) this.destinationInput.value = queryParams.get('arrival_city') || '';
        if (this.dateInput) this.dateInput.value = queryParams.get('date') || '';
        if (this.passengersInput) this.passengersInput.value = queryParams.get('seats') || '1';
    }

    validateForm() {
        if (!this.form.checkValidity()) {
            this.form.reportValidity();
            return false;
        }

        if (this.dateInput && this.dateInput.value) {
            const today = new Date();
            const selectedDate = new Date(this.dateInput.value);
            today.setHours(0, 0, 0, 0);
            selectedDate.setHours(0, 0, 0, 0);
            if (selectedDate < today) {
                this.dateInput.setCustomValidity("La date du trajet ne peut pas être dans le passé.");
                this.form.reportValidity();
                return false;
            }
        }
        return true;
    }

    handleSubmit(event) {
        event.preventDefault();

        if (!this.validateForm()) {
            return;
        }

        const searchCriteria = {
            departure_city: this.departureInput.value.trim(),
            arrival_city: this.destinationInput.value.trim(),
            date: this.dateInput.value,
            seats: parseInt(this.passengersInput.value, 10)
        };

        const newSearchParams = new URLSearchParams();
        newSearchParams.set('departure_city', searchCriteria.departure_city);
        newSearchParams.set('arrival_city', searchCriteria.arrival_city);
        newSearchParams.set('date', searchCriteria.date);
        newSearchParams.set('seats', searchCriteria.seats);

        const targetUrl = `/rides-search?${newSearchParams.toString()}`;

        // Si on est déjà sur la page de recherche, on utilise l'history API pour éviter un rechargement complet.
        // Sinon, on fait une redirection classique.
        if (window.location.pathname === '/rides-search') {
            window.history.pushState(Object.fromEntries(newSearchParams), '', targetUrl);
            // Déclencher un événement pour que la page de recherche sache qu'elle doit se mettre à jour
            window.dispatchEvent(new CustomEvent('search-updated', { detail: newSearchParams }));
        } else {
            window.location.href = targetUrl;
        }
    }
}
