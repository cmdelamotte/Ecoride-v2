<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use PDO;
use PDOException;

/**
 * Service UserService
 *
 * Gère toute la logique métier liée aux utilisateurs.
 * Centralise les interactions avec la base de données pour l'entité User.
 * Ce service est responsable de la création, lecture, mise à jour et suppression (CRUD)
 * des utilisateurs, ainsi que des opérations spécifiques comme la recherche
 * ou la gestion des jetons de réinitialisation.
 */
class UserService
{
    /**
     * @var PDO L'instance de la connexion PDO.
     */
    private PDO $db;

    /**
     * Le constructeur initialise la connexion à la base de données.
     */
    public function __construct()
    {
        // J'utilise un Singleton pour garantir une seule instance de connexion PDO
        // à travers toute l'application, optimisant ainsi les ressources.
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Trouve un utilisateur par son ID.
     *
     * @param int $id L'ID de l'utilisateur à rechercher.
     * @return User|null Retourne une instance de User si trouvé, sinon null.
     */
    public function findById(int $id): ?User
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // J'utilise setFetchMode pour que PDO peuple directement mon modèle User.
            // C'est propre et ça évite de devoir assigner manuellement chaque propriété.
            $stmt->setFetchMode(PDO::FETCH_CLASS, User::class);
            $user = $stmt->fetch();

            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Error in findById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trouve un utilisateur par son email ou son nom d'utilisateur.
     *
     * @param string $identifier L'email ou le nom d'utilisateur.
     * @return User|null Retourne une instance de User si trouvé, sinon null.
     */
    public function findByEmailOrUsername(string $identifier): ?User
    {
        try {
            // J'utilise des placeholders nommés uniques pour plus de clarté et de compatibilité.
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email OR username = :username");
            
            // Je passe un tableau associatif à execute(). PDO va lier chaque clé du tableau
            // au placeholder correspondant. C'est une manière propre et efficace de faire.
            $stmt->execute([
                'email' => $identifier,
                'username' => $identifier
            ]);

            $stmt->setFetchMode(PDO::FETCH_CLASS, User::class);
            $user = $stmt->fetch();

            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Error in findByEmailOrUsername: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée un nouvel utilisateur en base de données.
     *
     * @param array $data Les données pour le nouvel utilisateur.
     * @return int|false L'ID du nouvel utilisateur ou false en cas d'échec.
     */
    public function create(array $data): int|false
    {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));

            $sql = "INSERT INTO users ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);

            foreach ($data as $key => $value) {
                $paramType = match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_null($value) => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };
                $stmt->bindValue(':' . $key, $value, $paramType);
            }

            $stmt->execute();
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error in create user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour un utilisateur existant.
     *
     * @param int $id L'ID de l'utilisateur à mettre à jour.
     * @param array $data Les données à mettre à jour.
     * @return bool True si la mise à jour a réussi, sinon false.
     */
    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        try {
            $setParts = [];
            foreach ($data as $key => $value) {
                $setParts[] = "{$key} = :{$key}";
            }
            $setClause = implode(', ', $setParts);

            $sql = "UPDATE users SET {$setClause} WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            foreach ($data as $key => $value) {
                $paramType = match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_null($value) => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };
                $stmt->bindValue(':' . $key, $value, $paramType);
            }
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in update user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un utilisateur.
     *
     * @param int $id L'ID de l'utilisateur à supprimer.
     * @return bool True si la suppression a réussi, sinon false.
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in delete user: " . $e->getMessage());
            return false;
        }
    }
}