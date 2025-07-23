<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Erreur de Confirmation</h1>
        <p class="banner-subtitle lead"><?= htmlspecialchars($message ?? 'Une erreur est survenue lors de la confirmation de votre trajet.') ?></p>
    </div>
</section>

<section class="content-section py-5">
    <div class="container text-center">
        <p>Le lien de confirmation est peut-être invalide, expiré, ou le trajet a déjà été traité.</p>
        <p>Si le problème persiste, veuillez contacter le support.</p>
        <a href="/" class="btn primary-btn mt-4">Retour à l'accueil</a>
    </div>
</section>