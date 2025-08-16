<?php

namespace App\Repositories;

use App\Models\Ride;

interface RideRepositoryInterface
{
    /**
     * Retourne un trajet par son identifiant avec verrouillage FOR UPDATE.
     */
    public function findByIdForUpdate(int $rideId): ?Ride;

    /**
     * Met à jour le statut d'un trajet.
     */
    public function updateStatus(int $rideId, string $newStatus): bool;
}
