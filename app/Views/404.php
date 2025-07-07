<?php
/**
 * Vue pour l'erreur 404 (Page non trouvée).
 * Cette page est affichée lorsque l'utilisateur tente d'accéder à une URL qui n'existe pas sur le site.
 * Elle fournit un message clair et une option pour retourner à la page d'accueil.
 */

// Inclut le layout principal de l'application pour maintenir une cohérence visuelle.
// Le layout gère la structure HTML de base, le header, le footer, etc.
include __DIR__ . '/layout.php';
?>

<?php ob_start(); // Démarre la mise en mémoire tampon de la sortie HTML ?>

<div class="container text-center py-5">
    <h1 class="display-4">404 - Page non trouvée</h1>
    <p class="lead">Désolé, la page que vous recherchez n'existe pas.</p>
    <p>Il se peut que l'adresse ait été mal tapée, ou que la page ait été déplacée.</p>
    <a href="/" class="btn btn-primary mt-3">Retour à la page d'accueil</a>
</div>

<?php
$content = ob_get_clean(); // Récupère le contenu mis en mémoire tampon et le nettoie

// Inclut le footer du layout principal. Cela permet de fermer les balises HTML ouvertes
// dans le header du layout et d'ajouter des scripts ou des éléments de fin de page.
include __DIR__ . '/partials/footer.php';
?>