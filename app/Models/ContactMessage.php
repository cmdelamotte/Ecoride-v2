<?php

namespace App\Models;

/**
 * Modèle ContactMessage (POPO)
 *
 * Représente un message envoyé via le formulaire de contact.
 */
class ContactMessage
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $email = null;
    private ?string $subject = null;
    private ?string $message = null;
    private ?bool $is_read = false;
    private ?string $created_at = null;

    // --- GETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function isRead(): ?bool
    {
        return $this->is_read;
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

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function setIsRead(?bool $is_read): self
    {
        $this->is_read = $is_read;
        return $this;
    }

    public function setCreatedAt(?string $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }
}
