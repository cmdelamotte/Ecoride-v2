<?php
/**
 * Vue pour le formulaire de connexion.
 * Permet aux utilisateurs de se connecter à leur compte EcoRide.
 * Inclut des champs pour l'identifiant (email ou nom d'utilisateur) et le mot de passe.
 */

// Inclut le layout principal de l'application pour une structure HTML cohérente.
include __DIR__ . '/../layout.php';
?>

<?php ob_start(); // Démarre la mise en mémoire tampon pour capturer le contenu de la page ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">Connexion</div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) // Affiche les messages d'erreur de manière sécurisée ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success" role="alert">
                            <?= htmlspecialchars($success) // Affiche les messages de succès de manière sécurisée ?>
                        </div>
                    <?php endif; ?>
                    <form action="/login" method="POST">
                        <div class="mb-3">
                            <label for="identifier" class="form-label">Email ou Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="identifier" name="identifier" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Se connecter</button>
                        </div>
                        <div class="text-center mt-3">
                            <a href="/forgot-password">Mot de passe oublié ?</a>
                        </div>
                        <div class="text-center mt-2">
                            Pas encore de compte ? <a href="/register">S'inscrire</a>
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