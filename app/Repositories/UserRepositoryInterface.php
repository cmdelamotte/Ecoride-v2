<?php

namespace App\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Retourne un utilisateur par son identifiant.
     */
    public function findById(int $id): ?User;

    /**
     * Retourne un utilisateur par email ou nom d'utilisateur.
     */
    public function findByEmailOrUsername(string $identifier): ?User;

    /**
     * Crée un utilisateur et retourne son identifiant.
     */
    public function create(User $user): int|false;

    /**
     * Met à jour un sous-ensemble de champs d'un utilisateur.
     */
    public function updateFields(int $userId, array $fields): bool;

    /**
     * Supprime un utilisateur par identifiant.
     */
    public function delete(int $id): bool;

    /**
     * Retourne les rôles applicatifs d'un utilisateur.
     */
    public function getUserRolesArray(int $userId): array;

    /**
     * Met à jour la note moyenne du conducteur.
     */
    public function updateDriverRating(int $driverId, float $newRating): bool;
}


