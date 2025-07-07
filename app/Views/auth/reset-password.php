<?php
/**
 * Vue pour le formulaire de réinitialisation de mot de passe.
 * Permet aux utilisateurs de définir un nouveau mot de passe après avoir reçu un lien de réinitialisation.
 * Nécessite un token valide pour fonctionner.
 */

// Inclut le layout principal de l'application pour une structure HTML cohérente.
include __DIR__ . '/../layout.php';
?>

<?php ob_start(); // Démarre la mise en mémoire tampon pour capturer le contenu de la page ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">Réinitialisation du mot de passe</div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success" role="alert">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    <form action="/reset-password" method="POST">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Réinitialiser le mot de passe</button>
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