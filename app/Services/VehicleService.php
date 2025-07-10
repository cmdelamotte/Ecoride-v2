<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Vehicle;
use PDO;
use PDOException;

/**
 * Service VehicleService
 *
 * Gère la logique métier liée à la LECTURE des véhicules pour un utilisateur.
 * Sa seule responsabilité est de fournir des listes de véhicules.
 * Le CRUD est géré par VehicleManagementService.
 */
class VehicleService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Trouve tous les véhicules appartenant à un utilisateur donné.
     *
     * @param int $userId L'ID de l'utilisateur dont on veut récupérer les véhicules.
     * @return array Un tableau d'objets Vehicle.
     */
    public function findByUserId(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT v.*, b.name as brand_name FROM Vehicles v JOIN Brands b ON v.brand_id = b.id WHERE v.user_id = :user_id ORDER BY v.updated_at DESC");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $vehicles = [];
            foreach ($results as $data) {
                $vehicle = new Vehicle();
                $vehicle->setId($data['id'])
                        ->setUserId($data['user_id'])
                        ->setBrandId($data['brand_id'])
                        ->setModelName($data['model_name']) // Utilise model_name
                        ->setColor($data['color'])
                        ->setLicensePlate($data['license_plate']) // Utilise license_plate
                        ->setRegistrationDate($data['registration_date']) // Utilise registration_date
                        ->setPassengerCapacity($data['passenger_capacity']) // Utilise passenger_capacity
                        ->setIsElectric($data['is_electric'])
                        ->setEnergyType($data['energy_type'] ?? null) // energy_type peut être null
                        ->setCreatedAt($data['created_at'])
                        ->setUpdatedAt($data['updated_at']);
                
                $vehicle->setBrandName($data['brand_name']);

                $vehicles[] = $vehicle;
            }

            return $vehicles;
        } catch (PDOException $e) {
            error_log("Error in VehicleService::findByUserId: " . $e->getMessage());
            return [];
        }
    }
}