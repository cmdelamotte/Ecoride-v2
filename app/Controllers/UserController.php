<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Classe UserController
 * Gère les opérations liées au profil utilisateur et à la gestion de compte.
 * Cette classe est responsable de l'affichage du tableau de bord utilisateur,
 * de la mise à jour des informations personnelles, du mot de passe, etc.
 */
class UserController extends Controller
{
    /**
     * Affiche la page du compte utilisateur.
     * Correspond à la route GET /account.
     * Cette page est accessible uniquement aux utilisateurs authentifiés.
     */
    public function account()
    {
        // Pour l'instant, nous affichons une page simple.
        // Plus tard, nous récupérerons les données de l'utilisateur connecté
        // depuis la base de données et les passerons à la vue.
        $this->render('account/index', ['pageTitle' => 'Mon Compte']);
    }
}
