<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Vehicle;
use PDO;

/**
 * Service VehicleManagementService
 * 
 * Gère la logique métier complète pour le CRUD (Create, Read, Update, Delete) des véhicules.
 * Cette classe a la responsabilité unique de gérer le cycle de vie des entités Vehicle.
 */
class VehicleManagementService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ajoute un nouveau véhicule en base de données.
     *
     * @param array $data Les données du véhicule.
     * @return int|false L'ID du véhicule nouvellement créé ou false en cas d'échec.
     */
    public function addVehicle(array $data): int|false
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO Vehicles (user_id, brand_id, model_name, color, license_plate, registration_date, passenger_capacity, is_electric, energy_type)
                 VALUES (:user_id, :brand_id, :model_name, :color, :license_plate, :registration_date, :passenger_capacity, :is_electric, :energy_type)"
            );

            $success = $stmt->execute([
                ':user_id' => $data['user_id'],
                ':brand_id' => $data['brand_id'],
                ':model_name' => $data['model_name'],
                ':color' => $data['color'],
                ':license_plate' => $data['license_plate'],
                ':registration_date' => $data['registration_date'],
                ':passenger_capacity' => $data['passenger_capacity'],
                ':is_electric' => (int)$data['is_electric'],
                ':energy_type' => $data['energy_type'],
            ]);

            return $success ? (int)$this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            // En cas d'erreur, on l'enregistre dans les logs pour le débogage.
            error_log("VehicleManagementService::addVehicle Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère un véhicule par son ID, avec le nom de la marque.
     *
     * @param int $vehicleId L'ID du véhicule.
     * @return Vehicle|null L'objet Vehicle ou null s'il n'est pas trouvé.
     */
    public function findById(int $vehicleId): ?Vehicle
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT v.*, b.name as brand_name 
                 FROM Vehicles v
                 JOIN Brands b ON v.brand_id = b.id
                 WHERE v.id = :id"
            );
            $stmt->execute([':id' => $vehicleId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            return $this->hydrateVehicle($data);
        } catch (PDOException $e) {
            error_log("VehicleManagementService::findById Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Met à jour un véhicule existant.
     * (Logique à implémenter)
     */
    public function updateVehicle(int $vehicleId, array $data): bool
    {
        // TODO: Implémenter la logique de mise à jour.
        return true;
    }

    /**
     * Supprime un véhicule.
     * (Logique à implémenter)
     */
    public function deleteVehicle(int $vehicleId): bool
    {
        // TODO: Implémenter la logique de suppression.
        return true;
    }

    /**
     * Hydrate un objet Vehicle à partir d'un tableau de données.
     *
     * @param array $data Les données du véhicule.
     * @return Vehicle L'objet Vehicle hydraté.
     */
    private function hydrateVehicle(array $data): Vehicle
    {
        $vehicle = new Vehicle();
        $vehicle->setId($data['id'])
                ->setUserId($data['user_id'])
                ->setBrandId($data['brand_id'])
                ->setModelName($data['model_name'])
                ->setColor($data['color'])
                ->setLicensePlate($data['license_plate'])
                ->setRegistrationDate($data['registration_date'])
                ->setPassengerCapacity($data['passenger_capacity'])
                ->setIsElectric($data['is_electric'])
                ->setEnergyType($data['energy_type'])
                ->setCreatedAt($data['created_at'])
                ->setUpdatedAt($data['updated_at']);

        if (isset($data['brand_name'])) {
            $vehicle->setBrandName($data['brand_name']); // Propriété virtuelle
        }

        return $vehicle;
    }
}