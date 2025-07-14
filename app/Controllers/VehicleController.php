<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\VehicleManagementService;
use App\Services\BrandService;
use App\Helpers\RequestHelper;

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
