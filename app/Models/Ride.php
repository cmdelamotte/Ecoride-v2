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
    private ?string $departure_city = null;
    private ?string $arrival_city = null;
    private ?string $departure_address = null;
    private ?string $arrival_address = null;
    private ?string $departure_time = null;
    private ?string $estimated_arrival_time = null;
    private ?float $price_per_seat = null;
    private ?int $seats_offered = null;
    private ?string $ride_status = null;
    private ?float $total_net_credits_earned = null;
    private ?string $driver_message = null;
    private ?bool $is_eco_ride = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    private ?int $seats_available = null; // Nombre de places disponibles (calculé)

    // Propriétés pour les relations (objets)
    private ?User $driver = null;
    private ?Vehicle $vehicle = null;
    private array $bookings = [];
    private array $reviews = [];

    // --- GETTERS ---

    public function getId(): ?int { return $this->id; }
    public function getDriverId(): ?int { return $this->driver_id; }
    public function getVehicleId(): ?int { return $this->vehicle_id; }
    public function getDepartureCity(): ?string { return $this->departure_city; }
    public function getArrivalCity(): ?string { return $this->arrival_city; }
    public function getDepartureAddress(): ?string { return $this->departure_address; }
    public function getArrivalAddress(): ?string { return $this->arrival_address; }
    public function getDepartureTime(): ?string { return $this->departure_time; }
    public function getEstimatedArrivalTime(): ?string { return $this->estimated_arrival_time; }
    public function getPricePerSeat(): ?float { return $this->price_per_seat; }
    public function getSeatsOffered(): ?int { return $this->seats_offered; }
    public function getRideStatus(): ?string { return $this->ride_status; }
    public function getTotalNetCreditsEarned(): ?float { return $this->total_net_credits_earned; }
    public function getDriverMessage(): ?string { return $this->driver_message; }
    public function isEcoRide(): ?bool { return $this->is_eco_ride; }
    public function getSeatsAvailable(): ?int { return $this->seats_available; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }

    // Getters pour les relations
    public function getDriver(): ?User { return $this->driver; }
    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function getBookings(): array { return $this->bookings; }
    public function getReviews(): array { return $this->reviews; }

    // --- SETTERS ---

    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function setDriverId(?int $driver_id): self { $this->driver_id = $driver_id; return $this; }
    public function setVehicleId(?int $vehicle_id): self { $this->vehicle_id = $vehicle_id; return $this; }
    public function setDepartureCity(?string $departure_city): self { $this->departure_city = $departure_city; return $this; }
    public function setArrivalCity(?string $arrival_city): self { $this->arrival_city = $arrival_city; return $this; }
    public function setDepartureAddress(?string $departure_address): self { $this->departure_address = $departure_address; return $this; }
    public function setArrivalAddress(?string $arrival_address): self { $this->arrival_address = $arrival_address; return $this; }
    public function setDepartureTime(?string $departure_time): self { $this->departure_time = $departure_time; return $this; }
    public function setEstimatedArrivalTime(?string $estimated_arrival_time): self { $this->estimated_arrival_time = $estimated_arrival_time; return $this; }
    public function setPricePerSeat(?float $price_per_seat): self { $this->price_per_seat = $price_per_seat; return $this; }
    public function setSeatsOffered(?int $seats_offered): self { $this->seats_offered = $seats_offered; return $this; }
    public function setRideStatus(?string $ride_status): self { $this->ride_status = $ride_status; return $this; }
    public function setTotalNetCreditsEarned(?float $total_net_credits_earned): self { $this->total_net_credits_earned = $total_net_credits_earned; return $this; }
    public function setDriverMessage(?string $driver_message): self { $this->driver_message = $driver_message; return $this; }
    public function setIsEcoRide(?bool $is_eco_ride): self { $this->is_eco_ride = $is_eco_ride; return $this; }
    public function setSeatsAvailable(?int $seats_available): self { $this->seats_available = $seats_available; return $this; }
    public function setCreatedAt(?string $created_at): self { $this->created_at = $created_at; return $this; }
    public function setUpdatedAt(?string $updated_at): self { $this->updated_at = $updated_at; return $this; }

    // Setters pour les relations
    public function setDriver(?User $driver): self { $this->driver = $driver; return $this; }
    public function setVehicle(?Vehicle $vehicle): self { $this->vehicle = $vehicle; return $this; }
    public function setBookings(array $bookings): self { $this->bookings = $bookings; return $this; }
    public function setReviews(array $reviews): self { $this->reviews = $reviews; return $this; }
}