<?php

namespace App\Repositories;

use App\Models\Booking;

interface BookingRepositoryInterface
{
    /**
     * Retourne une réservation confirmée pour un couple (rideId, userId).
     */
    public function findByRideAndUser(int $rideId, int $userId): ?Booking;

    /**
     * Retourne une réservation par son token de confirmation.
     */
    public function findByToken(string $token): ?Booking;

    /**
     * Compte les réservations confirmées pour un trajet.
     */
    public function countConfirmedByRideId(int $rideId): int;

    /**
     * Indique si un utilisateur a déjà réservé ce trajet.
     */
    public function existsByRideAndUser(int $rideId, int $userId): bool;

    /**
     * Insère une réservation et retourne l'identifiant créé.
     */
    public function insert(Booking $booking): int;

    /**
     * Met à jour le statut d'une réservation.
     */
    public function updateStatus(int $bookingId, string $newStatus): bool;

    /**
     * Récupère toutes les réservations confirmées pour un trajet avec verrouillage FOR UPDATE.
     */
    public function findConfirmedByRideIdForUpdate(int $rideId): array;

    /**
     * Récupère une réservation confirmée pour un couple (rideId, userId) avec verrouillage FOR UPDATE.
     */
    public function findByRideAndUserForUpdate(int $rideId, int $userId): ?Booking;
}


