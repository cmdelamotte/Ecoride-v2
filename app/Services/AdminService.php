<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Services\UserService;
use App\Services\UserRoleService;
use App\Services\MongoLogService; // J'ajoute la dépendance
use Exception;

/**
 * Service gérant la logique métier de la section administration.
 * Je centralise ici les opérations complexes et les interactions entre les différents services
 * pour les fonctionnalités spécifiques à l'administrateur, respectant ainsi le principe de SoC.
 */
class AdminService
{
    private Database $db;
    private UserService $userService;
    private UserRoleService $userRoleService;
    private MongoLogService $mongoLogService; // J'ajoute la propriété

    /**
     * J'injecte les dépendances nécessaires via le constructeur.
     * Cela me permet de garder le service découplé et plus facile à tester.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->userService = new UserService();
        $this->userRoleService = new UserRoleService();
        $this->mongoLogService = new MongoLogService(); // J'initialise le service
    }

    /**
     * Je crée un nouvel employé.
     *
     * @param array $data Les données de l'employé (nom, prénom, email, mot de passe).
     * @return User L'objet User de l'employé créé.
     * @throws Exception Si la création échoue.
     */
    public function createEmployee(array $data): User
    {
        // TODO: Implémenter la logique de validation et de création.
        // 1. Valider les données.
        // 2. Appeler UserService pour créer l'utilisateur.
        // 3. Appeler UserRoleService pour assigner le rôle 'ROLE_EMPLOYEE'.
        return new User(); // Placeholder
    }

    /**
     * Je mets à jour le statut d'un compte utilisateur (active/suspended).
     * C'est une action purement administrative, donc sa logique est ici.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param string $status Le nouveau statut ('active' ou 'suspended').
     * @return bool True si la mise à jour a réussi, false sinon.
     */
    public function updateUserAccountStatus(int $userId, string $status): bool
    {
        // Je m'assure que le statut est valide pour des raisons de sécurité.
        if (!in_array($status, ['active', 'suspended'])) {
            return false;
        }

        $sql = "UPDATE users SET account_status = :status WHERE id = :id";
        $params = [
            'status' => $status,
            'id' => $userId
        ];

        // J'utilise la méthode execute de ma classe Database pour effectuer la mise à jour.
        // Elle retourne le nombre de lignes affectées, donc > 0 signifie que la mise à jour a réussi.
        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Je récupère les statistiques sur le nombre de trajets par jour depuis MongoDB.
     *
     * @return array Les données pour le graphique.
     */
    public function getRideStatisticsByDay(): array
    {
        return $this->mongoLogService->getCompletedRidesByDay();
    }

    /**
     * Je récupère les statistiques sur les gains de la plateforme par jour depuis MongoDB.
     *
     * @return array Les données pour le graphique.
     */
    public function getPlatformCreditEarningsByDay(): array
    {
        return $this->mongoLogService->getCommissionsByDay();
    }

    /**
     * Je calcule le total des crédits gagnés par la plateforme depuis MongoDB.
     *
     * @return float Le montant total des crédits.
     */
    public function getTotalPlatformCreditsEarned(): float
    {
        return $this->mongoLogService->getTotalCommissions();
    }

    /**
     * Je récupère la liste de tous les utilisateurs (sauf les administrateurs).
     *
     * @return array La liste des utilisateurs.
     */
    public function getAllUsers(): array
    {
        // Je sélectionne tous les utilisateurs qui n'ont pas le rôle 'ROLE_ADMIN'.
        // C'est une mesure de sécurité pour éviter qu'un admin ne se bloque lui-même.
        $sql = "SELECT u.* FROM users u JOIN UserRoles ur ON u.id = ur.user_id JOIN Roles r ON ur.role_id = r.id WHERE r.name != 'ROLE_ADMIN' ORDER BY u.created_at DESC";
        return $this->db->fetchAll($sql, [], User::class);
    }

    /**
     * Je récupère la liste de tous les employés.
     *
     * @return array La liste des employés.
     */
    public function getAllEmployees(): array
    {
        // Je sélectionne les utilisateurs ayant spécifiquement le rôle 'ROLE_EMPLOYEE'.
        $sql = "SELECT u.* FROM users u JOIN UserRoles ur ON u.id = ur.user_id JOIN Roles r ON ur.role_id = r.id WHERE r.name = 'ROLE_EMPLOYEE' ORDER BY u.created_at DESC";
        return $this->db->fetchAll($sql, [], User::class);
    }
}