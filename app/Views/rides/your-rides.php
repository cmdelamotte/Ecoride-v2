<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Mes Trajets</h1>
        <p class="banner-subtitle lead">Votre historique de covoiturages passés et à venir</p>
    </div>
</section>

<section class="rides-history-section py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-10">

                <div id="current-ride-highlight" class="mb-4 d-none">
                    </div>
                <ul class="nav nav-tabs mb-4 justify-content-center" id="ridesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="upcoming-rides-tab" data-bs-toggle="tab" data-bs-target="#upcoming-rides" type="button" role="tab" aria-controls="upcoming-rides" aria-selected="true">Trajets à venir</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="past-rides-tab" data-bs-toggle="tab" data-bs-target="#past-rides" type="button" role="tab" aria-controls="past-rides" aria-selected="false">Trajets passés</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="all-rides-tab" data-bs-toggle="tab" data-bs-target="#all-rides" type="button" role="tab" aria-controls="all-rides" aria-selected="false">Tous mes trajets</button>
                    </li>
                </ul>

                <div class="tab-content" id="ridesTabsContent">
                    <div class="tab-pane fade show active" id="upcoming-rides" role="tabpanel" aria-labelledby="upcoming-rides-tab">
                        <h3 class="visually-hidden">Trajets à venir</h3>
                        <div class="rides-list-container"></div>
                    </div>

                    <div class="tab-pane fade" id="past-rides" role="tabpanel" aria-labelledby="past-rides-tab">
                        <h3 class="visually-hidden">Trajets passés</h3>
                        <div class="rides-list-container"></div>
                    </div>

                    <div class="tab-pane fade" id="all-rides" role="tabpanel" aria-labelledby="all-rides-tab">
                        <p class="text-center text-muted mt-4">Affichage de tous les trajets (à venir et passés).</p>
                        <div class="rides-list-container"></div>
                    </div>
                </div>
                
                <div id="no-rides-message" class="text-center py-5 d-none"> 
                    <i class="bi bi-car-front display-1 text-muted"></i>
                    <p class="lead mt-3">Vous n'avez aucun trajet dans votre historique pour le moment.</p>
                    <a href="/rides-search" class="btn primary-btn mt-2">Rechercher un trajet</a>
                    <a href="/publish-ride" class="btn secondary-btn mt-2">Proposer un trajet</a>
                </div>

            </div>
        </div>
    </div>

<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> 
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Votre avis sur le trajet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="submit-review-form">
                <div class="modal-body"> 
                    <p class="mb-1">Trajet : <strong id="review-modal-ride-details">[Ville Départ] → [Ville Arrivée]</strong></p>
                    <p>Avec : <strong id="review-modal-driver-name">[PseudoChauffeur]</strong></p>
                    <hr>

                    <div class="mb-3">
                        <label class="form-label mb-1">Le trajet s'est-il globalement bien passé ?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tripOverallExperience" id="tripGood" value="good" checked>
                            <label class="form-check-label" for="tripGood">
                                Oui, tout s'est bien déroulé.
                            </label>
                            </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tripOverallExperience" id="tripBad" value="bad">
                            <label class="form-check-label" for="tripBad">
                                Non, j'ai rencontré un problème.
                            </label>
                            </div>
                    </div>

                    <div class="mb-3">
                        <label for="review-rating-stars" class="form-label mb-1">Votre note pour [PseudoChauffeur] :</label> <div id="review-rating-stars" class="rating-stars mb-2">
                            <i class="bi bi-star" data-value="1" role="button" tabindex="0" aria-label="Donner 1 étoile"></i>
                            <i class="bi bi-star" data-value="2" role="button" tabindex="0" aria-label="Donner 2 étoiles"></i>
                            <i class="bi bi-star" data-value="3" role="button" tabindex="0" aria-label="Donner 3 étoiles"></i>
                            <i class="bi bi-star" data-value="4" role="button" tabindex="0" aria-label="Donner 4 étoiles"></i>
                            <i class="bi bi-star" data-value="5" role="button" tabindex="0" aria-label="Donner 5 étoiles"></i>
                            </div>
                        <input type="hidden" name="ratingValue" id="ratingValueHiddenInput">
                        <div id="rating-error-message" class="text-danger d-none" style="font-size: 0.875em;"></div> 
                    </div>

                    <div class="mb-3">
                        <label for="review-comment" class="form-label mb-1">Votre commentaire (optionnel) :</label>
                        <textarea class="form-control form-control-custom-modal" id="review-comment" rows="4" placeholder="Partagez votre expérience..."></textarea>
                    </div>

                    <div id="report-problem-section" class="mb-3 border p-3 rounded bg-light d-none">
                        <label for="report-comment" class="form-label mb-1 text-danger">Veuillez décrire le problème rencontré :</label>
                        <textarea class="form-control form-control-custom-modal" id="report-comment" rows="4" placeholder="Expliquez en détail ce qui ne s'est pas bien passé..."></textarea>
                        <small class="form-text text-muted">Ces informations seront transmises à notre équipe pour examen.</small>
                    </div>

                </div>
                <div class="modal-footer"> 
                    <button type="button" class="btn secondary-btn" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn primary-btn" id="submit-review-btn">Soumettre l'avis</button>
                </div>
            </form>
        </div>
    </div>
</div>
</section>

<template id="ride-card-template">
    <div class="card ride-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <span class="badge ride-role"></span> <span class="ms-2 ride-eco-badge d-none"><i class="bi bi-leaf-fill text-success"></i> Éco</span>
            </div>
            <span class="text-muted ride-id"></span> </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h5 class="card-title mb-1 ride-title"></h5> <p class="card-text mb-1 ride-datetime">
                        <i class="bi bi-calendar3 me-1"></i> <span class="ride-date"></span> 
                        <i class="bi bi-dot"></i> <i class="bi bi-clock me-1"></i> <span class="ride-time"></span>
                    </p>
                    <p class="card-text mb-1 ride-duration-info"> 
                        <i class="bi bi-hourglass-split me-1"></i> <span class="form-label">Durée estimée :</span> <span class="ride-duration"></span>
                    </p>
                    <div class="role-specific-info">
                        <p class="card-text mb-1 passenger-view-driver-info d-none">
                            <span class="form-label">Chauffeur :</span> <span class="ride-driver-name"></span> 
                            (<i class="bi bi-star-fill text-warning"></i> <span class="ride-driver-rating"></span>)
                        </p>
                        <p class="card-text mb-1 ride-vehicle-info">
                            <span class="form-label">Véhicule :</span> <span class="ride-vehicle-details"></span>
                        </p>
                        <p class="card-text mb-1 driver-view-passengers-info d-none">
                            <span class="form-label">Passagers inscrits :</span> <span class="ride-passengers-current"></span> / <span class="ride-passengers-max"></span>
                        </p>
                        <p class="card-text mb-1 ride-price-info">
                            <span class="form-label price-label"></span><span class="ride-price-amount"></span> crédits
                        </p>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-2 mt-md-0 align-self-center ride-actions">
                    </div>
            </div>
        </div>
        <div class="card-footer text-muted ride-status-footer">
            Statut : <span class="ride-status-text"></span>
        </div>
    </div>
</template>