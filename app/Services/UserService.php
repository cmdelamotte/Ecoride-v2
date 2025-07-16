<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;

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
    private Database $db;

    /**
     * Le constructeur initialise l'instance de la base de données.
     */
    public function __construct()
    {
        // J'utilise le Singleton pour garantir une seule instance de connexion.
        $this->db = Database::getInstance();
    }

    /**
     * Trouve un utilisateur par son ID.
     *
     * @param int $id L'ID de l'utilisateur à rechercher.
     * @return User|null Retourne une instance de User si trouvé, sinon null.
     */
    public function findById(int $id): ?User
    {
        // J'utilise la nouvelle méthode fetchOne pour obtenir directement un objet User.
        // C'est plus propre et la logique est centralisée dans la classe Database.
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $id],
            User::class
        );
    }

    /**
     * Trouve un utilisateur par son email ou son nom d'utilisateur.
     *
     * @param string $identifier L'email ou le nom d'utilisateur.
     * @return User|null Retourne une instance de User si trouvé, sinon null.
     */
    public function findByEmailOrUsername(string $identifier): ?User
    {
        // La requête reste la même, mais l'exécution est simplifiée grâce à fetchOne.
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = :email OR username = :username",
            ['email' => $identifier, 'username' => $identifier],
            User::class
        );
    }

    /**
     * Crée un nouvel utilisateur en base de données.
     *
     * @param array $data Les données pour le nouvel utilisateur.
     * @return int|false L'ID du nouvel utilisateur ou false en cas d'échec.
     */
    public function create(array $data): int|false
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO users ($columns) VALUES ($placeholders)";
        
        // J'utilise la nouvelle méthode execute. Si elle réussit, je retourne le nouvel ID.
        $rowCount = $this->db->execute($sql, $data);

        return $rowCount > 0 ? (int)$this->db->lastInsertId() : false;
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

        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE users SET {$setClause} WHERE id = :id";
        $data['id'] = $id; // J'ajoute l'id au tableau de paramètres pour la liaison.

        // La méthode execute retourne le nombre de lignes affectées.
        // Une mise à jour réussie doit affecter au moins une ligne.
        return $this->db->execute($sql, $data) > 0;
    }

    /**
     * Supprime un utilisateur.
     *
     * @param int $id L'ID de l'utilisateur à supprimer.
     * @return bool True si la suppression a réussi, sinon false.
     */
    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM users WHERE id = :id", ['id' => $id]) > 0;
    }

    /**
     * Met à jour le jeton de réinitialisation de mot de passe et sa date d'expiration.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param string $token Le nouveau jeton.
     * @param string $expiresAt La date d'expiration.
     * @return bool Vrai si la mise à jour a réussi, faux sinon.
     */
    public function updateResetToken(int $userId, string $token, string $expiresAt): bool
    {
        return $this->update($userId, [
            'reset_token' => $token,
            'reset_token_expires_at' => $expiresAt
        ]);
    }

    /**
     * Trouve un utilisateur par son jeton de réinitialisation de mot de passe.
     *
     * @param string $token Le jeton à rechercher.
     * @return User|null Retourne une instance de User si le token est valide, sinon null.
     */
    public function findByResetToken(string $token): ?User
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE reset_token = :token AND reset_token_expires_at > NOW()",
            ['token' => $token],
            User::class
        );
    }
}
