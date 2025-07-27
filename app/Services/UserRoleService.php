<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Core\Logger;
use PDO;
use PDOException;

/**
 * Service UserRoleService
 *
 * Gère la logique métier spécifique à la mise à jour du rôle fonctionnel de l'utilisateur
 * et l'assignation des rôles système.
 * Ce service est responsable de la persistance des rôles en base de données.
 */
class UserRoleService
{
    private Database $db;
    private UserService $userService;

    public function __construct()
    {
        $this->db = Database::getInstance();
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
            Logger::error("Error in UserRoleService::updateFunctionalRole: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur interne du serveur lors de la mise à jour du rôle.', 'status' => 500];
        }
    }

    /**
     * J'assigne un rôle système à un utilisateur.
     * Cette méthode est utilisée pour lier un utilisateur à un rôle spécifique (ex: ROLE_EMPLOYEE, ROLE_ADMIN).
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param string $roleName Le nom du rôle à assigner (ex: 'ROLE_EMPLOYEE').
     * @return bool True si l'assignation a réussi, false sinon.
     */
    public function assignRoleToUser(int $userId, string $roleName): bool
    {
        // Je récupère l'ID du rôle à partir de son nom.
        $roleId = $this->getRoleIdByName($roleName);
        if (!$roleId) {
            Logger::error("Role not found: {$roleName}");
            return false;
        }

        // Je vérifie si l'utilisateur a déjà ce rôle pour éviter les doublons.
        if ($this->userHasRole($userId, $roleId)) {
            Logger::info("User {$userId} already has role {$roleName}.");
            return true; // Je considère que c'est un succès si le rôle est déjà assigné.
        }

        // J'insère l'association dans la table UserRoles.
        $sql = "INSERT INTO UserRoles (user_id, role_id) VALUES (:user_id, :role_id)";
        $params = [
            ':user_id' => $userId,
            ':role_id' => $roleId
        ];

        try {
            $this->db->execute($sql, $params);
            Logger::info("Role {$roleName} assigned to user {$userId}.");
            return true;
        } catch (PDOException $e) {
            Logger::error("Error assigning role {$roleName} to user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Je récupère l'ID d'un rôle à partir de son nom.
     *
     * @param string $roleName Le nom du rôle (ex: 'ROLE_ADMIN').
     * @return int|null L'ID du rôle ou null si non trouvé.
     */
    private function getRoleIdByName(string $roleName): ?int
    {
        $sql = "SELECT id FROM Roles WHERE name = :name";
        $params = [':name' => $roleName];
        $result = $this->db->fetchOne($sql, $params);
        return $result ? (int)$result['id'] : null;
    }

    /**
     * Je vérifie si un utilisateur possède déjà un rôle spécifique.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param int $roleId L'ID du rôle.
     * @return bool True si l'utilisateur possède le rôle, false sinon.
     */
    private function userHasRole(int $userId, int $roleId): bool
    {
        $sql = "SELECT COUNT(*) FROM UserRoles WHERE user_id = :user_id AND role_id = :role_id";
        $params = [':user_id' => $userId, ':role_id' => $roleId];
        return $this->db->fetchColumn($sql, $params) > 0;
    }
}
