<?php

namespace App\Models;

/**
 * Modèle Report (POPO)
 *
 * Représente un signalement fait par un utilisateur envers un autre.
 */
class Report
{
    private ?int $id = null;
    private ?int $reporter_id = null;
    private ?int $reported_driver_id = null;
    private ?int $ride_id = null;
    private ?string $reason = null;
    private ?string $report_status = null;
    private ?string $created_at = null;

    // --- GETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporterId(): ?int
    {
        return $this->reporter_id;
    }

    public function getReportedDriverId(): ?int
    {
        return $this->reported_driver_id;
    }

    public function getRideId(): ?int
    {
        return $this->ride_id;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getReportStatus(): ?string
    {
        return $this->report_status;
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

    public function setReporterId(?int $reporter_id): self
    {
        $this->reporter_id = $reporter_id;
        return $this;
    }

    public function setReportedDriverId(?int $reported_driver_id): self
    {
        $this->reported_driver_id = $reported_driver_id;
        return $this;
    }

    public function setRideId(?int $ride_id): self
    {
        $this->ride_id = $ride_id;
        return $this;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function setReportStatus(?string $report_status): self
    {
        $this->report_status = $report_status;
        return $this;
    }

    public function setCreatedAt(?string $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }
}
