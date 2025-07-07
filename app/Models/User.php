<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Classe User
 * Représente le modèle pour la gestion des utilisateurs dans la base de données.
 * Cette classe est responsable de toutes les opérations CRUD (Create, Read, Update, Delete)
 * liées à la table 'users'. Elle interagit directement avec la base de données
 * via l'objet PDO obtenu du Singleton Database.
 */
class User
{
    /**
     * @var PDO $db L'instance de la connexion PDO à la base de données.
     *              Cette propriété est initialisée via le constructeur en utilisant le Singleton Database.
     */
    private PDO $db;

    /**
     * Constructeur du modèle User.
     * Initialise la propriété $db avec l'instance de connexion PDO.
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Trouve un utilisateur par son ID.
     *
     * @param int $id L'ID de l'utilisateur à rechercher.
     * @return array|false Un tableau associatif représentant l'utilisateur si trouvé, false sinon.
     */
    public function findById(int $id): array|false
    {
        try {
            // Prépare la requête SQL pour sélectionner un utilisateur par son ID.
            // L'utilisation d'un placeholder ':id' et de requêtes préparées prévient les injections SQL.
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
            // Lie la valeur de l'ID au placeholder ':id'. PDO s'occupe de l'échappement.
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            // Exécute la requête préparée.
            $stmt->execute();
            // Récupère la première ligne du résultat sous forme de tableau associatif.
            // PDO::FETCH_ASSOC garantit que les clés du tableau sont les noms des colonnes.
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // En cas d'erreur PDO, logguer l'erreur pour le débogage.
            // En production, il est crucial de ne pas exposer les détails de l'erreur à l'utilisateur.
            error_log("Error in findById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouve un utilisateur par son email ou son nom d'utilisateur.
     * Utilisé principalement pour l'authentification.
     *
     * @param string $identifier L'email ou le nom d'utilisateur de l'utilisateur à rechercher.
     * @return array|false Un tableau associatif représentant l'utilisateur si trouvé, false sinon.
     */
    public function findByEmailOrUsername(string $identifier): array|false
    {
        try {
            // Prépare la requête SQL pour rechercher par email ou username.
            // Utilise OR pour vérifier les deux champs. Les placeholders sont essentiels ici.
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :identifier OR username = :identifier");
            // Lie la même valeur au deux placeholders.
            $stmt->bindParam(':identifier', $identifier, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in findByEmailOrUsername: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée un nouvel utilisateur dans la base de données.
     *
     * @param array $data Un tableau associatif contenant les données de l'utilisateur.
     *                    Ex: ['username' => 'john_doe', 'email' => 'john@example.com', 'password_hash' => '...'].
     * @return int|false L'ID du nouvel utilisateur inséré si succès, false sinon.
     */
    public function create(array $data): int|false
    {
        try {
            // Construit dynamiquement la liste des colonnes et des placeholders pour la requête INSERT.
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));

            $sql = "INSERT INTO users ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);

            // Lie chaque valeur du tableau $data à son placeholder correspondant.
            foreach ($data as $key => $value) {
                // Détermine le type PDO pour une liaison sécurisée.
                $paramType = match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_null($value) => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };
                $stmt->bindValue(':' . $key, $value, $paramType);
            }

            $stmt->execute();
            // Retourne l'ID de la dernière ligne insérée. Utile pour les opérations suivantes.
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error in create user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour les informations d'un utilisateur existant.
     *
     * @param int $id L'ID de l'utilisateur à mettre à jour.
     * @param array $data Un tableau associatif des champs à mettre à jour et de leurs nouvelles valeurs.
     * @return bool True si la mise à jour a réussi, false sinon.
     */
    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false; // Aucune donnée à mettre à jour.
        }

        try {
            // Construit dynamiquement la partie SET de la requête UPDATE.
            // Ex: 'field1 = :field1, field2 = :field2'
            $setParts = [];
            foreach ($data as $key => $value) {
                $setParts[] = "{$key} = :{$key}";
            }
            $setClause = implode(', ', $setParts);

            $sql = "UPDATE users SET {$setClause} WHERE id = :id";
            $stmt = $this->db->prepare($sql);

            // Lie les valeurs des données au placeholders.
            foreach ($data as $key => $value) {
                $paramType = match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_null($value) => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };
                $stmt->bindValue(':' . $key, $value, $paramType);
            }
            // Lie l'ID de l'utilisateur pour la clause WHERE.
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in update user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un utilisateur de la base de données.
     *
     * @param int $id L'ID de l'utilisateur à supprimer.
     * @return bool True si la suppression a réussi, false sinon.
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
     * Récupère tous les utilisateurs.
     *
     * @return array Un tableau d'utilisateurs, potentiellement vide.
     */
    public function findAll(): array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM users");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in findAll users: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Met à jour le token de réinitialisation de mot de passe et son expiration pour un utilisateur.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @param string|null $token Le token de réinitialisation (ou null pour le supprimer).
     * @param string|null $expiresAt La date d'expiration du token (format Y-m-d H:i:s, ou null).
     * @return bool True si la mise à jour a réussi, false sinon.
     */
    public function updateResetToken(int $userId, ?string $token, ?string $expiresAt): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE users SET reset_token = :token, reset_token_expires_at = :expires_at WHERE id = :user_id"
            );
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->bindParam(':expires_at', $expiresAt, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating reset token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouve un utilisateur par son token de réinitialisation de mot de passe.
     *
     * @param string $token Le token de réinitialisation.
     * @return array|false Un tableau associatif représentant l'utilisateur si trouvé, false sinon.
     */
    public function findByResetToken(string $token): array|false
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM users WHERE reset_token = :token AND reset_token_expires_at > NOW()"
            );
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding user by reset token: " . $e->getMessage());
            return false;
        }
    }
}
