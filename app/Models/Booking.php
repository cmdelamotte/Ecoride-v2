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
    private ?string $confirmation_token = null;
    private ?string $token_expires_at = null;
    private ?string $passenger_confirmed_at = null;
    private ?bool $credits_transferred_for_this_booking = null;
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

    public function getConfirmationToken(): ?string
    {
        return $this->confirmation_token;
    }

    public function getTokenExpiresAt(): ?string
    {
        return $this->token_expires_at;
    }

    public function getPassengerConfirmedAt(): ?string
    {
        return $this->passenger_confirmed_at;
    }

    public function getCreditsTransferredForThisBooking(): ?bool
    {
        return $this->credits_transferred_for_this_booking;
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

    public function setConfirmationToken(?string $confirmation_token): self
    {
        $this->confirmation_token = $confirmation_token;
        return $this;
    }

    public function setTokenExpiresAt(?string $token_expires_at): self
    {
        $this->token_expires_at = $token_expires_at;
        return $this;
    }

    public function setPassengerConfirmedAt(?string $passenger_confirmed_at): self
    {
        $this->passenger_confirmed_at = $passenger_confirmed_at;
        return $this;
    }

    public function setCreditsTransferredForThisBooking(?bool $credits_transferred_for_this_booking): self
    {
        $this->credits_transferred_for_this_booking = $credits_transferred_for_this_booking;
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
