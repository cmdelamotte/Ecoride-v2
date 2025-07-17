<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\VehicleManagementService;
use App\Services\BrandService;
use App\Helpers\RequestHelper;
use App\Models\Vehicle; // Import du modèle Vehicle
use App\Helpers\AuthHelper; // Import du AuthHelper pour récupérer l'utilisateur
use App\Services\ValidationService; // Import du ValidationService

/**
 * Gère toutes les opérations liées aux véhicules des utilisateurs.
 * Ce contrôleur centralise la logique pour ajouter, lister, mettre à jour,
 * et supprimer les véhicules, ainsi que pour lister les marques disponibles.
 */
class VehicleController extends Controller
{
    private VehicleManagementService $vehicleManagementService;
    private BrandService $brandService;

    public function __construct()
    {
        parent::__construct();
        $this->vehicleManagementService = new VehicleManagementService();
        $this->brandService = new BrandService();
    }

    /**
     * Récupère et renvoie la liste de toutes les marques de véhicules au format JSON.
     * Correspond à la route GET /api/brands.
     */
    public function getBrands()
    {
        $brands = $this->brandService->findAll();

        $brandsAsArray = [];
        foreach ($brands as $brand) {
            $brandsAsArray[] = [
                'id' => $brand->getId(),
                'name' => $brand->getName()
            ];
        }

        $this->jsonResponse(['success' => true, 'brands' => $brandsAsArray]);
    }

    /**
     * Gère l'ajout d'un nouveau véhicule pour l'utilisateur connecté.
     * Correspond à la route POST /api/vehicles.
     */
    public function add()
    {
        $requestData = RequestHelper::getApiRequestData();
        $user = AuthHelper::getAuthenticatedUser(); // Récupère l'objet User authentifié
        $data = $requestData['data'];

        // Validation des données brutes du formulaire
        $errors = ValidationService::validateVehicleData($data, null);
        if (!empty($errors)) {
            $this->jsonResponse(['success' => false, 'errors' => $errors], 400);
            return;
        }

        // Construction de l'objet Vehicle à partir des données validées
        $vehicle = (new Vehicle())
            ->setBrandId($data['brand_id'])
            ->setModelName(htmlspecialchars(trim($data['model'])))
            ->setColor(htmlspecialchars(trim($data['color'] ?? '')))
            ->setLicensePlate(htmlspecialchars(trim($data['license_plate'])))
            ->setRegistrationDate(empty($data['registration_date']) ? null : htmlspecialchars(trim($data['registration_date'])))
            ->setPassengerCapacity($data['passenger_capacity'])
            ->setIsElectric((bool)($data['is_electric'] ?? false))
            ->setEnergyType(htmlspecialchars(trim($data['energy_type'] ?? '')));

        // Appel du service avec l'objet Vehicle et l'ID utilisateur
        $result = $this->vehicleManagementService->addVehicle($vehicle, $user->getId());

        $this->jsonResponse($result, $result['status'] ?? 200);
    }

    /**
     * Gère la mise à jour d'un véhicule existant.
     * Correspond à la route POST /api/vehicles/{id}/update.
     */
    public function update(int $vehicleId)
    {
        $requestData = RequestHelper::getApiRequestData();
        $userId = $requestData['userId'];
        $data = $requestData['data'];

        $result = $this->vehicleManagementService->updateVehicle($vehicleId, $userId, $data);

        $this->jsonResponse($result, $result['status'] ?? 200);
    }

    /**
     * Gère la suppression d'un véhicule existant.
     * Correspond à la route POST /api/vehicles/{id}/delete.
     */
    public function delete(int $vehicleId)
    {
        $requestData = RequestHelper::getApiRequestData();
        $userId = $requestData['userId'];

        $result = $this->vehicleManagementService->deleteVehicle($vehicleId, $userId);

        $this->jsonResponse($result, $result['status'] ?? 200);
    }
}
