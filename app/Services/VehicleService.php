<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Vehicle;
use PDO;
use PDOException;

/**
 * Service VehicleService
 *
 * Gère toute la logique métier liée aux véhicules.
 * Centralise les interactions avec la base de données pour l'entité Vehicle.
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
            $stmt = $this->db->prepare("SELECT v.*, b.name as brand_name FROM vehicles v JOIN brands b ON v.brand_id = b.id WHERE v.user_id = :user_id ORDER BY v.updated_at DESC");
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

    /**
     * Crée un nouveau véhicule dans la base de données.
     *
     * @param array $data Un tableau associatif contenant les données du véhicule.
     * @return int|false L'ID du nouveau véhicule inséré si succès, false sinon.
     */
    public function create(array $data): int|false
    {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));

            $sql = "INSERT INTO vehicles ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);

            foreach ($data as $key => $value) {
                $paramType = match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_null($value) => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };
                $stmt->bindValue(':' . $key, $value, $paramType);
            }

            $stmt->execute();
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error in VehicleService::create: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouve un véhicule par son ID.
     *
     * @param int $id L'ID du véhicule à rechercher.
     * @return Vehicle|null Retourne une instance de Vehicle si trouvé, sinon null.
     */
    public function findById(int $id): ?Vehicle
    {
        try {
            $stmt = $this->db->prepare("SELECT v.*, b.name as brand_name FROM vehicles v JOIN brands b ON v.brand_id = b.id WHERE v.id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $vehicle = new Vehicle();
                $vehicle->setId($result['id'])
                        ->setUserId($result['user_id'])
                        ->setBrandId($result['brand_id'])
                        ->setModelName($result['model_name']) // Utilise model_name
                        ->setColor($result['color'])
                        ->setLicensePlate($result['license_plate']) // Utilise license_plate
                        ->setRegistrationDate($result['registration_date']) // Utilise registration_date
                        ->setPassengerCapacity($result['passenger_capacity']) // Utilise passenger_capacity
                        ->setIsElectric($result['is_electric'])
                        ->setEnergyType($result['energy_type'] ?? null) // energy_type peut être null
                        ->setCreatedAt($result['created_at'])
                        ->setUpdatedAt($result['updated_at']);
                
                $vehicle->setBrandName($result['brand_name']);

                return $vehicle;
            }
            return null;
        } catch (PDOException $e) {
            error_log("Error in VehicleService::findById: " . $e->getMessage());
            return null;
        }
    }
}