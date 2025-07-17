<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\UserService;
use App\Services\VehicleService;
use App\Services\UserRoleService;
use App\Services\DriverPreferenceService;
use App\Services\VehicleManagementService;
use App\Services\UserAccountService;
use App\Services\AvatarService;
use App\Helpers\AuthHelper;
use App\Helpers\RequestHelper;
use App\Helpers\VehicleHelper;

/**
 * Classe UserController
 * Gère les opérations liées au profil utilisateur et à la gestion de compte.
 * Cette classe est responsable de l'affichage du tableau de bord utilisateur,
 * de la mise à jour des informations personnelles, du mot de passe, etc.
 */
class UserController extends Controller
{
    private UserService $userService;
    private VehicleService $vehicleService;
    private UserRoleService $userRoleService;
    private DriverPreferenceService $driverPreferenceService;
    private VehicleManagementService $vehicleManagementService;
    private UserAccountService $userAccountService;
    private AvatarService $avatarService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->vehicleService = new VehicleService();
        $this->userRoleService = new UserRoleService();
        $this->driverPreferenceService = new DriverPreferenceService();
        $this->vehicleManagementService = new VehicleManagementService();
        $this->userAccountService = new UserAccountService();
        $this->avatarService = new AvatarService();
    }

    /**
     * Affiche la page du compte utilisateur avec ses informations.
     * Correspond à la route GET /account.
     * Cette page est accessible uniquement aux utilisateurs authentifiés.
     */
    public function account()
    {
        $user = AuthHelper::getAuthenticatedUser();
        $vehicles = $this->vehicleService->findByUserId($user->getId());
        $formattedVehicles = VehicleHelper::formatCollectionForApi($vehicles);

        $this->render('account/index', [
            'pageTitle' => 'Mon Compte',
            'user' => $user,
            'vehicles' => $formattedVehicles
        ]);
    }

    /**
     * Gère la mise à jour du rôle fonctionnel de l'utilisateur via une requête API (AJAX).
     * Le contrôleur se contente de recevoir la requête, d'appeler le service et de renvoyer la réponse.
     */
    public function updateRole()
    {
        $requestData = RequestHelper::getApiRequestData();
        // Je récupère l'utilisateur authentifié. C'est l'objet User que le service attend.
        $user = AuthHelper::getAuthenticatedUser();
        $data = $requestData['data'];

        $result = $this->userRoleService->updateFunctionalRole($user, $data['role'] ?? null);

        if ($result['success']) {
            // Je mets à jour le rôle en session pour qu'il soit immédiatement pris en compte.
            $_SESSION['user_roles'] = array_filter([$user->getSystemRole(), 'ROLE_' . strtoupper($result['new_functional_role'])]);
            $this->jsonResponse(['success' => true, 'message' => $result['message'], 'new_functional_role' => $result['new_functional_role']]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], $result['status'] ?? 500);
        }
    }

    /**
     * Gère la mise à jour des préférences du conducteur via une requête API (AJAX).
     * Le contrôleur se contente de recevoir la requête, d'appeler le service et de renvoyer la réponse.
     */
    public function updatePreferences()
    {
        $requestData = RequestHelper::getApiRequestData();
        // Je récupère l'utilisateur authentifié. C'est l'objet User que le service attend.
        $user = AuthHelper::getAuthenticatedUser();
        $data = $requestData['data'];

        $result = $this->driverPreferenceService->updatePreferences($user, $data);

        if ($result['success']) {
            $this->jsonResponse(['success' => true, 'message' => $result['message']]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], $result['status'] ?? 500);
        }
    }

    

    /**
     * Gère l'affichage et la soumission du formulaire d'édition du profil utilisateur.
     * Correspond à la route GET /account/edit (affichage) et POST /account/edit (traitement).
     * Le contrôleur se contente de recevoir la requête, d'appeler le service et de gérer la redirection/affichage.
     */
    public function updateInfo()
    {
        $user = AuthHelper::getAuthenticatedUser();

        // Gère la soumission du formulaire (méthode POST).
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Délègue la logique de mise à jour du profil au UserAccountService.
            // Le service est responsable de la validation, du téléchargement d'avatar et de la mise à jour en BDD.
            // Je passe l'objet User directement au service.
            $result = $this->userAccountService->updateProfile($user, $_POST, $_FILES);

            if ($result['success']) {
                // Recharger l'utilisateur après mise à jour pour s'assurer que l'objet en session est à jour.
                // Cela est important car les setters de l'objet User sont utilisés pour la mise à jour.
                $updatedUser = $this->userService->findById($user->getId());
                
                // Met à jour le nom d'utilisateur en session si modifié.
                if ($updatedUser && $updatedUser->getUsername() !== $_SESSION['username']) {
                    $_SESSION['username'] = $updatedUser->getUsername();
                }

                // Met à jour le pseudo en session si modifié.
                if ($updatedUser && $updatedUser->getUsername() !== $_SESSION['username']) {
                    $_SESSION['username'] = $updatedUser->getUsername();
                }

                $this->render('account/index', [
                    'pageTitle' => 'Mon Compte',
                    'user' => $updatedUser, // Utilise l'utilisateur mis à jour
                    'vehicles' => $this->vehicleService->findByUserId($user->getId()),
                    'success' => 'Votre profil a été mis à jour avec succès.'
                ]);
                return;
            } else {
                $this->render('account/edit-info', [
                    'pageTitle' => 'Modifier mon profil',
                    'user' => $user, // Utilise l'utilisateur original pour pré-remplir le formulaire
                    'errors' => $result['errors'] ?? [],
                    'oldInput' => $_POST
                ]);
                return;
            }
        }

        // Affiche le formulaire d'édition (méthode GET).
        $this->render('account/edit-info', [
            'pageTitle' => 'Modifier mon profil',
            'user' => $user
        ]);
    }

    /**
     * Gère la suppression du compte utilisateur.
     * Le contrôleur se contente de recevoir la requête, d'appeler le service et de gérer la redirection.
     */
    public function delete()
    {
        $user = AuthHelper::getAuthenticatedUser();

        $result = $this->userAccountService->deleteAccount($user);

        if ($result['success']) {
            $this->jsonResponse(['success' => true, 'message' => 'Votre compte a été supprimé avec succès. Redirection...'], 200);
        } else {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], $result['status'] ?? 500);
        }
    }

    /**
     * Gère l'affichage et la soumission du formulaire de changement de mot de passe.
     */
    public function updatePassword()
    {
        $user = AuthHelper::getAuthenticatedUser();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmNewPassword = $_POST['confirm_new_password'] ?? '';

            $result = $this->userAccountService->changePassword($user, $currentPassword, $newPassword, $confirmNewPassword);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Votre mot de passe a été mis à jour avec succès.';
                header('Location: /account');
                exit();
            } else {
                $this->render('account/edit-password', [
                    'pageTitle' => 'Modifier mon mot de passe',
                    'errors' => $result['errors'] ?? [],
                    'oldInput' => $_POST // Pour conserver les valeurs saisies (sauf mots de passe)
                ]);
            }
        } else {
            $this->render('account/edit-password', [
                'pageTitle' => 'Modifier mon mot de passe'
            ]);
        }
    }
}

