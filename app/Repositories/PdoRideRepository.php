<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Ride;

class PdoRideRepository implements RideRepositoryInterface
{
    private Database $db;

    public function __construct(?Database $database = null)
    {
        // Permettre l'injection d'une connexion custom (tests) tout en conservant un défaut sûr
        $this->db = $database ?? Database::getInstance();
    }

    public function findByIdForUpdate(int $rideId): ?Ride
    {
        return $this->db->fetchOne(
            "SELECT * FROM rides WHERE id = :id FOR UPDATE",
            ['id' => $rideId],
            Ride::class
        );
    }

    public function updateStatus(int $rideId, string $newStatus): bool
    {
        $sql = "UPDATE rides SET ride_status = :ride_status WHERE id = :id";
        $params = [
            ':ride_status' => $newStatus,
            ':id' => $rideId,
        ];
        return $this->db->execute($sql, $params) > 0;
    }
}
