<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Trajets disponibles</h1>
        <p class="banner-subtitle lead">Résultats de votre recherche</p>
    </div>
</section>

<section class="form-section search-results-section py-4">
    <div class="container">
        <form id="search-form">
            <div class="row g-3">
                <div class="col-lg col-md-6 col-12">
                    <div class="form-input-custom d-flex align-items-center">
                        <i class="bi bi-geo-alt me-2"></i>
                        <input id="search-form-departure" type="text" class="form-control-custom flex-grow-1" placeholder="Départ" aria-label="Lieu de départ" required>
                    </div>
                </div>

                <div class="col-lg col-md-6 col-12">
                    <div class="form-input-custom d-flex align-items-center">
                        <i class="bi bi-flag me-2"></i>
                        <input id="search-form-destination" type="text" class="form-control-custom flex-grow-1" placeholder="Destination" aria-label="Lieu de destination" required>
                    </div>
                </div>

                <div class="col-lg col-md-6 col-12">
                    <div class="form-input-custom d-flex align-items-center">
                        <i class="bi bi-calendar-check me-2"></i>
                        <input id="search-form-date" type="date" class="form-control-custom flex-grow-1" placeholder="Date" aria-label="Date du trajet" required>
                    </div>
                </div>

                <div class="col-lg col-md-6 col-12">
                    <div class="form-input-custom d-flex align-items-center">
                        <i class="bi bi-person me-2"></i>
                        <input id="search-form-passenger-numbers" type="number" class="form-control-custom flex-grow-1" placeholder="Passagers" aria-label="Nombre de passagers" min="1" value="1" required>
                    </div>
                </div>

                <div class="col-lg-auto col-md-12 col-12">
                    <div class="d-grid">
                        <button id="search-form-submit" type="submit" class="btn primary-btn">
                            <i class="bi bi-search me-1"></i> Rechercher
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <div id="search-form-error-message" class="alert alert-danger mt-3 d-none" role="alert"></div>
    </div>
</section>

<section>
    <div class="row justify-content-center mb-4">
        <div class="col-12 col-md-10 col-lg-8 text-center">
            <button class="btn secondary-btn" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                <i class="bi bi-filter me-1"></i> Filtrer
            </button>
        </div>
        <div class="collapse mt-3 col-12 col-md-10 col-lg-10" id="filterCollapse">
            <div class="card card-body">
                <h5 class="mb-3 form-label">Affiner les résultats</h5>
                <form id="filter-form">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label for="price-filter" class="form-label">Prix maximum (crédits)</label>
                            <input type="range" class="form-range" min="0" max="100" step="5" id="price-filter" name="price-filter">
                            <output class="badge bg-secondary" id="price-output">50</output>
                        </div>

                        <div class="col-md-6">
                            <label for="duration-filter-range" class="form-label">Durée maximum du trajet</label>
                            <input type="range" 
                                class="form-range" 
                                min="0.5" 
                                max="24" 
                                step="0.5" 
                                id="duration-filter-range" 
                                name="duration_filter_range" 
                                value="8">
                            <output for="duration-filter-range" class="badge bg-secondary" id="duration-output">4h00</output>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-block mb-2">Animaux autorisés</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="animal-option" id="animal-any" value="" checked>
                                    <label class="form-check-label" for="animalAny">Indifférent</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="animal-option" id="animal-yes" value="true">
                                    <label class="form-check-label" for="animalYes">Oui</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="animal-option" id="animal-no" value="false">
                                    <label class="form-check-label" for="animalNo">Non</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="rating-filter" class="form-label">Note minimale du conducteur</label>
                            <div id="rating-filter">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="rating-options" id="rating-any" value="0" checked>
                                    <label class="form-check-label" for="ratingAny">Tout</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="rating-options" id="rating-4" value="4">
                                    <label class="form-check-label" for="rating4">4+ <i class="bi bi-star-fill text-warning"></i></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="rating-options" id="rating-4.5" value="4.5">
                                    <label class="form-check-label" for="rating4.5">4.5+ <i class="bi bi-star-fill text-warning"></i></label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="eco-filter" name="eco-filter">
                                <label class="form-check-label form-label" for="eco-filter">Voyage Écologique uniquement</label>
                            </div>
                        </div>

                        <div class="col-12 text-end mt-3">
                            <button type="button" class="btn btn-sm secondary-btn me-2">Réinitialiser</button>
                            <button type="submit" class="btn btn-sm primary-btn">Appliquer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
        
        <div class="row justify-content-center mt-4">
            <div class="col-12 col-md-10 col-lg-8">

                <div id="loading-indicator" class="alert alert-info text-center d-none" role="status">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    Recherche des trajets en cours...
                </div>

                <div id="no-results-message" class="alert alert-warning text-center d-none" role="alert">
                </div>

                <div id="ride-results-container" class="row justify-content-center">
                    <div class = "col-12 col-md-10 col-lg-8"><!-- Injection des cartes de trajets ici --></div>
                </div>
            </div>
        </div>

            <nav aria-label="Navigation des pages de résultats" class="mt-4 d-flex justify-content-center d-none">
                <ul class="pagination"></ul>
            </nav>

            <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmationModalLabel">Confirmer la participation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p>Vous êtes sur le point de réserver le trajet :</p>
                            <p><strong>De :</strong> <span id="modal-ride-departure-location">Ville Départ Exemple</span></p>
                            <p><strong>À :</strong> <span id="modal-ride-arrival-location">Ville Arrivée Exemple</span></p>
                            <p><strong>Le :</strong> <span id="modal-ride-date-text">Date Exemple</span> à <span id="modal-ride-time-text">Heure Exemple</span></p>
                            <p class="mt-3">Cela utilisera <strong id="modal-ride-credits-cost"></strong> crédits de votre compte.</p>
                            <p>Confirmez-vous votre participation ?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn secondary-btn" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn primary-btn" id="confirm-booking-btn">Confirmer et utiliser les crédits</button>
                        </div>
                    </div>
                </div>
            </div>
