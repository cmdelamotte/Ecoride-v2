<?php
/**
 * Vue pour l'erreur 403 (Accès refusé).
 * Cette page est affichée lorsque l'utilisateur tente d'accéder à une ressource
 * pour laquelle il n'a pas les permissions nécessaires.
 * Elle informe l'utilisateur de l'interdiction d'accès et propose des actions.
 */
?>

<div class="container text-center py-5">
    <h1 class="display-4">403 - Accès refusé</h1>
    <p class="lead">Vous n'avez pas la permission d'accéder à cette page.</p>
    <p>Veuillez vous assurer que vous êtes connecté avec un compte ayant les droits nécessaires.</p>
    <a href="/login" class="btn btn-primary mt-3">Se connecter</a>
    <a href="/" class="btn btn-secondary mt-3">Retour à la page d'accueil</a>
</div>
