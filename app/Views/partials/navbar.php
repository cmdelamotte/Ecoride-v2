
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="/">
            <img src="/img/ecoride-logo.png" alt="Logo EcoRide" class="d-inline-block align-text-top">
            <span class="ms-2">EcoRide</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav ms-auto">
                <a class="nav-link auth-link" id="nav-login" href="/login" data-show="disconnected">Connexion</a>
                <a class="nav-link auth-link" id="nav-register" href="/register" data-show="disconnected">Inscription</a>
                <a class="nav-link user-link d-none" id="nav-admin-dashboard" href="/admin-dashboard" data-show="admin">Dashboard Admin</a>
                <a class="nav-link user-link d-none" id="nav-employee-dashboard" href="/employee-dashboard" data-show="employee admin">Dashboard Employé</a>
                <a class="nav-link" id="nav-rides-search" href="/rides-search">Rechercher un trajet</a>
                <a class="nav-link auth-link d-none" id="nav-your-rides" href="/your-rides" data-show="passenger driver passenger_driver">Covoiturages</a>
                <a class="nav-link user-link d-none" id="nav-publish-ride" href="/publish-ride" data-show="driver passenger_driver">Publier un trajet</a>
                <a class="nav-link user-link d-none" id="nav-profile" href="/account" data-show="passenger driver passenger_driver">Mon Compte</a>
                <a class="nav-link" id="nav-contact" href="/contact">Contact</a>
                <a class="nav-link user-link d-none" id="nav-logout" href="#" data-show="passenger driver passenger_driver employee admin">Déconnexion</a>
            </div>
        </div>
    </div>
</nav>
