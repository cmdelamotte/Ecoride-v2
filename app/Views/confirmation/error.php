<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Erreur de Confirmation</h1>
        <p class="banner-subtitle lead"><?= htmlspecialchars($message ?? 'Une erreur est survenue lors de la confirmation de votre trajet.') ?></p>
    </div>
</section>

<section class="content-section py-5">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-7 d-flex">
                <div class="card w-100">
                    <div class="card-body d-flex flex-column text-center">
                        <h2 class="card-title mb-4">Problème de Confirmation</h2>
                        <div class="flex-grow-1">
                            <p><?= htmlspecialchars($message ?? 'Le lien de confirmation est peut-être invalide, expiré, ou le trajet a déjà été traité.') ?></p>
                            <p>Si le problème persiste, veuillez contacter le support.</p>
                        </div>
                        <hr class="my-4">
                        <a href="/" class="btn primary-btn mt-2">Retour à l'accueil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>