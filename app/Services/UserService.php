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

    /**
     * Met à jour le jeton de réinitialisation de mot de passe et sa date d'expiration pour un utilisateur spécifique.
     * Cette méthode est utilisée lors de la demande de réinitialisation de mot de passe.
     *
     * @param int $userId L'ID de l'utilisateur dont le token doit être mis à jour.
     * @param string $token Le nouveau jeton de réinitialisation à stocker.
     * @param string $expiresAt La date et l'heure d'expiration du jeton.
     * @return bool Vrai si la mise à jour a réussi, faux sinon.
     */
    public function updateResetToken(int $userId, string $token, string $expiresAt): bool
    {
        // J'utilise la méthode update existante pour mettre à jour les champs spécifiques du token.
        // Cela assure la cohérence et réutilise la logique de mise à jour générique.
        return $this->update($userId, [
            'reset_token' => $token,
            'reset_token_expires_at' => $expiresAt
        ]);
    }

    /**
     * Trouve un utilisateur par son jeton de réinitialisation de mot de passe et vérifie sa validité.
     * Cette méthode est cruciale pour sécuriser le processus de réinitialisation de mot de passe.
     *
     * @param string $token Le jeton de réinitialisation à rechercher.
     * @return User|null Retourne une instance de User si un utilisateur est trouvé avec un token valide et non expiré, sinon null.
     */
    public function findByResetToken(string $token): ?User
    {
        try {
            // Prépare la requête pour trouver un utilisateur par son token et s'assurer qu'il n'a pas expiré.
            // Je compare la date d'expiration du token avec la date et heure actuelles.
            $stmt = $this->db->prepare(
                "SELECT * FROM users WHERE reset_token = :token AND reset_token_expires_at > NOW()"
            );
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();

            // Configure le mode de récupération pour hydrater directement un objet User.
            $stmt->setFetchMode(PDO::FETCH_CLASS, User::class);
            $user = $stmt->fetch();

            // Retourne l'utilisateur trouvé ou null si aucun utilisateur ne correspond ou si le token est expiré.
            return $user ?: null;
        } catch (PDOException $e) {
            // En cas d'erreur de base de données, je log l'erreur pour le débogage sans exposer d'informations sensibles à l'utilisateur.
            error_log("Error finding user by reset token: " . $e->getMessage());
            return null;
        }
    }
}