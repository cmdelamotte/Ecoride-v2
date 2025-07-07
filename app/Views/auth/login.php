<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Connexion</h1>
        <p class="banner-subtitle lead">Veuillez saisir vos identifiants</p>
    </div>
</section>

<section class="form-section auth-form py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <form action="/login" method="POST">
                    <div class="row g-3">
                        <?php if (isset($error)): ?>
                            <div class="col-12 mt-3">
                                <div class="alert alert-danger" role="alert">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($success)): ?>
                            <div class="col-12 mt-3">
                                <div class="alert alert-success" role="alert">
                                    <?= htmlspecialchars($success) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label for="identifier" class="visually-hidden">Pseudo ou email</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-person me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1" id="identifier" name="identifier" placeholder="Pseudo ou email" aria-label="Pseudo ou email" required autocomplete="username">
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="password" class="visually-hidden">Mot de passe</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-shield-lock me-2"></i>
                                <input type="password" class="form-control-custom flex-grow-1" id="password" name="password" placeholder="Mot de passe" aria-label="Mot de passe" required autocomplete="current-password">
                            </div>
                        </div>

                        <div class="col-8 mx-auto">
                            <div class="d-grid">
                                <button type="submit" class="btn primary-btn">Se connecter</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="text-center mt-1 mb-3">
        <a href="/forgot-password" class="link text-muted">Mot de passe oublié ?</a>
    </div>
    <div class="auth-link-bar mb-4">
        <p class="mb-0 text-center">
            Pas encore inscrit ? <a href="/register" class="link">Cliquez ici</a>
        </p>
    </div>
</section>