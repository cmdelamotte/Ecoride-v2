<?php

/**
 * Fichier de configuration des routes de l'application.
 * Chaque route est définie comme un tableau associatif avec les clés suivantes :
 * - 'path' : Le chemin de l'URL (ex: '/', '/login').
 * - 'http_method' : La méthode HTTP acceptée pour cette route (ex: 'GET', 'POST', 'PUT', 'DELETE').
 *                   Si omise, la route correspondra à n'importe quelle méthode HTTP.
 * - 'controller' : Le nom du contrôleur à appeler (ex: 'HomeController', 'AuthController').
 * - 'method' : La méthode du contrôleur à exécuter (ex: 'index', 'login', 'register').
 * - 'auth' : Booléen indiquant si la route nécessite une authentification (true) ou non (false).
 * - 'roles' : Tableau des rôles autorisés à accéder à la route. Vide si tous les utilisateurs authentifiés sont autorisés, ou si 'auth' est false.
 *              Ex: ['ROLE_ADMIN', 'ROLE_EMPLOYEE', 'ROLE_USER'].
 */

return [
    // ---------------------------------------------------------------------
    // Routes publiques (accessibles sans authentification)
    // ---------------------------------------------------------------------
    [
        'path' => '/',
        'http_method' => 'GET', // Affichage de la page d'accueil
        'controller' => 'HomeController',
        'method' => 'index',
        'auth' => false,
        'roles' => []
    ],
    // ---------------------------------------------------------------------
    // Routes pour la gestion des véhicules (VehicleController)
    // ---------------------------------------------------------------------
    [
        'path' => '/api/brands',
        'http_method' => 'GET', // API pour récupérer toutes les marques de véhicules
        'controller' => 'VehicleController',
        'method' => 'getBrands',
        'auth' => true, // Nécessite d'être connecté pour voir les marques
        'roles' => []
    ],
    [
        'path' => '/api/vehicles',
        'http_method' => 'POST', // Ajout d'un véhicule
        'controller' => 'VehicleController',
        'method' => 'add',
        'auth' => true,
        'roles' => ['ROLE_DRIVER', 'ROLE_PASSENGER_DRIVER']
    ],
    [
        'path' => '/api/vehicles/{id}/update',
        'http_method' => 'POST', // Mise à jour d'un véhicule
        'controller' => 'VehicleController',
        'method' => 'update',
        'auth' => true,
        'roles' => ['ROLE_DRIVER', 'ROLE_PASSENGER_DRIVER']
    ],
    [
        'path' => '/api/vehicles/{id}/delete',
        'http_method' => 'POST', // Suppression d'un véhicule
        'controller' => 'VehicleController',
        'method' => 'delete',
        'auth' => true,
        'roles' => ['ROLE_DRIVER', 'ROLE_PASSENGER_DRIVER']
    ],
    [
        'path' => '/api/user/vehicles',
        'http_method' => 'GET',
        'controller' => 'VehicleController',
        'method' => 'getUserVehiclesApi',
        'auth' => true,
        'roles' => ['ROLE_DRIVER', 'ROLE_PASSENGER_DRIVER']
    ],
    [
        'path' => '/contact',
        'http_method' => 'GET', // Affichage du formulaire de contact
        'controller' => 'ContactController',
        'method' => 'index',
        'auth' => false,
        'roles' => []
    ],
    [
        'path' => '/contact',
        'http_method' => 'POST', // Soumission du formulaire de contact
        'controller' => 'ContactController',
        'method' => 'submit',
        'auth' => false,
        'roles' => []
    ],
    [
        'path' => '/legal-mentions',
        'http_method' => 'GET', // Affichage des mentions légales
        'controller' => 'LegalMentionsController',
        'method' => 'index',
        'auth' => false,
        'roles' => []
    ],

    // ---------------------------------------------------------------------
    // Routes d'authentification
    // ---------------------------------------------------------------------
    [
        'path' => '/login',
        'http_method' => 'GET', // Affichage du formulaire de connexion
        'controller' => 'AuthController',
        'method' => 'loginForm', // Renommé pour clarté
        'auth' => false,
        'roles' => []
    ],
    [
        'path' => '/login',
        'http_method' => 'POST', // Traitement de la connexion
        'controller' => 'AuthController',
        'method' => 'login',
        'auth' => false,
        'roles' => []
    ],
    [
        'path' => '/register',
        'http_method' => 'GET', // Affichage du formulaire d'inscription
        'controller' => 'AuthController',
        'method' => 'registerForm', // Renommé pour clarté
        'auth' => false,
        'roles' => []
    ],
    [
        'path' => '/register',
        'http_method' => 'POST', // Traitement de l'inscription
        'controller' => 'AuthController',
        'method' => 'register',
        'auth' => false,
        'roles' => []
    ],
    [
        'path' => '/forgot-password',
        'http_method' => 'GET', // Affichage du formulaire de demande de réinitialisation
        'controller' => 'AuthController',
        'method' => 'forgotPasswordForm', // Renommé pour clarté
        'auth' => false,
        'roles' => []
    ],
    [
        'path' => '/forgot-password',
        'http_method' => 'POST', // Traitement de la demande de réinitialisation
        'controller' => 'AuthController',
        'method' => 'forgotPassword',
        'auth' => false,
        'roles' => []
    ],
    [
        'path' => '/reset-password',
        'http_method' => 'GET', // Affichage du formulaire de réinitialisation avec token
        'controller' => 'AuthController',
        'method' => 'resetPasswordForm', // Renommé pour clarté
        'auth' => false,
        'roles' => []
    ],
    [
        'path' => '/reset-password',
        'http_method' => 'POST', // Traitement de la réinitialisation du mot de passe
        'controller' => 'AuthController',
        'method' => 'resetPassword',
        'auth' => false,
        'roles' => []
    ],

    // ---------------------------------------------------------------------
    // Routes nécessitant une authentification (rôle générique ROLE_USER)
    // ---------------------------------------------------------------------
    [
        'path' => '/logout',
        'http_method' => 'GET', // Déconnexion (souvent GET pour simplicité, POST pour sécurité accrue)
        'controller' => 'AuthController',
        'method' => 'logout',
        'auth' => true,
        'roles' => [] // Tous les utilisateurs authentifiés
    ],
    [
        'path' => '/account',
        'http_method' => 'GET', // Affichage du profil utilisateur
        'controller' => 'UserController',
        'method' => 'account',
        'auth' => true,
        'roles' => []
    ],
    [
        'path' => '/account/update-info',
        'http_method' => 'GET', // Affichage du formulaire de modification des informations personnelles
        'controller' => 'UserController',
        'method' => 'updateInfo',
        'auth' => true,
        'roles' => []
    ],
    [
        'path' => '/account/update-info',
        'http_method' => 'POST', // Mise à jour des informations personnelles
        'controller' => 'UserController',
        'method' => 'updateInfo',
        'auth' => true,
        'roles' => []
    ],
    [
        'path' => '/account/update-password',
        'http_method' => 'GET', // Affichage du formulaire de mise à jour du mot de passe
        'controller' => 'UserController',
        'method' => 'updatePassword',
        'auth' => true,
        'roles' => []
    ],
    [
        'path' => '/account/update-password',
        'http_method' => 'POST', // Traitement du formulaire de mise à jour du mot de passe
        'controller' => 'UserController',
        'method' => 'updatePassword',
        'auth' => true,
        'roles' => []
    ],
    [
        'path' => '/account/update-role',
        'http_method' => 'POST', // Mise à jour du rôle fonctionnel
        'controller' => 'UserController',
        'method' => 'updateRole',
        'auth' => true,
        'roles' => []
    ],
    [
        'path' => '/account/delete',
        'http_method' => 'POST', // Suppression du compte
        'controller' => 'UserController',
        'method' => 'delete',
        'auth' => true,
        'roles' => []
    ],
    [
        'path' => '/account/update-preferences',
        'http_method' => 'POST', // Mise à jour des préférences chauffeur
        'controller' => 'UserController',
        'method' => 'updatePreferences',
        'auth' => true,
        'roles' => []
    ],
    

    // ---------------------------------------------------------------------
    // Routes pour la recherche de trajets (RideSearchController)
    // ---------------------------------------------------------------------
    [
        'path' => '/rides-search',
        'http_method' => 'GET', // Affiche la page de recherche de trajets (HTML, CSS, JS)
        'controller' => 'RideSearchController',
        'method' => 'searchPage',
        'auth' => false,
        'roles' => []
    ],
    [
        'path' => '/api/rides/search',
        'http_method' => 'GET', // Point de terminaison de l'API pour effectuer la recherche
        'controller' => 'RideSearchController',
        'method' => 'searchApi',
        'auth' => false,
        'roles' => []
    ],
    [
        'path' => '/api/rides/{id}/details',
        'http_method' => 'GET', // API pour récupérer les détails d'un trajet spécifique
        'controller' => 'RideSearchController',
        'method' => 'detailsApi',
        'auth' => false, // Les détails peuvent être vus par des non-authentifiés
        'roles' => []
    ],

    // ---------------------------------------------------------------------
    // Routes pour la gestion des trajets (RideController)
    // ---------------------------------------------------------------------

    [
        'path' => '/publish-ride',
        'http_method' => 'GET', // Affichage du formulaire de publication de trajet
        'controller' => 'RideController',
        'method' => 'publishForm', // Renommé pour clarté
        'auth' => true,
        'roles' => ['ROLE_DRIVER', 'ROLE_PASSENGER_DRIVER'] // Seuls les chauffeurs peuvent publier
    ],
    [
        'path' => '/publish-ride',
        'http_method' => 'POST', // Soumission du formulaire de publication de trajet
        'controller' => 'RideController',
        'method' => 'publish',
        'auth' => true,
        'roles' => ['ROLE_DRIVER', 'ROLE_PASSENGER_DRIVER']
    ],
    [
        'path' => '/rides/{id}',
        'http_method' => 'GET', // Affichage des détails d'un trajet
        'controller' => 'RideController',
        'method' => 'show',
        'auth' => false, // Les détails peuvent être vus par des non-authentifiés
        'roles' => []
    ],
    [
        'path' => '/rides/{id}/book',
        'http_method' => 'POST', // Réservation d'un trajet
        'controller' => 'RideController',
        'method' => 'book',
        'auth' => true,
        'roles' => ['ROLE_PASSENGER', 'ROLE_PASSENGER_DRIVER'] // Seuls les passagers peuvent réserver
    ],
    [
        'path' => '/rides/{id}/cancel',
        'http_method' => 'POST', // Annulation d'une réservation ou d'un trajet
        'controller' => 'RideController',
        'method' => 'cancel',
        'auth' => true,
        'roles' => ['ROLE_PASSENGER', 'ROLE_PASSENGER_DRIVER', 'ROLE_DRIVER'] // Passager ou chauffeur peut annuler
    ],
    [
        'path' => '/rides/{id}/start',
        'http_method' => 'POST', // Démarrage d'un trajet par le chauffeur
        'controller' => 'RideController',
        'method' => 'start',
        'auth' => true,
        'roles' => ['ROLE_DRIVER', 'ROLE_PASSENGER_DRIVER'] // Seuls les chauffeurs peuvent démarrer
    ],
    [
        'path' => '/rides/{id}/finish',
        'http_method' => 'POST', // Fin d'un trajet par le chauffeur
        'controller' => 'RideController',
        'method' => 'finish',
        'auth' => true,
        'roles' => ['ROLE_DRIVER', 'ROLE_PASSENGER_DRIVER'] // Seuls les chauffeurs peuvent terminer
    ],
    [
        'path' => '/confirm-ride',
        'http_method' => 'GET', // Confirmation de trajet par le passager via token
        'controller' => 'ConfirmationController',
        'method' => 'confirmRide',
        'auth' => false, // Accessible via un lien email, donc pas d'authentification requise sur la route
        'roles' => []
    ],
    [
        'path' => '/your-rides',
        'http_method' => 'GET', // Affichage de l'historique des trajets de l'utilisateur
        'controller' => 'RideController',
        'method' => 'yourRides',
        'auth' => true,
        'roles' => []
    ],
    [
        'path' => '/api/user-rides',
        'http_method' => 'GET', // API pour récupérer l'historique des trajets de l'utilisateur
        'controller' => 'RideController',
        'method' => 'getUserRidesApi',
        'auth' => true,
        'roles' => []
    ],

    // ---------------------------------------------------------------------
    // Routes pour l'administration (AdminController)
    // ---------------------------------------------------------------------
    [
        'path' => '/admin/dashboard',
        'http_method' => 'GET', // Affichage du tableau de bord administrateur
        'controller' => 'AdminController',
        'method' => 'dashboard',
        'auth' => true,
        'roles' => ['ROLE_ADMIN']
    ],
    [
        'path' => '/admin/users',
        'http_method' => 'GET', // Gestion des utilisateurs par l'administrateur
        'controller' => 'AdminController',
        'method' => 'manageUsers',
        'auth' => true,
        'roles' => ['ROLE_ADMIN']
    ],
    [
        'path' => '/admin/employees',
        'http_method' => 'GET', // Gestion des employés par l'administrateur
        'controller' => 'AdminController',
        'method' => 'manageEmployees',
        'auth' => true,
        'roles' => ['ROLE_ADMIN']
    ],
    [
        'path' => '/admin/create-employee',
        'http_method' => 'GET', // Affichage du formulaire de création d'employé
        'controller' => 'AdminController',
        'method' => 'createEmployeeForm', // Renommé pour clarté
        'auth' => true,
        'roles' => ['ROLE_ADMIN']
    ],
    [
        'path' => '/admin/create-employee',
        'http_method' => 'POST', // Soumission du formulaire de création d'employé
        'controller' => 'AdminController',
        'method' => 'createEmployee',
        'auth' => true,
        'roles' => ['ROLE_ADMIN']
    ],
    [
        'path' => '/admin/update-user-status',
        'http_method' => 'POST', // Mise à jour du statut d'un utilisateur par l'administrateur
        'controller' => 'AdminController',
        'method' => 'updateUserStatus',
        'auth' => true,
        'roles' => ['ROLE_ADMIN']
    ],

    // ---------------------------------------------------------------------
    // Routes pour les employés (EmployeeController)
    // ---------------------------------------------------------------------
    [
        'path' => '/employee/dashboard',
        'http_method' => 'GET', // Affichage du tableau de bord employé
        'controller' => 'EmployeeController',
        'method' => 'dashboard',
        'auth' => true,
        'roles' => ['ROLE_EMPLOYEE']
    ],
    [
        'path' => '/employee/reviews',
        'http_method' => 'GET', // Gestion des avis par l'employé
        'controller' => 'EmployeeController',
        'method' => 'manageReviews',
        'auth' => true,
        'roles' => ['ROLE_EMPLOYEE']
    ],
    [
        'path' => '/employee/reports',
        'http_method' => 'GET', // Gestion des signalements par l'employé
        'controller' => 'EmployeeController',
        'method' => 'manageReports',
        'auth' => true,
        'roles' => ['ROLE_EMPLOYEE']
    ],

    // ---------------------------------------------------------------------
    // Route 404 (à placer en dernier)
    // ---------------------------------------------------------------------
    [
        'path' => '/404',
        'http_method' => 'GET', // Page d'erreur 404
        'controller' => 'ErrorController',
        'method' => 'notFound',
        'auth' => false,
        'roles' => []
    ]
];