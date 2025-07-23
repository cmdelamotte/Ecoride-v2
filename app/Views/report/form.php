<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Signaler un Problème</h1>
        <p class="banner-subtitle lead">Veuillez décrire le problème rencontré lors de votre trajet.</p>
    </div>
</section>

<section class="form-section report-form-section py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <form id="report-form" novalidate>
                    <div class="row g-3">

                        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                        <input type="hidden" name="reporter_user_id" value="<?= htmlspecialchars($reporterUser->getId() ?? '') ?>">
                        <input type="hidden" name="reported_user_id" value="<?= htmlspecialchars($reportedUser->getId() ?? '') ?>">
                        <input type="hidden" name="ride_id" value="<?= htmlspecialchars($ride->getId() ?? '') ?>">

                        <div class="col-12">
                            <label for="reason" class="form-label">Raison du signalement</label>
                            <select class="form-select-custom flex-grow-1" id="reason" name="reason" required>
                                <option value="">Sélectionnez une raison</option>
                                <option value="Comportement inapproprié du conducteur">Comportement inapproprié du conducteur</option>
                                <option value="Problème avec le véhicule">Problème avec le véhicule</option>
                                <option value="Retard important">Retard important</option>
                                <option value="Annulation non justifiée">Annulation non justifiée</option>
                                <option value="Autre">Autre</option>
                            </select>
                            <div class="invalid-feedback">Veuillez sélectionner une raison.</div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description détaillée (optionnel)</label>
                            <textarea class="form-control-custom flex-grow-1" id="description" name="description" rows="5" placeholder="Décrivez le problème en détail..." maxlength="1000"></textarea>
                            <div class="invalid-feedback">La description ne doit pas dépasser 1000 caractères.</div>
                        </div>

                        <div class="col-12 mt-3">
                            <div id="report-message" class="alert d-none" role="alert"></div>
                        </div>

                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn primary-btn btn-lg">Envoyer le signalement</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php $pageScripts = ['/js/pages/reportPage.js']; ?>