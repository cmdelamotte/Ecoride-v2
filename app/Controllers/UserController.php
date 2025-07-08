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

    /**
     * Gère la mise à jour du rôle fonctionnel de l'utilisateur via une requête API (AJAX).
     */
    public function updateRole()
    {
        // Je m'assure que la méthode est bien POST pour la sécurité.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
            return;
        }

        // Je récupère le corps de la requête qui est en JSON.
        $data = json_decode(file_get_contents('php://input'), true);

        $newRole = $data['role'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;

        // Validation des données
        if (!$userId) {
            $this->jsonResponse(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
            return;
        }

        $allowedRoles = ['passenger', 'driver', 'passenger_driver'];
        if (!$newRole || !in_array($newRole, $allowedRoles)) {
            $this->jsonResponse(['success' => false, 'error' => 'Rôle non valide'], 400);
            return;
        }

        // Appel au service pour mettre à jour les données
        $success = $this->userService->update($userId, ['functional_role' => $newRole]);

        if ($success) {
            $this->jsonResponse(['success' => true, 'message' => 'Rôle mis à jour avec succès.', 'new_functional_role' => $newRole]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'Erreur lors de la mise à jour du rôle.'], 500);
        }
    }

    /**
     * Gère la mise à jour des préférences du conducteur via une requête API (AJAX).
     */
    public function updatePreferences()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $this->jsonResponse(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
            return;
        }

        // Récupération et validation des préférences
        $prefSmoker = filter_var($data['pref_smoker'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $prefAnimals = filter_var($data['pref_animals'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $prefMusic = filter_var($data['pref_music'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $prefCustom = htmlspecialchars(trim($data['pref_custom'] ?? ''));

        // Préparation des données pour la mise à jour
        $updateData = [
            'driver_pref_smoker' => $prefSmoker,
            'driver_pref_animals' => $prefAnimals,
            'driver_pref_music' => $prefMusic,
            'driver_pref_custom' => $prefCustom,
        ];

        // Appel au service pour mettre à jour les données
        $success = $this->userService->update($userId, $updateData);

        if ($success) {
            $this->jsonResponse(['success' => true, 'message' => 'Préférences mises à jour avec succès.']);
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'Erreur lors de la mise à jour des préférences.'], 500);
        }
    }
}
