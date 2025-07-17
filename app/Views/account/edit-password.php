<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Modifier mon mot de passe</h1>
        <p class="banner-subtitle lead">Mettez à jour votre mot de passe ci-dessous.</p>
    </div>
</section>

<section class="form-section auth-form py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <form id="edit-password-form" action="/account/update-password" method="POST">
                    <div class="row g-3">

                        <?php if (isset($success)): ?>
                            <div class="col-12">
                                <div class="alert alert-success" role="alert">
                                    <?= htmlspecialchars($success) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($errors) && !empty($errors)): ?>
                            <div class="col-12">
                                <div class="alert alert-danger" role="alert">
                                    Veuillez corriger les erreurs suivantes :
                                    <ul>
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label for="current_password" class="visually-hidden">Ancien mot de passe</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-shield-lock me-2"></i>
                                <input type="password" class="form-control-custom flex-grow-1 <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" id="current_password" name="current_password" placeholder="Ancien mot de passe" aria-label="Ancien mot de passe" required autocomplete="current-password">
                            </div>
                            <?php if (isset($errors['current_password'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['current_password']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="new_password" class="visually-hidden">Nouveau mot de passe</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-key me-2"></i>
                                <input type="password" class="form-control-custom flex-grow-1 <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" id="new_password" name="new_password" placeholder="Nouveau mot de passe" aria-label="Nouveau mot de passe" aria-describedby="new-password-help" required autocomplete="new-password">
                            </div>
                            <?php if (isset($errors['new_password'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['new_password']) ?>
                                </div>
                            <?php endif; ?>
                            <small id="new-password-help" class="form-text text-muted ps-1">
                                Le mot de passe doit contenir au moins 8 caractères, incluant majuscule, minuscule, chiffre et caractère spécial.
                            </small>
                        </div>

                        <div class="col-12">
                            <label for="confirm_new_password" class="visually-hidden">Confirmer le nouveau mot de passe</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-key-fill me-2"></i>
                                <input type="password" class="form-control-custom flex-grow-1 <?= isset($errors['confirm_new_password']) ? 'is-invalid' : '' ?>" id="confirm_new_password" name="confirm_new_password" placeholder="Confirmer le nouveau mot de passe" aria-label="Confirmer le nouveau mot de passe" required autocomplete="new-password">
                            </div>
                            <?php if (isset($errors['confirm_new_password'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['confirm_new_password']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-10 mx-auto mt-4">
                            <div class="d-grid">
                                <button type="submit" class="btn primary-btn">Enregistrer les modifications</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="auth-link-bar mt-4 mb-4">
        <p class="mb-0 text-center">
            <a href="/account" class="link">Retour à Mon Compte</a>
        </p>
    </div>
</section>