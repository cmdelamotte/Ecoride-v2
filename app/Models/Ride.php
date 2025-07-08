<?php

namespace App\Models;

/**
 * Modèle Ride (POPO)
 *
 * Représente un trajet proposé par un conducteur.
 */
class Ride
{
    private ?int $id = null;
    private ?int $driver_id = null;
    private ?int $vehicle_id = null;
    private ?string $departure_location = null;
    private ?string $arrival_location = null;
    private ?string $departure_time = null;
    private ?string $arrival_time = null;
    private ?int $available_seats = null;
    private ?float $price_per_seat = null;
    private ?string $ride_status = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // --- GETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDriverId(): ?int
    {
        return $this->driver_id;
    }

    public function getVehicleId(): ?int
    {
        return $this->vehicle_id;
    }

    public function getDepartureLocation(): ?string
    {
        return $this->departure_location;
    }

    public function getArrivalLocation(): ?string
    {
        return $this->arrival_location;
    }

    public function getDepartureTime(): ?string
    {
        return $this->departure_time;
    }

    public function getArrivalTime(): ?string
    {
        return $this->arrival_time;
    }

    public function getAvailableSeats(): ?int
    {
        return $this->available_seats;
    }

    public function getPricePerSeat(): ?float
    {
        return $this->price_per_seat;
    }

    public function getRideStatus(): ?string
    {
        return $this->ride_status;
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

    public function setDriverId(?int $driver_id): self
    {
        $this->driver_id = $driver_id;
        return $this;
    }

    public function setVehicleId(?int $vehicle_id): self
    {
        $this->vehicle_id = $vehicle_id;
        return $this;
    }

    public function setDepartureLocation(?string $departure_location): self
    {
        $this->departure_location = $departure_location;
        return $this;
    }

    public function setArrivalLocation(?string $arrival_location): self
    {
        $this->arrival_location = $arrival_location;
        return $this;
    }

    public function setDepartureTime(?string $departure_time): self
    {
        $this->departure_time = $departure_time;
        return $this;
    }

    public function setArrivalTime(?string $arrival_time): self
    {
        $this->arrival_time = $arrival_time;
        return $this;
    }

    public function setAvailableSeats(?int $available_seats): self
    {
        $this->available_seats = $available_seats;
        return $this;
    }

    public function setPricePerSeat(?float $price_per_seat): self
    {
        $this->price_per_seat = $price_per_seat;
        return $this;
    }

    public function setRideStatus(?string $ride_status): self
    {
        $this->ride_status = $ride_status;
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
