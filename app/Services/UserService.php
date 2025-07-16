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
    public function update(User $user): bool
    {
        // Je construis le tableau de données à partir des propriétés de l'objet User.
        // Cela garantit que seules les données de l'objet sont utilisées pour la mise à jour.
        $data = [
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'address' => $user->getAddress(),
            'birth_date' => $user->getBirthDate(),
            'phone_number' => $user->getPhoneNumber(),
            'profile_picture_path' => $user->getProfilePicturePath(),
            'system_role' => $user->getSystemRole(),
            'functional_role' => $user->getFunctionalRole(),
            'driver_rating' => $user->getDriverRating(),
            'account_status' => $user->getAccountStatus(),
            'credits' => $user->getCredits(),
            'driver_pref_animals' => $user->getDriverPrefAnimals(),
            'driver_pref_smoker' => $user->getDriverPrefSmoker(),
            'driver_pref_music' => $user->getDriverPrefMusic(),
            'driver_pref_custom' => $user->getDriverPrefCustom(),
            'reset_token' => $user->getResetToken(),
            'reset_token_expires_at' => $user->getResetTokenExpiresAt(),
            'created_at' => $user->getCreatedAt(),
            'updated_at' => $user->getUpdatedAt(),
        ];

        // Je filtre les valeurs nulles ou vides pour ne mettre à jour que les champs pertinents.
        // Cela évite d'écraser des données avec des valeurs nulles si elles ne sont pas fournies.
        $updateData = array_filter($data, function($value, $key) {
            // Exclure les valeurs nulles
            if (is_null($value)) {
                return false;
            }

            // Gérer spécifiquement les booléens
            if (in_array($key, ['driver_pref_animals', 'driver_pref_smoker', 'driver_pref_music'])) {
                // Si c'est un booléen, je le garde (true/false/0/1 sont valides)
                return true;
            }

            // Exclure les chaînes vides pour les colonnes qui ne les acceptent pas
            if (is_string($value) && $value === '') {
                // Je suppose que les colonnes suivantes ne doivent pas être mises à jour avec une chaîne vide
                // Adaptez cette liste selon les contraintes de votre BDD
                $nullableStringColumns = ['address', 'profile_picture_path', 'driver_pref_custom', 'reset_token', 'reset_token_expires_at'];
                if (!in_array($key, $nullableStringColumns)) {
                    return false;
                }
            }
            
            // Pour les autres types (int, float, string non vide), je les garde
            return true;
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($updateData)) {
            return false; // Rien à mettre à jour
        }

        return $this->updatePartial($user->getId(), $updateData);
    }

    /**
     * Met à jour un utilisateur existant avec un tableau de données spécifique.
     * Cette méthode est utilisée pour des mises à jour partielles et ciblées,
     * notamment pour des champs qui ne sont pas directement liés à l'objet User complet
     * ou pour des cas où seul un sous-ensemble de données est pertinent.
     *
     * @param int $id L'ID de l'utilisateur à mettre à jour.
     * @param array $data Les données à mettre à jour (clé-valeur).
     * @return bool True si la mise à jour a réussi, sinon false.
     */
    public function updatePartial(int $id, array $data): bool
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
        // Je construis un tableau de données contenant uniquement les champs à mettre à jour.
        // Cela garantit que seuls les champs pertinents sont envoyés à la base de données.
        $updateData = [
            'reset_token' => $token,
            'reset_token_expires_at' => $expiresAt
        ];

        // J'appelle la nouvelle méthode updatePartial pour cette mise à jour ciblée.
        return $this->updatePartial($userId, $updateData);
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
