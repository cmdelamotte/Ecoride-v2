<?php

namespace App\Helpers;

use App\Models\Booking;

class BookingHelper
{
    /**
     * Formate un objet Booking en tableau associatif pour l'API ou les vues.
     *
     * @param Booking $booking L'objet Booking à formater.
     * @return array Le tableau associatif formaté.
     */
    public static function formatBookingForApi(Booking $booking): array
    {
        return [
            'id' => $booking->getId(),
            'ride_id' => $booking->getRideId(),
            'passenger_id' => $booking->getPassengerId(),
            'number_of_seats_booked' => $booking->getNumberOfSeatsBooked(),
            'booking_status' => $booking->getBookingStatus(),
            'created_at' => $booking->getCreatedAt(),
            'updated_at' => $booking->getUpdatedAt(),
        ];
    }

    /**
     * Formate une collection d'objets Booking en tableaux associatifs pour l'API ou les vues.
     *
     * @param array $bookings La collection d'objets Booking.
     * @return array Le tableau de tableaux associatifs formatés.
     */
    public static function formatCollectionForApi(array $bookings): array
    {
        $formattedBookings = [];
        foreach ($bookings as $booking) {
            $formattedBookings[] = self::formatBookingForApi($booking);
        }
        return $formattedBookings;
    }
}