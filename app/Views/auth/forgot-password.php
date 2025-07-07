<?php
/**
 * Vue pour le formulaire de demande de réinitialisation de mot de passe.
 * Permet aux utilisateurs de demander un lien de réinitialisation via leur adresse email.
 */

// Inclut le layout principal de l'application pour une structure HTML cohérente.
include __DIR__ . '/../layout.php';
?>

<?php ob_start(); // Démarre la mise en mémoire tampon pour capturer le contenu de la page ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">Mot de passe oublié</div>
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
                    <form action="/forgot-password" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Votre adresse email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Envoyer le lien de réinitialisation</button>
                        </div>
                        <?php if (isset($debugLink)): // À retirer en production ?>
                            <div class="alert alert-info mt-3">
                                Lien de débogage (à retirer en production) : <a href="<?= htmlspecialchars($debugLink) ?>" target="_blank"><?= htmlspecialchars($debugLink) ?></a>
                            </div>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="/login">Retour à la connexion</a>
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