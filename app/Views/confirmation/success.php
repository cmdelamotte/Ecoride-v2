<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Confirmation Réussie !</h1>
        <p class="banner-subtitle lead"><?= htmlspecialchars($message ?? 'Merci d\'avoir confirmé votre trajet !') ?></p>
    </div>
</section>

<section class="content-section py-5">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-7 d-flex">
                <div class="card w-100">
                    <div class="card-body d-flex flex-column text-center">
                        <h2 class="card-title mb-4">Confirmation du Trajet</h2>
                        <div class="flex-grow-1">
                            <p>Le conducteur a bien été crédité pour ce trajet.</p>
                            <p>Nous espérons que vous avez apprécié votre voyage avec EcoRide.</p>
                            <p>N'hésitez pas à <a href="/your-rides" class="link">consulter vos trajets</a> pour laisser un avis ou en découvrir de nouveaux.</p>
                        </div>
                        <hr class="my-4">
                        <a href="/" class="btn primary-btn mt-2">Retour à l'accueil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
