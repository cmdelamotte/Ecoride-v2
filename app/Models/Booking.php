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
    private ?int $user_id = null;
    private ?int $ride_id = null;
    private ?int $seats_booked = null;
    private ?string $booking_status = null;
    private ?string $booking_date = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // --- GETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function getRideId(): ?int
    {
        return $this->ride_id;
    }

    public function getSeatsBooked(): ?int
    {
        return $this->seats_booked;
    }

    public function getBookingStatus(): ?string
    {
        return $this->booking_status;
    }

    public function getBookingDate(): ?string
    {
        return $this->booking_date;
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

    public function setUserId(?int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function setRideId(?int $ride_id): self

    {
        $this->ride_id = $ride_id;
        return $this;
    }

    public function setSeatsBooked(?int $seats_booked): self
    {
        $this->seats_booked = $seats_booked;
        return $this;
    }

    public function setBookingStatus(?string $booking_status): self
    {
        $this->booking_status = $booking_status;
        return $this;
    }

    public function setBookingDate(?string $booking_date): self
    {
        $this->booking_date = $booking_date;
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
