<?php

namespace App\Models;

/**
 * Modèle Booking (POPO)
 *
 * Représente une réservation faite par un passager pour un trajet.
 */
class Booking
{
    private ?int $id = null;
    private ?int $ride_id = null;
    private ?int $passenger_id = null;
    private ?int $number_of_seats_booked = null;
    private ?string $booking_status = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // --- GETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRideId(): ?int
    {
        return $this->ride_id;
    }

    public function getPassengerId(): ?int
    {
        return $this->passenger_id;
    }

    public function getNumberOfSeatsBooked(): ?int
    {
        return $this->number_of_seats_booked;
    }

    public function getBookingStatus(): ?string
    {
        return $this->booking_status;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    // --- SETTERS ---

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setRideId(?int $ride_id): self
    {
        $this->ride_id = $ride_id;
        return $this;
    }

    public function setPassengerId(?int $passenger_id): self
    {
        $this->passenger_id = $passenger_id;
        return $this;
    }

    public function setNumberOfSeatsBooked(?int $number_of_seats_booked): self
    {
        $this->number_of_seats_booked = $number_of_seats_booked;
        return $this;
    }

    public function setBookingStatus(?string $booking_status): self
    {
        $this->booking_status = $booking_status;
        return $this;
    }

    public function setCreatedAt(?string $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function setUpdatedAt(?string $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }
}
