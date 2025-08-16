<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Services\UserService;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    public function test_find_by_email_or_username_returns_user_when_repository_finds_one(): void
    {
        $fakeRepo = new class implements UserRepositoryInterface {
            public function findById(int $id): ?User { return null; }
            public function findByEmailOrUsername(string $identifier): ?User {
                return (new User())->setId(1)->setUsername('jdoe')->setEmail('john@example.com');
            }
            public function create(User $user): int|false { return 1; }
            public function updateFields(int $userId, array $fields): bool { return true; }
            public function delete(int $id): bool { return true; }
            public function getUserRolesArray(int $userId): array { return ['ROLE_USER']; }
            public function updateDriverRating(int $driverId, float $newRating): bool { return true; }
            public function updateCredits(int $userId, int $newCredits): bool { return true; }
        };

        $service = new UserService($fakeRepo);
        $user = $service->findByEmailOrUsername('john@example.com');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('jdoe', $user->getUsername());
        $this->assertSame('john@example.com', $user->getEmail());
    }

    public function test_update_returns_false_and_does_not_call_repository_when_no_changes(): void
    {
        $calls = (object)['updateCalled' => false];
        $existing = (new User())
            ->setId(1)
            ->setUsername('jdoe')
            ->setEmail('john@example.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        $fakeRepo = new class($calls, $existing) implements UserRepositoryInterface {
            private $calls; private $existing;
            public function __construct($calls, $existing) { $this->calls = $calls; $this->existing = $existing; }
            public function findById(int $id): ?User { return $this->existing; }
            public function findByEmailOrUsername(string $identifier): ?User { return null; }
            public function create(User $user): int|false { return 1; }
            public function updateFields(int $userId, array $fields): bool { $this->calls->updateCalled = true; return true; }
            public function delete(int $id): bool { return true; }
            public function getUserRolesArray(int $userId): array { return []; }
            public function updateDriverRating(int $driverId, float $newRating): bool { return true; }
            public function updateCredits(int $userId, int $newCredits): bool { return true; }
        };

        $service = new UserService($fakeRepo);

        $userNoChange = (new User())
            ->setId(1)
            ->setUsername('jdoe')
            ->setEmail('john@example.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        $result = $service->update($userNoChange);

        $this->assertFalse($result);
        $this->assertFalse($calls->updateCalled, 'Le dépôt ne doit pas être appelé s’il n’y a aucun changement.');
    }
}


