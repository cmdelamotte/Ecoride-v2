<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\PdoUserRepository;

/**
 * Service UserService
 *
 * Gère toute la logique métier liée aux utilisateurs.
 * Centralise les interactions avec la base de données pour l'entité User.
 * Ce service est responsable de la création, lecture, mise à jour et suppression (CRUD)
 * des utilisateurs, en utilisant la couche d'abstraction de la base de données.
 */
class UserService
{
    private UserRepositoryInterface $userRepository;

    /**
     * Le constructeur initialise l'instance de la base de données.
     */
    public function __construct(?UserRepositoryInterface $userRepository = null)
    {
        // Injection souple: par défaut, utiliser l'implémentation PDO
        $this->userRepository = $userRepository ?? new PdoUserRepository(Database::getInstance());
    }

    /**
     * Trouve un utilisateur par son ID.
     *
     * @param int $id L'ID de l'utilisateur à rechercher.
     * @return User|null Retourne une instance de User si trouvé, sinon null.
     */
    public function findById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Trouve un utilisateur par son email ou son nom d'utilisateur.
     *
     * @param string $identifier L'email ou le nom d'utilisateur.
     * @return User|null Retourne une instance de User si trouvé, sinon null.
     */
    public function findByEmailOrUsername(string $identifier): ?User
    {
        return $this->userRepository->findByEmailOrUsername($identifier);
    }

    /**
     * Crée un nouvel utilisateur en base de données.
     *
     * @param array $data Les données pour le nouvel utilisateur.
     * @return int|false L'ID du nouvel utilisateur ou false en cas d'échec.
     */
    public function create(User $user): int|false
    {
        return $this->userRepository->create($user);
    }

    /**
     * Met à jour un utilisateur existant.
     *
     * @param int $id L'ID de l'utilisateur à mettre à jour.
     * @param array $data Les données à mettre à jour.
     * @return bool True si la mise à jour a réussi, sinon false.
     */
    public function update(User $user): bool
    {
        // Conserver la logique de comparaison locale, mais déléguer la persistance au repository
        $existingUser = $this->findById($user->getId());
        if (!$existingUser) {
            return false;
        }
        $updateData = [];
        $reflectionClass = new \ReflectionClass($user);
        $properties = $reflectionClass->getProperties(\ReflectionProperty::IS_PRIVATE);
        foreach ($properties as $property) {
            $propertyName = $property->getName();
            if (in_array($propertyName, ['id', 'created_at', 'updated_at', 'system_role'])) {
                continue;
            }
            $property->setAccessible(true);
            $newValue = $property->getValue($user);
            $oldValue = $property->getValue($existingUser);
            if ($newValue !== $oldValue) {
                if (is_bool($newValue)) {
                    $updateData[$propertyName] = (int)$newValue;
                } elseif (is_string($newValue) && $newValue === '') {
                    $nullableStringColumns = ['address', 'profile_picture_path', 'driver_pref_custom', 'reset_token', 'reset_token_expires_at'];
                    $updateData[$propertyName] = in_array($propertyName, $nullableStringColumns) ? null : $newValue;
                } else {
                    $updateData[$propertyName] = $newValue;
                }
            }
        }
        if (empty($updateData)) {
            return false;
        }
        return $this->userRepository->updateFields($user->getId(), $updateData);
    }

    

    /**
     * Supprime un utilisateur.
     *
     * @param int $id L'ID de l'utilisateur à supprimer.
     * @return bool True si la suppression a réussi, sinon false.
     */
    public function delete(int $id): bool
    {
        return $this->userRepository->delete($id);
    }

    /**
     * Récupère tous les rôles d'un utilisateur à partir des tables UserRoles et Roles.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @return array Un tableau de noms de rôles (ex: ['ROLE_USER', 'ROLE_EMPLOYEE']).
     */
    public function getUserRolesArray(int $userId): array
    {
        return $this->userRepository->getUserRolesArray($userId);
    }

    /**
     * Met à jour la note moyenne d'un conducteur.
     *
     * @param int $driverId L'ID du conducteur.
     * @param float $newRating La nouvelle note moyenne.
     * @return bool True si la mise à jour a réussi, sinon false.
     */
    public function updateDriverRating(int $driverId, float $newRating): bool
    {
        return $this->userRepository->updateDriverRating($driverId, $newRating);
    }
}
