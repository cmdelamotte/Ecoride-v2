<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Espace Employé</h1>
        <p class="banner-subtitle lead">Gestion des avis et suivi des signalements</p>
    </div>
</section>

<section class="employee-dashboard-section py-4">
    <div class="container">

        <h2 class="mb-4">Avis en attente de modération</h2>
        <div class="review-list mb-5">
            </div>

        <nav aria-label="Pagination des avis">
            <ul class="pagination justify-content-center" id="reviews-pagination"></ul>
        </nav>

        <div class="text-center py-3 text-muted d-none" id="no-pending-reviews">
            <p><i class="bi bi-check2-circle"></i> Aucun avis en attente de modération pour le moment.</p>
        </div>

        <h2 class="mb-4">Covoiturages signalés</h2>
        <div class="reported-rides-list">
            </div>

        <nav aria-label="Pagination des signalements">
            <ul class="pagination justify-content-center" id="reports-pagination"></ul>
        </nav>

        <div class="text-center py-3 text-muted d-none" id="no-reported-rides">
            <p><i class="bi bi-check2-circle"></i> Aucun covoiturage signalé actuellement.</p>
        </div>

    </div>
</section>

<template id="pending-review-card-template">
    <div class="card ride-card mb-3" data-review-id="">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Avis de : <span class="fw-bold review-passenger-name"></span> sur <span class="fw-bold review-driver-name"></span></span>
            <span class="text-muted review-id">ID Avis: #</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-9">
                    <div class="d-flex align-items-center mb-1">
                        <span class="review-rating-stars me-2"></span>
                        <span class="review-rating-text"></span>
                    </div>
                    <p class="card-text mb-2 review-comment-text">
                        <span class="fst-italic review-comment-content"></span> </p>
                    <p class="small text-muted">Soumis le : <span class="review-submitted-date"></span> - Trajet ID #<span class="review-ride-id"></span> (<span class="review-ride-details"></span>)</p>
                </div>
                <div class="col-md-3 text-md-end mt-2 mt-md-0 align-self-center review-actions">
                    <button class="btn btn-success btn-sm mb-1 w-100 action-validate-review">Valider l'avis</button>
                    <button class="btn btn-outline-danger btn-sm w-100 action-reject-review">Refuser l'avis</button>
                </div>
            </div>
        </div>
    </div>
</template>

<template id="reported-ride-card-template">
    <div class="card ride-card mb-3 border-danger" data-report-id="">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <span class="report-title">Signalement pour trajet : #<span class="report-ride-id"></span></span>
            <span class="text-white-50 report-submission-date">Date signalement: </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-9">
                    <p class="card-text mb-1"><span class="fw-bold">Trajet :</span> <span class="report-ride-departure"></span> <i class="bi bi-arrow-right-short"></i> <span class="report-ride-arrival"></span> (<span class="report-ride-date"></span>)</p>
                    <p class="card-text mb-1"><span class="fw-bold">Passager :</span> <span class="report-passenger-name"></span> (<a href="" class="link report-passenger-email"></a>)</p>
                    <p class="card-text mb-1"><span class="fw-bold">Chauffeur :</span> <span class="report-driver-name"></span> (<a href="" class="link report-driver-email"></a>)</p>
                    <hr>
                    <p class="card-text mb-1"><span class="fw-bold">Motif / Commentaire du passager :</span></p>
                    <p class="card-text bg-light p-2 rounded report-reason-comment">
                        <span class="fst-italic report-reason-content"></span> </p>
                </div>
                <div class="col-md-3 text-md-end mt-2 mt-md-0 align-self-center report-actions">
                    <button class="btn btn-success btn-sm mb-1 w-100 action-credit-driver">Créditer le chauffeur</button>
                    <button class="btn btn-outline-info btn-sm w-100 action-contact-driver">Contacter le chauffeur</button>
                </div>
            </div>
        </div>
        <div class="card-footer text-danger">
            Statut : Signalé - Action requise
        </div>
    </div>
</template>

<script type="module" src="/js/pages/employeeDashboardPage.js"></script>