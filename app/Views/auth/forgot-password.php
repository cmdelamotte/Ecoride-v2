<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Mot de passe oublié ?</h1>
        <p class="banner-subtitle lead">Entrez votre email pour recevoir un lien de réinitialisation</p>
    </div>
</section>

<section class="form-section auth-form py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <form action="/forgot-password" method="POST">
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
                            <label for="email" class="visually-hidden">Email</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-envelope me-2"></i>
                                <input type="email" class="form-control-custom flex-grow-1" id="email" name="email" placeholder="Votre adresse email" aria-label="Adresse email" value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="col-10 mx-auto">
                            <div class="d-grid">
                                <button type="submit" class="btn primary-btn">Envoyer le lien de réinitialisation</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="auth-link-bar mb-4 mt-4">
        <p class="mb-0 text-center">
            Retourner à la page de <a href="/login" class="link">Connexion</a>
        </p>
    </div>
</section>