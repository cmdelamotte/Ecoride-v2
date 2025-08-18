<?php

namespace Tests\Integration\Services;

use App\Core\Database;
use App\Repositories\PdoUserRepository;
use App\Services\UserService;
use App\Models\User;
use Tests\Integration\Database\SqliteTestBootstrap;
use Tests\TestCase;

class UserServiceIntegrationTest extends TestCase
{
    private UserService $userService;
    private $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        SqliteTestBootstrap::migrate();
        $this->pdo = Database::getInstance()->getConnection();
        $this->userService = new UserService(new PdoUserRepository());
        
        // Créer un rôle de base
        $this->pdo->exec("INSERT OR IGNORE INTO roles (name) VALUES ('ROLE_USER')");
    }

    public function test_complete_user_lifecycle(): void
    {
        // 1. Créer un nouvel utilisateur
        $username = 'testuser_' . uniqid();
        $email = 'test_' . uniqid() . '@example.com';
        
        $newUser = (new User())
            ->setUsername($username)
            ->setEmail($email)
            ->setPasswordHash(password_hash('Secret123!', PASSWORD_DEFAULT))
            ->setFirstName('Jean')
            ->setLastName('Dupont')
            ->setPhoneNumber('0612345678')
            ->setBirthDate('1990-01-01')
            ->setFunctionalRole('passenger')
            ->setAccountStatus('active')
            ->setCredits(50.0);

        $userId = $this->userService->create($newUser);
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        // Lier l'utilisateur au rôle système par défaut pour refléter le comportement de l'application
        $roleId = (int)$this->pdo->query("SELECT id FROM roles WHERE name = 'ROLE_USER'")->fetchColumn();
        $stmt = $this->pdo->prepare('INSERT OR IGNORE INTO userroles (user_id, role_id) VALUES (:uid, :rid)');
        $stmt->execute([':uid' => $userId, ':rid' => $roleId]);

        // 2. Rechercher l'utilisateur par email
        $foundByEmail = $this->userService->findByEmailOrUsername($email);
        $this->assertInstanceOf(User::class, $foundByEmail);
        $this->assertSame('Jean', $foundByEmail->getFirstName());
        $this->assertSame('Dupont', $foundByEmail->getLastName());

        // 3. Rechercher l'utilisateur par username
        $foundByUsername = $this->userService->findByEmailOrUsername($username);
        $this->assertInstanceOf(User::class, $foundByUsername);
        $this->assertSame('Jean', $foundByUsername->getFirstName());

        // 4. Mettre à jour les informations (changer seulement le prénom et nom)
        $existing = $this->userService->findById($userId);
        $updatedUser = (new User())
            ->setId($userId)
            ->setUsername($existing->getUsername())
            ->setEmail($existing->getEmail())
            ->setPasswordHash(password_hash('NewSecret123!', PASSWORD_DEFAULT))
            ->setFirstName('Jean-Pierre') // Changer le prénom
            ->setLastName('Martin') // Changer le nom
            ->setPhoneNumber($existing->getPhoneNumber()) // Garder le même téléphone
            ->setBirthDate($existing->getBirthDate()) // Garder la même date
            ->setFunctionalRole($existing->getFunctionalRole()) // Garder le même rôle
            ->setAccountStatus($existing->getAccountStatus())
            ->setCredits($existing->getCredits()) // Garder les mêmes crédits
            ->setDriverPrefAnimals((bool)$existing->getDriverPrefAnimals())
            ->setDriverPrefSmoker((bool)$existing->getDriverPrefSmoker())
            ->setDriverRating((float)$existing->getDriverRating());

        $updateResult = $this->userService->update($updatedUser);
        $this->assertTrue($updateResult);

        // 5. Vérifier que les mises à jour ont été appliquées
        $updatedFound = $this->userService->findById($userId);
        $this->assertSame('Jean-Pierre', $updatedFound->getFirstName());
        $this->assertSame('Martin', $updatedFound->getLastName());
        $this->assertSame('passenger', $updatedFound->getFunctionalRole()); // N'a pas changé
        $this->assertSame(50.0, $updatedFound->getCredits()); // N'a pas changé

        // 6. Vérifier la gestion des rôles
        $roles = $this->userService->getUserRolesArray($userId);
        $this->assertIsArray($roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function test_user_credits_management(): void
    {
        // Créer un utilisateur avec des crédits
        $user = (new User())
            ->setUsername('creditsuser_' . uniqid())
            ->setEmail('credits_' . uniqid() . '@example.com')
            ->setPasswordHash(password_hash('Secret123!', PASSWORD_DEFAULT))
            ->setFirstName('Marie')
            ->setLastName('Durand')
            ->setPhoneNumber('0634567890')
            ->setBirthDate('1988-03-20')
            ->setFunctionalRole('passenger')
            ->setAccountStatus('active')
            ->setCredits(100.0);

        $userId = $this->userService->create($user);
        
        // Vérifier les crédits initiaux
        $foundUser = $this->userService->findById($userId);
        $this->assertSame(100.0, $foundUser->getCredits());

        // Mettre à jour les crédits via le repository
        $repository = new PdoUserRepository();
        $updateResult = $repository->updateCredits($userId, 75.0);
        $this->assertTrue($updateResult);

        // Vérifier que les crédits ont été mis à jour
        $updatedUser = $this->userService->findById($userId);
        $this->assertSame(75.0, $updatedUser->getCredits());
    }

    public function test_user_not_found_scenarios(): void
    {
        // Rechercher un utilisateur inexistant
        $notFound = $this->userService->findById(99999);
        $this->assertNull($notFound);

        $notFoundByEmail = $this->userService->findByEmailOrUsername('nonexistent@example.com');
        $this->assertNull($notFoundByEmail);

        $notFoundByUsername = $this->userService->findByEmailOrUsername('nonexistentuser');
        $this->assertNull($notFoundByUsername);
    }
}
