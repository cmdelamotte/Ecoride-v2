<?php
/**
 * Vue pour l'erreur 403 (Accès refusé).
 * Cette page est affichée lorsque l'utilisateur tente d'accéder à une ressource
 * pour laquelle il n'a pas les permissions nécessaires.
 * Elle informe l'utilisateur de l'interdiction d'accès et propose des actions.
 */

// Inclut le layout principal de l'application pour maintenir une cohérence visuelle.
include __DIR__ . '/layout.php';
?>

<?php ob_start(); // Démarre la mise en mémoire tampon de la sortie HTML ?>

<div class="container text-center py-5">
    <h1 class="display-4">403 - Accès refusé</h1>
    <p class="lead">Vous n'avez pas la permission d'accéder à cette page.</p>
    <p>Veuillez vous assurer que vous êtes connecté avec un compte ayant les droits nécessaires.</p>
    <a href="/login" class="btn btn-primary mt-3">Se connecter</a>
    <a href="/" class="btn btn-secondary mt-3">Retour à la page d'accueil</a>
</div>

<?php
$content = ob_get_clean(); // Récupère le contenu mis en mémoire tampon et le nettoie

// Inclut le footer du layout principal.
include __DIR__ . '/partials/footer.php';
?>