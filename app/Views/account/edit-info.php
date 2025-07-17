<h1 class="text-center my-4">Modifier mon profil</h1>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success" role="alert">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            Veuillez corriger les erreurs suivantes :
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="/account/update-info" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Prénom</label>
                            <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" id="first_name" name="first_name" value="<?= htmlspecialchars($oldInput['first_name'] ?? $user->getFirstName()) ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['first_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nom</label>
                            <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" id="last_name" name="last_name" value="<?= htmlspecialchars($oldInput['last_name'] ?? $user->getLastName()) ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['last_name']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($oldInput['email'] ?? $user->getEmail()) ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['email']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Numéro de téléphone</label>
                            <input type="tel" class="form-control <?= isset($errors['phone_number']) ? 'is-invalid' : '' ?>" id="phone_number" name="phone_number" value="<?= htmlspecialchars($oldInput['phone_number'] ?? $user->getPhoneNumber()) ?>">
                            <?php if (isset($errors['phone_number'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['phone_number']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control <?= isset($errors['birth_date']) ? 'is-invalid' : '' ?>" id="birth_date" name="birth_date" value="<?= htmlspecialchars($oldInput['birth_date'] ?? $user->getBirthDate()) ?>">
                            <?php if (isset($errors['birth_date'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['birth_date']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <input type="text" class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address" name="address" value="<?= htmlspecialchars($oldInput['address'] ?? $user->getAddress()) ?>">
                            <?php if (isset($errors['address'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['address']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="avatar" class="form-label">Photo de profil</label>
                            <input class="form-control <?= isset($errors['avatar']) ? 'is-invalid' : '' ?>" type="file" id="avatar" name="avatar" accept="image/jpeg, image/png, image/gif">
                            <?php if (isset($errors['avatar'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['avatar']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($user->getProfilePicturePath()): ?>
                                <div class="mt-2">
                                    Photo actuelle: <img src="/img/avatars/<?= htmlspecialchars($user->getProfilePicturePath()) ?>" alt="Photo de profil actuelle" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        <a href="/account" class="btn btn-secondary">Annuler</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>