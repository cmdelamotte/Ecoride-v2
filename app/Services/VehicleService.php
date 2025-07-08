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

            // Je récupère les résultats sous forme de tableaux associatifs pour pouvoir
            // ajouter la `brand_name` avant de les mapper en objets Vehicle.
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $vehicles = [];
            foreach ($results as $data) {
                $vehicle = new Vehicle();
                // Je peuple l'objet Vehicle avec les données de la base.
                // J'utilise les setters pour chaque propriété.
                $vehicle->setId($data['id'])
                        ->setUserId($data['user_id'])
                        ->setBrandId($data['brand_id'])
                        ->setModel($data['model'])
                        ->setColor($data['color'])
                        ->setRegistrationNumber($data['registration_number'])
                        ->setYear($data['year'])
                        ->setCreatedAt($data['created_at'])
                        ->setUpdatedAt($data['updated_at']);
                
                // J'ajoute la propriété `brand_name` dynamiquement à l'objet Vehicle
                // car elle n'est pas une propriété native du modèle Vehicle.
                // Cela permet au JS de l'utiliser directement.
                $vehicle->brand_name = $data['brand_name'];

                $vehicles[] = $vehicle;
            }

            return $vehicles;
        } catch (PDOException $e) {
            error_log("Error in VehicleService::findByUserId: " . $e->getMessage());
            return [];
        }
    }
}
