<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Modifier mes Informations</h1>
        <p class="banner-subtitle lead">Mettez à jour vos informations et confirmez avec votre mot de passe.</p>
    </div>
</section>

<section class="form-section auth-form py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <form id="edit-personal-info-form" action="/account/update-info" method="POST" enctype="multipart/form-data">
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
                            <label for="username" class="visually-hidden">Pseudo</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-person-badge me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1 <?= isset($errors['username']) ? 'is-invalid' : '' ?>" id="username" name="username" placeholder="Pseudo" aria-label="Pseudo" value="<?= htmlspecialchars($oldInput['username'] ?? $user->getUsername()) ?>" required>
                            </div>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['username']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="first_name" class="visually-hidden">Prénom</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-person me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1 <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" id="first_name" name="first_name" placeholder="Prénom" aria-label="Prénom" value="<?= htmlspecialchars($oldInput['first_name'] ?? $user->getFirstName()) ?>" required>
                            </div>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['first_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="last_name" class="visually-hidden">Nom</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-person-lines-fill me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1 <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" id="last_name" name="last_name" placeholder="Nom" aria-label="Nom" value="<?= htmlspecialchars($oldInput['last_name'] ?? $user->getLastName()) ?>" required>
                            </div>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['last_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="email" class="visually-hidden">Email</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-envelope me-2"></i>
                                <input type="email" class="form-control-custom flex-grow-1 <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" placeholder="Nouvelle adresse email" aria-label="Email" value="<?= htmlspecialchars($oldInput['email'] ?? $user->getEmail()) ?>" required autocomplete="email">
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['email']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="birth_date" class="visually-hidden">Date de naissance</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-calendar-event me-2"></i>
                                <input type="date" class="form-control-custom flex-grow-1 <?= isset($errors['birth_date']) ? 'is-invalid' : '' ?>" id="birth_date" name="birth_date" aria-label="Date de naissance" value="<?= htmlspecialchars($oldInput['birth_date'] ?? $user->getBirthDate()) ?>" required>
                            </div>
                            <?php if (isset($errors['birth_date'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['birth_date']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="phone_number" class="visually-hidden">Téléphone</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-telephone me-2"></i>
                                <input type="tel" class="form-control-custom flex-grow-1 <?= isset($errors['phone_number']) ? 'is-invalid' : '' ?>" id="phone_number" name="phone_number" placeholder="Numéro de téléphone" aria-label="Numéro de téléphone" pattern="[0-9]{10}" title="Format attendu : 10 chiffres (ex: 0612345678)" value="<?= htmlspecialchars($oldInput['phone_number'] ?? $user->getPhoneNumber()) ?>">
                            </div>
                            <?php if (isset($errors['phone_number'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['phone_number']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="address" class="visually-hidden">Adresse</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-house me-2"></i>
                                <input type="text" class="form-control-custom flex-grow-1 <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address" name="address" placeholder="Adresse" aria-label="Adresse" value="<?= htmlspecialchars((string)($oldInput['address'] ?? $user->getAddress() ?? '')) ?>">
                            </div>
                            <?php if (isset($errors['address'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['address']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label for="avatar" class="visually-hidden">Photo de profil</label>
                            <input class="form-control <?= isset($errors['avatar']) ? 'is-invalid' : '' ?>" type="file" id="avatar" name="avatar" accept="image/jpeg, image/png, image/gif">
                            <?php if (isset($errors['avatar'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['avatar']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($user->getProfilePicturePath()): ?>
                                <div class="mt-2">
                                    Photo actuelle: <img src="/img/avatars/<?= htmlspecialchars($user->getProfilePicturePath()) ?>" alt="Photo de profil actuelle" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr class="my-3">

                        <div class="col-12">
                            <label for="current_password" class="visually-hidden">Mot de passe actuel (pour confirmation)</label>
                            <div class="form-input-custom d-flex align-items-center">
                                <i class="bi bi-shield-lock me-2"></i>
                                <input type="password" class="form-control-custom flex-grow-1 <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" id="current_password" name="current_password" placeholder="Mot de passe actuel pour confirmer" aria-label="Mot de passe actuel pour confirmer" required autocomplete="current-password">
                            </div>
                            <?php if (isset($errors['current_password'])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars($errors['current_password']) ?>
                                </div>
                            <?php endif; ?>
                            <small id="password-help" class="form-text text-muted ps-1">
                                Veuillez saisir votre mot de passe actuel pour valider les modifications.
                            </small>
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