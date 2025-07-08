<?php
/**
 * Vue pour la page du compte utilisateur.
 * Affiche les informations de l'utilisateur connecté et fournit des liens
 * pour la gestion du compte.
 *
 * @var \App\Models\User $user L'objet utilisateur contenant les données à afficher.
 */
?>

<div class="container mt-5">
    <div class="row">
        <!-- Colonne de navigation latérale -->
        <div class="col-md-3">
            <div class="list-group">
                <a href="/account" class="list-group-item list-group-item-action active">Informations Personnelles</a>
                <a href="/account/vehicles" class="list-group-item list-group-item-action">Mes Véhicules</a>
                <a href="/my-rides" class="list-group-item list-group-item-action">Mes Trajets</a>
                <a href="/my-bookings" class="list-group-item list-group-item-action">Mes Réservations</a>
                <a href="/account/reviews" class="list-group-item list-group-item-action">Mes Avis</a>
            </div>
            <hr>
            <a href="/logout" class="btn btn-danger w-100">Déconnexion</a>
        </div>

        <!-- Colonne de contenu principal -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h3>Informations Personnelles</h3>
                </div>
                <div class="card-body">
                    <p><strong>Nom d'utilisateur :</strong> <?= htmlspecialchars($user->getUsername()) ?></p>
                    <p><strong>Email :</strong> <?= htmlspecialchars($user->getEmail()) ?></p>
                    <p><strong>Prénom :</strong> <?= htmlspecialchars($user->getFirstName() ?? 'Non renseigné') ?></p>
                    <p><strong>Nom de famille :</strong> <?= htmlspecialchars($user->getLastName() ?? 'Non renseigné') ?></p>
                    
                    <hr>
                    <a href="/account/edit" class="btn btn-primary">Modifier mes informations</a>
                    <a href="/account/edit-password" class="btn btn-secondary">Changer mon mot de passe</a>
                </div>
            </div>
        </div>
    </div>
</div>
