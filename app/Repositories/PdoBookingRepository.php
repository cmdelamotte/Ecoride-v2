<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Booking;

class PdoBookingRepository implements BookingRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function findByRideAndUser(int $rideId, int $userId): ?Booking
    {
        return $this->db->fetchOne(
            "SELECT * FROM bookings WHERE ride_id = :ride_id AND user_id = :user_id AND booking_status = 'confirmed'",
            ['ride_id' => $rideId, 'user_id' => $userId],
            Booking::class
        );
    }

    public function findByToken(string $token): ?Booking
    {
        return $this->db->fetchOne(
            "SELECT * FROM bookings WHERE confirmation_token = :token",
            ['token' => $token],
            Booking::class
        );
    }

    public function countConfirmedByRideId(int $rideId): int
    {
        return (int)($this->db->fetchColumn(
            "SELECT COUNT(*) FROM bookings WHERE ride_id = :ride_id AND booking_status = 'confirmed'",
            ['ride_id' => $rideId]
        ) ?? 0);
    }

    public function existsByRideAndUser(int $rideId, int $userId): bool
    {
        return (int)($this->db->fetchColumn(
            "SELECT COUNT(*) FROM bookings WHERE ride_id = :ride_id AND user_id = :user_id",
            ['ride_id' => $rideId, 'user_id' => $userId]
        ) ?? 0) > 0;
    }

    public function insert(Booking $booking): int
    {
        $rowCount = $this->db->execute(
            "INSERT INTO bookings (user_id, ride_id, seats_booked, booking_status) VALUES (:user_id, :ride_id, :seats_booked, :booking_status)",
            [
                'user_id' => $booking->getUserId(),
                'ride_id' => $booking->getRideId(),
                'seats_booked' => $booking->getSeatsBooked(),
                'booking_status' => $booking->getBookingStatus()
            ]
        );
        return $rowCount > 0 ? (int)$this->db->lastInsertId() : 0;
    }
}


