<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Confirmation Réussie !</h1>
        <p class="banner-subtitle lead"><?= htmlspecialchars($message ?? "Merci d'avoir confirmé votre trajet !") ?></p>
    </div>
</section>

<section class="content-section py-5">
    <div class="container text-center">
        <p>Le conducteur a bien été crédité pour ce trajet.</p>
        <p>Nous espérons que vous avez apprécié votre voyage avec EcoRide.</p>
        <p>N'hésitez pas à <a href="/your-rides" class="link">consulter vos trajets</a> pour laisser un avis ou en découvrir de nouveaux.</p>
        <a href="/" class="btn primary-btn mt-4">Retour à l'accueil</a>
    </div>
</section>