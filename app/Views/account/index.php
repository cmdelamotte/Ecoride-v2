<?php
/**
 * Vue pour la page du compte utilisateur.
 * Affiche un aperçu des informations de l'utilisateur connecté.
 * Cette page sera enrichie ultérieurement avec les détails du profil,
 * les options de modification, l'historique des trajets, etc.
 */
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-center">Mon Compte</div>
                <div class="card-body">
                    <h2 class="card-title">Bienvenue sur votre espace personnel !</h2>
                    <p class="card-text">Ceci est votre page de compte. Les détails de votre profil et d'autres fonctionnalités seront affichés ici.</p>
                    <p class="card-text">Vous êtes connecté en tant que : <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Invité') ?></strong></p>
                    <a href="/logout" class="btn btn-danger">Déconnexion</a>
                </div>
            </div>
        </div>
    </div>
</div>
