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
    private Database $db;
    private VehicleService $vehicleService; // Ajout de la propriété pour VehicleService

    public function __construct()
    {
        $this->db = Database::getInstance(); // Utilise notre classe Database
        $this->vehicleService = new VehicleService(); // Initialisation de VehicleService
    }

    /**
     * Ajoute un nouveau véhicule en base de données.
     *
     * @param array $data Les données du véhicule.
     * @return int|false L'ID du véhicule nouvellement créé ou false en cas d'échec.
     */
    public function addVehicle(Vehicle $vehicle, int $userId): array
    {
        // La validation des données brutes devrait idéalement être faite avant d'appeler ce service,
        // par exemple dans le contrôleur ou un service de validation dédié.
        // Ici, je m'attends à un objet Vehicle déjà partiellement peuplé.

        try {
            // Je m'assure que l'ID utilisateur est bien défini sur l'objet Vehicle.
            $vehicle->setUserId($userId);

            // Je construis le tableau de données à partir des propriétés de l'objet Vehicle.
            // Cela garantit que seules les données de l'objet sont utilisées pour l'insertion.
            $data = [
                'user_id' => $vehicle->getUserId(),
                'brand_id' => $vehicle->getBrandId(),
                'model_name' => $vehicle->getModelName(),
                'color' => $vehicle->getColor(),
                'license_plate' => $vehicle->getLicensePlate(),
                'registration_date' => $vehicle->getRegistrationDate(),
                'passenger_capacity' => $vehicle->getPassengerCapacity(),
                'is_electric' => (int)$vehicle->getIsElectric(),
                'energy_type' => $vehicle->getEnergyType(),
            ];

            // Je filtre les valeurs nulles pour ne pas les inclure dans la requête INSERT.
            $insertData = array_filter($data, function($value) {
                return !is_null($value); // Garde les valeurs non nulles
            });

            $columns = implode(', ', array_keys($insertData));
            $placeholders = ':' . implode(', :', array_keys($insertData));

            $sql = "INSERT INTO vehicles ($columns) VALUES ($placeholders)";
            
            $rowCount = $this->db->execute($sql, $insertData);

            if ($rowCount > 0) {
                $vehicleId = (int)$this->db->lastInsertId();
                $vehicle->setId($vehicleId); // Met à jour l'ID de l'objet Vehicle
                
                // Je récupère l'objet Vehicle complet avec sa marque pour le retour.
                $newVehicle = $this->vehicleService->findWithBrandById($vehicleId);

                return ['success' => true, 'message' => 'Véhicule ajouté avec succès.', 'vehicle' => $newVehicle, 'status' => 201];
            } else {
                return ['success' => false, 'error' => "Erreur lors de l'ajout du véhicule.", 'errors' => [], 'status' => 500];
            }
        } catch (\PDOException $e) {
            
            // Vérifier si l'erreur est due à une contrainte d'unicité (SQLSTATE 23000)
            if ($e->getCode() === '23000') {
                return ['success' => false, 'errors' => ['license_plate' => 'Cette plaque d\'immatriculation est déjà enregistrée.'], 'status' => 409];
            } else {
                return ['success' => false, 'error' => "Erreur interne du serveur lors de l'ajout du véhicule.", 'status' => 500];
            }
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
    public function updateVehicle(Vehicle $vehicle, int $userId): array
    {
        try {
            // Vérifier que le véhicule appartient bien à l'utilisateur
            // Je récupère le véhicule existant pour comparaison et pour m'assurer de son appartenance.
            $existingVehicle = $this->vehicleService->findById($vehicle->getId());
            if (!$existingVehicle || $existingVehicle->getUserId() !== $userId) {
                return ['success' => false, 'error' => 'Véhicule non trouvé ou non autorisé.', 'status' => 404];
            }

            // Je construis le tableau de données à partir des propriétés de l'objet Vehicle.
            // Cela garantit que seules les données de l'objet sont utilisées pour la mise à jour.
            $data = [
                'brand_id' => $vehicle->getBrandId(),
                'model_name' => $vehicle->getModelName(),
                'color' => $vehicle->getColor(),
                'license_plate' => $vehicle->getLicensePlate(),
                'registration_date' => $vehicle->getRegistrationDate(),
                'passenger_capacity' => $vehicle->getPassengerCapacity(),
                'is_electric' => (int)$vehicle->getIsElectric(), // Caster en int pour la BDD
                'energy_type' => $vehicle->getEnergyType(),
            ];

            // Je filtre les valeurs nulles ou vides pour ne pas les inclure dans la requête UPDATE.
            $updateData = array_filter($data, function($value) {
                return !is_null($value) && $value !== ''; // Garde les valeurs non nulles et non vides
            });

            if (empty($updateData)) {
                return ['success' => true, 'message' => 'Aucune modification à appliquer.', 'vehicle' => $vehicle, 'status' => 200];
            }

            $columns = implode(', ', array_keys($updateData));
            $placeholders = ':' . implode(', :', array_keys($updateData));

            $setParts = [];
            foreach (array_keys($updateData) as $key) {
                $setParts[] = "{$key} = :{$key}";
            }
            $setClause = implode(', ', $setParts);

            $sql = "UPDATE vehicles SET {$setClause} WHERE id = :id";
            $updateData['id'] = $vehicle->getId(); // J'ajoute l'id du véhicule à mettre à jour.

            $rowCount = $this->db->execute($sql, $updateData);

            if ($rowCount > 0) {
                // Je récupère l'objet Vehicle complet avec sa marque pour le retour.
                $updatedVehicle = $this->vehicleService->findWithBrandById($vehicle->getId());
                return ['success' => true, 'message' => 'Véhicule mis à jour avec succès.', 'vehicle' => $updatedVehicle, 'status' => 200];
            } else {
                return ['success' => false, 'error' => 'Erreur lors de la mise à jour du véhicule.', 'status' => 500];
            }
        } catch (\PDOException $e) {
            
            // Vérifier si l'erreur est due à une contrainte d'unicité (SQLSTATE 23000)
            if ($e->getCode() === '23000') {
                return ['success' => false, 'errors' => ['license_plate' => 'Cette plaque d\'immatriculation est déjà enregistrée.'], 'status' => 409];
            } else {
                return ['success' => false, 'error' => 'Erreur interne du serveur lors de la mise à jour du véhicule.', 'status' => 500];
            }
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

            $rowCount = $this->db->execute("DELETE FROM vehicles WHERE id = :id", ['id' => $vehicleId]);

            if ($rowCount > 0) {
                return ['success' => true, 'message' => 'Véhicule supprimé avec succès.', 'status' => 200];
            } else {
                return ['success' => false, 'error' => 'Erreur lors de la suppression du véhicule.', 'status' => 500];
            }
        } catch (\PDOException $e) {
            // Log l'erreur complète pour le débogage côté serveur
            

            // Vérifier si l'erreur est due à une contrainte de clé étrangère (SQLSTATE 23000)
            if ($e->getCode() === '23000') {
                return ['success' => false, 'error' => 'Impossible de supprimer ce véhicule car il est associé à un ou plusieurs trajets.', 'status' => 409]; // 409 Conflict
            } else {
                // Pour les autres types d'erreurs PDO, renvoyer une erreur interne générique
                return ['success' => false, 'error' => 'Erreur interne du serveur lors de la suppression du véhicule.', 'status' => 500];
            }
        }
    }

    
}