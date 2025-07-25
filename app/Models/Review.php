<?php

namespace App\Models;

/**
 * Modèle Review (POPO)
 *
 * Représente un avis laissé par un utilisateur sur un autre utilisateur
 * après un trajet.
 */
class Review
{
    private ?int $id = null;
    private ?int $ride_id = null;
    private ?int $author_id = null;
    private ?int $driver_id = null;
    private ?int $rating = null;
    private ?string $comment = null;
    private ?string $review_status = null;
    private ?string $created_at = null;

    // --- GETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRideId(): ?int
    {
        return $this->ride_id;
    }

    public function getAuthorId(): ?int
    {
        return $this->author_id;
    }

    public function getDriverId(): ?int
    {
        return $this->driver_id;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getReviewStatus(): ?string
    {
        return $this->review_status;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
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

    public function setAuthorId(?int $author_id): self
    {
        $this->author_id = $author_id;
        return $this;
    }

    public function setDriverId(?int $driver_id): self
    {
        $this->driver_id = $driver_id;
        return $this;
    }

    public function setRating(?int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function setReviewStatus(?string $review_status): self
    {
        $this->review_status = $review_status;
        return $this;
    }

    public function setCreatedAt(?string $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }
}
