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
    private ?string $model = null;
    private ?string $color = null;
    private ?string $registration_number = null;
    private ?int $year = null;
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

    public function getBrandId(): ?int
    {
        return $this->brand_id;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registration_number;
    }

    public function getYear(): ?int
    {
        return $this->year;
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

    public function setBrandId(?int $brand_id): self
    {
        $this->brand_id = $brand_id;
        return $this;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function setRegistrationNumber(?string $registration_number): self
    {
        $this->registration_number = $registration_number;
        return $this;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;
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
