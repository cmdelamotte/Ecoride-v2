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
    public function addVehicle(int $userId, array $data): array
    {
        $errors = [];

        // Validation des données
        if (empty($data['brand_id'])) $errors['brand_id'] = 'La marque est requise.';
        if (empty($data['model'])) $errors['model'] = 'Le modèle est requis.';
        if (empty($data['license_plate'])) $errors['license_plate'] = "La plaque d'immatriculation est requise.";
        if (!isset($data['passenger_capacity']) || !filter_var($data['passenger_capacity'], FILTER_VALIDATE_INT) || $data['passenger_capacity'] < 1 || $data['passenger_capacity'] > 8) {
            $errors['passenger_capacity'] = 'Le nombre de places est invalide (entre 1 et 8).';
        }

        // Validation de la date d'immatriculation
        if (!empty($data['registration_date'])) {
            try {
                $registrationDate = new \DateTime($data['registration_date']);
                if ($registrationDate > new \DateTime()) {
                    $errors['registration_date'] = 'La date d\'immatriculation ne peut pas être dans le futur.';
                }
            } catch (\Exception $e) {
                $errors['registration_date'] = 'Le format de la date d\'immatriculation est invalide.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'status' => 400];
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO Vehicles (user_id, brand_id, model_name, color, license_plate, registration_date, passenger_capacity, is_electric, energy_type)
                 VALUES (:user_id, :brand_id, :model_name, :color, :license_plate, :registration_date, :passenger_capacity, :is_electric, :energy_type)"
            );

            $success = $stmt->execute([
                ':user_id' => $userId,
                ':brand_id' => $data['brand_id'],
                ':model_name' => htmlspecialchars(trim($data['model'])),
                ':color' => htmlspecialchars(trim($data['color'] ?? '')),
                ':license_plate' => htmlspecialchars(trim($data['license_plate'])),
                ':registration_date' => htmlspecialchars(trim($data['registration_date'] ?? '')),
                ':passenger_capacity' => $data['passenger_capacity'],
                ':is_electric' => (int)($data['is_electric'] ?? false),
                ':energy_type' => htmlspecialchars(trim($data['energy_type'] ?? '')),
            ]);

            if ($success) {
                $vehicleId = (int)$this->db->lastInsertId();
                $newVehicle = $this->findById($vehicleId);
                return ['success' => true, 'message' => 'Véhicule ajouté avec succès.', 'vehicle' => $newVehicle, 'status' => 201];
            } else {
                return ['success' => false, 'error' => "Erreur lors de l'ajout du véhicule.", 'status' => 500];
            }
        } catch (\PDOException $e) {
            error_log("VehicleManagementService::addVehicle Error: " . $e->getMessage());
            return ['success' => false, 'error' => "Erreur interne du serveur lors de l'ajout du véhicule.", 'status' => 500];
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
        } catch (\PDOException $e) {
            error_log("VehicleManagementService::findById Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Met à jour un véhicule existant.
     *
     * @param int $vehicleId L'ID du véhicule à mettre à jour.
     * @param int $userId L'ID de l'utilisateur (pour la vérification des droits).
     * @param array $data Les nouvelles données du véhicule.
     * @return array Résultat de l'opération.
     */
    public function updateVehicle(int $vehicleId, int $userId, array $data): array
    {
        $errors = [];

        // Validation (similaire à l'ajout)
        if (empty($data['brand_id'])) $errors['brand_id'] = 'La marque est requise.';
        if (empty($data['model'])) $errors['model'] = 'Le modèle est requis.';
        if (empty($data['license_plate'])) $errors['license_plate'] = "La plaque d'immatriculation est requise.";
        if (!isset($data['passenger_capacity']) || !filter_var($data['passenger_capacity'], FILTER_VALIDATE_INT) || $data['passenger_capacity'] < 1 || $data['passenger_capacity'] > 8) {
            $errors['passenger_capacity'] = 'Le nombre de places est invalide (entre 1 et 8).';
        }

        // Validation de la date d'immatriculation
        if (!empty($data['registration_date'])) {
            try {
                $registrationDate = new \DateTime($data['registration_date']);
                if ($registrationDate > new \DateTime()) {
                    $errors['registration_date'] = 'La date d\'immatriculation ne peut pas être dans le futur.';
                }
            } catch (\Exception $e) {
                $errors['registration_date'] = 'Le format de la date d\'immatriculation est invalide.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'status' => 400];
        }

        try {
            // Vérifier que le véhicule appartient bien à l'utilisateur
            $vehicle = $this->findById($vehicleId);
            if (!$vehicle || $vehicle->getUserId() !== $userId) {
                return ['success' => false, 'error' => 'Véhicule non trouvé ou non autorisé.', 'status' => 404];
            }

            $stmt = $this->db->prepare(
                "UPDATE Vehicles SET 
                    brand_id = :brand_id, 
                    model_name = :model_name, 
                    color = :color, 
                    license_plate = :license_plate, 
                    registration_date = :registration_date, 
                    passenger_capacity = :passenger_capacity, 
                    is_electric = :is_electric, 
                    energy_type = :energy_type
                 WHERE id = :id"
            );

            $success = $stmt->execute([
                ':brand_id' => $data['brand_id'],
                ':model_name' => htmlspecialchars(trim($data['model'])),
                ':color' => htmlspecialchars(trim($data['color'] ?? '')),
                ':license_plate' => htmlspecialchars(trim($data['license_plate'])),
                ':registration_date' => htmlspecialchars(trim($data['registration_date'] ?? '')),
                ':passenger_capacity' => $data['passenger_capacity'],
                ':is_electric' => (int)($data['is_electric'] ?? false),
                ':energy_type' => htmlspecialchars(trim($data['energy_type'] ?? '')),
                ':id' => $vehicleId
            ]);

            if ($success) {
                $updatedVehicle = $this->findById($vehicleId);
                return ['success' => true, 'message' => 'Véhicule mis à jour avec succès.', 'vehicle' => $updatedVehicle, 'status' => 200];
            } else {
                return ['success' => false, 'error' => 'Erreur lors de la mise à jour du véhicule.', 'status' => 500];
            }
        } catch (\PDOException $e) {
            error_log("VehicleManagementService::updateVehicle Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur interne du serveur.', 'status' => 500];
        }
    }

    /**
     * Supprime un véhicule.
     *
     * @param int $vehicleId L'ID du véhicule à supprimer.
     * @param int $userId L'ID de l'utilisateur effectuant l'action (pour vérification).
     * @return array Résultat de l'opération.
     */
    public function deleteVehicle(int $vehicleId, int $userId): array
    {
        try {
            // D'abord, je vérifie que le véhicule appartient bien à l'utilisateur connecté.
            $vehicle = $this->findById($vehicleId);
            if (!$vehicle || $vehicle->getUserId() !== $userId) {
                return ['success' => false, 'error' => 'Véhicule non trouvé ou non autorisé.', 'status' => 404];
            }

            $stmt = $this->db->prepare("DELETE FROM Vehicles WHERE id = :id");
            $success = $stmt->execute(['id' => $vehicleId]);

            if ($success) {
                return ['success' => true, 'message' => 'Véhicule supprimé avec succès.', 'status' => 200];
            } else {
                return ['success' => false, 'error' => 'Erreur lors de la suppression du véhicule.', 'status' => 500];
            }
        } catch (\PDOException $e) {
            error_log("VehicleManagementService::deleteVehicle Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur interne du serveur.', 'status' => 500];
        }
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