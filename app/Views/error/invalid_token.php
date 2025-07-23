<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Lien Invalide</h1>
        <p class="banner-subtitle lead"><?= htmlspecialchars($message ?? 'Le lien que vous avez utilisé est invalide ou manquant.') ?></p>
    </div>
</section>

<section class="content-section py-5">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-7 d-flex">
                <div class="card w-100">
                    <div class="card-body d-flex flex-column text-center">
                        <h2 class="card-title mb-4">Lien Invalide</h2>
                        <div class="flex-grow-1">
                            <p><?= htmlspecialchars($message ?? 'Veuillez vérifier le lien ou contacter le support si vous pensez qu\'il s\'agit d\'une erreur.') ?></p>
                        </div>
                        <hr class="my-4">
                        <a href="/" class="btn primary-btn mt-2">Retour à l\'accueil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>