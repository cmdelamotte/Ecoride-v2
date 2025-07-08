<?php

namespace App\Models;

/**
 * Modèle Brand (POPO)
 *
 * Représente une marque de véhicule.
 * C'est une structure de données simple, sans logique métier.
 */
class Brand
{
    private ?int $id = null;
    private ?string $name = null;

    // --- GETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    // --- SETTERS ---

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }
}