</section>

<template id="ride-card-template">
        <div class="card mb-3 ride-card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center text-md-start mb-2 mb-md-0">
                        <img alt="Photo de profil" class="rounded-circle mb-2 mx-auto d-block driver-profile-photo" width="50" height="50">
                        <span class="driver-username h5 card-title mb-1"></span>
                        <p class="card-text mb-0"><i class="bi bi-star-fill text-warning"></i><span class="driver-rating"></span></p>
                    </div>
                    <div class="col-md-6 mb-2 mb-md-0">
                        <p class="card-text mb-1"><span class="form-label">Ville de départ : </span><span class="ride-departure-location"></span></p>
                        <p class="card-text mb-1"><span class="form-label">Destination : </span><span class="ride-arrival-location"></span></p>
                        <p class="card-text mb-1"><span class="form-label">Heure de départ : </span><span class="ride-departure-time"></span></p>
                        <p class="card-text mb-1"><span class="form-label">Durée estimée : </span><span class="ride-estimated-duration"></span></p>
                        <p class="card-text mb-1"><span class="form-label">Prix : </span><span class="ride-price"></span></p>
                        <p class="card-text mb-1"><span class="form-label">Places dispo : </span><span class="ride-available-seats"></span></p>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input is-ride-eco" type="checkbox" id="ecoCheck_TEMPLATE_ID" value="option1">
                            <label class="form-check-label is-ride-eco" for="ecoCheck_TEMPLATE_ID">Voyage Écologique</label>
                        </div>
                    </div>
                    <div class="col-md-3 text-center text-md-end">
                        <button class="btn secondary-btn ride-details-button" type="button" data-bs-toggle="collapse" data-bs-target="#detailsCollapseRide_TEMPLATE_ID" aria-expanded="false" aria-controls="detailsCollapse_TEMPLATE_ID">
                            <i class="bi bi-filter me-1"></i> Détails
                        </button>
                    </div>
                    <div class="collapse mt-3" id="detailsCollapseRide_TEMPLATE_ID">
                        <div class="card card-body">
                            <div class="loading-details-message text-center py-3">Chargement des détails...</div>
                            <div class="error-details-message alert alert-danger d-none"></div>
                            <div class="ride-details-content-wrapper d-none">
                                <div class="ride-details-content row">
                                    <div class="col-md-12 adresses-container-details mb-2">
                                        <h5 class="mt-3 mb-2 form-label">Points de RDV</h5>
                                        <p class="mb-1"><span class="form-label">Adresse de départ : </span><span class="ride-departure-address-details"></span></p>
                                        <p class="mb-1"><span class="form-label">Adresse d'arrivée : </span><span class="ride-arrival-address-details"></span></p>
                                    </div>
                                    <hr>
                                    <div class="col-md-6 driver-reviews-container">
                                        <h5 class="mb-2 form-label">Avis sur le conducteur</h5>
                                    </div>

                                    <div class="col-md-6 vehicle-info-container">
                                        <h5 class="mb-2 form-label">Infos Véhicule</h5>
                                        <p class="mb-1"><span class="form-label">Modèle : </span><span class="ride-car-model"></span></p>
                                        <p class="mb-1"><span class="form-label">Année : </span><span class="ride-car-registration-year"></span></p>
                                        <p class="mb-1"><span class="form-label">Énergie : </span><span class="ride-car-energy"></span></p>
                                    </div>
                                    <div class="driver-preferences-container mt-3">
                                        <h5 class="mb-2 form-label">Préférences Conducteur</h5>
                                        <div class="driver-preferences-text">
                                        </div>
                                        <p class="small text-muted no-prefs-message d-none">Préférences non spécifiées.</p>
                                    </div>
                                    <hr class="my-3">
                                    <div class="text-center">
                                        <button class="btn primary-btn participate-button" type="button" data-bs-toggle="modal" data-bs-target="#confirmationModal">
                                            <i class="bi bi-check-circle me-1"></i> Participer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</template>


                                <template id="driver-review-item-template">
                                    <div class="review-item mb-2 small border-bottom pb-2">
                                        <p class="mb-0">
                                            <strong class="review-author"></strong> 
                                            <span class="review-date text-muted ms-1"></span> 
                                            <span class="review-stars ms-1"></span>
                                        </p>
                                        <p class="fst-italic mb-0 review-comment"></p>
                                    </div>
                                </template>

<script type="module" src="/js/pages/ridesSearchPage.js"></script>
