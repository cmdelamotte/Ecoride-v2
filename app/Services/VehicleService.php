<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Vehicle;
use App\Models\Brand; // Importer le modèle Brand

/**
 * Service VehicleService
 *
 * Gère la logique métier liée à la LECTURE des véhicules.
 * Ce service est maintenant responsable de construire des objets Vehicle complets,
 * y compris leurs relations (comme la marque).
 */
class VehicleService
{
    private Database $db;
    private BrandService $brandService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->brandService = new BrandService(); // Injection de dépendance manuelle
    }

    /**
     * Trouve un véhicule par son ID.
     * Note : cette méthode ne charge pas les relations.
     *
     * @param int $vehicleId L'ID du véhicule.
     * @return Vehicle|null L'objet Vehicle ou null s'il n'est pas trouvé.
     */
    public function findById(int $vehicleId): ?Vehicle
    {
        return $this->db->fetchOne(
            "SELECT * FROM Vehicles WHERE id = :id",
            ['id' => $vehicleId],
            Vehicle::class
        );
    }

    /**
     * Trouve un véhicule par son ID et y attache l'objet Brand associé.
     *
     * @param int $vehicleId L'ID du véhicule.
     * @return Vehicle|null L'objet Vehicle complet avec sa marque, ou null.
     */
    public function findWithBrandById(int $vehicleId): ?Vehicle
    {
        // Étape 1: Récupérer l'objet Vehicle de base.
        $vehicle = $this->findById($vehicleId);

        if ($vehicle && $vehicle->getBrandId()) {
            // Étape 2: Récupérer l'objet Brand associé via le BrandService.
            $brand = $this->brandService->findById($vehicle->getBrandId());
            if ($brand) {
                // Étape 3: Attacher l'objet Brand à l'objet Vehicle.
                $vehicle->setBrand($brand);
            }
        }

        return $vehicle;
    }

    /**
     * Trouve tous les véhicules appartenant à un utilisateur donné, avec leur marque.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @return array Un tableau d'objets Vehicle, chacun avec sa marque chargée.
     */
    public function findByUserId(int $userId): array
    {
        // D'abord, je récupère tous les objets Vehicle de base pour cet utilisateur.
        $vehicles = $this->db->fetchAll(
            "SELECT * FROM Vehicles WHERE user_id = :user_id ORDER BY updated_at DESC",
            ['user_id' => $userId],
            Vehicle::class
        );

        // Ensuite, pour chaque véhicule, je charge sa marque.
        // C'est un exemple de "N+1 query", qui est simple à écrire mais peut être inefficace
        // sur de très grandes listes. Pour ce projet, c'est une approche claire et acceptable.
        foreach ($vehicles as $vehicle) {
            if ($vehicle->getBrandId()) {
                $brand = $this->brandService->findById($vehicle->getBrandId());
                if ($brand) {
                    $vehicle->setBrand($brand);
                }
            }
        }

        return $vehicles;
    }
}
