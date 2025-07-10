<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\UserService;
use App\Services\VehicleService;
use App\Services\UserRoleService;
use App\Services\DriverPreferenceService;
use App\Services\VehicleManagementService;

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

    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
        $this->vehicleService = new VehicleService();
        $this->userRoleService = new UserRoleService();
        $this->driverPreferenceService = new DriverPreferenceService();
        $this->vehicleManagementService = new VehicleManagementService();
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

        // Je récupère les véhicules de l'utilisateur
        $vehicles = $this->vehicleService->findByUserId($userId);

        // Je passe l'objet User et les véhicules à la vue pour qu'elle puisse afficher les informations.
        $this->render('account/index', [
            'pageTitle' => 'Mon Compte',
            'user' => $user,
            'vehicles' => $vehicles
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
        $success = $this->userRoleService->updateFunctionalRole($userId, $newRole);

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

        // Appel au service dédié pour mettre à jour les préférences
        $success = $this->driverPreferenceService->updatePreferences($userId, $updateData);

        if ($success) {
            $this->jsonResponse(['success' => true, 'message' => 'Préférences mises à jour avec succès.']);
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'Erreur lors de la mise à jour des préférences.'], 500);
        }
    }

    /**
     * Gère l'ajout d'un nouveau véhicule pour l'utilisateur connecté.
     */
    public function addVehicle()
    {
        error_log("--- Début de addVehicle ---");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Méthode non autorisée : " . $_SERVER['REQUEST_METHOD']);
            $this->jsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
            return;
        }

        $jsonInput = file_get_contents('php://input');
        error_log("Données JSON reçues : " . $jsonInput);
        $data = json_decode($jsonInput, true);

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            error_log("Utilisateur non authentifié.");
            $this->jsonResponse(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
            return;
        }

        // Validation des données du véhicule
        $brandId = filter_var($data['brand_id'] ?? null, FILTER_VALIDATE_INT);
        $modelName = htmlspecialchars(trim($data['model'] ?? ''));
        $color = htmlspecialchars(trim($data['color'] ?? ''));
        $licensePlate = htmlspecialchars(trim($data['license_plate'] ?? ''));
        $registrationDate = htmlspecialchars(trim($data['registration_date'] ?? ''));
        $passengerCapacity = filter_var($data['passenger_capacity'] ?? null, FILTER_VALIDATE_INT);
        $isElectric = filter_var($data['is_electric'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $energyType = htmlspecialchars(trim($data['energy_type'] ?? ''));

        $errors = [];
        if (!$brandId) $errors['brand_id'] = 'La marque est requise.';
        if (empty($modelName)) $errors['model'] = 'Le modèle est requis.';
        if (empty($licensePlate)) $errors['license_plate'] = "La plaque d'immatriculation est requise.";
        if (!$passengerCapacity || $passengerCapacity < 1 || $passengerCapacity > 8) $errors['passenger_capacity'] = 'Le nombre de places est invalide (entre 1 et 8).';

        if (!empty($errors)) {
            error_log("Erreurs de validation : " . print_r($errors, true));
            $this->jsonResponse(['success' => false, 'errors' => $errors], 400);
            return;
        }

        $vehicleData = [
            'user_id' => $userId,
            'brand_id' => $brandId,
            'model_name' => $modelName,
            'color' => $color,
            'license_plate' => $licensePlate,
            'registration_date' => $registrationDate,
            'passenger_capacity' => $passengerCapacity,
            'is_electric' => $isElectric,
            'energy_type' => $energyType
        ];
        error_log("Données du véhicule préparées pour le service : " . print_r($vehicleData, true));

        $vehicleId = $this->vehicleManagementService->addVehicle($vehicleData);
        error_log("ID du véhicule retourné par le service : " . ($vehicleId ?: 'false'));

        if ($vehicleId) {
            $newVehicle = $this->vehicleManagementService->findById($vehicleId);
            error_log("Véhicule récupéré après création : " . ($newVehicle ? print_r($newVehicle, true) : 'null'));
            $this->jsonResponse(['success' => true, 'message' => 'Véhicule ajouté avec succès.', 'vehicle' => $newVehicle]);
        } else {
            error_log("Échec de l'ajout du véhicule, renvoi d'une erreur JSON.");
            $this->jsonResponse(['success' => false, 'error' => "Erreur lors de l'ajout du véhicule."], 500);
        }
    }
}

