<?php

namespace App\Services;

use App\Core\Database;
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
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Met à jour le rôle fonctionnel d'un utilisateur dans la base de données.
     *
     * @param int $userId L'ID de l'utilisateur dont le rôle doit être mis à jour.
     * @param string $newFunctionalRole Le nouveau rôle fonctionnel (ex: 'passenger', 'driver', 'passenger_driver').
     * @return bool True si la mise à jour a réussi, false sinon.
     */
    public function updateFunctionalRole(int $userId, string $newFunctionalRole): array
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET functional_role = :functional_role WHERE id = :user_id");
            $stmt->bindParam(':functional_role', $newFunctionalRole, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $success = $stmt->execute();

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
