
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
                <?php if (!isset($_SESSION['user_id'])): ?>
                <a class="nav-link" id="nav-login" href="/login">Connexion</a>
                <a class="nav-link" id="nav-register" href="/register">Inscription</a>
                <?php endif; ?>
                <a class="nav-link" id="nav-rides-search" href="/rides-search">Rechercher un trajet</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a class="nav-link" id="nav-your-rides" href="/your-rides">Covoiturages</a>
                <?php 
                    $userRoles = $_SESSION['user_roles'] ?? [];
                    if (in_array('ROLE_DRIVER', $userRoles) || in_array('ROLE_PASSENGER_DRIVER', $userRoles)):
                ?>
                <a class="nav-link" id="nav-publish-ride" href="/publish-ride">Publier un trajet</a>
                <?php endif; ?>
                <a class="nav-link" id="nav-profile" href="/account">Mon Compte</a>
                <a class="nav-link" id="nav-logout" href="/logout">Déconnexion</a>
                <?php endif; ?>
                <a class="nav-link" id="nav-contact" href="/contact">Contact</a>
            </div>
        </div>
    </div>
</nav>
