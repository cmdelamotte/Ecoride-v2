<?php

namespace App\Models;

/**
 * Modèle User (POPO - Plain Old PHP Object)
 *
 * Représente une entité utilisateur. Cette classe est un reflet exact de la table `users`.
 * Elle contient uniquement des propriétés privées pour encapsuler les données,
 * et des getters/setters publics pour y accéder et les modifier de manière contrôlée.
 */
class User
{
    private ?int $id = null;
    private ?string $username = null;
    private ?string $email = null;
    private ?string $password_hash = null;
    private ?string $first_name = null;
    private ?string $last_name = null;
    private ?string $address = null;
    private ?string $birth_date = null;
    private ?string $phone_number = null;
    private ?string $profile_picture_path = null;
    private ?string $system_role = null;
    private ?string $functional_role = null;
    private ?float $driver_rating = null; // Nouvelle propriété pour la note du conducteur
    private ?string $account_status = null;
    private ?float $credits = null;
    private ?bool $driver_pref_animals = null;
    private ?bool $driver_pref_smoker = null;
    private ?string $driver_pref_custom = null;
    private ?string $reset_token = null;
    private ?string $reset_token_expires_at = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    // Propriété pour la relation (avis de l'utilisateur)
    private array $reviews = [];

    // --- GETTERS ---

    public function getId(): ?int { return $this->id; }
    public function getUsername(): ?string { return $this->username; }
    public function getEmail(): ?string { return $this->email; }
    public function getPasswordHash(): ?string { return $this->password_hash; }
    public function getFirstName(): ?string { return $this->first_name; }
    public function getLastName(): ?string { return $this->last_name; }
    public function getAddress(): ?string { return $this->address; }
    public function getBirthDate(): ?string { return $this->birth_date; }
    public function getPhoneNumber(): ?string { return $this->phone_number; }
    public function getProfilePicturePath(): ?string { return $this->profile_picture_path; }
    public function getSystemRole(): ?string { return $this->system_role; }
    public function getFunctionalRole(): ?string { return $this->functional_role; }
    public function getDriverRating(): ?float { return $this->driver_rating; }
    public function getAccountStatus(): ?string { return $this->account_status; }
    public function getCredits(): ?float { return $this->credits; }
    public function getDriverPrefAnimals(): ?bool { return $this->driver_pref_animals; }
    public function getDriverPrefSmoker(): ?bool { return $this->driver_pref_smoker; }
    public function getDriverPrefCustom(): ?string { return $this->driver_pref_custom; }
    public function getResetToken(): ?string { return $this->reset_token; }
    public function getResetTokenExpiresAt(): ?string { return $this->reset_token_expires_at; }
    public function getCreatedAt(): ?string { return $this->created_at; }
    public function getUpdatedAt(): ?string { return $this->updated_at; }
    public function getReviews(): array { return $this->reviews; }

    // --- SETTERS ---

    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function setUsername(?string $username): self { $this->username = $username; return $this; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }
    public function setPasswordHash(?string $password_hash): self { $this->password_hash = $password_hash; return $this; }
    public function setFirstName(?string $first_name): self { $this->first_name = $first_name; return $this; }
    public function setLastName(?string $last_name): self { $this->last_name = $last_name; return $this; }
    public function setAddress(?string $address): self { $this->address = $address; return $this; }
    public function setBirthDate(?string $birth_date): self { $this->birth_date = $birth_date; return $this; }
    public function setPhoneNumber(?string $phone_number): self { $this->phone_number = $phone_number; return $this; }
    public function setProfilePicturePath(?string $profile_picture_path): self { $this->profile_picture_path = $profile_picture_path; return $this; }
    public function setSystemRole(?string $system_role): self { $this->system_role = $system_role; return $this; }
    public function setFunctionalRole(?string $functional_role): self { $this->functional_role = $functional_role; return $this; }
    public function setDriverRating(?float $driver_rating): self { $this->driver_rating = $driver_rating; return $this; }
    public function setAccountStatus(?string $account_status): self { $this->account_status = $account_status; return $this; }
    public function setCredits(?float $credits): self { $this->credits = $credits; return $this; }
    public function setDriverPrefAnimals(?bool $driver_pref_animals): self { $this->driver_pref_animals = $driver_pref_animals; return $this; }
    public function setDriverPrefSmoker(?bool $driver_pref_smoker): self { $this->driver_pref_smoker = $driver_pref_smoker; return $this; }
    public function setDriverPrefCustom(?string $driver_pref_custom): self { $this->driver_pref_custom = $driver_pref_custom; return $this; }
    public function setResetToken(?string $reset_token): self { $this->reset_token = $reset_token; return $this; }
    public function setResetTokenExpiresAt(?string $reset_token_expires_at): self { $this->reset_token_expires_at = $reset_token_expires_at; return $this; }
    public function setCreatedAt(?string $created_at): self { $this->created_at = $created_at; return $this; }
    public function setUpdatedAt(?string $updated_at): self { $this->updated_at = $updated_at; return $this; }
    public function setReviews(array $reviews): self { $this->reviews = $reviews; return $this; }
}