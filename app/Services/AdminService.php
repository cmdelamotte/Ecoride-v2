<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Services\UserService;
use App\Services\UserRoleService;
use App\Services\MongoLogService;
use App\Core\Logger;
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
     * La méthode orchestre la validation, la création de l'utilisateur via UserService,
     * et l'assignation du rôle via UserRoleService.
     *
     * @param array $data Les données de l'employé (first_name, last_name, email, password).
     * @return User L'objet User de l'employé créé.
     * @throws Exception Si une validation échoue ou si la création en base de données échoue.
     */
    public function createEmployee(array $data): User
    {
        // 1. Je valide les données d'entrée.
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['password'])) {
            throw new Exception("Tous les champs sont requis pour créer un employé.");
        }

        // Je vérifie si un utilisateur avec cet email n'existe pas déjà.
        if ($this->userService->findByEmailOrUsername($data['email'])) {
            throw new Exception("Un utilisateur avec cet email existe déjà.");
        }

        // 2. Je crée et configure l'objet User.
        $user = new User();
        $user->setFirstName($data['first_name']);
        $user->setLastName($data['last_name']);
        $user->setEmail($data['email']);
        $user->setUsername($data['email']); // J'utilise l'email comme pseudo par défaut.
        $user->setPasswordHash(password_hash($data['password'], PASSWORD_DEFAULT));
        $user->setPhoneNumber('0000000000'); // Numéro par défaut pour les employés
        $user->setBirthDate('1900-01-01'); // Date de naissance par défaut pour les employés
        $user->setAccountStatus('active');
        $user->setCreatedAt(date('Y-m-d H:i:s'));
        $user->setUpdatedAt(date('Y-m-d H:i:s'));

        // 3. J'appelle UserService pour créer l'utilisateur en BDD.
        $userId = $this->userService->create($user);
        if (!$userId) {
            throw new Exception("La création de l'employé a échoué.");
        }
        $user->setId($userId);

        // 4. J'assigne le rôle 'ROLE_EMPLOYEE' via UserRoleService.
        $roleAssigned = $this->userRoleService->assignRoleToUser($userId, 'ROLE_EMPLOYEE');
        if (!$roleAssigned) {
            // Idéalement, il faudrait une logique pour annuler la création de l'utilisateur si l'assignation du rôle échoue.
            throw new Exception("L'assignation du rôle d'employé a échoué.");
        }

        return $user;
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
     * J'utilise une sous-requête NOT EXISTS pour exclure les utilisateurs ayant le rôle 'ROLE_ADMIN',
     * ce qui me permet d'inclure les utilisateurs sans rôle spécifique assigné dans UserRoles.
     *
     * @return array La liste des utilisateurs.
     */
    public function getAllUsers(): array
    {
        $sql = "SELECT u.* FROM users u WHERE NOT EXISTS (SELECT 1 FROM UserRoles ur JOIN Roles r ON ur.role_id = r.id WHERE ur.user_id = u.id AND r.name IN ('ROLE_ADMIN', 'ROLE_EMPLOYEE')) ORDER BY u.created_at DESC";
        $users = $this->db->fetchAll($sql, [], User::class);
        return $users;
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
        $employees = $this->db->fetchAll($sql, [], User::class);
        return $employees;
    }
}