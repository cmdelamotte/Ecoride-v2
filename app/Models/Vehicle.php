<?php

namespace App\Models;

/**
 * Modèle Vehicle (POPO)
 *
 * Représente un véhicule appartenant à un utilisateur.
 */
class Vehicle
{
    private ?int $id = null;
    private ?int $user_id = null;
    private ?int $brand_id = null;
    private ?string $brand_name = null; // Propriété pour le nom de la marque (jointure)
    private ?string $model_name = null; // Correspond à `model_name` en BDD
    private ?string $color = null;
    private ?string $license_plate = null; // Correspond à `license_plate` en BDD
    private ?string $registration_date = null; // Correspond à `registration_date` en BDD
    private ?int $passenger_capacity = null; // Correspond à `passenger_capacity` en BDD
    private ?bool $is_electric = null; // Correspond à `is_electric` en BDD
    private ?string $energy_type = null; // Correspond à `energy_type` en BDD
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // --- GETTERS ---

    public function getId(): ?int { return $this->id; }
    public function getUserId(): ?int { return $this->user_id; }
    public function getBrandId(): ?int { return $this->brand_id; }
    public function getBrandName(): ?string { return $this->brand_name; }
    public function getModelName(): ?string { return $this->model_name; }
    public function getColor(): ?string { return $this->color; }
    public function getLicensePlate(): ?string { return $this->license_plate; }
    public function getRegistrationDate(): ?string { return $this->registration_date; }
    public function getPassengerCapacity(): ?int { return $this->passenger_capacity; }
    public function getIsElectric(): ?bool { return $this->is_electric; }
    public function getEnergyType(): ?string { return $this->energy_type; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }

    // --- SETTERS ---

    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function setUserId(?int $user_id): self { $this->user_id = $user_id; return $this; }
    public function setBrandId(?int $brand_id): self { $this->brand_id = $brand_id; return $this; }
    public function setBrandName(?string $brand_name): self { $this->brand_name = $brand_name; return $this; }
    public function setModelName(?string $model_name): self { $this->model_name = $model_name; return $this; }
    public function setColor(?string $color): self { $this->color = $color; return $this; }
    public function setLicensePlate(?string $license_plate): self { $this->license_plate = $license_plate; return $this; }
    public function setRegistrationDate(?string $registration_date): self { $this->registration_date = $registration_date; return $this; }
    public function setPassengerCapacity(?int $passenger_capacity): self { $this->passenger_capacity = $passenger_capacity; return $this; }
    public function setIsElectric(?bool $is_electric): self { $this->is_electric = $is_electric; return $this; }
    public function setEnergyType(?string $energy_type): self { $this->energy_type = $energy_type; return $this; }
    public function setCreatedAt(?string $created_at): self { $this->created_at = $created_at; return $this; }
    public function setUpdatedAt(?string $updated_at): self { $this->updated_at = $updated_at; return $this; }
}