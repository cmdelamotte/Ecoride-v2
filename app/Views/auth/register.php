<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Inscription</h1>
        <p class="banner-subtitle lead">Veuillez créer votre compte</p>
    </div>
</section>

<section class="form-section auth-form py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <form action="/register" method="POST">
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
                            <label for="username" class="visually-hidden">Pseudo</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-person me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1" id="username" name="username" placeholder="Pseudo" aria-label="Pseudo voulu par l'utilisateur" value="<?= htmlspecialchars($oldInput['username'] ?? '') ?>" required autocomplete="username">
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="last_name" class="visually-hidden">Nom</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-person-lines-fill me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1" id="last_name" name="last_name" placeholder="Nom" aria-label="Nom de l'utilisateur" value="<?= htmlspecialchars($oldInput['last_name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="first_name" class="visually-hidden">Prénom</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-person me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1" id="first_name" name="first_name" placeholder="Prénom" aria-label="Prénom de l'utilisateur" value="<?= htmlspecialchars($oldInput['first_name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="email" class="visually-hidden">Email</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-envelope me-2"></i>
                                <input type="email" class="form-control-custom flex-grow-1" id="email" name="email" placeholder="Email" aria-label="Email de l'utilisateur" value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>" required autocomplete="email">
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="birth_date" class="visually-hidden">Date de naissance</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-calendar-event me-2"></i>
                                <input type="date" class="form-control-custom flex-grow-1" id="birth_date" name="birth_date" placeholder="Date de naissance" value="<?= htmlspecialchars($oldInput['birth_date'] ?? '') ?>" aria-label="Date de naissance de l'utilisateur" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="phone_number" class="visually-hidden">Téléphone</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-telephone me-2"></i>
                                <input type="tel" class="form-control-custom flex-grow-1" id="phone_number" name="phone_number" placeholder="Numéro de téléphone" aria-label="Numéro de téléphone de l'utilisateur" pattern="[0-9]{10}" title="Format attendu : 10 chiffres sans espaces (ex: 0612345678)" value="<?= htmlspecialchars($oldInput['phone_number'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="password" class="visually-hidden">Mot de passe</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-shield-lock me-2"></i>
                                <input type="password" class="form-control-custom flex-grow-1" id="password" name="password" placeholder="Mot de passe" aria-label="Choix du mot de passe" aria-describedby="password-help" required autocomplete="new-password">
                            </div>
                            <small id="password-help" class="form-text text-muted ps-1">
                                Le mot de passe doit contenir au moins 8 caractères, incluant majuscule, minuscule, chiffre et caractère spécial.
                            </small>
                        </div>

                        <div class="col-12">
                            <label for="confirm_password" class="visually-hidden">Confirmation du mot de passe</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-shield-check me-2"></i>
                                <input type="password" class="form-control-custom flex-grow-1" id="confirm_password" name="confirm_password" placeholder="Confirmer le mot de passe" aria-label="Confirmation du mot de passe" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="col-8 mx-auto">
                            <div class="d-grid">
                                <button type="submit" class="btn primary-btn">S'inscrire</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="auth-link-bar mt-4 mb-4">
        <p class="mb-0 text-center">
            Déjà inscrit ? <a href="/login" class="link">Cliquez ici</a>
        </p>
    </div>
</section>