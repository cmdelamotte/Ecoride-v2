<?php
/**
 * Vue pour l'erreur 500 (Erreur interne du serveur).
 * Cette page est affichée en cas de problème inattendu côté serveur.
 * Elle fournit un message générique pour éviter d'exposer des informations sensibles
 * à l'utilisateur final.
 */

// Inclut le layout principal de l'application pour maintenir une cohérence visuelle.
include __DIR__ . '/layout.php';
?>

<?php ob_start(); // Démarre la mise en mémoire tampon de la sortie HTML ?>

<div class="container text-center py-5">
    <h1 class="display-4">500 - Erreur interne du serveur</h1>
    <p class="lead">Désolé, une erreur inattendue est survenue.</p>
    <p>Nous travaillons à résoudre le problème. Veuillez réessayer plus tard.</p>
    <a href="/" class="btn btn-primary mt-3">Retour à la page d'accueil</a>
</div>

<?php
$content = ob_get_clean(); // Récupère le contenu mis en mémoire tampon et le nettoie

// Inclut le footer du layout principal.
include __DIR__ . '/partials/footer.php';
?>