<?php

namespace Tests\Integration\Repositories;

use App\Core\Database;
use App\Models\User;
use App\Repositories\PdoUserRepository;
use Tests\Integration\Database\SqliteTestBootstrap;
use Tests\TestCase;

class PdoUserRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Charger le schéma SQLite commun de tests
        SqliteTestBootstrap::migrate();
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


