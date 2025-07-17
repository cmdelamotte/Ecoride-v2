<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use PDO;
use PDOException;

/**
 * Service UserRoleService
 *
 * Gère la logique métier spécifique à la mise à jour du rôle fonctionnel de l'utilisateur.
 * Ce service est responsable de la persistance du rôle en base de données.
 */
class UserRoleService
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * Met à jour le rôle fonctionnel d'un utilisateur.
     *
     * @param User $user L'objet User dont le rôle doit être mis à jour.
     * @param string $newFunctionalRole Le nouveau rôle fonctionnel (ex: 'passenger', 'driver', 'passenger_driver').
     * @return array Résultat de l'opération avec le statut de succès et un message.
     */
    public function updateFunctionalRole(User $user, string $newFunctionalRole): array
    {
        try {
            // Je mets à jour la propriété functional_role de l'objet User.
            $user->setFunctionalRole($newFunctionalRole);

            // Je délègue la persistance de l'objet User mis à jour au UserService.
            $success = $this->userService->update($user);

            if ($success) {
                return ['success' => true, 'message' => 'Rôle mis à jour avec succès.', 'new_functional_role' => $newFunctionalRole];
            } else {
                return ['success' => false, 'error' => 'Erreur lors de la mise à jour du rôle.', 'status' => 500];
            }
        } catch (PDOException $e) {
            error_log("Error in UserRoleService::updateFunctionalRole: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur interne du serveur lors de la mise à jour du rôle.', 'status' => 500];
        }
    }
}
