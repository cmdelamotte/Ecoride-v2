<?php
/**
 * Vue pour la page du compte utilisateur.
 * Affiche les informations de l'utilisateur, permet de gérer son rôle,
 * ses véhicules et ses préférences.
 *
 * @var \App\Models\User $user L'objet utilisateur contenant les données à afficher.
 */

// Je prépare les données pour l'affichage, en prévoyant des valeurs par défaut
// et en protégeant contre le XSS. C'est une bonne pratique de le faire en haut du fichier.
$username = htmlspecialchars($user->getUsername() ?? '[N/A]');
$lastName = htmlspecialchars($user->getLastName() ?? '[N/A]');
$firstName = htmlspecialchars($user->getFirstName() ?? '[N/A]');
$email = htmlspecialchars($user->getEmail() ?? '[N/A]');
$birthDate = $user->getBirthDate() ? htmlspecialchars(date('d/m/Y', strtotime($user->getBirthDate()))) : 'Non renseignée';
$phone = htmlspecialchars($user->getPhoneNumber() ?? 'Non renseigné');
$credits = htmlspecialchars($user->getCredits() ?? '0');
$functionalRole = $user->getFunctionalRole(); // Pas besoin de htmlspecialchars pour la logique interne

// J'ajoute le script spécifique à cette page.
$pageScripts = ['/js/pages/accountPage.js'];

?>

<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Mon Espace</h1>
        <p class="banner-subtitle lead">Gérez vos informations et préférences</p>
    </div>
</section>

