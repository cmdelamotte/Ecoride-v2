<?php

namespace Tests\Integration\Repositories;

use App\Core\Database;
use App\Models\User;
use App\Repositories\PdoUserRepository;
use Tests\TestCase;

class PdoUserRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Prépare une base SQLite en mémoire conforme (autant que possible) au schéma physique
        $pdo = Database::getInstance()->getConnection();

        // users
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                phone_number TEXT NOT NULL,
                birth_date TEXT NOT NULL,
                profile_picture_path TEXT NULL,
                address TEXT NULL,
                credits NUMERIC NOT NULL DEFAULT 0.00,
                account_status TEXT NOT NULL DEFAULT "active",
                driver_pref_smoker INTEGER NOT NULL DEFAULT 0,
                driver_pref_animals INTEGER NOT NULL DEFAULT 0,
                driver_pref_custom TEXT NULL,
                functional_role TEXT NOT NULL DEFAULT "passenger",
                driver_rating REAL NOT NULL DEFAULT 0.0,
                reset_token TEXT NULL,
                reset_token_expires_at TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        // roles
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE
            )'
        );

        // userroles (table de jonction)
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS userroles (
                user_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                PRIMARY KEY (user_id, role_id)
            )'
        );
    }

    public function test_create_and_find_user(): void
    {
        $repo = new PdoUserRepository(Database::getInstance());

        $user = (new User())
            ->setUsername('alice')
            ->setEmail('alice@example.com')
            ->setPasswordHash(password_hash('Secret1!', PASSWORD_DEFAULT))
            ->setFirstName('Alice')
            ->setLastName('Doe')
            ->setPhoneNumber('0612345678')
            ->setBirthDate('1990-01-01')
            ->setFunctionalRole('passenger')
            ->setAccountStatus('active')
            ->setCredits(10.0);

        $newId = $repo->create($user);
        $this->assertIsInt($newId);
        $this->assertGreaterThan(0, $newId);

        $found = $repo->findById($newId);
        $this->assertInstanceOf(User::class, $found);
        $this->assertSame('alice', $found->getUsername());
        $this->assertSame('alice@example.com', $found->getEmail());
    }

    public function test_get_user_roles_array(): void
    {
        $pdo = Database::getInstance()->getConnection();
        $repo = new PdoUserRepository(Database::getInstance());

        // Créer un utilisateur
        $user = (new User())
            ->setUsername('bob')
            ->setEmail('bob@example.com')
            ->setPasswordHash(password_hash('Secret1!', PASSWORD_DEFAULT))
            ->setFirstName('Bob')
            ->setLastName('Smith')
            ->setPhoneNumber('0612345678')
            ->setBirthDate('1991-02-02')
            ->setFunctionalRole('driver')
            ->setAccountStatus('active');
        $userId = $repo->create($user);

        // Créer un rôle et lier
        $pdo->exec("INSERT INTO roles (name) VALUES ('ROLE_USER')");
        $roleId = (int)$pdo->lastInsertId();
        $stmt = $pdo->prepare('INSERT INTO userroles (user_id, role_id) VALUES (:uid, :rid)');
        $stmt->execute([':uid' => $userId, ':rid' => $roleId]);

        $roles = $repo->getUserRolesArray($userId);
        $this->assertContains('ROLE_USER', $roles);
    }
}


