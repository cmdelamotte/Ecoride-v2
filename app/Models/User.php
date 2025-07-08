<?php

namespace App\Models;

/**
 * Modèle User (POPO - Plain Old PHP Object)
 *
 * Représente une entité utilisateur. Cette classe n'est qu'une structure de données.
 * Elle contient uniquement des propriétés privées pour encapsuler les données,
 * et des getters/setters publics pour y accéder et les modifier de manière contrôlée.
 *
 * Le but est de séparer clairement les données de la logique métier.
 * Cette classe ne doit JAMAIS contenir de logique de base de données (pas de requêtes SQL)
 * ou de logique métier complexe. C'est le rôle des Services.
 */
class User
{
    // J'utilise des propriétés privées pour respecter le principe d'encapsulation.
    // Les types `?` indiquent que la propriété peut être `null`, ce qui est utile
    // pour les entités qui ne sont pas encore persistées en base de données.
    private ?int $id = null;
    private ?string $username = null;
    private ?string $email = null;
    private ?string $password_hash = null;
    private ?string $first_name = null;
    private ?string $last_name = null;
    private ?string $profile_picture_path = null;
    private ?string $role = null;
    private ?string $status = null;
    private ?string $reset_token = null;
    private ?string $reset_token_expires_at = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // --- GETTERS ---
    // Les getters permettent un accès en lecture seule aux propriétés.

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPasswordHash(): ?string
    {
        return $this->password_hash;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function getProfilePicturePath(): ?string
    {
        return $this->profile_picture_path;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getResetToken(): ?string
    {
        return $this->reset_token;
    }

    public function getResetTokenExpiresAt(): ?string
    {
        return $this->reset_token_expires_at;
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
    // Les setters permettent de modifier les propriétés.
    // Le `return $this;` permet le "chaînage" des méthodes (fluent interface),
    // ce qui rend le code plus lisible et concis. Ex: $user->setEmail(...)->setUsername(...);

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPasswordHash(?string $password_hash): self
    {
        $this->password_hash = $password_hash;
        return $this;
    }

    public function setFirstName(?string $first_name): self
    {
        $this->first_name = $first_name;
        return $this;
    }

    public function setLastName(?string $last_name): self
    {
        $this->last_name = $last_name;
        return $this;
    }

    public function setProfilePicturePath(?string $profile_picture_path): self
    {
        $this->profile_picture_path = $profile_picture_path;
        return $this;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setResetToken(?string $reset_token): self
    {
        $this->reset_token = $reset_token;
        return $this;
    }

    public function setResetTokenExpiresAt(?string $reset_token_expires_at): self
    {
        $this->reset_token_expires_at = $reset_token_expires_at;
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