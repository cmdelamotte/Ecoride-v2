<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Signaler un Problème</h1>
        <p class="banner-subtitle lead">Veuillez décrire le problème rencontré lors de votre trajet.</p>
    </div>
</section>

<section class="content-section py-5">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-7 d-flex">
                <div class="card w-100">
                    <div class="card-body">
                        <h2 class="card-title mb-4 form-label">Signaler un problème</h2>

                        <?php if (isset($errorMessage) && $errorMessage): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($errorMessage) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($ride) && $ride && isset($reporterUser) && $reporterUser && isset($reportedDriver) && $reportedDriver): ?>
                            <div class="mb-4">
                                <p class="form-label"><strong>Trajet concerné :</strong> De <?= htmlspecialchars($ride->getDepartureCity()) ?> à <?= htmlspecialchars($ride->getArrivalCity()) ?> le <?= (new DateTime($ride->getDepartureTime()))->format('d/m/Y à H:i') ?></p>
                                <p class="form-label"><strong>Passager rapporteur :</strong> <?= htmlspecialchars($reporterUser->getFirstName() . ' ' . $reporterUser->getLastName()) ?></p>
                                <p class="form-label"><strong>Conducteur signalé :</strong> <?= htmlspecialchars($reportedDriver->getFirstName() . ' ' . $reportedDriver->getLastName()) ?></p>
                            </div>
                        <?php endif; ?>

                        <form id="report-form" novalidate>
                            <div class="row g-3">

                                <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                                <input type="hidden" name="reporter_id" value="<?= htmlspecialchars($reporterUser->getId() ?? '') ?>">
                                <input type="hidden" name="reported_driver_id" value="<?= htmlspecialchars($reportedDriver->getId() ?? '') ?>">
                                <input type="hidden" name="ride_id" value="<?= htmlspecialchars($ride->getId() ?? '') ?>">

                                <div class="col-12">
                                    <label for="reason" class="form-label">Raison du signalement</label>
                                    <div class="form-input-custom d-flex align-items-start">
                                        <i class="bi bi-pencil me-2 pt-1"></i>
                                        <textarea class="form-control-custom flex-grow-1" id="reason" name="reason" rows="5" placeholder="Décrivez le problème en détail..." required minlength="10" maxlength="1000"></textarea>
                                    </div>
                                    <div class="invalid-feedback">Veuillez décrire la raison du signalement (au moins 10 caractères).</div>
                                </div>

                                <div class="col-12 mt-3">
                                    <div id="report-message" class="alert d-none" role="alert"></div>
                                </div>

                                <div class="col-12 text-end">
                                    <button type="submit" class="btn primary-btn">Envoyer le signalement</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php $pageScripts = ['/js/pages/reportPage.js']; ?>