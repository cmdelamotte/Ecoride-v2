<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Lien Invalide</h1>
        <p class="banner-subtitle lead"><?= htmlspecialchars($message ?? 'Le lien que vous avez utilisé est invalide ou manquant.') ?></p>
    </div>
</section>

<section class="content-section py-5">
    <div class="container text-center">
        <p>Veuillez vérifier le lien ou contacter le support si vous pensez qu'il s'agit d'une erreur.</p>
        <a href="/" class="btn primary-btn mt-4">Retour à l'accueil</a>
    </div>
</section>