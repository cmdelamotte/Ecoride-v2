<?php
// Je définis le titre de la page qui sera utilisé dans le layout
$pageTitle = "Espace Administrateur";
?>

<section class="banner-container position-relative text-white text-center">
    <div class="container">
        <h1 class="banner-title display-4 mb-3">Espace Administrateur</h1>
        <p class="banner-subtitle lead">Gestion et statistiques de la plateforme EcoRide</p>
    </div>
</section>

<section class="admin-dashboard-section py-4">
    <div class="container-fluid px-4">
        <h2 class="mb-4">Statistiques Clés</h2>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h5 class="card-title">Crédits Plateforme Totaux</h5>
                        <p class="display-6 fw-bold" id="admin-total-credits">Chargement...</p>
                    </div>
                    <div class="card-footer text-muted">
                        Cumul total
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-center">Covoiturages / Jour</h5>
                        <div class="chart-container flex-grow-1" style="position: relative; height:200px; width:100%;">
                            <canvas id="ridesPerDayChart"></canvas> 
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-center">Crédits Gagnés / Jour</h5>
                        <div class="chart-container flex-grow-1" style="position: relative; height:200px; width:100%;">
                            <canvas id="creditsGainedPerDayChart"></canvas> 
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <h2 class="mb-3 d-flex justify-content-between align-items-center">
            <span>Gestion des Employés</span>
            <button class="btn primary-btn btn-sm" data-bs-toggle="modal" data-bs-target="#createEmployeeModal">
                <i class="bi bi-plus-circle me-1"></i> Ajouter un employé
            </button>
        </h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
                <thead class="table-light">
                    <tr>
                        <th scope="col">#ID</th>
                        <th scope="col">Nom</th>
                        <th scope="col">Prénom</th>
                        <th scope="col">Email</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody id="employees-table-body">
                    <!-- Les lignes des employés seront injectées ici par JavaScript -->
                </tbody>
            </table>
            <div id="no-employees-message" class="text-center py-3 text-muted d-none">
                <p><i class="bi bi-person-x-fill"></i> Aucun employé trouvé.</p>
            </div>
        </div>

        <template id="employee-row-template">
            <tr>
                <th scope="row" data-label="ID_Employé"></th>
                <td data-label="Nom"></td>
                <td data-label="Prénom"></td>
                <td data-label="Email"></td>
                <td data-label="Statut"><span class="badge"></span></td>
                <td data-label="Actions">
                    <button class="btn btn-warning btn-sm action-suspend" title="Suspendre ce compte">
                        <i class="bi bi-person-slash"></i> Suspendre
                    </button>
                    <button class="btn btn-success btn-sm action-reactivate d-none" title="Réactiver ce compte">
                        <i class="bi bi-person-check"></i> Réactiver
                    </button>
                </td>
            </tr>
        </template>

        <h2 class="mt-5 mb-3">Gestion des Utilisateurs (Clients)</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
                <thead class="table-light">
                    <tr>
                        <th scope="col">#ID</th>
                        <th scope="col">Pseudo</th>
                        <th scope="col">Email</th>
                        <th scope="col">Crédits</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <!-- Les lignes des utilisateurs seront injectées ici par JavaScript -->
                </tbody>
            </table>
            <div id="no-users-message" class="text-center py-3 text-muted d-none">
                <p><i class="bi bi-person-x-fill"></i> Aucun utilisateur trouvé.</p>
            </div>
        </div>

        <template id="user-row-template">
            <tr>
                <th scope="row" data-label="ID_Utilisateur"></th>
                <td data-label="Pseudo"></td>
                <td data-label="Email"></td>
                <td data-label="Crédits"></td>
                <td data-label="Statut"><span class="badge"></span></td>
                <td data-label="Actions">
                    <button class="btn btn-warning btn-sm user-action-suspend" title="Suspendre ce compte">
                        <i class="bi bi-person-slash"></i> Suspendre
                    </button>
                    <button class="btn btn-success btn-sm user-action-reactivate d-none" title="Réactiver ce compte">
                        <i class="bi bi-person-check"></i> Réactiver
                    </button>
                </td>
            </tr>
        </template>
    </div>
</section>

<!-- Modale de création d'employé -->
<div class="modal fade" id="createEmployeeModal" tabindex="-1" aria-labelledby="createEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEmployeeModalLabel">Ajouter un nouvel employé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="create-employee-form">
                    <div class="mb-3">
                        <label for="emp-nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="emp-nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="emp-prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="emp-prenom" required>
                    </div>
                    <div class="mb-3">
                        <label for="emp-email" class="form-label">Email Professionnel</label>
                        <input type="email" class="form-control" id="emp-email" required>
                    </div>
                    <div class="mb-3">
                        <label for="emp-password" class="form-label">Mot de passe initial</label>
                        <input type="password" class="form-control" id="emp-password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn secondary-btn" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="create-employee-form" class="btn primary-btn">Créer le compte</button>
            </div>
        </div>
    </div>
</div>

<!-- J'inclus le script JavaScript spécifique à cette page -->
<script src="/js/pages/adminDashboardPage.js" type="module"></script>
