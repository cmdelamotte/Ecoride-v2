<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Réinitialiser le mot de passe</h1>
        <p class="banner-subtitle lead">Veuillez choisir un nouveau mot de passe.</p>
    </div>
</section>

<section class="form-section auth-form py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <form action="/reset-password" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                    <div class="row g-3">
                        <?php if (isset($errors) && !empty($errors)): ?>
                            <div class="col-12 mt-3">
                                <div class="alert alert-danger" role="alert">
                                    <ul>
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($error) && !empty($error)): // Pour les erreurs non-tableau ?>
                            <div class="col-12 mt-3">
                                <div class="alert alert-danger" role="alert">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label for="password" class="visually-hidden">Nouveau mot de passe</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-key me-2"></i>
                                <input type="password" class="form-control-custom flex-grow-1" id="password" name="password" placeholder="Nouveau mot de passe" aria-label="Nouveau mot de passe" aria-describedby="password-help-reset" required autocomplete="new-password">
                            </div>
                            <small id="password-help-reset" class="form-text text-muted ps-1">
                                Le mot de passe doit contenir au moins 8 caractères, incluant majuscule, minuscule, chiffre et caractère spécial.
                            </small>
                        </div>

                        <div class="col-12">
                            <label for="confirm_password" class="visually-hidden">Confirmer le nouveau mot de passe</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-key-fill me-2"></i>
                                <input type="password" class="form-control-custom flex-grow-1" id="confirm_password" name="confirm_password" placeholder="Confirmer le nouveau mot de passe" aria-label="Confirmer le nouveau mot de passe" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="col-10 mx-auto mt-2">
                            <div class="d-grid">
                                <button type="submit" class="btn primary-btn">Réinitialiser le mot de passe</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="auth-link-bar mt-4 mb-4">
        <p class="mb-0 text-center">
            Retourner à la page de <a href="/login" class="link">Connexion</a>
        </p>
    </div>
</section>