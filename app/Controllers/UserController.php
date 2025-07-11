<?php

namespace App\Controllers;

file_put_contents('debug.log', "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents('debug.log', "RAW: " . file_get_contents('php://input') . "\n", FILE_APPEND);
file_put_contents('debug.log', "SESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);


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
        parent::__construct();
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
        $userId = $requestData['userId'];
        $data = $requestData['data'];

        $result = $this->userRoleService->updateFunctionalRole($userId, $data['role'] ?? null);

        if ($result['success']) {
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
        $userId = $requestData['userId'];
        $data = $requestData['data'];

        $result = $this->driverPreferenceService->updatePreferences($userId, $data);

        if ($result['success']) {
            $this->jsonResponse(['success' => true, 'message' => $result['message']]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], $result['status'] ?? 500);
        }
    }

    /**
     * Gère l'ajout d'un nouveau véhicule pour l'utilisateur connecté.
     * Le contrôleur se contente de recevoir la requête, d'appeler le service et de renvoyer la réponse.
     */
    public function addVehicle()
    {
        $requestData = RequestHelper::getApiRequestData();
        $userId = $requestData['userId'];
        $data = $requestData['data'];

        $result = $this->vehicleManagementService->addVehicle($userId, $data);

        if ($result['success']) {
            $this->jsonResponse(['success' => true, 'message' => $result['message'], 'vehicle' => $result['vehicle']]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => $result['error'], 'errors' => $result['errors']], $result['status'] ?? 500);
        }
    }

    /**
     * Gère l'affichage et la soumission du formulaire d'édition du profil utilisateur.
     * Correspond à la route GET /account/edit (affichage) et POST /account/edit (traitement).
     * Le contrôleur se contente de recevoir la requête, d'appeler le service et de gérer la redirection/affichage.
     */
    public function edit()
    {
        $user = AuthHelper::getAuthenticatedUser();

        // Gère la soumission du formulaire (méthode POST).
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Délègue la logique de mise à jour du profil au UserAccountService.
            // Le service est responsable de la validation, du téléchargement d'avatar et de la mise à jour en BDD.
            $result = $this->userAccountService->updateProfile($user->getId(), $_POST, $_FILES);

            if ($result['success']) {
                // Met à jour le nom d'utilisateur en session si modifié.
                if (isset($_POST['username'])) {
                    $_SESSION['username'] = $_POST['username'];
                }
                $this->render('account/index', [
                    'pageTitle' => 'Mon Compte',
                    'user' => $this->userService->findById($user->getId()), // Recharger l'utilisateur après mise à jour
                    'vehicles' => $this->vehicleService->findByUserId($user->getId()),
                    'success' => 'Votre profil a été mis à jour avec succès.'
                ]);
                return;
            } else {
                $this->render('account/edit', [
                    'pageTitle' => 'Modifier mon profil',
                    'user' => $user, // Utilise l'utilisateur original pour pré-remplir le formulaire
                    'errors' => $result['errors'] ?? [],
                    'oldInput' => $_POST
                ]);
                return;
            }
        }

        // Affiche le formulaire d'édition (méthode GET).
        $this->render('account/edit', [
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
        $requestData = RequestHelper::getApiRequestData();
        $userId = $requestData['userId'];

        $result = $this->userAccountService->deleteAccount($userId);

        if ($result['success']) {
            session_destroy();
            header('Location: /login');
            exit();
        } else {
            $this->jsonResponse(['success' => false, 'error' => $result['error']], $result['status'] ?? 500);
        }
    }

    }

