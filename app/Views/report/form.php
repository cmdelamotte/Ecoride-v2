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
                        <input type="hidden" name="reporter_id" value="<?= htmlspecialchars($reporterUser->getId() ?? '') ?>">
                        <input type="hidden" name="reported_driver_id" value="<?= htmlspecialchars($reportedDriver->getId() ?? '') ?>">
                        <input type="hidden" name="ride_id" value="<?= htmlspecialchars($ride->getId() ?? '') ?>">

                        <div class="col-12">
                            <label for="reason" class="form-label">Raison du signalement</label>
                            <textarea class="form-control-custom flex-grow-1" id="reason" name="reason" rows="5" placeholder="Décrivez le problème en détail..." required maxlength="1000"></textarea>
                            <div class="invalid-feedback">Veuillez décrire la raison du signalement (au moins 10 caractères).</div>
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