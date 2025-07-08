<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\UserService;

/**
 * Classe UserController
 * Gère les opérations liées au profil utilisateur et à la gestion de compte.
 * Cette classe est responsable de l'affichage du tableau de bord utilisateur,
 * de la mise à jour des informations personnelles, du mot de passe, etc.
 */
class UserController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
    }

    /**
     * Affiche la page du compte utilisateur avec ses informations.
     * Correspond à la route GET /account.
     * Cette page est accessible uniquement aux utilisateurs authentifiés.
     */
    public function account()
    {
        // Je vérifie d'abord si l'utilisateur est bien authentifié
        // en regardant si son ID est en session.
        if (!isset($_SESSION['user_id'])) {
            // Si non, je le redirige vers la page de connexion.
            // C'est une mesure de sécurité de base.
            header('Location: /login');
            exit();
        }

        // Je récupère l'ID de l'utilisateur depuis la session.
        $userId = $_SESSION['user_id'];

        // J'utilise le UserService pour récupérer l'objet User complet.
        // Cela sépare bien la récupération des données (Service) de la logique de la page (Contrôleur).
        $user = $this->userService->findById($userId);

        // Si pour une raison quelconque l'utilisateur n'est pas trouvé en BDD
        // (par ex. supprimé entre-temps), je déconnecte et redirige.
        if (!$user) {
            session_destroy();
            header('Location: /login');
            exit();
        }

        // Je passe l'objet User à la vue pour qu'elle puisse afficher les informations.
        $this->render('account/index', [
            'pageTitle' => 'Mon Compte',
            'user' => $user
        ]);
    }
}