<section class="form-section account-section py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">

                <!-- CARTE INFORMATIONS PERSONNELLES -->
                <div class="card mb-4">
                    <?php if (\App\Core\FlashMessage::has('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= \App\Core\FlashMessage::get('success') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Informations Personnelles</h2>
                        <button type="button" id="delete-account-btn" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmDeleteAccountModal">
                            <i class="bi bi-trash3"></i> Supprimer votre compte
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="form-label">Pseudo :</span>
                            <span id="account-username-display" class="fw-bold"><?= $username ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="form-label">Nom :</span>
                            <span id="account-last-name-display" class="fw-bold"><?= $lastName ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="form-label">Prénom :</span>
                            <span id="account-first-name-display" class="fw-bold"><?= $firstName ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="form-label">Email :</span>
                            <span id="account-email-display" class="fw-bold"><?= $email ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="form-label">Date de naissance :</span>
                            <span id="account-birthdate-display" class="fw-bold"><?= $birthDate ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="form-label">Téléphone :</span>
                            <span id="account-phone-display" class="fw-bold"><?= $phone ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="form-label">Adresse :</span>
                            <span id="account-address-display" class="fw-bold"><?= htmlspecialchars($user->getAddress() ?? 'Non renseignée') ?></span>
                        </div>
                        <p><span class="form-label">Crédits EcoRide :</span> <span id="account-credits" class="fw-bold"><?= $credits ?></span></p>
                        <div id="personal-info-actions">
                            <!-- TODO: Créer les routes et pages pour ces liens -->
                            <a href="/account/update-info" class="btn secondary-btn btn-sm mt-2">Modifier mes informations</a>
                            <a href="/account/update-password" class="btn btn-outline-danger btn-sm mt-2">Changer de mot de passe</a>
                        </div>
                    </div>
                </div>

                <!-- CARTE GESTION DU RÔLE -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Mon Rôle sur EcoRide</h2>
                    </div>
                    <div class="card-body">
                        <!-- La logique de ce formulaire (soumission en JS) sera implémentée plus tard -->
                        <form id="role-form">
                            <p class="form-label">Comment souhaitez-vous utiliser EcoRide ?</p>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_role_form" id="passenger-role" value="passenger" <?= ($functionalRole === 'passenger') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="passenger-role"> En tant que Passager</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_role_form" id="driver-role" value="driver" <?= ($functionalRole === 'driver') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="driver-role"> En tant que Chauffeur</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_role_form" id="passenger_driver-role" value="passenger_driver" <?= ($functionalRole === 'passenger_driver') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="passenger_driver-role"> Les deux ! (Passager et Chauffeur)</label>
                            </div>
                            <button type="submit" class="btn primary-btn mt-3">Enregistrer mon rôle</button>
                        </form>
                    </div>
                </div>

                <!-- CARTE INFORMATIONS CHAUFFEUR (logique d'affichage et contenu à implémenter) -->
                <div id="driver-info-section" class="card mb-4 <?= ($functionalRole === 'driver' || $functionalRole === 'passenger_driver') ? '' : 'd-none' ?>">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Informations Chauffeur</h2>
                    </div>
                    <div class="card-body">
                        <?php if (($functionalRole === 'driver' || $functionalRole === 'passenger_driver') && $user->getDriverRating() !== null && $user->getDriverRating() > 0): ?>
                            <p class="mb-3">
                                <span class="form-label">Note moyenne conducteur :</span>
                                <span class="fw-bold"><?= htmlspecialchars(number_format($user->getDriverRating(), 1)) ?> <i class="bi bi-star-fill text-warning"></i></span>
                            </p>
                        <?php endif; ?>
                        <!-- La gestion des véhicules (liste, ajout, etc.) sera implémentée ici dans une future étape -->
                        <h3 class="h6 form-label">Mes Véhicules</h3>
                        <div id="vehicles-list">
                            <!-- La liste des véhicules sera chargée ici en JS -->
                            <p class="text-muted">Le chargement des véhicules sera bientôt disponible.</p>
                        </div>
                        <button type="button" id="add-vehicle-btn" class="btn secondary-btn btn-sm mb-3">
                            <i class="bi bi-plus-circle me-1"></i> Ajouter un véhicule
                        </button>

                        <!-- Le formulaire d'ajout/modification de véhicule sera géré en JS -->
                        <div id="vehicle-form-container" class="card card-body mb-3 d-none">
                            <form id="vehicle-form"> 
                                <h3 class="h6 form-label border-bottom pb-3 mb-3" id="vehicle-form-title">Ajouter un Véhicule</h3>
                                <input type="hidden" id="editing-vehicle-id" name="editing_vehicle_id" value="">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="vehicle-brand-select" class="form-label">Marque</label> <div class="form-input-custom d-flex align-items-center"> 
                                            <select class="form-select-custom flex-grow-1" id="vehicle-brand-select" name="brand_id" aria-label="Marque du véhicule" required>
                                                <option value="" selected disabled>Marques de véhicule</option>
                                                </select>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="vehicle-model" class="form-label">Modèle</label>
                                        <div class="form-input-custom d-flex align-items-center">
                                            <input type="text" class="form-control-custom flex-grow-1" id="vehicle-model" name="vehicle_model" placeholder="Modèle" aria-label="Modèle du véhicule" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="vehicle-color" class="form-label">Couleur</label>
                                        <div class="form-input-custom d-flex align-items-center">
                                            <input type="text" class="form-control-custom flex-grow-1" id="vehicle-color" name="vehicle_color" placeholder="Couleur" aria-label="Couleur du véhicule">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="vehicle-license-plate" class="form-label">Plaque d'immatriculation</label>
                                        <div class="form-input-custom d-flex align-items-center">
                                            <input type="text" class="form-control-custom flex-grow-1" id="vehicle-license-plate" name="license_plate" placeholder="Plaque d'immatriculation (Ex: AA-123-BB)" aria-label="Plaque d'immatriculation" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="vehicle-registration-date" class="form-label">Date de 1ère immatriculation</label>
                                        <div class="form-input-custom d-flex align-items-center">
                                            <input type="date" class="form-control-custom flex-grow-1" id="vehicle-registration-date" name="registration_date" aria-label="Date de 1ère immatriculation">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="vehicle-seats" class="form-label">Nombre de places disponibles (hors chauffeur)</label>
                                        <div class="form-input-custom d-flex align-items-center">
                                            <input type="number" class="form-control-custom flex-grow-1" id="vehicle-seats" name="vehicle_seats" placeholder="Nombre de places disponibles" aria-label="Nombre de places disponibles" min="1" max="8" required>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <div class="form-check form-check-custom-vehicle">
                                            <input class="form-check-input" type="checkbox" id="vehicle-electric" name="vehicle_electric">
                                            <label class="form-check-label" for="vehicle-electric">Véhicule électrique (pour trajets écologiques)</label>
                                        </div>
                                    </div>
                                    <div class="col-12 text-center mt-3">
                                        <button type="button" id="cancel-vehicle-form-btn" class="btn secondary-btn me-2">Annuler</button>
                                        <button type="submit" id="save-vehicle-btn" class="btn primary-btn">Enregistrer Véhicule</button>
                                    </div>
                                </div>
                            </form> 
                        </div>

                        <!-- La logique de ce formulaire (soumission en JS) sera implémentée plus tard -->
                        <form id="driver-preferences-form">
                            <h3 class="h6 form-label mt-4">Mes Préférences de Conduite</h3>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pref-smoker" name="pref_smoker" <?= $user->getDriverPrefSmoker() ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pref-smoker">Accepte les fumeurs</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pref-animals" name="pref_animals" <?= $user->getDriverPrefAnimals() ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pref-animals">Accepte les animaux</label>
                            </div>
                            <div class="mt-2">
                                <label for="pref-custom" class="form-label-sm">Autres préférences :</label>
                                <textarea class="form-control form-control-custom" id="pref-custom" name="pref_custom" rows="2"><?= htmlspecialchars($user->getDriverPrefCustom() ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn primary-btn mt-3">Enregistrer les Préférences</button>
                        </form>
                    </div>
                </div>

                <div class="auth-link-bar mt-4">
                    <!-- Le lien pointe vers la future page "Mes Trajets" -->
                    <p class="mb-0 text-center"><a href="/your-rides" class="link">Voir mon historique de trajets</a></p>
                </div>
                <div class="auth-link-bar mt-2">
                    <p class="mb-0 text-center"><a href="/logout" class="link" id="logout-account-btn">Se déconnecter</a></p>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- MODALE DE SUPPRESSION DE COMPTE -->
<div class="modal fade" id="confirmDeleteAccountModal" tabindex="-1" aria-labelledby="confirmDeleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteAccountModalLabel">Confirmer la suppression du compte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer définitivement votre compte EcoRide ?</p>
                <p class="text-danger fw-bold">Attention : Cette action est irréversible.</p>
                <p>Toutes vos données, y compris vos crédits, trajets et historique, seront supprimés.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <!-- Ce bouton déclenchera une action JS qui appellera une route API -->
                <button type="button" id="confirm-delete-btn" class="btn btn-danger">Oui, supprimer mon compte</button>
            </div>
        </div>
    </div>
</div>

<!-- D'autres modales (ex: pour les véhicules) pourront être ajoutées ici plus tard -->

<script>
    // J'injecte les données des véhicules directement dans le JavaScript.
    // C'est une méthode simple pour passer des données du backend au frontend
    // sans avoir besoin d'un appel API supplémentaire au chargement de la page.
    const initialVehiclesData = <?= json_encode($vehicles ?? []) ?>;
</script>
