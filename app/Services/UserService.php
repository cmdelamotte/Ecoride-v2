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
    public function create(User $user): int|false
    {
        // Je construis le tableau de données à partir des propriétés de l'objet User.
        // Cela garantit que seules les données de l'objet sont utilisées pour la création.
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
            'driver_pref_custom' => $user->getDriverPrefCustom(),
            'reset_token' => $user->getResetToken(),
            'reset_token_expires_at' => $user->getResetTokenExpiresAt(),
            'created_at' => $user->getCreatedAt(),
            'updated_at' => $user->getUpdatedAt(),
        ];

        // Je filtre les valeurs nulles pour ne pas les inclure dans la requête INSERT.
        $insertData = array_filter($data, function($value) {
            return !is_null($value); // Garde les valeurs non nulles
        });

        $columns = implode(', ', array_keys($insertData));
        $placeholders = ':' . implode(', :', array_keys($insertData));

        $sql = "INSERT INTO users ($columns) VALUES ($placeholders)";
        
        // J'utilise la méthode execute. Si elle réussit, je retourne le nouvel ID.
        $rowCount = $this->db->execute($sql, $insertData);

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
        // 1. Récupérer l'utilisateur existant de la base de données pour comparaison.
        $existingUser = $this->findById($user->getId());

        if (!$existingUser) {
            // L'utilisateur n'existe pas, impossible de mettre à jour.
            return false;
        }

        $updateData = [];
        // Utiliser la réflexion pour obtenir toutes les propriétés de l'objet User.
        $reflectionClass = new \ReflectionClass($user);
        $properties = $reflectionClass->getProperties(\ReflectionProperty::IS_PRIVATE);

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            // Ignorer les propriétés qui ne sont pas des colonnes de la BDD ou qui sont gérées automatiquement.
            if (in_array($propertyName, ['id', 'created_at', 'updated_at', 'system_role'])) {
                continue;
            }

            // Rendre la propriété accessible pour lire sa valeur.
            $property->setAccessible(true);
            $newValue = $property->getValue($user);
            $oldValue = $property->getValue($existingUser);

            // Comparer les valeurs. Si elles sont différentes, inclure dans la mise à jour.
            // Gérer les cas spécifiques pour les booléens et les chaînes vides.
            if ($newValue !== $oldValue) {
                // Pour les booléens, s'assurer que false est bien 0 et true est 1.
                if (is_bool($newValue)) {
                    $updateData[$propertyName] = (int)$newValue;
                } 
                // Pour les chaînes vides, les traiter comme null si la colonne est nullable
                // et que la valeur précédente n'était pas vide.
                elseif (is_string($newValue) && $newValue === '') {
                    // Liste des colonnes qui peuvent être nulles et pour lesquelles une chaîne vide doit être null
                    $nullableStringColumns = ['address', 'profile_picture_path', 'driver_pref_custom', 'reset_token', 'reset_token_expires_at'];
                    if (in_array($propertyName, $nullableStringColumns)) {
                        $updateData[$propertyName] = null;
                    } else {
                        $updateData[$propertyName] = $newValue; // Garder la chaîne vide si la colonne n'est pas nullable
                    }
                }
                else {
                    $updateData[$propertyName] = $newValue;
                }
            }
        }

        if (empty($updateData)) {
            return false; // Rien à mettre à jour
        }

        $setParts = [];
        foreach (array_keys($updateData) as $key) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE users SET {$setClause} WHERE id = :id";
        $updateData['id'] = $user->getId(); // J'ajoute l'id de l'utilisateur à mettre à jour.

        // La méthode execute retourne le nombre de lignes affectées.
        // Une mise à jour réussie doit affecter au moins une ligne.
        return $this->db->execute($sql, $updateData) > 0;
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
     * Récupère tous les rôles d'un utilisateur à partir des tables UserRoles et Roles.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @return array Un tableau de noms de rôles (ex: ['ROLE_USER', 'ROLE_EMPLOYEE']).
     */
    public function getUserRolesArray(int $userId): array
    {
        $sql = "SELECT r.name FROM Roles r JOIN UserRoles ur ON r.id = ur.role_id WHERE ur.user_id = :user_id";
        $params = ['user_id' => $userId];
        error_log("UserService: getUserRolesArray SQL: " . $sql . " Params: " . print_r($params, true));
        $results = $this->db->fetchAll($sql, $params, \PDO::FETCH_COLUMN);
        error_log("UserService: getUserRolesArray Result: " . print_r($results, true));
        return $results;
    }

    /**
     * Met à jour la note moyenne d'un conducteur.
     *
     * @param int $driverId L'ID du conducteur.
     * @param float $newRating La nouvelle note moyenne.
     * @return bool True si la mise à jour a réussi, sinon false.
     */
    public function updateDriverRating(int $driverId, float $newRating): bool
    {
        $sql = "UPDATE users SET driver_rating = :driver_rating WHERE id = :id";
        $params = [
            ':driver_rating' => $newRating,
            ':id' => $driverId
        ];
        return $this->db->execute($sql, $params) > 0;
    }
}
