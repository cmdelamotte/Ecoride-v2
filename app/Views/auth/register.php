<?php
/**
 * Vue pour le formulaire d'inscription.
 * Permet aux nouveaux utilisateurs de créer un compte EcoRide.
 * Inclut des champs pour les informations personnelles et les identifiants de connexion.
 */

// Inclut le layout principal de l'application pour une structure HTML cohérente.
include __DIR__ . '/../layout.php';
?>

<?php ob_start(); // Démarre la mise en mémoire tampon pour capturer le contenu de la page ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-center">Inscription</div>
                <div class="card-body">
                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error) && !empty($error)): // Pour les erreurs non-tableau ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <form action="/register" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($oldInput['username'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($oldInput['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($oldInput['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Numéro de téléphone</label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?= htmlspecialchars($oldInput['phone_number'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?= htmlspecialchars($oldInput['birth_date'] ?? '') ?>" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">S'inscrire</button>
                        </div>
                        <div class="text-center mt-3">
                            Déjà un compte ? <a href="/login">Se connecter</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean(); // Récupère le contenu mis en mémoire tampon

// Inclut le footer du layout principal.
include __DIR__ . '/../partials/footer.php';
?>