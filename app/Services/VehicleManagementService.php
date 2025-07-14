<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Vehicle;
use App\Services\VehicleService; // Ajout de l'import pour VehicleService
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
    private VehicleService $vehicleService; // Ajout de la propriété pour VehicleService

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->vehicleService = new VehicleService(); // Initialisation de VehicleService
    }

    /**
     * Ajoute un nouveau véhicule en base de données.
     *
     * @param array $data Les données du véhicule.
     * @return int|false L'ID du véhicule nouvellement créé ou false en cas d'échec.
     */
    public function addVehicle(int $userId, array $data): array
    {
        error_log("addVehicle: Données reçues: " . print_r($data, true));
        error_log("addVehicle: User ID: " . $userId);

        $errors = \App\Services\ValidationService::validateVehicleData($data);

        if (!empty($errors)) {
            error_log("addVehicle: Erreurs de validation: " . print_r($errors, true));
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
                $newVehicle = $this->vehicleService->findById($vehicleId);
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
     * Met à jour un véhicule existant.
     *
     * @param int $vehicleId L'ID du véhicule à mettre à jour.
     * @param int $userId L'ID de l'utilisateur (pour la vérification des droits).
     * @param array $data Les nouvelles données du véhicule.
     * @return array Résultat de l'opération.
     */
    public function updateVehicle(int $vehicleId, int $userId, array $data): array
    {
        error_log("updateVehicle: Vehicle ID: " . $vehicleId);
        error_log("updateVehicle: User ID: " . $userId);
        error_log("updateVehicle: Données reçues: " . print_r($data, true));

        $errors = \App\Services\ValidationService::validateVehicleData($data);

        if (!empty($errors)) {
            error_log("updateVehicle: Erreurs de validation: " . print_r($errors, true));
            return ['success' => false, 'errors' => $errors, 'status' => 400];
        }

        try {
            // Vérifier que le véhicule appartient bien à l'utilisateur
            $vehicle = $this->vehicleService->findById($vehicleId);
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
                $updatedVehicle = $this->vehicleService->findById($vehicleId);
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
            $vehicle = $this->vehicleService->findById($vehicleId);
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

    
}