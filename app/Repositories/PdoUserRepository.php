<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;

class PdoUserRepository implements UserRepositoryInterface
{
    private Database $db;

    public function __construct(?Database $database = null)
    {
        // Permettre l'injection d'une connexion custom (tests) tout en conservant un défaut sûr
        $this->db = $database ?? Database::getInstance();
    }

    public function findById(int $id): ?User
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $id],
            User::class
        );
    }

    public function findByEmailOrUsername(string $identifier): ?User
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = :email OR username = :username",
            ['email' => $identifier, 'username' => $identifier],
            User::class
        );
    }

    public function create(User $user): int|false
    {
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

        $insertData = array_filter($data, fn($v) => !is_null($v));
        $columns = implode(', ', array_keys($insertData));
        $placeholders = ':' . implode(', :', array_keys($insertData));
        $sql = "INSERT INTO users ($columns) VALUES ($placeholders)";

        $rowCount = $this->db->execute($sql, $insertData);
        return $rowCount > 0 ? (int)$this->db->lastInsertId() : false;
    }

    public function updateFields(int $userId, array $fields): bool
    {
        if (empty($fields)) {
            return false;
        }
        $setParts = [];
        foreach (array_keys($fields) as $key) {
            $setParts[] = "$key = :$key";
        }
        $setClause = implode(', ', $setParts);
        $fields['id'] = $userId;
        $sql = "UPDATE users SET {$setClause} WHERE id = :id";
        return $this->db->execute($sql, $fields) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM users WHERE id = :id", ['id' => $id]) > 0;
    }

    public function getUserRolesArray(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT r.name FROM roles r JOIN userroles ur ON r.id = ur.role_id WHERE ur.user_id = :user_id",
            ['user_id' => $userId],
            \PDO::FETCH_COLUMN
        );
    }

    public function updateDriverRating(int $driverId, float $newRating): bool
    {
        $sql = "UPDATE users SET driver_rating = :driver_rating WHERE id = :id";
        $params = [
            ':driver_rating' => $newRating,
            ':id' => $driverId,
        ];
        return $this->db->execute($sql, $params) > 0;
    }

    public function updateCredits(int $userId, int $newCredits): bool
    {
        $sql = "UPDATE users SET credits = :credits WHERE id = :id";
        $params = [
            ':credits' => $newCredits,
            ':id' => $userId,
        ];
        return $this->db->execute($sql, $params) > 0;
    }
}


