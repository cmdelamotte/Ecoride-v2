/**
 * FilterForm.js
 * Gère la logique du formulaire de filtres de recherche de trajets.
 */
export class FilterForm {
    constructor(formId) {
        this.form = document.getElementById(formId);
        if (!this.form) {
            console.error(`Le formulaire avec l'ID '${formId}' est introuvable.`);
            return;
        }
        this.priceRangeInput = this.form.querySelector('#price-filter');
        this.durationRangeInput = this.form.querySelector('#duration-filter-range');
        this.ecoSwitch = this.form.querySelector('#eco-filter');
        this.resetButton = this.form.querySelector('button[type="button"].secondary-btn');

        this.init();
    }

    init() {
        this.prefillFormFromURL();
        this.setupEventListeners();
    }

    prefillFormFromURL() {
        const queryParams = new URLSearchParams(window.location.search);

        if (this.priceRangeInput && queryParams.has('maxPrice')) {
            this.priceRangeInput.value = queryParams.get('maxPrice');
            this.updatePriceOutputDisplay(this.priceRangeInput.value);
        }
        if (this.durationRangeInput && queryParams.has('maxDuration')) {
            this.durationRangeInput.value = queryParams.get('maxDuration');
            this.updateDurationOutputDisplay(this.durationRangeInput.value);
        }
        if (this.ecoSwitch && queryParams.has('ecoOnly')) {
            this.ecoSwitch.checked = (queryParams.get('ecoOnly') === 'true');
        }

        // Pour les radios (animalsAllowed, minRating)
        ['animal-option', 'rating-options'].forEach(name => {
            const paramName = name === 'animal-option' ? 'animalsAllowed' : 'minRating';
            if (queryParams.has(paramName)) {
                const value = queryParams.get(paramName);
                const radios = this.form.querySelectorAll(`input[name="${name}"]`);
                radios.forEach(radio => {
                    if (radio.value === value) {
                        radio.checked = true;
                    }
                });
            }
        });
    }

    setupEventListeners() {
        if (this.priceRangeInput) {
            this.priceRangeInput.addEventListener('input', (e) => this.updatePriceOutputDisplay(e.target.value));
        }
        if (this.durationRangeInput) {
            this.durationRangeInput.addEventListener('input', (e) => this.updateDurationOutputDisplay(e.target.value));
        }

        this.form.addEventListener('submit', this.handleSubmit.bind(this));

        if (this.resetButton) {
            this.resetButton.addEventListener('click', this.handleReset.bind(this));
        }
    }

    updateDurationOutputDisplay(valueString) {
        const outputElement = this.form.querySelector('#duration-output');
        if (outputElement) {
            const value = parseFloat(valueString);
            const hours = Math.floor(value);
            const minutes = (value - hours) * 60;
            outputElement.textContent = `${hours}h${minutes < 10 ? '0' : ''}${minutes}`;
        }
    }

    updatePriceOutputDisplay(valueString) {
        const outputElement = this.form.querySelector('#price-output');
        if (outputElement) {
            outputElement.textContent = `${valueString} crédits`;
        }
    }

    handleSubmit(event) {
        event.preventDefault();

        const currentSearchParams = new URLSearchParams(window.location.search);

        // Supprimer les anciens paramètres de filtre pour éviter les doublons
        ['maxPrice', 'maxDuration', 'animalsAllowed', 'minRating', 'ecoOnly', 'page'].forEach(key => currentSearchParams.delete(key));
        currentSearchParams.set('page', '1'); // Toujours aller à la page 1 quand on applique de nouveaux filtres

        // Ajouter les nouveaux filtres depuis le formulaire de filtre
        const formData = new FormData(this.form);
        if (formData.get('price-filter')) currentSearchParams.set('maxPrice', formData.get('price-filter'));
        if (formData.get('duration-filter-range')) currentSearchParams.set('maxDuration', formData.get('duration-filter-range'));
        
        const animalOption = formData.get('animal-option');
        if (animalOption && animalOption !== "") currentSearchParams.set('animalsAllowed', animalOption);
        
        const ratingOption = formData.get('rating-options');
        if (ratingOption && ratingOption !== "0") currentSearchParams.set('minRating', ratingOption);
        
        if (this.ecoSwitch?.checked) currentSearchParams.set('ecoOnly', 'true');
        else currentSearchParams.delete('ecoOnly');

        const newUrl = `${window.location.pathname}?${currentSearchParams.toString()}`;
        window.history.pushState({ filtersApplied: Object.fromEntries(currentSearchParams) }, "", newUrl);
        
        // Déclencher un événement pour que la page de recherche sache qu'elle doit se mettre à jour
        window.dispatchEvent(new CustomEvent('search-updated', { detail: currentSearchParams }));
    }

    handleReset() {
        const currentSearchParams = new URLSearchParams(window.location.search);
        // Garder les paramètres de recherche principaux, supprimer les filtres et la page
        const searchCriteriaToKeep = {};
        ['departure_city', 'arrival_city', 'date', 'seats'].forEach(key => {
            if (currentSearchParams.has(key)) searchCriteriaToKeep[key] = currentSearchParams.get(key);
        });
        
        const newUrl = `${window.location.pathname}?${new URLSearchParams(searchCriteriaToKeep).toString()}`;
        window.history.pushState({ filtersReset: true }, "", newUrl);
        
        // Recharger la page entière pour que prefillFilterFormFromURL remette les valeurs par défaut des filtres aussi
        // ou déclencher un événement pour que ridesSearchPage.js recharge les données sans les filtres.
        window.dispatchEvent(new CustomEvent('search-updated', { detail: new URLSearchParams(searchCriteriaToKeep) }));
    }
}
