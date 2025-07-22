

<?php
// C:\laragon\www\Ecoride\New_Code\app\Views\rides\publish.php

// Titre de la page
$pageTitle = 'Publier un nouveau trajet';
?>

<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3"><?= htmlspecialchars($pageTitle) ?></h1>
        <p class="banner-subtitle lead">Proposez votre itinéraire à la communauté EcoRide</p>
    </div>
</section>

<section class="form-section publish-ride-section py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <form id="publish-ride-form" novalidate>
                    <div class="row g-3">

                        <!-- Champ Ville de Départ -->
                        <div class="col-12">
                            <label for="departure-city" class="form-label visually-hidden">Ville de Départ</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-geo-alt me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1" id="departure-city" name="departure_city" placeholder="Ville de Départ" aria-label="Ville de départ" required>
                            </div>
                            <div class="invalid-feedback">Veuillez saisir une ville de départ.</div>
                        </div>

                        <!-- Champ Adresse de Départ -->
                        <div class="col-12">
                            <label for="departure-address" class="form-label visually-hidden">Adresse de départ</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-signpost-split me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1" id="departure-address" name="departure_address" placeholder="Adresse précise de départ" aria-label="Adresse précise de départ" required>
                            </div>
                            <div class="invalid-feedback">Veuillez saisir une adresse de départ.</div>
                        </div>

                        <!-- Champ Ville de Destination -->
                        <div class="col-12">
                            <label for="arrival-city" class="form-label visually-hidden">Ville de Destination</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-flag me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1" id="arrival-city" name="arrival_city" placeholder="Ville de Destination" aria-label="Ville de Destination" required>
                            </div>
                            <div class="invalid-feedback">Veuillez saisir une ville de destination.</div>
                        </div>

                        <!-- Champ Adresse de Destination -->
                        <div class="col-12">
                            <label for="arrival-address" class="form-label visually-hidden">Adresse de destination</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-pin-map-fill me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1" id="arrival-address" name="arrival_address" placeholder="Adresse précise de destination" aria-label="Adresse précise de destination" required>
                            </div>
                            <div class="invalid-feedback">Veuillez saisir une adresse de destination.</div>
                        </div>

                        <!-- Champ Date et Heure de Départ -->
                        <div class="col-md-6">
                            <label for="departure-datetime" class="label-custom mb-1">Date et heure de départ</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-calendar-event me-2"></i>
                                <input type="datetime-local" class="form-control-custom flex-grow-1" id="departure-datetime" name="departure_datetime" aria-label="Date et heure de départ" required>
                            </div>
                            <div class="invalid-feedback">Veuillez saisir une date et une heure de départ valides.</div>
                        </div>

                        <!-- Champ Date et Heure d'Arrivée Estimée -->
                        <div class="col-md-6">
                            <label for="estimated-arrival-datetime" class="label-custom mb-1">Date et heure d'arrivée estimée</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-calendar-check me-2"></i>
                                <input type="datetime-local" class="form-control-custom flex-grow-1" id="estimated-arrival-datetime" name="estimated_arrival_datetime" aria-label="Date et heure d'arrivée estimée" required>
                            </div>
                            <div class="invalid-feedback">Veuillez saisir une date et une heure d'arrivée valides.</div>
                        </div>

                        <!-- Champ Places Disponibles -->
                        <div class="col-md-6">
                            <label for="seats-offered" class="form-label visually-hidden">Places disponibles</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-people me-2"></i>
                                <input type="number" class="form-control-custom flex-grow-1" id="seats-offered" name="seats_offered" placeholder="Places disponibles" aria-label="Nombre de places disponibles" min="1" max="8" required>
                            </div>
                            <div class="invalid-feedback">Veuillez indiquer un nombre de places entre 1 et 8.</div>
                        </div>

                        <!-- Champ Prix par Passager -->
                        <div class="col-md-6">
                            <label for="price-per-seat" class="form-label visually-hidden">Prix par passager</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-coin me-2"></i>
                                <input type="number" class="form-control-custom flex-grow-1" id="price-per-seat" name="price_per_seat" placeholder="Prix par passager (crédits)" aria-label="Prix par passager en crédits" min="2" step="0.5" required>
                            </div>
                            <small class="form-text text-muted ms-1">La plateforme prélève 2 crédits par trajet.</small>
                            <div class="invalid-feedback">Le prix doit être d'au moins 2 crédits.</div>
                        </div>

                        <!-- Champ Sélection du Véhicule -->
                        <div class="col-12">
                            <label for="vehicle-id" class="form-label visually-hidden">Choisissez votre véhicule</label>
                            <div class="form-input-custom">
                                <select class="form-select-custom flex-grow-1" id="vehicle-id" name="vehicle_id" aria-label="Choisissez votre véhicule" required>
                                    <option selected disabled value="">Chargement des véhicules...</option>
                                    <!-- Les options seront chargées par JavaScript -->
                                </select>
                            </div>
                            <div class="invalid-feedback">Veuillez sélectionner un véhicule.</div>
                            <div class="text-center mt-3">
                                <a href="/account" class="btn secondary-btn btn-sm">
                                    <i class="bi bi-car-front me-1"></i> Gérer mes véhicules
                                </a>
                            </div>
                        </div>
                        
                        <!-- Champ Message pour les Passagers -->
                        <div class="col-12">
                            <label for="driver-message" class="form-label visually-hidden">Message pour les passagers (optionnel)</label>
                            <div class="form-input-custom">
                                <textarea class="form-control-custom flex-grow-1" id="driver-message" name="driver_message" rows="3" placeholder="Message pour les passagers (ex: point de RDV précis, bagages autorisés...)" aria-label="Message pour les passagers" maxlength="1000"></textarea>
                            </div>
                            <div class="invalid-feedback">Le message ne doit pas dépasser 1000 caractères.</div>
                        </div>

                        <!-- Conteneur pour les messages d'erreur/succès -->
                        <div class="col-12 mt-3">
                            <div id="publish-ride-message" class="alert d-none" role="alert"></div>
                        </div>

                        <!-- Bouton de Soumission -->
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn primary-btn btn-lg">Publier le Trajet</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

